<?php
/**
 * GLOBALO — Paiements InTouch / TouchPay (API gutouch.com).
 *
 * Variables d’environnement (ou constantes config.php) :
 *   INTOUCH_API_USERNAME, INTOUCH_API_PASSWORD — auth digest API
 *   INTOUCH_LOGIN_AGENT, INTOUCH_PASSWORD_AGENT — identifiants agent TouchPay
 *   INTOUCH_ID — code agence (ex. SECOG8069)
 *   INTOUCH_SERVICE_ORANGE, INTOUCH_SERVICE_MOOV, INTOUCH_SERVICE_WAVE — codes Pay In Mali (surchargeables)
 *
 * Webhook : POST /intouch/callback (JSON). idFromClient doit correspondre au payment_id GLOBALO.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\TransactionModel;
use App\Models\AbonnementModel;
use App\Models\ParametreModel;
use App\Models\NotificationModel;

class IntouchPaymentService
{
    private TransactionModel $transactionModel;
    private AbonnementModel $abonnementModel;
    private ParametreModel $parametres;

    private const DEFAULT_MERCHANT_URL = 'https://apidist.gutouch.net/apidist/sec/touchpayapi/[INTOUCH_ID]/transaction?loginAgent=[LOGIN_AGENT]&passwordAgent=[PASSWORD_AGENT]';

    private const ABONNEMENT_TYPES = [
        'abonnement', 'abonnement_client', 'abonnement_expert',
        'abonnement_etudiant', 'abonnement_professeur',
    ];

    private const DEPOT_TYPES = ['depot_portefeuille', 'paiement_session_touchpay'];

    /** Référence grille agrégateur Mali (Orange / Moov / Wave, paiement marchand) — si wave_commission_pct absent ou hors bornes. */
    private const COMMISSION_DEFAUT_POURCENTAGE = 2.5;

    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
        $this->abonnementModel  = new AbonnementModel();
        $this->parametres       = new ParametreModel();
    }

    public function isConfigured(): bool
    {
        $c = $this->getCredentials();

        return $c['username'] !== '' && $c['password'] !== ''
            && $c['login_agent'] !== '' && $c['password_agent'] !== ''
            && $c['intouch_id'] !== '';
    }

    /**
     * @return array{username:string,password:string,login_agent:string,password_agent:string,intouch_id:string}
     */
    private function getCredentials(): array
    {
        return [
            'username'        => trim((string) (getenv('INTOUCH_API_USERNAME') ?: (defined('INTOUCH_API_USERNAME') ? INTOUCH_API_USERNAME : ''))),
            'password'        => trim((string) (getenv('INTOUCH_API_PASSWORD') ?: (defined('INTOUCH_API_PASSWORD') ? INTOUCH_API_PASSWORD : ''))),
            'login_agent'     => trim((string) (getenv('INTOUCH_LOGIN_AGENT') ?: (defined('INTOUCH_LOGIN_AGENT') ? INTOUCH_LOGIN_AGENT : ''))),
            'password_agent'  => trim((string) (getenv('INTOUCH_PASSWORD_AGENT') ?: (defined('INTOUCH_PASSWORD_AGENT') ? INTOUCH_PASSWORD_AGENT : ''))),
            'intouch_id'      => trim((string) (getenv('INTOUCH_ID') ?: (defined('INTOUCH_ID') ? INTOUCH_ID : ''))),
        ];
    }

    private function merchantEndpointUrl(): string
    {
        $tpl  = getenv('INTOUCH_MERCHANT_URL') ?: (defined('INTOUCH_MERCHANT_URL') ? INTOUCH_MERCHANT_URL : self::DEFAULT_MERCHANT_URL);
        $c    = $this->getCredentials();

        return str_replace(
            ['[INTOUCH_ID]', '[LOGIN_AGENT]', '[PASSWORD_AGENT]'],
            [rawurlencode($c['intouch_id']), rawurlencode($c['login_agent']), rawurlencode($c['password_agent'])],
            $tpl
        );
    }

    private function serviceCodeForOperator(string $operator): string
    {
        $op = strtoupper(trim($operator));
        if ($op === 'OM') {
            $op = 'ORANGE';
        }

        $fromEnv = static function (string $key, string $constantDefault): string {
            $v = getenv($key);
            if ($v !== false && trim((string) $v) !== '') {
                return trim((string) $v);
            }
            if (defined($key)) {
                $c = constant($key);
                if (is_string($c) && trim($c) !== '') {
                    return trim($c);
                }
            }

            return $constantDefault;
        };

        if ($op === 'MOOV') {
            return $fromEnv('INTOUCH_SERVICE_MOOV', 'ML_PAIEMENTMARCHAND_MOOV_TP');
        }
        if ($op === 'WAVE') {
            return $fromEnv('INTOUCH_SERVICE_WAVE', 'ML_PAIEMENTWAVE_TP');
        }
        if ($op === 'ORANGE') {
            return $fromEnv('INTOUCH_SERVICE_ORANGE', 'ML_PAIEMENTMARCHAND_OM_TP');
        }

        return $fromEnv('INTOUCH_SERVICE_ORANGE', 'ML_PAIEMENTMARCHAND_OM_TP');
    }

    /**
     * @return array{ok: bool, payment_id?: string, instructions?: array, error?: string}
     */
    public function createPayment(
        int $userId,
        float $amount,
        float $platformFee,
        string $phone,
        string $type = 'abonnement',
        string $abonnementType = 'client',
        string $operator = 'ORANGE'
    ): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Paiement InTouch non configuré. Contactez l’administrateur.'];
        }
        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Utilisateur invalide.'];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Montant invalide.'];
        }

        $phoneE164 = $this->normalizePhone($phone);
        if (!$this->isValidPhone($phoneE164)) {
            return ['ok' => false, 'error' => $this->phoneErrorMessage()];
        }

        $recipientLocal = $this->toRecipientLocalDigits($phoneE164);
        if ($recipientLocal === '') {
            return ['ok' => false, 'error' => 'Numéro invalide pour Mobile Money.'];
        }

        try {
            if ($type === 'depot_portefeuille') {
                $platformFee = 0.0;
            } elseif ($platformFee <= 0) {
                $platformFee = $this->calculateFee($amount);
            }

            $existing = $this->getPendingForUser($userId, $type, $abonnementType);
            if ($existing !== null && ($existing['provider'] ?? '') === 'intouch') {
                return $this->buildInstructions($existing);
            }

            $paymentId = $this->transactionModel->createTransaction(
                $userId,
                $amount,
                $platformFee,
                $phoneE164,
                $type,
                $abonnementType,
                'intouch',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $tx = $this->transactionModel->findByPaymentId($paymentId);
            if ($tx === null) {
                return ['ok' => false, 'error' => 'Erreur de création de la transaction.'];
            }

            $baseUrl  = rtrim(BASE_URL ?? '', '/');
            $callback = $baseUrl . '/intouch/callback';
            $apiResp  = $this->requestMerchantPayment(
                $paymentId,
                (string) (int) round($tx['total_amount']),
                $recipientLocal,
                $this->serviceCodeForOperator($operator),
                $callback
            );

            if (!$apiResp['ok']) {
                $stmt = \App\Core\Database::getInstance()->prepare("
                    UPDATE transactions SET status = 'failed', notes = ?, updated_at = NOW()
                    WHERE payment_id = ? AND status = 'pending'
                ");
                $stmt->execute(['API InTouch : ' . ($apiResp['error'] ?? 'échec'), $paymentId]);

                return ['ok' => false, 'error' => $apiResp['error'] ?? 'Impossible d’initier le paiement InTouch.'];
            }

            $placeholder = 'ITP-' . strtoupper(bin2hex(random_bytes(4)));
            $this->transactionModel->submitTransactionCode($paymentId, $placeholder);

            return $this->buildInstructions($this->transactionModel->findByPaymentId($paymentId) ?? $tx);
        } catch (\Throwable $e) {
            error_log('[IntouchPaymentService::createPayment] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, error?: string, raw?: string}
     */
    private function requestMerchantPayment(
        string $idFromClient,
        string $amountXof,
        string $recipientLocalDigits,
        string $serviceCode,
        string $callbackUrl
    ): array {
        $c    = $this->getCredentials();
        $url  = $this->merchantEndpointUrl();
        $body = json_encode([
            'idFromClient'      => $idFromClient,
            'amount'            => $amountXof,
            'callback'          => $callbackUrl,
            'recipientNumber'   => $recipientLocalDigits,
            'serviceCode'       => $serviceCode,
            'additionalInfos'   => ['source' => 'GLOBALO'],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
            CURLOPT_USERPWD        => $c['username'] . ':' . $c['password'],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            error_log('[InTouch cURL] erreur réseau : ' . ($err ?: 'échec cURL') . ' | URL=' . $url);
            return ['ok' => false, 'error' => 'Erreur réseau InTouch : ' . ($err ?: 'cURL failed'), 'raw' => ''];
        }

        error_log('[InTouch API] HTTP ' . $code . ' | response=' . substr((string) $raw, 0, 300));

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Réponse InTouch invalide (HTTP ' . $code . ') : ' . substr((string) $raw, 0, 100), 'raw' => (string) $raw];
        }

        $ok = ($data['status'] ?? '') === 'INITIATED'
            || isset($data['amount'])
            || ($code >= 200 && $code < 300 && empty($data['error']));

        if (!$ok) {
            $msg = $data['message'] ?? $data['error'] ?? $data['status'] ?? 'HTTP ' . $code;
            $detail = is_string($msg) ? $msg : json_encode($msg);
            return ['ok' => false, 'error' => 'InTouch : ' . $detail . ' (HTTP ' . $code . ')', 'raw' => (string) $raw];
        }

        return ['ok' => true, 'raw' => (string) $raw];
    }

    /**
     * @return array{ok: bool, instructions?: array, payment_id?: string, status?: string, error?: string}
     */
    public function getInstructions(string $paymentId): array
    {
        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null) {
            return ['ok' => false, 'error' => 'Transaction introuvable.'];
        }

        return $this->buildInstructions($tx);
    }

    public function submitCode(string $paymentId, string $transactionCode): array
    {
        $paymentId       = trim($paymentId);
        $transactionCode = strtoupper(trim($transactionCode));
        if ($paymentId === '' || $transactionCode === '') {
            return ['ok' => false, 'error' => 'Paramètres manquants.'];
        }
        if (!preg_match('/^[A-Z0-9\-]{4,40}$/', $transactionCode)) {
            return ['ok' => false, 'error' => 'Référence invalide.'];
        }
        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null) {
            return ['ok' => false, 'error' => 'Transaction introuvable.'];
        }
        if (($tx['provider'] ?? '') !== 'intouch') {
            return ['ok' => false, 'error' => 'Transaction non InTouch.'];
        }
        if ($tx['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'Cette transaction ne peut plus être modifiée.'];
        }
        if (str_starts_with((string) ($tx['transaction_code'] ?? ''), 'ITP-')) {
            $ok = $this->transactionModel->replacePlaceholderTransactionCode($paymentId, $transactionCode);
            if (!$ok) {
                return ['ok' => false, 'error' => 'Impossible d’enregistrer cette référence (déjà utilisée ou transaction inchangée).'];
            }

            return ['ok' => true, 'message' => 'Référence enregistrée. Notre équipe peut finaliser si le webhook n’a pas confirmé.'];
        }

        return ['ok' => false, 'error' => 'Aucune mise à jour nécessaire.'];
    }

    /**
     * @return array{ok: bool, abonnement_active?: bool, error?: string, transaction?: array}
     */
    public function validateByAdmin(string $paymentId, int $adminId, string $notes = ''): array
    {
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            $tx = $this->transactionModel->validate($paymentId, $adminId, $notes);
            if ($tx === null) {
                $db->rollBack();

                return ['ok' => false, 'error' => 'Transaction introuvable, déjà traitée, ou référence manquante.'];
            }
            if (($tx['provider'] ?? '') !== 'intouch') {
                $db->rollBack();

                return ['ok' => false, 'error' => 'Fournisseur incorrect.'];
            }

            $abonnementActive = false;
            if (in_array($tx['type'], self::ABONNEMENT_TYPES, true)) {
                $abonnementActive = $this->activateAbonnement(
                    (int) $tx['user_id'],
                    $tx['abonnement_type'] ?? 'client',
                    $tx['payment_id'],
                    (float) $tx['amount']
                );
            } elseif (in_array($tx['type'], self::DEPOT_TYPES, true)) {
                $this->creditWallet((int) $tx['user_id'], (float) $tx['amount']);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();

            return ['ok' => false, 'error' => 'Erreur lors de l’activation : ' . $e->getMessage()];
        }
        $this->notifyUser((int) $tx['user_id'], 'success', $tx);

        return ['ok' => true, 'abonnement_active' => $abonnementActive, 'transaction' => $tx];
    }

    public function refuseByAdmin(string $paymentId, int $adminId, string $notes = ''): bool
    {
        $ok = $this->transactionModel->refuse($paymentId, $adminId, $notes);
        if ($ok) {
            $tx = $this->transactionModel->findByPaymentId($paymentId);
            if ($tx) {
                $this->notifyUser((int) $tx['user_id'], 'failed', $tx);
            }
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, error?: string}
     */
    public function completeFromWebhook(array $payload): array
    {
        $paymentId = $this->extractPaymentIdFromPayload($payload);
        if ($paymentId === '') {
            return ['ok' => false, 'error' => 'idFromClient manquant'];
        }
        if (!$this->isSuccessPayload($payload)) {
            return ['ok' => false, 'error' => 'statut non confirmé'];
        }

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || ($tx['provider'] ?? '') !== 'intouch' || $tx['status'] !== 'pending') {
            return ['ok' => true];
        }

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            $updated = $this->transactionModel->finalizeIntouchSuccess($paymentId, 'Webhook InTouch');
            if ($updated === null) {
                $db->rollBack();

                return ['ok' => false, 'error' => 'finalisation impossible'];
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
            error_log('[IntouchPaymentService::completeFromWebhook] ' . $e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $this->notifyUser((int) $tx['user_id'], 'success', $this->transactionModel->findByPaymentId($paymentId) ?? $tx);

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractPaymentIdFromPayload(array $payload): string
    {
        $keys = ['idFromClient', 'id_from_client', 'partner_transaction_id', 'reference'];
        foreach ($keys as $k) {
            if (!empty($payload[$k]) && is_string($payload[$k])) {
                return trim($payload[$k]);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isSuccessPayload(array $payload): bool
    {
        $s = strtoupper((string) ($payload['status'] ?? $payload['transaction_status'] ?? $payload['statut'] ?? ''));
        if (str_contains($s, 'SUCCESS') || str_contains($s, 'SUCCES') || $s === 'OK' || $s === 'COMPLETED') {
            return true;
        }
        if (!empty($payload['success']) && $payload['success'] === true) {
            return true;
        }
        if (isset($payload['code']) && (string) $payload['code'] === '0') {
            return true;
        }

        return false;
    }

    public function calculateFee(float $amount): float
    {
        $pct = (float) $this->parametres->get(
            'wave_commission_pct',
            (string) self::COMMISSION_DEFAUT_POURCENTAGE
        );
        if ($pct <= 0 || $pct > 50) {
            $pct = self::COMMISSION_DEFAUT_POURCENTAGE;
        }

        return round($amount * $pct / 100, 2);
    }

    /** Widget TouchPay (script SendPaymentInfos) — code agence + code sécurité ([doc PAYMENT/22](https://developers.intouchgroup.net/documentation/PAYMENT/22)). */
    public function isTouchpayWidgetConfigured(): bool
    {
        return $this->getAgencyCode() !== '' && $this->getTouchpaySecureCode() !== '';
    }

    /**
     * Détermine si le widget TouchPay doit être utilisé pour les abonnements.
     * Mode 'api' (défaut) → toujours le formulaire API digest (pas de restriction de domaine).
     * Mode 'widget'       → widget SendPaymentInfos (domaine doit être enregistré chez InTouch).
     * Mode 'auto'         → widget si configuré, sinon API.
     */
    public function shouldUseTouchpayWidgetForAbonnement(): bool
    {
        // Lire depuis constante, puis getenv, puis $_SERVER, puis fallback 'api'
        $mode = 'api'; // défaut : API Pay-In, pas de restriction domaine
        if (defined('TOUCHPAY_ABONNEMENT_MODE') && (string) TOUCHPAY_ABONNEMENT_MODE !== '') {
            $mode = (string) TOUCHPAY_ABONNEMENT_MODE;
        } elseif (getenv('TOUCHPAY_ABONNEMENT_MODE') !== false && getenv('TOUCHPAY_ABONNEMENT_MODE') !== '') {
            $mode = (string) getenv('TOUCHPAY_ABONNEMENT_MODE');
        } elseif (!empty($_SERVER['TOUCHPAY_ABONNEMENT_MODE'])) {
            $mode = (string) $_SERVER['TOUCHPAY_ABONNEMENT_MODE'];
        }

        if ($mode === 'api') {
            return false; // toujours formulaire API
        }
        if ($mode === 'widget') {
            return $this->isTouchpayWidgetConfigured();
        }
        // 'auto' : widget si configuré
        return $this->isTouchpayWidgetConfigured();
    }

    /**
     * API Pay-In (digest) absente mais TouchPay (INTOUCH_ID + TOUCHPAY_SECURE_CODE) présent :
     * utiliser TouchPay pour ne pas proposer un lien /intouch/paiement/ cassé.
     */
    public function canFallbackAbonnementToTouchpayWidget(): bool
    {
        return !$this->isConfigured() && $this->isTouchpayWidgetConfigured();
    }

    /** Chemin relatif : /intouch/touchpay/{type} ou /intouch/paiement/{type}. */
    public function getAbonnementPaymentRelativePath(string $abonnementType): string
    {
        if ($this->shouldUseTouchpayWidgetForAbonnement()) {
            return '/intouch/touchpay/' . rawurlencode($abonnementType);
        }
        if ($this->canFallbackAbonnementToTouchpayWidget()) {
            return '/intouch/touchpay/' . rawurlencode($abonnementType);
        }

        return '/intouch/paiement/' . rawurlencode($abonnementType);
    }

    /** Message utilisateur lorsque ni API digest ni TouchPay ne sont configurés pour l’abonnement. */
    public function getAbonnementPayInUnavailableMessage(): string
    {
        $mode = defined('TOUCHPAY_ABONNEMENT_MODE') ? (string) TOUCHPAY_ABONNEMENT_MODE : (getenv('TOUCHPAY_ABONNEMENT_MODE') ?: 'auto');
        if ($mode === 'api') {
            return 'Paiement abonnement (API) : les identifiants API InTouch (digest) ne sont pas renseignés sur le serveur. Complétez INTOUCH_API_USERNAME, INTOUCH_API_PASSWORD, INTOUCH_LOGIN_AGENT et INTOUCH_PASSWORD_AGENT, ou utilisez TouchPay (INTOUCH_ID + TOUCHPAY_SECURE_CODE) en retirant le mode « api ».';
        }

        return 'Paiement en ligne non disponible : définissez au minimum INTOUCH_ID et TOUCHPAY_SECURE_CODE (TouchPay), ou les identifiants API InTouch (digest) pour le formulaire classique.';
    }

    public function getTouchpayScriptUrl(): string
    {
        return defined('TOUCHPAY_SCRIPT_URL') ? (string) TOUCHPAY_SCRIPT_URL : 'https://touchpay.gutouch.net/touchpayv2/script/touchpaynr/prod_touchpay-0.0.1.js';
    }

    public function getTouchpayDomainName(): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $host = parse_url($base, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    /**
     * Dépôt portefeuille via widget TouchPay (sans API digest).
     * Le montant est crédité au portefeuille via webhook ou validation admin.
     *
     * @return array{ok: bool, error?: string, payment_id?: string, amount?: float, platform_fee?: float, total_amount?: float}
     */
    public function prepareTouchpayWidgetDepot(int $userId, float $amount): array
    {
        if (!$this->isTouchpayWidgetConfigured()) {
            return ['ok' => false, 'error' => 'TouchPay widget : définissez INTOUCH_ID et TOUCHPAY_SECURE_CODE.'];
        }
        if ($userId <= 0 || $amount <= 0) {
            return ['ok' => false, 'error' => 'Montant ou utilisateur invalide.'];
        }
        try {
            $paymentId = $this->transactionModel->createTransaction(
                $userId,
                $amount,
                0.0,
                '+22370000001',
                'depot_portefeuille',
                'depot_widget',
                'intouch',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            \App\Core\Database::getInstance()
                ->prepare('UPDATE transactions SET notes = ? WHERE payment_id = ? AND status = ? LIMIT 1')
                ->execute(['touchpay_widget_pending', $paymentId, 'pending']);
        } catch (\Throwable $e) {
            error_log('[IntouchPaymentService::prepareTouchpayWidgetDepot] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Impossible de créer la commande de dépôt.'];
        }
        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null) {
            return ['ok' => false, 'error' => 'Transaction introuvable après création.'];
        }
        return [
            'ok'           => true,
            'payment_id'   => $paymentId,
            'amount'       => (float) $tx['amount'],
            'platform_fee' => (float) $tx['platform_fee'],
            'total_amount' => (float) $tx['total_amount'],
        ];
    }

    /**
     * Paiement direct d'une session de réservation via widget TouchPay.
     * Le montant crédite le portefeuille (webhook/admin) ; le client finalise ensuite le paiement escrow.
     *
     * @return array{ok: bool, error?: string, payment_id?: string, amount?: float, platform_fee?: float, total_amount?: float}
     */
    public function prepareTouchpayWidgetSession(int $userId, int $reservationId, float $amount): array
    {
        if (!$this->isTouchpayWidgetConfigured()) {
            return ['ok' => false, 'error' => 'TouchPay widget : définissez INTOUCH_ID et TOUCHPAY_SECURE_CODE.'];
        }
        if ($userId <= 0 || $reservationId <= 0 || $amount <= 0) {
            return ['ok' => false, 'error' => 'Paramètres invalides.'];
        }
        // Réutiliser une transaction pendante pour la même réservation (< 2h)
        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM transactions
                WHERE user_id = ? AND type = 'paiement_session_touchpay'
                  AND notes = ? AND status = 'pending'
                  AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
                ORDER BY created_at DESC LIMIT 1
            ");
            $noteKey = 'session:' . $reservationId;
            $stmt->execute([$userId, $noteKey]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($existing) {
                return [
                    'ok'           => true,
                    'payment_id'   => (string) $existing['payment_id'],
                    'amount'       => (float) $existing['amount'],
                    'platform_fee' => (float) $existing['platform_fee'],
                    'total_amount' => (float) $existing['total_amount'],
                    'reused'       => true,
                ];
            }
        } catch (\Throwable $e) {
            // table absente ou erreur : on continue et on crée
        }
        try {
            $paymentId = $this->transactionModel->createTransaction(
                $userId,
                $amount,
                0.0,
                '+22370000001',
                'paiement_session_touchpay',
                'session',
                'intouch',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            \App\Core\Database::getInstance()
                ->prepare('UPDATE transactions SET notes = ? WHERE payment_id = ? AND status = ? LIMIT 1')
                ->execute(['session:' . $reservationId, $paymentId, 'pending']);
        } catch (\Throwable $e) {
            error_log('[IntouchPaymentService::prepareTouchpayWidgetSession] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Impossible de créer la commande de paiement.'];
        }
        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null) {
            return ['ok' => false, 'error' => 'Transaction introuvable après création.'];
        }
        return [
            'ok'           => true,
            'payment_id'   => $paymentId,
            'amount'       => (float) $tx['amount'],
            'platform_fee' => (float) $tx['platform_fee'],
            'total_amount' => (float) $tx['total_amount'],
            'reused'       => false,
        ];
    }

    /**
     * Commande abonnement pour la page TouchPay (sans PUT API digest).
     *
     * @return array{ok: bool, error?: string, payment_id?: string, amount?: float, platform_fee?: float, total_amount?: float, reused?: bool}
     */
    public function prepareTouchpayWidgetAbonnement(int $userId, string $abonnementType, float $amount, float $platformFee): array
    {
        if (!$this->isTouchpayWidgetConfigured()) {
            return ['ok' => false, 'error' => 'TouchPay widget : définissez INTOUCH_ID et TOUCHPAY_SECURE_CODE.'];
        }
        if ($userId <= 0 || $amount <= 0) {
            return ['ok' => false, 'error' => 'Montant ou utilisateur invalide.'];
        }
        $txType = 'abonnement';
        $existing = $this->getPendingForUser($userId, $txType, $abonnementType);
        if ($existing !== null) {
            return [
                'ok'             => true,
                'payment_id'     => (string) $existing['payment_id'],
                'amount'         => (float) $existing['amount'],
                'platform_fee'   => (float) $existing['platform_fee'],
                'total_amount'   => (float) $existing['total_amount'],
                'reused'         => true,
            ];
        }
        if ($platformFee <= 0) {
            $platformFee = $this->calculateFee($amount);
        }
        $placeholderPhone = '+22370000001';
        try {
            $paymentId = $this->transactionModel->createTransaction(
                $userId,
                $amount,
                $platformFee,
                $placeholderPhone,
                $txType,
                $abonnementType,
                'intouch',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare('UPDATE transactions SET notes = ? WHERE payment_id = ? AND status = ? LIMIT 1');
            $stmt->execute(['touchpay_widget_pending', $paymentId, 'pending']);
        } catch (\Throwable $e) {
            error_log('[IntouchPaymentService::prepareTouchpayWidgetAbonnement] ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Impossible de créer la commande.'];
        }
        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null) {
            return ['ok' => false, 'error' => 'Transaction introuvable.'];
        }

        return [
            'ok'           => true,
            'payment_id'   => $paymentId,
            'amount'       => (float) $tx['amount'],
            'platform_fee' => (float) $tx['platform_fee'],
            'total_amount' => (float) $tx['total_amount'],
            'reused'       => false,
        ];
    }

    /**
     * Arguments passés à SendPaymentInfos (ordre doc InTouch). Surcharge : variable d’environnement TOUCHPAY_SENDPAYMENT_ARGS_JSON (tableau JSON avec placeholders {{payment_id}}, {{agency_code}}, {{secure_code}}, {{domain_name}}, {{amount}}, {{callback}}, {{return_url}}).
     *
     * @return list<mixed>
     */
    public function buildTouchpaySendPaymentArgs(array $txRow, array $userInfo = []): array
    {
        $base      = rtrim(BASE_URL ?? '', '/');
        $paymentId = (string) ($txRow['payment_id'] ?? '');
        $total     = (int) round((float) ($txRow['total_amount'] ?? 0));
        $agency    = $this->getAgencyCode();
        $secure    = $this->getTouchpaySecureCode();
        $domain    = $this->getTouchpayDomainName();

        // URLs de succès et d'échec (Checkout Page script2)
        $urlSuccess = $base . '/intouch/succes/' . rawurlencode($paymentId);
        $urlFailed  = $base . '/intouch/echec/' . rawurlencode($paymentId);

        // Infos client (depuis la transaction jointure utilisateurs ou userInfo passé en param)
        $email     = (string) ($userInfo['email']  ?? $txRow['email']  ?? '');
        $prenom    = (string) ($userInfo['prenom'] ?? $txRow['prenom'] ?? '');
        $nom       = (string) ($userInfo['nom']    ?? $txRow['nom']    ?? '');
        $phone     = (string) ($userInfo['phone']  ?? $txRow['phone']  ?? '');
        $ville     = (string) ($userInfo['ville']  ?? '');

        // Nettoyer le téléphone (format local sans +223)
        $phoneLocal = ltrim(preg_replace('/^\+?223/', '', preg_replace('/\s+/', '', $phone)) ?? '', '0');
        if ($phoneLocal === '') $phoneLocal = '00000000';

        return [
            $paymentId,   // order_number
            $agency,      // agency_code
            $secure,      // secure_code
            $domain,      // domain_name
            $urlSuccess,  // url_redirection_success
            $urlFailed,   // url_redirection_failed
            $total,       // amount
            $ville ?: 'Bamako',  // city
            $email,       // email
            $prenom ?: 'Client', // clientFirstName
            $nom    ?: 'GLOBALO',// clientLastName
            $phoneLocal,  // clientPhone
        ];
    }

    /**
     * @param list<mixed>|array<int|string, mixed> $template
     * @return list<mixed>
     */
    private function interpolateTouchpayTemplate(
        array $template,
        string $paymentId,
        string $agency,
        string $secure,
        string $domain,
        int $total,
        string $callback,
        string $base
    ): array {
        $returnUrl = $base . '/intouch/verification/' . rawurlencode($paymentId);
        $map       = [
            '{{payment_id}}'   => $paymentId,
            '{{agency_code}}'  => $agency,
            '{{secure_code}}'  => $secure,
            '{{domain_name}}'  => $domain,
            '{{amount}}'       => $total,
            '{{callback}}'     => $callback,
            '{{return_url}}'   => $returnUrl,
        ];
        $out = [];
        foreach ($template as $item) {
            $out[] = is_string($item) ? strtr($item, $map) : $item;
        }

        return $out;
    }

    private function getAgencyCode(): string
    {
        return trim((string) (getenv('INTOUCH_ID') ?: (defined('INTOUCH_ID') ? INTOUCH_ID : '')));
    }

    /**
     * Masque le code agence pour affichage (TouchPay / prod) sans exposer l’identifiant complet.
     */
    public function getAgencyCodeMaskedForDisplay(): string
    {
        $id = $this->getAgencyCode();
        if ($id === '') {
            return '—';
        }
        if (strlen($id) <= 4) {
            return '****';
        }

        return substr($id, 0, 4) . str_repeat('•', min(12, strlen($id) - 4));
    }

    private function getTouchpaySecureCode(): string
    {
        return trim((string) (getenv('TOUCHPAY_SECURE_CODE') ?: (defined('TOUCHPAY_SECURE_CODE') ? TOUCHPAY_SECURE_CODE : '')));
    }

    /**
     * @return array{ok: bool, instructions: array, payment_id: string, status: string}
     */
    private function buildInstructions(array $tx): array
    {
        $total = (float) $tx['total_amount'];

        return [
            'ok'           => true,
            'payment_id'   => $tx['payment_id'],
            'status'       => $tx['status'],
            'instructions' => [
                'montant_total' => $total,
                'montant_net'   => (float) $tx['amount'],
                'commission'    => (float) $tx['platform_fee'],
                'devise'        => 'XOF',
                'reference'     => $tx['payment_id'],
                'etapes'        => [
                    'Une demande de paiement a été envoyée sur votre numéro Mobile Money.',
                    'Validez l’opération dans l’application ou via le code USSD de votre opérateur.',
                    'Après validation, votre abonnement ou dépôt sera crédité automatiquement (quelques instants).',
                    'Si rien ne s’affiche, vérifiez votre solde et réessayez, ou contactez le support.',
                ],
            ],
        ];
    }

    private function activateAbonnement(int $userId, string $type, string $paymentId, float $amount): bool
    {
        if ($this->abonnementModel->getByExternalReference($paymentId) !== null) {
            return true;
        }
        $dureeJours = (int) $this->parametres->get('abonnement_duree_jours', '30') ?: 30;
        $this->abonnementModel->createFromPayment(
            $userId,
            $type,
            'premium',
            'intouch',
            $paymentId,
            $amount,
            'XOF',
            $dureeJours
        );

        return true;
    }

    private function notifyUser(int $userId, string $status, array $tx): void
    {
        try {
            $notifModel = new NotificationModel();
            $isDepot    = in_array($tx['type'], self::DEPOT_TYPES, true);
            $isSession  = ($tx['type'] ?? '') === 'paiement_session_touchpay';
            // Pour un paiement session TouchPay, extraire le reservationId depuis notes
            $sessionReservationId = null;
            if ($isSession && !empty($tx['notes']) && str_starts_with((string) $tx['notes'], 'session:')) {
                $sessionReservationId = (int) substr((string) $tx['notes'], 8);
            }
            $lienRetour = $isSession && $sessionReservationId
                ? '/client/payer/' . $sessionReservationId
                : ($isDepot ? '/client/portefeuille' : '/intouch/historique');
            if ($status === 'success') {
                if ($isDepot) {
                    $titre   = 'Dépôt validé ✓';
                    $contenu = sprintf(
                        'Votre dépôt de %s XOF (réf. %s) est confirmé.',
                        number_format((float) $tx['amount'], 0, ',', ' '),
                        $tx['payment_id']
                    );
                } else {
                    $titre   = 'Paiement InTouch confirmé ✓';
                    $contenu = sprintf(
                        'Votre paiement de %s XOF (réf. %s) est confirmé. Abonnement actif.',
                        number_format((float) $tx['total_amount'], 0, ',', ' '),
                        $tx['payment_id']
                    );
                }
            } else {
                $motif   = !empty($tx['notes']) ? ' Motif : ' . $tx['notes'] : '';
                $titre   = $isDepot ? 'Dépôt refusé' : 'Paiement refusé';
                $contenu = sprintf('Réf. %s.%s', $tx['payment_id'], $motif);
            }
            $notifModel->create($userId, 'paiement', $titre, $contenu, $lienRetour);
        } catch (\Throwable $e) {
        }
    }

    private function creditWallet(int $userId, float $montant): void
    {
        $portefeuille = new \App\Models\PortefeuilleModel();
        $portefeuille->crediter($userId, $montant);
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

    private function getPendingForUser(int $userId, string $type, string $abonnementType): ?array
    {
        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM transactions
                WHERE user_id = ? AND type = ? AND abonnement_type = ?
                  AND status = 'pending' AND provider = 'intouch'
                  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $type, $abonnementType]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private const MM_COUNTRIES = [
        '221' => [9],
        '223' => [8],
        '224' => [8, 9],
        '225' => [8, 9, 10],
        '226' => [8],
        '237' => [9],
    ];

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\.]/', '', $phone) ?? '';
        if (preg_match('/^00(221|223|224|225|226|237)(\d+)$/', $phone, $m)) {
            return '+' . $m[1] . $m[2];
        }
        if (preg_match('/^[0-9]{8}$/', $phone)) {
            return '+223' . $phone;
        }

        return $phone;
    }

    private function isValidPhone(string $phone): bool
    {
        foreach (self::MM_COUNTRIES as $code => $lengths) {
            if (str_starts_with($phone, '+' . $code)) {
                $local = substr($phone, strlen($code) + 1);
                if (ctype_digit($local) && in_array(strlen($local), $lengths, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function phoneErrorMessage(): string
    {
        return 'Numéro invalide. Ex. +223XXXXXXXX pour le Mali.';
    }

    /** Chiffres locaux pour l’API (sans indicatif). */
    private function toRecipientLocalDigits(string $e164): string
    {
        foreach (array_keys(self::MM_COUNTRIES) as $code) {
            $p = '+' . $code;
            if (str_starts_with($e164, $p)) {
                return substr($e164, strlen($p));
            }
        }

        return '';
    }
}
