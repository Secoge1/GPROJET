<?php
/**
 * GLOBALO — Paiements PayTech (https://paytech.sn)
 *
 * Configuration (voir config.php : getenv / _intouch_env) :
 *   PAYTECH_API_KEY, PAYTECH_API_SECRET — obligatoires pour activer PayTech (isConfigured()).
 *   PAYTECH_ENV — "test" | "prod" (défaut prod ; ne pas mélanger clés prod avec env test).
 *
 * Conformité doc PayTech :
 *   • POST https://paytech.sn/api/payment/request-payment + en-têtes API_KEY / API_SECRET
 *   • ipn_url / success_url / cancel_url — HTTPS obligatoire en production (BASE_URL doit pointer en https://)
 *   • IPN POST form : vérification HMAC SHA256 ou double api_key_sha256 / api_secret_sha256
 *
 * Flux :
 *   1. requestPayment | requestDepot → API → redirect_url (enrichi pn/nn/fn par PayTechCheckoutAssistant si possible).
 *   2. Page hébergée PayTech puis IPN POST /paytech/callback → completeFromIpn().
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\TransactionModel;
use App\Models\AbonnementModel;
use App\Models\ParametreModel;
use App\Models\NotificationModel;
use App\Models\UtilisateurModel;

class PayTechPaymentService
{
    private const API_BASE        = 'https://paytech.sn/api';
    private const PAYMENT_ENDPOINT = self::API_BASE . '/payment/request-payment';
    private const STATUS_ENDPOINT  = self::API_BASE . '/payment/get-status';

    private const ABONNEMENT_TYPES = [
        'abonnement', 'abonnement_client', 'abonnement_expert',
        'abonnement_etudiant', 'abonnement_professeur',
    ];
    private const DEPOT_TYPES = ['depot_portefeuille', 'paiement_session_paytech'];

    private const COMMISSION_DEFAULT_PCT = 2.5;

    private TransactionModel $transactionModel;
    private AbonnementModel  $abonnementModel;
    private ParametreModel   $parametres;
    private PayTechCheckoutAssistant $checkoutAssistant;

    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
        $this->abonnementModel  = new AbonnementModel();
        $this->parametres       = new ParametreModel();
        $this->checkoutAssistant = new PayTechCheckoutAssistant();
    }

    // ---------------------------------------------------------------
    // Configuration
    // ---------------------------------------------------------------

    public function isConfigured(): bool
    {
        return $this->getApiKey() !== '' && $this->getApiSecret() !== '';
    }

    private function getApiKey(): string
    {
        return trim((string) (getenv('PAYTECH_API_KEY') ?: (defined('PAYTECH_API_KEY') ? PAYTECH_API_KEY : '')));
    }

    private function getApiSecret(): string
    {
        return trim((string) (getenv('PAYTECH_API_SECRET') ?: (defined('PAYTECH_API_SECRET') ? PAYTECH_API_SECRET : '')));
    }

    private function getEnv(): string
    {
        $env = trim((string) (getenv('PAYTECH_ENV') ?: (defined('PAYTECH_ENV') ? PAYTECH_ENV : 'prod')));
        return in_array($env, ['test', 'prod'], true) ? $env : 'prod';
    }

    /** Chemin relatif vers la page de paiement abonnement (/paytech/checkout/…). */
    public function getAbonnementPaymentRelativePath(string $abonnementType): string
    {
        return '/paytech/checkout/' . rawurlencode($abonnementType);
    }

    // ---------------------------------------------------------------
    // Initier un paiement abonnement
    // ---------------------------------------------------------------

    /**
     * Crée une transaction en base puis appelle l'API PayTech.
     *
     * @param array{pn:string,nn:string}|null $phonePairResolved Paire normalisée (obligatoire si pas de téléphone valide en profil)
     * @return array{ok: bool, payment_id?: string, token?: string, redirect_url?: string, error?: string}
     */
    public function requestPayment(
        int    $userId,
        float  $amount,
        float  $platformFee,
        string $type = 'abonnement',
        string $abonnementType = 'client',
        string $itemName = 'Abonnement GLOBALO',
        string $commandName = 'Paiement abonnement GLOBALO',
        ?array $phonePairResolved = null
    ): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'PayTech non configuré. Contactez l\'administrateur.'];
        }
        if ($userId <= 0 || $amount <= 0) {
            return ['ok' => false, 'error' => 'Paramètres de paiement invalides.'];
        }

        $user       = (new UtilisateurModel())->find($userId);
        $countryIso = $this->checkoutAssistant->resolveCountryIso2($user);
        if ($phonePairResolved !== null) {
            $phonePair = $phonePairResolved;
        } else {
            $phonePair = $this->checkoutAssistant->normalizePhoneForCheckout(
                isset($user['telephone']) ? (string) $user['telephone'] : null,
                $countryIso
            );
        }
        if ($phonePair === null) {
            return ['ok' => false, 'error' => 'Numéro Mobile Money requis. Saisissez un numéro valide avec indicatif pays.'];
        }
        $phoneForDb = $phonePair['pn'];
        $fullName   = $this->checkoutAssistant->fullName($user);

        // Créer la transaction en base avant d'appeler PayTech
        $paymentId = $this->transactionModel->createTransaction(
            $userId,
            $amount,
            $platformFee,
            $phoneForDb,
            $type,
            $abonnementType,
            'paytech',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return $this->callPayTechApi(
            $paymentId,
            $amount + $platformFee,
            $itemName,
            $commandName,
            $type,
            $countryIso,
            $phonePair,
            $fullName
        );
    }

    /**
     * Crée une transaction dépôt portefeuille puis appelle PayTech.
     *
     * @param array{pn:string,nn:string}|null $phonePairResolved
     * @return array{ok: bool, payment_id?: string, token?: string, redirect_url?: string, error?: string}
     */
    public function requestDepot(int $userId, float $amount, ?array $phonePairResolved = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'PayTech non configuré. Contactez l\'administrateur.'];
        }
        if ($userId <= 0 || $amount < 500) {
            return ['ok' => false, 'error' => 'Montant minimum : 500 XOF.'];
        }

        $user       = (new UtilisateurModel())->find($userId);
        $countryIso = $this->checkoutAssistant->resolveCountryIso2($user);
        if ($phonePairResolved !== null) {
            $phonePair = $phonePairResolved;
        } else {
            $phonePair = $this->checkoutAssistant->normalizePhoneForCheckout(
                isset($user['telephone']) ? (string) $user['telephone'] : null,
                $countryIso
            );
        }
        if ($phonePair === null) {
            return ['ok' => false, 'error' => 'Numéro Mobile Money requis pour ce dépôt.'];
        }
        $phoneForDb = $phonePair['pn'];
        $fullName   = $this->checkoutAssistant->fullName($user);

        $paymentId = $this->transactionModel->createTransaction(
            $userId,
            $amount,
            0.0,
            $phoneForDb,
            'depot_portefeuille',
            'depot_widget',
            'paytech',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return $this->callPayTechApi(
            $paymentId,
            $amount,
            'Dépôt portefeuille GLOBALO',
            'Rechargement portefeuille GLOBALO',
            'depot_portefeuille',
            $countryIso,
            $phonePair,
            $fullName
        );
    }

    /**
     * Crédit immédiat du portefeuille pour payer une réservation (équivalent legacy TouchPay session).
     * Après confirmation IPN, le client finalise sur /client/payer/{id}.
     *
     * @param array{pn:string,nn:string}|null $phonePairResolved Saisi au formulaire ; si null, repli sur le téléphone du profil.
     * @return array{ok: bool, payment_id?: string, token?: string, redirect_url?: string, error?: string}
     */
    public function requestSessionWalletTopUp(int $userId, int $reservationId, float $amount, ?array $phonePairResolved = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'PayTech non configuré. Contactez l\'administrateur.'];
        }
        if ($userId <= 0 || $reservationId <= 0 || $amount <= 0) {
            return ['ok' => false, 'error' => 'Paramètres de paiement invalides.'];
        }
        $noteKey = 'session:' . $reservationId;

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM transactions
                WHERE user_id = ? AND type = 'paiement_session_paytech'
                  AND notes LIKE ?
                  AND notes LIKE '%paytech_token:%'
                  AND status = 'pending'
                  AND created_at > DATE_SUB(NOW(), INTERVAL 45 MINUTE)
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $noteKey . '%']);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (is_array($existing) && !empty($existing['payment_id'])) {
                return $this->resumePendingPayTechRequest(
                    (string) $existing['payment_id'],
                    round((float) $existing['amount'], 2),
                    'Crédit mission GLOBALO #' . $reservationId,
                    'Paiement Mobile Money réservation #' . $reservationId . ' — GLOBALO',
                    'paiement_session_paytech',
                    $userId
                );
            }
        } catch (\Throwable $e) {
            error_log('[PayTechPaymentService::requestSessionWalletTopUp] reuse: ' . $e->getMessage());
        }

        $user       = (new UtilisateurModel())->find($userId);
        $countryIso = $this->checkoutAssistant->resolveCountryIso2($user);
        if ($phonePairResolved !== null) {
            $phonePair = $phonePairResolved;
        } else {
            $phonePair = $this->checkoutAssistant->normalizePhoneForCheckout(
                isset($user['telephone']) ? (string) $user['telephone'] : null,
                $countryIso
            );
        }
        if ($phonePair === null) {
            return ['ok' => false, 'error' => 'Numéro Mobile Money requis. Saisissez un numéro valide avec indicatif pays.'];
        }
        $phoneForDb = $phonePair['pn'];
        $fullName   = $this->checkoutAssistant->fullName($user);

        try {
            $paymentId = $this->transactionModel->createTransaction(
                $userId,
                $amount,
                0.0,
                $phoneForDb,
                'paiement_session_paytech',
                'session',
                'paytech',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            Database::getInstance()
                ->prepare('UPDATE transactions SET notes = ? WHERE payment_id = ? AND status = ? LIMIT 1')
                ->execute([$noteKey, $paymentId, 'pending']);
        } catch (\Throwable $e) {
            error_log('[PayTechPaymentService::requestSessionWalletTopUp] create: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Impossible de créer la demande de paiement.'];
        }

        return $this->callPayTechApi(
            $paymentId,
            $amount,
            'Crédit mission GLOBALO #' . $reservationId,
            'Paiement Mobile Money réservation #' . $reservationId . ' — GLOBALO',
            'paiement_session_paytech',
            $countryIso,
            $phonePair,
            $fullName
        );
    }

    /**
     * Reconstruit l’URL PayTech si une transaction pendante existe déjà (évite doubles débits).
     *
     * @return array{ok: bool, payment_id?: string, token?: string, redirect_url?: string, error?: string}
     */
    private function resumePendingPayTechRequest(
        string $paymentId,
        float $_totalAmount,
        string $_itemName,
        string $_commandName,
        string $_type,
        int $userId
    ): array {
        $stmt = Database::getInstance()->prepare(
            'SELECT notes FROM transactions WHERE payment_id = ? AND status = ? AND provider = ? LIMIT 1'
        );
        $stmt->execute([$paymentId, 'pending', 'paytech']);
        $notesRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$notesRow) {
            return ['ok' => false, 'error' => 'Transaction introuvable.'];
        }

        $rawNotes = (string) ($notesRow['notes'] ?? '');
        if (!preg_match('/paytech_token:([^\s]+)/', $rawNotes, $m)) {
            return ['ok' => false, 'error' => 'Impossible de reprendre le paiement. Réessayez.'];
        }
        $token = trim($m[1]);

        $user       = (new UtilisateurModel())->find($userId);
        $countryIso = $this->checkoutAssistant->resolveCountryIso2($user);
        $phonePair  = $this->checkoutAssistant->normalizePhoneForCheckout(
            isset($user['telephone']) ? (string) $user['telephone'] : null,
            $countryIso
        );
        $fullName   = $this->checkoutAssistant->fullName($user);

        $status = $this->getPaymentStatus($token);
        if (($status['ok'] ?? false)
            && in_array(strtolower((string) ($status['status'] ?? '')), ['completed', 'success'], true)) {
            return ['ok' => false, 'error' => 'Ce paiement est déjà traité. Rechargez la page de la réservation.'];
        }

        $checkoutUrl = 'https://paytech.sn/payment/checkout/' . rawurlencode($token);
        $redirectUrl = $this->checkoutAssistant->appendCheckoutPrefetchParams(
            $checkoutUrl,
            $phonePair,
            $fullName !== '' ? $fullName : null
        );

        return [
            'ok'           => true,
            'payment_id'   => $paymentId,
            'token'        => $token,
            'redirect_url' => $redirectUrl,
        ];
    }

    // ---------------------------------------------------------------
    // Appel API PayTech
    // ---------------------------------------------------------------

    /**
     * PayTech peut renvoyer `success` en entier ou en chaîne selon l'encodeur JSON.
     */
    private function paytechApiResponseSucceeded(array $resp): bool
    {
        $s = $resp['success'] ?? null;
        if ($s === true || $s === 1 || $s === '1') {
            return true;
        }
        if (is_numeric($s)) {
            return (int) round((float) $s) === 1;
        }

        return false;
    }

    /**
     * @return array{ok: bool, payment_id: string, token?: string, redirect_url?: string, error?: string}
     * @param array{pn:string, nn:string}|null $phonePair
     */
    private function callPayTechApi(
        string $paymentId,
        float  $totalAmount,
        string $itemName,
        string $commandName,
        string $type,
        ?string $countryIso,
        ?array $phonePair,
        string $fullName
    ): array {
        $base = rtrim(BASE_URL ?? '', '/');

        // custom_field : JSON encodé en base64 pour identifier la transaction dans l'IPN
        $customField = base64_encode(json_encode([
            'payment_id'     => $paymentId,
            'type'           => $type,
        ]));

        $payload = [
            'item_name'    => $itemName,
            'item_price'   => (int) round($totalAmount),
            'currency'     => 'XOF',
            'ref_command'  => $paymentId,
            'command_name' => $commandName,
            'env'          => $this->getEnv(),
            'ipn_url'      => $base . '/paytech/callback',
            'success_url'  => $base . '/paytech/succes/' . rawurlencode($paymentId),
            'cancel_url'   => $base . '/paytech/echec/' . rawurlencode($paymentId),
            'custom_field' => $customField,
        ];
        $targetCsv = $this->checkoutAssistant->targetPaymentCsvForApi($countryIso);
        if ($targetCsv !== null) {
            $payload['target_payment'] = $targetCsv;
        }

        $body = http_build_query($payload);

        $ch = curl_init(self::PAYMENT_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'API_KEY: '    . $this->getApiKey(),
                'API_SECRET: ' . $this->getApiSecret(),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            error_log('[PayTech] cURL: ' . $err);
            $this->markFailed($paymentId, 'Erreur réseau cURL');
            return ['ok' => false, 'payment_id' => $paymentId, 'error' => 'Connexion à PayTech impossible. Réessayez dans quelques instants.'];
        }

        $resp = json_decode((string) $raw, true);
        if (!is_array($resp) || !$this->paytechApiResponseSucceeded($resp)) {
            $msg = is_array($resp['errors'] ?? null) ? implode(', ', $resp['errors']) : ($resp['message'] ?? 'Réponse PayTech invalide (HTTP ' . $code . ')');
            error_log('[PayTech] API error ' . $code . ': ' . $raw);
            $this->markFailed($paymentId, 'API error: ' . $msg);
            return ['ok' => false, 'payment_id' => $paymentId, 'error' => $msg];
        }

        $data = $resp['data'] ?? [];
        $data = is_array($data) ? $data : [];

        $token       = (string) ($resp['token'] ?? $data['token'] ?? '');
        $redirectUrl = (string) (
            $resp['redirect_url'] ?? $resp['redirectUrl']
            ?? $data['redirect_url'] ?? $data['redirectUrl']
            ?? ''
        );
        if ($redirectUrl === '' && $token !== '') {
            $redirectUrl = 'https://paytech.sn/payment/checkout/' . rawurlencode($token);
        }
        if ($redirectUrl !== '') {
            $redirectUrl = $this->checkoutAssistant->appendCheckoutPrefetchParams(
                $redirectUrl,
                $phonePair,
                $fullName !== '' ? $fullName : null
            );
        }

        // Conserver une note métier (ex. « session:R » ) en plus du token PayTech.
        $prevNotes = '';
        try {
            $stNotes = Database::getInstance()->prepare('SELECT COALESCE(notes, \'\') AS n FROM transactions WHERE payment_id = ? LIMIT 1');
            $stNotes->execute([$paymentId]);
            $rowNotes  = $stNotes->fetch(\PDO::FETCH_ASSOC);
            $prevNotes = trim((string) (($rowNotes['n'] ?? '') ?: ''));
        } catch (\Throwable $e) {
        }
        $tokenNote = 'paytech_token:' . $token;
        if ($prevNotes !== '' && str_contains($prevNotes, 'paytech_token:')) {
            $mergedNotes = preg_replace('/paytech_token:\S+/', $tokenNote, $prevNotes) ?? $tokenNote;
        } elseif ($prevNotes !== '') {
            $mergedNotes = $prevNotes . ' ' . $tokenNote;
        } else {
            $mergedNotes = $tokenNote;
        }

        try {
            Database::getInstance()
                ->prepare('UPDATE transactions SET notes = ? WHERE payment_id = ? LIMIT 1')
                ->execute([$mergedNotes, $paymentId]);
        } catch (\Throwable $e) {
            error_log('[PayTech] notes UPDATE failed (colonne absente ?) : ' . $e->getMessage());
        }

        return [
            'ok'           => true,
            'payment_id'   => $paymentId,
            'token'        => $token,
            'redirect_url' => $redirectUrl,
        ];
    }

    // ---------------------------------------------------------------
    // IPN (webhook) PayTech
    // ---------------------------------------------------------------

    private function mergePayTechFinalizeNotes(string $existingNotes, string $ipnLine): string
    {
        if ($ipnLine === '') {
            return $existingNotes !== '' ? $existingNotes : 'PayTech OK';
        }
        if ($existingNotes !== '' && preg_match('/session:\d+/', $existingNotes, $m)) {
            return $m[0] . ' · ' . $ipnLine;
        }

        return $ipnLine;
    }

    /**
     * Traite la notification IPN envoyée par PayTech.
     * PayTech envoie un POST form-encoded (pas JSON).
     *
     * @param  array<string, mixed> $payload $_POST
     * @return array{ok: bool, error?: string}
     */
    public function completeFromIpn(array $payload): array
    {
        if (!$this->verifyIpnSignature($payload)) {
            error_log('[PayTech] IPN signature invalide: ' . json_encode(array_keys($payload)));
            return ['ok' => false, 'error' => 'Signature IPN invalide'];
        }

        $typeEvent = (string) ($payload['type_event'] ?? '');
        if ($typeEvent !== 'sale_complete') {
            return ['ok' => true]; // Ignorer les autres événements (transfer, refund…)
        }

        $paymentId = $this->extractPaymentId($payload);
        if ($paymentId === '') {
            return ['ok' => false, 'error' => 'payment_id introuvable dans l\'IPN'];
        }

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || $tx['status'] !== 'pending') {
            return ['ok' => true]; // Déjà traité ou introuvable
        }
        if (($tx['provider'] ?? '') !== 'paytech') {
            return ['ok' => false, 'error' => 'Fournisseur incorrect'];
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $ipnLine      = trim('IPN PayTech - ' . ($payload['payment_method'] ?? 'mobile'));
            $finalizeNote = $this->mergePayTechFinalizeNotes(trim((string) ($tx['notes'] ?? '')), $ipnLine);
            $updated = $this->transactionModel->finalizePayTechSuccess(
                $paymentId,
                $finalizeNote
            );
            if ($updated === null) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Finalisation impossible'];
            }

            if (in_array($updated['type'], self::ABONNEMENT_TYPES, true)) {
                $this->activateAbonnement(
                    (int) $updated['user_id'],
                    $updated['abonnement_type'] ?? 'client',
                    $updated['payment_id'],
                    (float) $updated['amount']
                );
            } elseif (in_array($updated['type'], self::DEPOT_TYPES, true)) {
                $this->creditWallet((int) $updated['user_id'], (float) $updated['amount']);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[PayTech::completeFromIpn] ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $fresh = $this->transactionModel->findByPaymentId($paymentId);
        $this->notifyUser((int) $tx['user_id'], 'success', $fresh ?? $tx);

        return ['ok' => true];
    }

    // ---------------------------------------------------------------
    // Vérification de signature IPN
    // ---------------------------------------------------------------

    /**
     * Méthode 1 (recommandée) : HMAC-SHA256
     *   message  = item_price|ref_command|api_key
     *   expected = hash_hmac('sha256', message, api_secret)
     *
     * Méthode 2 : SHA256 des clés
     *   sha256(api_key)    === api_key_sha256
     *   sha256(api_secret) === api_secret_sha256
     */
    public function verifyIpnSignature(array $payload): bool
    {
        $apiKey    = $this->getApiKey();
        $apiSecret = $this->getApiSecret();

        // Méthode 1 — HMAC-SHA256
        if (!empty($payload['hmac_compute'])
            && isset($payload['item_price'], $payload['ref_command'])
        ) {
            $msg      = $payload['item_price'] . '|' . $payload['ref_command'] . '|' . $apiKey;
            $expected = hash_hmac('sha256', $msg, $apiSecret);
            if (hash_equals($expected, (string) $payload['hmac_compute'])) {
                return true;
            }
        }

        // Méthode 2 — SHA256 hash
        if (!empty($payload['api_key_sha256']) && !empty($payload['api_secret_sha256'])) {
            $keyOk    = hash_equals(hash('sha256', $apiKey),    (string) $payload['api_key_sha256']);
            $secretOk = hash_equals(hash('sha256', $apiSecret), (string) $payload['api_secret_sha256']);
            if ($keyOk && $secretOk) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------
    // Statut de paiement
    // ---------------------------------------------------------------

    /**
     * @return array{ok: bool, status?: string, error?: string}
     */
    public function getPaymentStatus(string $token): array
    {
        if (!$this->isConfigured() || $token === '') {
            return ['ok' => false, 'error' => 'Token ou configuration manquant.'];
        }

        $url = self::STATUS_ENDPOINT . '?token_payment=' . rawurlencode($token);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'API_KEY: '    . $this->getApiKey(),
                'API_SECRET: ' . $this->getApiSecret(),
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode((string) $raw, true);
        if (!is_array($resp) || !$this->paytechApiResponseSucceeded($resp)) {
            return ['ok' => false, 'error' => 'Impossible de récupérer le statut.'];
        }

        return ['ok' => true, 'status' => (string) ($resp['status'] ?? '')];
    }

    // ---------------------------------------------------------------
    // Frais de service
    // ---------------------------------------------------------------

    public function calculateFee(float $amount): float
    {
        $pct = (float) $this->parametres->get('wave_commission_pct', (string) self::COMMISSION_DEFAULT_PCT);
        if ($pct <= 0 || $pct > 50) {
            $pct = self::COMMISSION_DEFAULT_PCT;
        }
        return round($amount * $pct / 100, 2);
    }

    // ---------------------------------------------------------------
    // Privé : helpers métier
    // ---------------------------------------------------------------

    private function extractPaymentId(array $payload): string
    {
        // ref_command est défini comme payment_id lors de la requête
        if (!empty($payload['ref_command']) && is_string($payload['ref_command'])) {
            return trim($payload['ref_command']);
        }
        // Fallback : custom_field base64-encodé
        if (!empty($payload['custom_field'])) {
            $decoded = json_decode((string) base64_decode((string) $payload['custom_field'], true), true);
            if (is_array($decoded) && !empty($decoded['payment_id'])) {
                return (string) $decoded['payment_id'];
            }
        }
        return '';
    }

    private function activateAbonnement(int $userId, string $type, string $paymentId, float $amount): bool
    {
        if ($this->abonnementModel->getByExternalReference($paymentId) !== null) {
            return true;
        }
        $dureeJours = (int) $this->parametres->get('abonnement_duree_jours', '30') ?: 30;
        $this->abonnementModel->createFromPayment(
            $userId, $type, 'premium', 'paytech',
            $paymentId, $amount, 'XOF', $dureeJours
        );
        return true;
    }

    private function creditWallet(int $userId, float $montant): void
    {
        (new \App\Models\PortefeuilleModel())->crediter($userId, $montant);
        try {
            (new \App\Models\PaiementModel())->create([
                'reservation_id' => null,
                'client_id'      => $userId,
                'expert_id'      => null,
                'type'           => 'depot',
                'montant'        => $montant,
                'statut'         => 'effectue',
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function notifyUser(int $userId, string $status, array $tx): void
    {
        try {
            $t           = $tx['type'] ?? '';
            $notes       = (string) ($tx['notes'] ?? '');
            $resSession  = null;
            if ($t === 'paiement_session_paytech' && preg_match('/session:(\d+)/', $notes, $m)) {
                $resSession = (int) $m[1];
            }
            $isDepot = in_array($t, self::DEPOT_TYPES, true);

            $lienRetour = '/paytech/historique';
            if ($resSession !== null) {
                $lienRetour = $status === 'success'
                    ? ('/client/payer/' . $resSession)
                    : ('/paytech/paiement-session/' . $resSession);
            } elseif ($isDepot) {
                $lienRetour = '/client/portefeuille';
            }

            if ($status === 'success') {
                if ($resSession !== null) {
                    $titre   = 'Portefeuille crédité ✓';
                    $contenu = sprintf(
                        'Votre compte a été crédité de %s XOF (réf. %s). Finalisez le paiement de la réservation depuis la page mission.',
                        number_format((float) $tx['amount'], 0, ',', ' '),
                        $tx['payment_id']
                    );
                } elseif ($isDepot) {
                    $titre   = 'Dépôt validé ✓';
                    $contenu = sprintf(
                        'Votre dépôt de %s XOF (réf. %s) est confirmé. Portefeuille crédité.',
                        number_format((float) $tx['amount'], 0, ',', ' '),
                        $tx['payment_id']
                    );
                } else {
                    $titre   = 'Paiement PayTech confirmé ✓';
                    $contenu = sprintf(
                        'Votre paiement de %s XOF (réf. %s) est confirmé. Abonnement actif.',
                        number_format((float) $tx['total_amount'], 0, ',', ' '),
                        $tx['payment_id']
                    );
                }
            } else {
                if ($resSession !== null) {
                    $titre   = 'Paiement mission annulé';
                } else {
                    $titre   = $isDepot ? 'Dépôt annulé' : 'Paiement annulé';
                }
                $contenu = sprintf('Réf. %s. Aucun montant n\'a été débité.', $tx['payment_id']);
            }

            (new NotificationModel())->create($userId, 'paiement', $titre, $contenu, $lienRetour);
        } catch (\Throwable $e) {
        }
    }

    private function markFailed(string $paymentId, string $note): void
    {
        try {
            Database::getInstance()
                ->prepare("UPDATE transactions SET status='failed', notes=?, updated_at=NOW() WHERE payment_id=? AND status='pending' LIMIT 1")
                ->execute([$note, $paymentId]);
        } catch (\Throwable $e) {
        }
    }
}
