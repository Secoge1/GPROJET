<?php
/**
 * GLOBALO — Paiements PayTech
 *
 * GET  /paytech/checkout/{type}       — Page de confirmation abonnement + bouton payer
 * POST /paytech/initier               — Créer la demande PayTech, rediriger vers PayTech
 * GET  /paytech/paiement-session/{id} — écran récap + téléphone Mobile Money puis PayTech
 * POST /paytech/initier-paiement-session — crédit portefeuille mission → PayTech
 * POST /paytech/initier-depot         — Créer la demande dépôt PayTech
 * GET  /paytech/succes/{paymentId}    — Succès après redirection avec référence (session requise)
 * GET  /paytech/echec/{paymentId}      — Échec / abandon avec référence (session requise)
 * GET  /paytech/paiement-reussi        — Succès générique (URL fixe web + tableau de bord PayTech)
 * GET  /paytech/paiement-annule        — Annulation générique (idem)
 * GET  /paytech/historique            — Historique transactions
 * POST /paytech/callback              — IPN PayTech (webhook, sans auth session)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\TransactionModel;
use App\Models\UtilisateurModel;
use App\Services\PayTechPaymentService;
use App\Services\PayTechCheckoutAssistant;
use App\Services\SubscriptionService;

class PaytechController extends Controller
{
    private PayTechPaymentService $paytech;
    private TransactionModel      $transactionModel;
    private SubscriptionService   $subscriptionService;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->paytech             = new PayTechPaymentService();
        $this->transactionModel    = new TransactionModel();
        $this->subscriptionService = new SubscriptionService();
    }

    // ---------------------------------------------------------------
    // Abonnement
    // ---------------------------------------------------------------

    /** GET /paytech/checkout/{type} — résumé commande + bouton "Payer" */
    public function checkout(string $abonnementType = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base = rtrim(BASE_URL ?? '', '/');

        if (!$this->paytech->isConfigured()) {
            $_SESSION['flash_error'] = 'Paiement PayTech non disponible. Contactez l\'administrateur.';
            $this->redirect($base . '/abonnement');
            return;
        }

        if ($abonnementType === '') {
            $abonnementType = $this->defaultAbonnementTypeFromRole();
        }

        $prix       = $this->getPrixAbonnement($abonnementType);
        $commission = $this->paytech->calculateFee($prix);
        $total      = round($prix + $commission, 2);

        $ux = $this->payTechUxContext();

        $this->render('checkout', [
            'pageTitle'       => 'Paiement — Abonnement GLOBALO',
            'user'            => ['id' => Auth::id(), 'role' => Auth::role()],
            'abonnement_type' => $abonnementType,
            'montant'         => $prix,
            'commission'      => $commission,
            'total'           => $total,
            'csrf_token'      => \App\Core\Security::generateCsrfToken(),
        ] + $ux);
    }

    /** POST /paytech/initier — demande paiement → redirect vers PayTech */
    public function initier(): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base = rtrim(BASE_URL ?? '', '/');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($base . '/abonnement');
            return;
        }

        $abonnementType = trim((string) ($_POST['abonnement_type'] ?? $this->defaultAbonnementTypeFromRole()));
        $userId         = (int) Auth::id();
        $logCtx         = '[PayTech/initier] user=' . $userId . ' abo=' . $abonnementType;

        if (!$this->paytech->isConfigured()) {
            error_log($logCtx . ' ERREUR isConfigured=false');
            $_SESSION['flash_error'] = 'Paiement PayTech non disponible sur ce serveur. Contactez l\'administrateur.';
            $this->redirect($base . '/paytech/checkout/' . rawurlencode($abonnementType));
            return;
        }

        $phoneRes = $this->resolvePaytechPhoneFromPost($userId);
        if (!$phoneRes['ok']) {
            error_log($logCtx . ' ERREUR téléphone: ' . ($phoneRes['error'] ?? '?'));
            $_SESSION['flash_error'] = $phoneRes['error'];
            $this->redirect($base . '/paytech/checkout/' . rawurlencode($abonnementType));
            return;
        }
        /** @var array{pn:string,nn:string} $phonePair */
        $phonePair = $phoneRes['pair'];
        $prix      = $this->getPrixAbonnement($abonnementType);
        $commission = $this->paytech->calculateFee($prix);

        $typeLabels = [
            'client'     => 'Abonnement Client GLOBALO',
            'expert'     => 'Abonnement Expert GLOBALO',
            'etudiant'   => 'Abonnement Étudiant GLOBALO',
            'professeur' => 'Abonnement Professeur GLOBALO',
        ];
        $itemName    = $typeLabels[$abonnementType] ?? 'Abonnement GLOBALO';
        $commandName = 'Paiement abonnement ' . $abonnementType . ' — GLOBALO';

        error_log($logCtx . ' appel API PayTech prix=' . $prix . ' comm=' . $commission . ' pn=' . $phonePair['pn']);

        try {
            $result = $this->paytech->requestPayment(
                $userId, $prix, $commission,
                'abonnement', $abonnementType,
                $itemName, $commandName,
                $phonePair
            );
        } catch (\Throwable $e) {
            error_log($logCtx . ' EXCEPTION requestPayment: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $_SESSION['flash_error'] = 'Erreur interne lors de l\'initiation du paiement. Réessayez.';
            $this->redirect($base . '/paytech/checkout/' . rawurlencode($abonnementType));
            return;
        }

        if (!$result['ok']) {
            $err = $result['error'] ?? 'Impossible d\'initier le paiement. Réessayez.';
            error_log($logCtx . ' ERREUR API: ' . $err);
            $_SESSION['flash_error'] = $err;
            $this->redirect($base . '/paytech/checkout/' . rawurlencode($abonnementType));
            return;
        }

        $redirectUrl = trim((string) ($result['redirect_url'] ?? ''));
        if ($redirectUrl === '' || stripos($redirectUrl, 'http') !== 0) {
            error_log($logCtx . ' ERREUR redirect_url vide result=' . json_encode($result));
            $_SESSION['flash_error'] = 'Réponse PayTech incomplète (URL vide). Réessayez ou contactez le support.';
            $this->redirect($base . '/paytech/checkout/' . rawurlencode($abonnementType));
            return;
        }

        error_log($logCtx . ' SUCCÈS redirect=' . $redirectUrl);
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    // ---------------------------------------------------------------
    // Dépôt portefeuille
    // ---------------------------------------------------------------

    /** GET /paytech/paiement-session/{reservationId} — récap mission + saisie téléphone → POST initier */
    public function paiementSession(string $reservationIdParam = ''): void
    {
        Auth::requireRole('client');
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? $reservationIdParam ?? 0);
        $payerUrl = $this->paytechSessionPayerUrl($reservationId);

        if (!$this->paytech->isConfigured()) {
            $_SESSION['flash_error'] = 'PayTech n\'est pas configuré sur ce serveur.';
            $this->redirect($payerUrl);
            return;
        }

        if ($reservationId <= 0) {
            $this->redirect($this->clientReservationsListUrl());
            return;
        }

        $reservation = (new \App\Models\ReservationModel())->find($reservationId);
        if (!$reservation || (int) $reservation['client_id'] !== (int) Auth::id()) {
            $this->redirect($this->clientReservationsListUrl());
            return;
        }
        if ($reservation['statut'] !== 'acceptee') {
            $this->redirect($this->clientReservationShowUrl($reservationId));
            return;
        }

        $montant   = (float) $reservation['montant_total'];
        $missionTitre = isset($reservation['demande_titre']) ? trim((string) $reservation['demande_titre']) : '';

        $this->render('paiement_session', [
            'pageTitle'          => 'Paiement mission — PayTech',
            'user'               => ['id' => Auth::id(), 'role' => Auth::role()],
            'reservation_id'     => $reservationId,
            'mission_titre'      => $missionTitre,
            'montant'            => $montant,
            'commission'         => 0.0,
            'total'              => $montant,
            'payer_back_url'     => $payerUrl,
            'csrf_token'         => \App\Core\Security::generateCsrfToken(),
        ] + $this->payTechUxContext());
    }

    /** POST /paytech/initier-paiement-session — crée la demande PayTech (crédit portefeuille mission). */
    public function initierPaiementSession(): void
    {
        Auth::requireRole('client');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->clientReservationsListUrl());
            return;
        }

        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $payerUrl      = $this->paytechSessionPayerUrl($reservationId);
        $sessionGetUrl = $this->paytechSessionFormUrl($reservationId);

        if (!$this->paytech->isConfigured()) {
            $_SESSION['flash_error'] = 'PayTech n\'est pas configuré sur ce serveur.';
            $this->redirect($payerUrl);
            return;
        }

        if ($reservationId <= 0) {
            $this->redirect($this->clientReservationsListUrl());
            return;
        }

        $reservation = (new \App\Models\ReservationModel())->find($reservationId);
        if (!$reservation || (int) $reservation['client_id'] !== (int) Auth::id()) {
            $this->redirect($this->clientReservationsListUrl());
            return;
        }
        if ($reservation['statut'] !== 'acceptee') {
            $this->redirect($this->clientReservationShowUrl($reservationId));
            return;
        }

        $userId   = (int) Auth::id();
        $phoneRes = $this->resolvePaytechPhoneFromPost($userId);
        if (!$phoneRes['ok']) {
            $_SESSION['flash_error'] = $phoneRes['error'];
            $this->redirect($sessionGetUrl);
            return;
        }
        /** @var array{pn:string,nn:string} $phonePair */
        $phonePair = $phoneRes['pair'];
        $montant   = (float) $reservation['montant_total'];

        try {
            $result = $this->paytech->requestSessionWalletTopUp($userId, $reservationId, $montant, $phonePair);
        } catch (\Throwable $e) {
            error_log('[PayTech/initierPaiementSession] EXCEPTION user=' . $userId . ' res=' . $reservationId . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $_SESSION['flash_error'] = 'Erreur interne lors de l\'initiation du paiement. Réessayez.';
            $this->redirect($sessionGetUrl);
            return;
        }

        if (empty($result['ok'])) {
            $_SESSION['flash_error'] = $result['error'] ?? 'Impossible de lancer le paiement PayTech.';
            $this->redirect($sessionGetUrl);
            return;
        }

        $redirectPay = trim((string) ($result['redirect_url'] ?? ''));
        if ($redirectPay === '' || stripos($redirectPay, 'http') !== 0) {
            $_SESSION['flash_error'] = 'Réponse PayTech incomplète. Réessayez.';
            $this->redirect($sessionGetUrl);
            return;
        }

        header('Location: ' . $redirectPay, true, 302);
        exit;
    }

    /** GET /paytech/depot[/{montant}] — formulaire ou confirmation */
    public function depot(string $montantParam = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base    = rtrim(BASE_URL ?? '', '/');
        $montant = max(0.0, (float) ($montantParam ?: ($_GET['montant'] ?? $this->router->getParams()[0] ?? 0)));

        if ($montant < 500) {
            $this->render('depot_form', [
                'pageTitle' => 'Recharger le portefeuille — GLOBALO',
                'user'      => ['id' => Auth::id(), 'role' => Auth::role()],
                'retour_portefeuille_url' => $this->portefeuilleHomeUrl(),
            ] + $this->payTechUxContext());
            return;
        }

        $this->render('depot', [
            'pageTitle'  => 'Dépôt portefeuille — GLOBALO',
            'user'       => ['id' => Auth::id(), 'role' => Auth::role()],
            'montant'    => $montant,
            'csrf_token' => \App\Core\Security::generateCsrfToken(),
            'retour_portefeuille_url' => $this->portefeuilleHomeUrl(),
        ] + $this->payTechUxContext());
    }

    /** POST /paytech/initier-depot */
    public function initierDepot(): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base = rtrim(BASE_URL ?? '', '/');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        $montant = max(0.0, (float) ($_POST['montant'] ?? 0));
        if ($montant < 500) {
            $_SESSION['flash_error'] = 'Montant minimum : 500 XOF.';
            $this->redirect($base . '/paytech/depot');
            return;
        }

        $userId   = (int) Auth::id();
        $phoneRes = $this->resolvePaytechPhoneFromPost($userId);
        if (!$phoneRes['ok']) {
            $_SESSION['flash_error'] = $phoneRes['error'];
            $this->redirect($base . '/paytech/depot/' . (int) $montant);
            return;
        }
        /** @var array{pn:string,nn:string} $phonePair */
        $phonePair = $phoneRes['pair'];

        try {
            $result = $this->paytech->requestDepot($userId, $montant, $phonePair);
        } catch (\Throwable $e) {
            error_log('[PayTech/initierDepot] EXCEPTION user=' . $userId . ' montant=' . $montant . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $_SESSION['flash_error'] = 'Erreur interne lors de l\'initiation du dépôt. Réessayez.';
            $this->redirect($base . '/paytech/depot/' . (int) $montant);
            return;
        }

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['error'] ?? 'Impossible d\'initier le dépôt. Réessayez.';
            $this->redirect($base . '/paytech/depot/' . (int) $montant);
            return;
        }

        $redirectUrl = trim((string) ($result['redirect_url'] ?? ''));
        if ($redirectUrl === '' || stripos($redirectUrl, 'http') !== 0) {
            $_SESSION['flash_error'] = 'Réponse PayTech incomplète. Réessayez.';
            $this->redirect($base . '/paytech/depot/' . (int) $montant);
            return;
        }

        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    // ---------------------------------------------------------------
    // Retour PayTech
    // ---------------------------------------------------------------

    /**
     * Succès paiement — URL fixe pour configuration PayTech (web).
     * Ex. https://domaine.fr/paytech/paiement-reussi
     * App équivalente : …/app/paytech-paiement-reussi
     */
    public function paiementReussi(): void
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $guest = !Auth::check();

        $data = [
            'pageTitle'                     => 'Paiement bien reçu — GLOBALO',
            'guest'                         => $guest,
            '_layout_paytech_standalone'    => $guest,
            'seo'                           => \App\Services\SeoService::forPage('default', [
                'title'       => 'Paiement reçu — GLOBALO PayTech',
                'description' => 'Confirmation de paiement Mobile Money GLOBALO.',
            ]),
        ];

        if (!$guest && Auth::id() !== null) {
            $data['user'] = ['id' => Auth::id(), 'role' => (string) Auth::role()];
            $data['_layout_paytech_standalone'] = false;
            $data['retour_url']                  = $this->portefeuilleHomeUrl();
            $data['secondaire_abonnement']     = $base . '/abonnement';
            $data['historique_url']           = $base . '/paytech/historique';
        } else {
            $data['lien_connexion']  = $base . '/connexion';
            $data['lien_accueil']    = $base . '/';
            $data['lien_abonnement'] = $base . '/abonnement';
        }

        $this->render('paiement_reussi_public', $data);
    }

    /**
     * Annulation / abandon paiement — URL fixe (web).
     * Ex. https://domaine.fr/paytech/paiement-annule
     * App : …/app/paytech-paiement-annule
     */
    public function paiementAnnule(): void
    {
        $base  = rtrim(BASE_URL ?? '', '/');
        $guest = !Auth::check();

        $data = [
            'pageTitle'                   => 'Paiement annulé — GLOBALO',
            'guest'                       => $guest,
            '_layout_paytech_standalone'  => $guest,
            'seo'                         => \App\Services\SeoService::forPage('default', [
                'title'       => 'Paiement annulé — GLOBALO',
                'description' => 'Annulation ou abandon du paiement PayTech sur GLOBALO.',
            ]),
        ];

        if (!$guest && Auth::id() !== null) {
            $data['user'] = ['id' => Auth::id(), 'role' => (string) Auth::role()];
            $data['_layout_paytech_standalone'] = false;
            $data['retry_portefeuille_url']      = $this->portefeuilleHomeUrl();
            $data['retry_abonnement_url']       = $base . '/paytech/checkout';
        } else {
            $data['lien_connexion']  = $base . '/connexion';
            $data['lien_accueil']    = $base . '/';
            $data['lien_abonnement'] = $base . '/abonnement';
        }

        $this->render('paiement_annule_public', $data);
    }

    /** GET /paytech/succes/{paymentId} */
    public function succes(string $paymentId = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();
        $base   = rtrim(BASE_URL ?? '', '/');

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($base . '/abonnement');
            return;
        }

        $isDepotLike = in_array($tx['type'] ?? '', ['depot_portefeuille', 'paiement_session_paytech'], true);
        $sessionPayerUrl = $this->paytechReservationPayerUrl($tx);
        $retourUrl      = $sessionPayerUrl
            ?? ($isDepotLike ? $this->portefeuilleHomeUrl() : ($base . '/abonnement'));
        $succesButton   = $sessionPayerUrl !== null
            ? 'Finaliser le paiement de la réservation'
            : ($isDepotLike ? 'Voir mon portefeuille' : 'Accéder à mon espace');

        $this->render('succes', [
            'pageTitle'           => 'Paiement confirmé — GLOBALO',
            'user'                => ['id' => $userId, 'role' => Auth::role()],
            'transaction'         => $tx,
            'is_depot'            => $isDepotLike,
            'retour_url'          => $retourUrl,
            'succes_button_label' => $succesButton,
        ]);
    }

    /** GET /paytech/echec/{paymentId} */
    public function echec(string $paymentId = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();
        $base   = rtrim(BASE_URL ?? '', '/');

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($base . '/abonnement');
            return;
        }

        $isDepotLike = in_array($tx['type'] ?? '', ['depot_portefeuille', 'paiement_session_paytech'], true);
        $sessionPayerUrl = $this->paytechReservationPayerUrl($tx);
        $sessionRetryUrl = $this->paytechReservationSessionPayUrl($tx);
        $retryUrl       = $sessionRetryUrl
            ?? ($sessionPayerUrl
                ? $sessionPayerUrl
                : ($isDepotLike ? $this->portefeuilleHomeUrl() : ($base . '/abonnement')));

        // Marquer comme failed si encore pending (abandon) — après lecture des notes pour les liens.
        if ($tx['status'] === 'pending') {
            \App\Core\Database::getInstance()
                ->prepare("UPDATE transactions SET status='failed', notes='Abandon paiement PayTech', updated_at=NOW() WHERE payment_id=? AND status='pending' LIMIT 1")
                ->execute([$paymentId]);
        }

        $this->render('echec', [
            'pageTitle'   => 'Paiement annulé — GLOBALO',
            'user'        => ['id' => $userId, 'role' => Auth::role()],
            'transaction' => $tx,
            'is_depot'    => $isDepotLike,
            'retry_url'   => $retryUrl,
        ]);
    }

    /** GET /paytech/historique */
    public function historique(...$_): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId       = (int) Auth::id();
        $transactions = $this->transactionModel->getByUser($userId, 50);

        $this->render('historique', [
            'pageTitle'    => 'Historique paiements — GLOBALO',
            'user'         => ['id' => $userId, 'role' => Auth::role()],
            'transactions' => array_filter($transactions, static fn($t) => ($t['provider'] ?? '') === 'paytech'),
        ]);
    }

    // ---------------------------------------------------------------
    // Webhook IPN PayTech
    // ---------------------------------------------------------------

    /** POST /paytech/callback — notifié par PayTech après paiement (pas de session) */
    public function callback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'method']);
            return;
        }

        // PayTech envoie un POST form-encoded
        $payload = $_POST;
        if (empty($payload)) {
            $raw     = file_get_contents('php://input') ?: '';
            $payload = json_decode($raw, true) ?? [];
        }

        $result = $this->paytech->completeFromIpn($payload);

        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($result['ok'] ? 200 : 400);
        echo json_encode(['ok' => $result['ok']]);
    }

    // ---------------------------------------------------------------
    // Helpers privés
    // ---------------------------------------------------------------

    /**
     * Pays détecté + message d’aide + indicateur téléphone + données formulaire PayTech.
     *
     * @return array<string, mixed>
     */
    private function payTechUxContext(): array
    {
        $user = (new UtilisateurModel())->find((int) Auth::id()) ?: null;
        $a    = new PayTechCheckoutAssistant();
        $iso  = $a->resolveCountryIso2($user);
        $pair = $a->normalizePhoneForCheckout(
            $user !== null && isset($user['telephone']) ? (string) $user['telephone'] : null,
            $iso
        );

        $split = ['dial' => $a->defaultDialForIso($iso), 'local' => ''];
        if ($pair !== null) {
            $split = $a->splitPhonePairForForm($pair);
        }

        return [
            'paytech_country_iso'         => $iso,
            'paytech_context_hint'        => $a->contextualHint($iso),
            'paytech_phone_prefill_ready' => $pair !== null,
            'paytech_phone_dial_options'  => $a->dialOptionsForSelect(),
            'paytech_phone_dial_default'  => $split['dial'],
            'paytech_phone_local_value'   => $split['local'],
        ];
    }

    /**
     * Téléphone Mobile Money (POST) : validation, normalisation E.164, enregistrement profil optionnel.
     *
     * @return array{ok:true,pair:array{pn:string,nn:string}}|array{ok:false,error:string}
     */
    private function resolvePaytechPhoneFromPost(int $userId): array
    {
        $userRow = (new UtilisateurModel())->find($userId) ?: null;
        $a       = new PayTechCheckoutAssistant();
        $iso     = $a->resolveCountryIso2($userRow);

        $dial  = preg_replace('/\D+/', '', (string) ($_POST['paytech_dial'] ?? '')) ?? '';
        $local = preg_replace('/\D+/', '', (string) ($_POST['paytech_local'] ?? '')) ?? '';
        $local = ltrim($local, '0');

        if ($dial === '' || strlen($local) < 6 || strlen($local) > 12) {
            return [
                'ok'    => false,
                'error' => 'Indiquez un numéro Mobile Money valide (6 à 12 chiffres après l’indicatif, sans le 0 initial).',
            ];
        }

        $raw       = '+' . $dial . $local;
        $isoDial   = $a->isoForDialDigits($dial);
        $pair      = $a->normalizePhoneForCheckout($raw, $isoDial ?? $iso);
        if ($pair === null) {
            return [
                'ok'    => false,
                'error' => 'Numéro invalide pour l’indicatif sélectionné. Vérifiez les chiffres.',
            ];
        }

        $save = isset($_POST['save_paytech_phone']) && (string) $_POST['save_paytech_phone'] === '1';
        if ($save) {
            (new UtilisateurModel())->update($userId, [
                'telephone'  => $pair['pn'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return ['ok' => true, 'pair' => $pair];
    }

    private function defaultAbonnementTypeFromRole(): string
    {
        $role = Auth::role();
        switch ($role) {
            case 'expert':
                return 'expert';
            case 'professeur':
                return 'professeur';
            case 'etudiant':
                return 'etudiant';
            default:
                return 'client';
        }
    }

    private function getPrixAbonnement(string $type): float
    {
        switch ($type) {
            case 'expert':
                return $this->subscriptionService->getPrixExpertXof();
            case 'etudiant':
                return $this->subscriptionService->getPrixEtudiantXof();
            case 'professeur':
                return $this->subscriptionService->getPrixProfesseurXof();
            default:
                return $this->subscriptionService->getPrixClientXof();
        }
    }

    private function portefeuilleHomeUrl(): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $role = Auth::role();
        if ($role === 'expert') return $base . '/expert/revenus';
        if ($role === 'etudiant') return $base . '/etudiant/portefeuille';
        if ($role === 'professeur') return $base . '/professeur/portefeuille';
        if ($this->isMobileView) return $base . '/app/portefeuille';
        return $base . '/client/portefeuille';
    }

    /** @return non-empty-string|null */
    private function paytechReservationPayerUrl(array $tx): ?string
    {
        if (($tx['type'] ?? '') !== 'paiement_session_paytech') {
            return null;
        }
        $notes = (string) ($tx['notes'] ?? '');
        if (preg_match('/session:(\d+)/', $notes, $m)) {
            return rtrim(BASE_URL ?? '', '/') . '/client/payer/' . (int) $m[1];
        }

        return null;
    }

    /** Relance PayTech pour créditer le portefeuille (mission). */
    private function paytechReservationSessionPayUrl(array $tx): ?string
    {
        if (($tx['type'] ?? '') !== 'paiement_session_paytech') {
            return null;
        }
        $notes = (string) ($tx['notes'] ?? '');
        if (!preg_match('/session:(\d+)/', $notes, $m)) {
            return null;
        }

        return rtrim(BASE_URL ?? '', '/') . '/paytech/paiement-session/' . (int) $m[1];
    }

    /** URL GET du formulaire mission PayTech (web ou /app). */
    private function paytechSessionFormUrl(int $reservationId): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $id   = max(1, $reservationId);
        if ($this->router->isApp()) {
            return $base . '/app/paytech-session/' . $id;
        }
        return $base . '/paytech/paiement-session/' . $id;
    }

    private function clientReservationsListUrl(): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        return $this->router->isApp() ? $base . '/app/reservations' : $base . '/client/reservations';
    }

    private function clientReservationShowUrl(int $reservationId): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $id   = max(1, $reservationId);
        return $this->router->isApp() ? $base . '/app/reservations/' . $id : $base . '/client/reservations/' . $id;
    }

    /** Lien « retour » vers l’écran payer mission (web ou /app). */
    private function paytechSessionPayerUrl(int $reservationId): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $id   = max(1, $reservationId);
        if ($this->router->isApp()) {
            return $base . '/app/payer/' . $id;
        }
        return $base . '/client/payer/' . $id;
    }
}
