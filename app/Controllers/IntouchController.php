<?php
/**
 * GLOBALO — Paiements InTouch / TouchPay
 *
 * GET  /intouch/touchpay/{type}           — TouchPay widget abonnement (SendPaymentInfos)
 * GET  /intouch/touchpay-depot            — Dépôt portefeuille (redirige vers PayTech si configuré)
 * GET  /intouch/touchpay-session/{resId}  — TouchPay widget paiement direct session
 * GET  /intouch/paiement/{type}           — Formulaire API Pay-In abonnement
 * POST /intouch/initier                   — Initier paiement abonnement (API digest)
 * POST /intouch/initier-depot             — Initier dépôt portefeuille (API digest)
 * GET  /intouch/verification/{paymentId}  — Suivi transaction
 * POST /intouch/soumettre                 — Soumettre code transaction
 * GET  /intouch/succes/{paymentId}        — Page de succès
 * GET  /intouch/echec/{paymentId}         — Page d'échec / abandon
 * GET  /intouch/historique                — Historique paiements
 * POST /intouch/callback                  — Webhook InTouch (sans auth)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Models\TransactionModel;
use App\Services\IntouchPaymentService;
use App\Services\PayTechPaymentService;
use App\Services\SubscriptionService;

class IntouchController extends Controller
{
    private const INTOUCH_OPERATORS = ['ORANGE', 'MOOV', 'WAVE'];

    private IntouchPaymentService $intouchService;
    private TransactionModel $transactionModel;
    private SubscriptionService $subscriptionService;

    private function operatorIntouch(string $raw): string
    {
        $op = strtoupper(trim($raw));

        return in_array($op, self::INTOUCH_OPERATORS, true) ? $op : 'ORANGE';
    }

    /** Si PayTech est configuré, le dépôt portefeuille se fait uniquement via PayTech. */
    private function redirectToPaytechDepotIfConfigured(float $montantHint = 0.0): bool
    {
        if (!(new PayTechPaymentService())->isConfigured()) {
            return false;
        }
        $base = rtrim(BASE_URL ?? '', '/');
        $url  = $base . '/paytech/depot';
        if ($montantHint >= 500.0 && $montantHint <= 500000.0) {
            $url .= '/' . (int) round($montantHint);
        }
        $this->redirect($url);

        return true;
    }

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->intouchService       = new IntouchPaymentService();
        $this->transactionModel    = new TransactionModel();
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * Page TouchPay « lien de redirection » : script gutouch + SendPaymentInfos.
     * @see https://developers.intouchgroup.net/documentation/PAYMENT/22
     */
    public function touchpay(string $abonnementType = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();
        $role   = Auth::role();
        $base   = rtrim(BASE_URL ?? '', '/');

        if ($abonnementType === '') {
            $abonnementType = $this->defaultAbonnementTypeFromRole();
        }

        $pay = new PayTechPaymentService();
        if ($pay->isConfigured()) {
            $this->redirect($base . $pay->getAbonnementPaymentRelativePath($abonnementType));
            return;
        }

        if (!$this->intouchService->isTouchpayWidgetConfigured()) {
            $_SESSION['flash_error'] = 'TouchPay (page de paiement) non configuré : définissez INTOUCH_ID et TOUCHPAY_SECURE_CODE.';
            $this->redirect($base . '/abonnement');
            return;
        }

        $prix       = $this->getPrixAbonnement($abonnementType);
        $commission = $this->intouchService->calculateFee($prix);
        $prep       = $this->intouchService->prepareTouchpayWidgetAbonnement($userId, $abonnementType, $prix, $commission);
        if (empty($prep['ok'])) {
            $_SESSION['flash_error'] = $prep['error'] ?? 'Impossible de préparer le paiement.';
            $this->redirect($base . '/abonnement');
            return;
        }

        $tx = $this->transactionModel->findByPaymentId((string) ($prep['payment_id'] ?? ''));
        if ($tx === null) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($base . '/abonnement');
            return;
        }

        $args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
            'email'  => $tx['email']  ?? '',
            'prenom' => $tx['prenom'] ?? '',
            'nom'    => $tx['nom']    ?? '',
        ]);

        $this->render('touchpay', [
            'pageTitle'       => 'Paiement TouchPay — GLOBALO',
            'user'            => ['id' => $userId, 'role' => $role],
            'abonnement_type' => $abonnementType,
            'montant'         => $prix,
            'commission'      => $commission,
            'total'           => round($prix + $commission, 2),
            'payment_id'      => $prep['payment_id'],
            'touchpay_script_url' => $this->intouchService->getTouchpayScriptUrl(),
            'touchpay_send_args'  => $args,
            'paiement_classique_url' => $base . '/intouch/paiement/' . rawurlencode($abonnementType),
        ]);
    }

    public function paiement(string $abonnementType = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();
        $role   = Auth::role();

        if ($abonnementType === '') {
            $abonnementType = $this->defaultAbonnementTypeFromRole();
        }

        $base = rtrim(BASE_URL ?? '', '/');

        $pay = new PayTechPaymentService();
        if ($pay->isConfigured()) {
            $this->redirect($base . $pay->getAbonnementPaymentRelativePath($abonnementType));
            return;
        }

        if (!$this->intouchService->isConfigured()) {
            if ($this->intouchService->canFallbackAbonnementToTouchpayWidget()) {
                header('Location: ' . $base . '/intouch/touchpay/' . rawurlencode($abonnementType), true, 302);
                exit;
            }
            $_SESSION['flash_error'] = $this->intouchService->getAbonnementPayInUnavailableMessage();
            $this->redirect($base . '/abonnement');
            return;
        }

        $prix       = $this->getPrixAbonnement($abonnementType);
        $commission = $this->intouchService->calculateFee($prix);

        $this->render('paiement', [
            'pageTitle'        => 'Payer via InTouch — GLOBALO',
            'user'             => ['id' => $userId, 'role' => $role],
            'abonnement_type'  => $abonnementType,
            'montant'          => $prix,
            'commission'       => $commission,
            'total'            => round($prix + $commission, 2),
        ]);
    }

    /**
     * GET /intouch/touchpay-depot[/{montant}]
     * Route principale dépôt : dispatche vers widget (SendPaymentInfos) ou API form
     * selon TOUCHPAY_ABONNEMENT_MODE (widget | api | auto).
     */
    public function touchpayDepot(): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base      = rtrim(BASE_URL ?? '', '/');
        $retourUrl = $this->portefeuilleHomeUrl();

        $userId  = (int) Auth::id();
        $role    = Auth::role();
        $params  = $this->router->getParams();
        $montant = max(0.0, (float) ($params[0] ?? ($_GET['montant'] ?? 0)));

        if ($this->redirectToPaytechDepotIfConfigured($montant)) {
            return;
        }

        // Si montant non fourni → formulaire de saisie (commun aux deux modes)
        if ($montant < 500) {
            $this->render('touchpay_depot_form', [
                'pageTitle'     => 'Recharger — GLOBALO',
                'user'          => ['id' => $userId, 'role' => $role],
                'mode_widget'   => $this->intouchService->shouldUseTouchpayWidgetForAbonnement(),
                'retour_portefeuille_url' => $retourUrl,
            ]);
            return;
        }

        // Dispatcher selon le mode
        if ($this->intouchService->shouldUseTouchpayWidgetForAbonnement()) {
            // ── Mode WIDGET (Checkout Page) ──────────────────────────────
            if (!$this->intouchService->isTouchpayWidgetConfigured()) {
                $_SESSION['flash_error'] = 'TouchPay (widget) non configuré : définissez INTOUCH_ID et TOUCHPAY_SECURE_CODE.';
                $this->redirect($retourUrl);
                return;
            }

            $prep = $this->intouchService->prepareTouchpayWidgetDepot($userId, $montant);
            if (empty($prep['ok'])) {
                $_SESSION['flash_error'] = $prep['error'] ?? 'Impossible de préparer le paiement.';
                $this->redirect($retourUrl);
                return;
            }

            $tx = $this->transactionModel->findByPaymentId((string) ($prep['payment_id'] ?? ''));
            if ($tx === null) {
                $_SESSION['flash_error'] = 'Transaction introuvable.';
                $this->redirect($retourUrl);
                return;
            }

            $args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
                'email'  => $tx['email']  ?? '',
                'prenom' => $tx['prenom'] ?? '',
                'nom'    => $tx['nom']    ?? '',
            ]);

            $this->render('touchpay_depot', [
                'pageTitle'           => 'Dépôt TouchPay — GLOBALO',
                'user'                => ['id' => $userId, 'role' => $role],
                'montant'             => $montant,
                'total'               => (float) ($prep['total_amount'] ?? $montant),
                'payment_id'          => $prep['payment_id'],
                'touchpay_script_url' => $this->intouchService->getTouchpayScriptUrl(),
                'touchpay_send_args'  => $args,
                'retour_url'          => $retourUrl,
            ]);
        } else {
            // ── Mode API (formulaire téléphone) ──────────────────────────
            $this->paiementDepotForm($userId, $role, $montant, $retourUrl);
        }
    }

    /**
     * GET /intouch/paiement-depot[/{montant}]
     * Formulaire API Pay-In pour dépôt portefeuille (saisie numéro + opérateur).
     */
    public function paiementDepot(): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $params  = $this->router->getParams();
        $montant = max(0.0, (float) ($params[0] ?? ($_GET['montant'] ?? 0)));

        if ($this->redirectToPaytechDepotIfConfigured($montant)) {
            return;
        }

        if ($montant < 500) {
            if ($this->redirectToPaytechDepotIfConfigured(0.0)) {
                return;
            }
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/intouch/touchpay-depot');
            return;
        }

        $this->paiementDepotForm(
            (int) Auth::id(),
            Auth::role(),
            $montant,
            $this->portefeuilleHomeUrl()
        );
    }

    /** Affiche le formulaire API dépôt (partagé par touchpayDepot et paiementDepot). */
    private function paiementDepotForm(int $userId, string $role, float $montant, string $retourUrl): void
    {
        if (!$this->intouchService->isConfigured()) {
            $_SESSION['flash_error'] = 'Dépôt indisponible : InTouch non configuré.';
            $this->redirect($retourUrl);
            return;
        }

        $devise = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

        $this->render('paiement_depot', [
            'pageTitle'  => 'Dépôt portefeuille — GLOBALO',
            'user'       => ['id' => $userId, 'role' => $role],
            'montant'    => $montant,
            'retour_url' => $retourUrl,
            'devise'     => $devise,
        ]);
    }

    /**
     * GET /intouch/touchpay-session/{reservationId}
     * Page TouchPay (widget SendPaymentInfos) pour payer directement une session de réservation.
     * Crédite le portefeuille du montant exact → client finalise le paiement escrow via /client/payer/{id}.
     */
    public function touchpaySession(): void
    {
        Auth::requireRole('client');
        $base         = rtrim(BASE_URL ?? '', '/');
        $params       = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? ($_GET['reservation'] ?? 0));

        if ($reservationId <= 0) {
            $this->redirect($base . '/client/reservations');
            return;
        }

        // Charger et vérifier la réservation
        $reservation = (new \App\Models\ReservationModel())->find($reservationId);
        if (!$reservation || (int) $reservation['client_id'] !== (int) Auth::id()) {
            $this->redirect($base . '/client/reservations');
            return;
        }
        if ($reservation['statut'] !== 'acceptee') {
            $this->redirect($base . '/client/reservations/' . $reservationId);
            return;
        }

        $payer_url = $base . '/client/payer/' . $reservationId;

        $pay = new PayTechPaymentService();
        if ($pay->isConfigured()) {
            $this->redirect($base . '/paytech/paiement-session/' . $reservationId);
            return;
        }

        if (!$this->intouchService->isTouchpayWidgetConfigured()) {
            $_SESSION['flash_error'] = 'TouchPay non configuré. Rechargez votre portefeuille et réessayez.';
            $this->redirect($payer_url);
            return;
        }

        $userId  = (int) Auth::id();
        $role    = Auth::role();
        $montant = (float) $reservation['montant_total'];

        $prep = $this->intouchService->prepareTouchpayWidgetSession($userId, $reservationId, $montant);
        if (empty($prep['ok'])) {
            $_SESSION['flash_error'] = $prep['error'] ?? 'Impossible de préparer le paiement.';
            $this->redirect($payer_url);
            return;
        }

        $tx = $this->transactionModel->findByPaymentId((string) ($prep['payment_id'] ?? ''));
        if ($tx === null) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($payer_url);
            return;
        }

        $args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
            'email'  => $tx['email']  ?? '',
            'prenom' => $tx['prenom'] ?? '',
            'nom'    => $tx['nom']    ?? '',
        ]);

        $this->render('touchpay_session', [
            'pageTitle'           => 'Paiement session — GLOBALO',
            'user'                => ['id' => $userId, 'role' => $role],
            'reservation'         => $reservation,
            'montant'             => $montant,
            'total'               => (float) ($prep['total_amount'] ?? $montant),
            'payment_id'          => $prep['payment_id'],
            'reservation_id'      => $reservationId,
            'touchpay_script_url' => $this->intouchService->getTouchpayScriptUrl(),
            'touchpay_send_args'  => $args,
            'payer_url'           => $payer_url,
        ]);
    }

    public function initierDepot(...$_): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base      = rtrim(BASE_URL, '/');
        $retourUrl = $this->portefeuilleHomeUrl();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($retourUrl);
            return;
        }

        if (!Security::validateCsrf()) {
            $_SESSION['flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect($retourUrl);
            return;
        }

        if ($this->redirectToPaytechDepotIfConfigured((float) ($_POST['montant'] ?? 0))) {
            return;
        }

        if (!$this->intouchService->isConfigured()) {
            $_SESSION['flash_error'] = 'Dépôt indisponible : InTouch non configuré.';
            $this->redirect($retourUrl);
            return;
        }

        $userId   = (int) Auth::id();
        $phone    = trim((string) ($_POST['phone'] ?? ''));
        $montant  = (float) ($_POST['montant'] ?? 0);
        $operator = $this->operatorIntouch((string) ($_POST['operator'] ?? 'ORANGE'));

        if ($montant < 500 || $montant > 500000) {
            $_SESSION['flash_error'] = 'Montant invalide. Entre 500 et 500 000 XOF.';
            $this->redirect($retourUrl);
            return;
        }

        if (strlen((string) (preg_replace('/\s+/', '', $phone) ?? '')) < 8) {
            $_SESSION['flash_error'] = 'Numéro Mobile Money invalide.';
            $this->redirect($retourUrl);
            return;
        }

        try {
            $result = $this->intouchService->createPayment(
                $userId,
                $montant,
                0,
                $phone,
                'depot_portefeuille',
                'depot',
                $operator
            );
        } catch (\Throwable $ex) {
            error_log('[IntouchController::initierDepot] ' . $ex->getMessage() . ' | ' . $ex->getFile() . ':' . $ex->getLine());
            $_SESSION['flash_error'] = 'Erreur technique : ' . $ex->getMessage();
            $this->redirect($retourUrl);
            return;
        }

        if (empty($result['ok'])) {
            $_SESSION['flash_error'] = $result['error'] ?? 'Impossible d\'initier le dépôt.';
            $this->redirect($retourUrl);
            return;
        }

        $this->redirect($base . '/intouch/verification/' . rawurlencode((string) $result['payment_id']));
    }

    public function initier(...$_): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base = rtrim(BASE_URL ?? '', '/');
        $urlPaiementType = $base . '/intouch/paiement/client';

        try {
            $typeFromPost = trim((string) ($_POST['abonnement_type'] ?? ''));
            $typeFallback = in_array($typeFromPost, ['client', 'expert', 'etudiant', 'professeur'], true)
                ? $typeFromPost
                : $this->defaultAbonnementTypeFromRole();
            $urlPaiement = $base . '/intouch/paiement/' . rawurlencode($typeFallback);
            $urlPaiementType = $urlPaiement;

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect($base . '/intouch/paiement/' . rawurlencode($this->defaultAbonnementTypeFromRole()));
                return;
            }

            if (!Security::validateCsrf()) {
                $_SESSION['flash_error'] = 'Session expirée. Veuillez réessayer.';
                $this->redirect($urlPaiement);
                return;
            }

            if (!$this->intouchService->isConfigured()) {
                if ($this->intouchService->canFallbackAbonnementToTouchpayWidget()) {
                    header('Location: ' . $base . '/intouch/touchpay/' . rawurlencode($typeFallback), true, 302);
                    exit;
                }
                $_SESSION['flash_error'] = $this->intouchService->getAbonnementPayInUnavailableMessage();
                $this->redirect($this->abonnementManageUrl());
                return;
            }

            $userId   = (int) Auth::id();
            $phone    = trim((string) ($_POST['phone'] ?? ''));
            $operator = $this->operatorIntouch((string) ($_POST['operator'] ?? 'ORANGE'));
            $type     = $typeFromPost !== '' ? $typeFromPost : $this->defaultAbonnementTypeFromRole();

            if (!in_array($type, ['client', 'expert', 'etudiant', 'professeur'], true)) {
                $_SESSION['flash_error'] = 'Type d\'abonnement invalide.';
                $this->redirect($this->abonnementManageUrl());
                return;
            }

            $urlPaiementType = $base . '/intouch/paiement/' . rawurlencode($type);

            $phoneCompact = (string) (preg_replace('/\s+/', '', $phone) ?? '');
            $phoneLen     = strlen($phoneCompact);
            if ($phoneLen < 8) {
                $_SESSION['flash_error'] = 'Numéro Mobile Money invalide.';
                $this->redirect($urlPaiementType);
                return;
            }

            $prix = $this->getPrixAbonnement($type);

            $result = $this->intouchService->createPayment(
                $userId,
                $prix,
                0,
                $phone,
                'abonnement',
                $type,
                $operator
            );

            if (empty($result['ok'])) {
                $_SESSION['flash_error'] = $result['error'] ?? 'Impossible d\'initier le paiement.';
                $this->redirect($urlPaiementType);
                return;
            }

            $this->redirect($base . '/intouch/verification/' . rawurlencode((string) $result['payment_id']));
        } catch (\Throwable $e) {
            error_log('[IntouchController::initier] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            $_SESSION['flash_error'] = 'Erreur technique : ' . $e->getMessage();
            $this->redirect($urlPaiementType);
        }
    }

    public function verification(string $paymentId = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId   = (int) Auth::id();
        $base     = rtrim(BASE_URL, '/');
        $fallback = $this->portefeuilleHomeUrl();

        if ($paymentId === '') {
            $_SESSION['flash_error'] = 'Référence de paiement manquante.';
            $this->redirect($fallback);
            return;
        }

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId || ($tx['provider'] ?? '') !== 'intouch') {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($fallback);
            return;
        }

        $aboFallback = $base . '/abonnement';

        if (($tx['type'] ?? '') === 'depot_portefeuille' && ($tx['status'] ?? '') === 'success') {
            $_SESSION['flash_success'] = 'Votre dépôt est confirmé.';
            $this->redirect($fallback);
            return;
        }

        $instructions = $this->intouchService->getInstructions($paymentId);
        if (empty($instructions['ok'])) {
            $_SESSION['flash_error'] = $instructions['error'] ?? 'Impossible d\'afficher la transaction.';
            $redirectErr = (($tx['type'] ?? '') === 'depot_portefeuille') ? $fallback : $aboFallback;
            $this->redirect($redirectErr);
            return;
        }

        $tc = (string) ($tx['transaction_code'] ?? '');
        $this->render('verification', [
            'pageTitle'            => 'Paiement InTouch — GLOBALO',
            'user'                 => ['id' => $userId, 'role' => Auth::role()],
            'transaction'          => $tx,
            'instructions'         => $instructions['instructions'] ?? [],
            'payment_id'           => $paymentId,
            'intouch_push_pending' => str_starts_with($tc, 'ITP-'),
            'admin_pending'        => $tc !== '' && !str_starts_with($tc, 'ITP-'),
        ]);
    }

    public function soumettre(...$_): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $base = rtrim(BASE_URL ?? '', '/');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        $postPaymentId = trim((string) ($_POST['payment_id'] ?? ''));

        if (!Security::validateCsrf()) {
            $_SESSION['flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect($postPaymentId !== '' ? $base . '/intouch/verification/' . rawurlencode($postPaymentId) : $this->portefeuilleHomeUrl());
            return;
        }

        $userId    = (int) Auth::id();
        $paymentId = $postPaymentId;
        $code      = strtoupper(trim((string) ($_POST['transaction_code'] ?? '')));

        if ($paymentId === '') {
            $_SESSION['flash_error'] = 'Référence manquante.';
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }
        if ($code === '') {
            $this->redirect($base . '/intouch/verification/' . rawurlencode($paymentId));
            return;
        }

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        $result = $this->intouchService->submitCode($paymentId, $code);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['error'];
            $this->redirect($base . '/intouch/verification/' . rawurlencode($paymentId));
            return;
        }

        $_SESSION['flash_success'] = $result['message'] ?? 'Référence enregistrée.';
        $this->redirect($base . '/intouch/succes/' . rawurlencode($paymentId));
    }

    public function succes(string $paymentId = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId) {
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        $isDepot   = ($tx['type'] ?? '') === 'depot_portefeuille';
        $pageTitle = $isDepot ? 'Dépôt — GLOBALO' : 'Paiement — GLOBALO';

        $this->render('succes', [
            'pageTitle'   => $pageTitle,
            'user'        => ['id' => $userId, 'role' => Auth::role()],
            'transaction' => $tx,
            'is_depot'    => $isDepot,
        ]);
    }

    public function echec(string $paymentId = ''): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId = (int) Auth::id();
        $base   = rtrim(BASE_URL ?? '', '/');

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx['user_id'] !== $userId) {
            $_SESSION['flash_error'] = 'Transaction introuvable.';
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        // Marquer comme failed si encore pending
        if ($tx['status'] === 'pending') {
            $db = \App\Core\Database::getInstance();
            $db->prepare("UPDATE transactions SET status='failed', notes='Abandon paiement CheckOut', updated_at=NOW() WHERE payment_id=? AND status='pending' LIMIT 1")
               ->execute([$paymentId]);
        }

        $isDepot = ($tx['type'] ?? '') === 'depot_portefeuille'
                || ($tx['type'] ?? '') === 'paiement_session_touchpay';

        $this->render('echec', [
            'pageTitle'   => 'Paiement annulé — GLOBALO',
            'user'        => ['id' => $userId, 'role' => Auth::role()],
            'transaction' => $tx,
            'is_depot'    => $isDepot,
            'retry_url'   => $isDepot
                ? $this->portefeuilleHomeUrl()
                : ($base . '/abonnement'),
        ]);
    }

    public function historique(...$_): void
    {
        Auth::requireRole('client', 'expert', 'etudiant', 'professeur');
        $userId       = (int) Auth::id();
        $transactions = $this->transactionModel->getByUser($userId, 50);

        $this->render('historique', [
            'pageTitle'    => 'Historique paiements — GLOBALO',
            'user'         => ['id' => $userId, 'role' => Auth::role()],
            'transactions' => $transactions,
        ]);
    }

    /** POST /intouch/callback — notification serveur InTouch */
    public function callback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'method']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $secret = getenv('INTOUCH_CALLBACK_SECRET') ?: (defined('INTOUCH_CALLBACK_SECRET') ? INTOUCH_CALLBACK_SECRET : '');
        if ($secret !== '') {
            $hdr = $_SERVER['HTTP_X_INTOUCH_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '';
            if ($hdr === '' || !hash_equals($secret, (string) $hdr)) {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false]);
                return;
            }
        }

        $result = $this->intouchService->completeFromWebhook($payload);
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($result['ok'] ? 200 : 400);
        echo json_encode(['ok' => $result['ok']]);
    }

    private function defaultAbonnementTypeFromRole(): string
    {
        $role = Auth::role();
        if ($role === 'expert') {
            return 'expert';
        }
        if ($role === 'professeur') {
            return 'professeur';
        }
        if ($role === 'etudiant') {
            return 'etudiant';
        }

        return 'client';
    }

    private function abonnementManageUrl(): string
    {
        return $this->router->isApp() ? '/app/abonnement' : '/abonnement';
    }

    private function getPrixAbonnement(string $type): float
    {
        switch ($type) {
            case 'expert':     return $this->subscriptionService->getPrixExpertXof();
            case 'etudiant':   return $this->subscriptionService->getPrixEtudiantXof();
            case 'professeur': return $this->subscriptionService->getPrixProfesseurXof();
            default:           return $this->subscriptionService->getPrixClientXof();
        }
    }

    private function portefeuilleHomeUrl(): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $role = Auth::role();
        if ($role === 'expert') {
            return $base . '/expert/revenus';
        }
        if ($role === 'etudiant') {
            return $base . '/etudiant/portefeuille';
        }
        if ($role === 'professeur') {
            return $base . '/professeur/portefeuille';
        }
        if ($this->isMobileView) {
            return $base . '/app/portefeuille';
        }

        return $base . '/client/portefeuille';
    }
}
