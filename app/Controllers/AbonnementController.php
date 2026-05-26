<?php
/**
 * GLOBALO - Page abonnement (client ou expert connecté)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Services\IntouchPaymentService;
use App\Services\PayTechPaymentService;
use App\Services\SubscriptionService;

class AbonnementController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('client', 'expert', 'professeur', 'etudiant');
        $this->subscriptionService = new SubscriptionService();
    }

    public function index(): void
    {
        $userId = (int) Auth::id();
        $role   = Auth::role();

        if ($role === 'expert') {
            $type = 'expert';
        } elseif ($role === 'professeur') {
            $type = 'professeur';
        } elseif ($role === 'etudiant') {
            $type = 'etudiant';
        } else {
            $type = 'client';
        }

        $abonnement = $this->subscriptionService->getAbonnementActif($userId, $type);

        if ($type === 'etudiant') {
            $prixXof = $this->subscriptionService->getPrixEtudiantXof();
        } elseif ($type === 'professeur') {
            $prixXof = $this->subscriptionService->getPrixProfesseurXof();
        } elseif ($type === 'expert') {
            $prixXof = $this->subscriptionService->getPrixExpertXof();
        } else {
            $prixXof = $this->subscriptionService->getPrixClientXof();
        }

        $planGratuitActif = $this->subscriptionService->isPlanGratuitActif();
        $modeAbonnement   = $this->subscriptionService->isModeAbonnement();
        $paytech          = new PayTechPaymentService();
        $intouch          = new IntouchPaymentService();
        $paytechConfigured = $paytech->isConfigured();
        $intouch_payment_path = $paytechConfigured
            ? $paytech->getAbonnementPaymentRelativePath($type)
            : $intouch->getAbonnementPaymentRelativePath($type);

        $this->render('index', [
            'pageTitle'         => 'Mon abonnement - GLOBALO',
            'user'              => ['id' => $userId, 'role' => $role],
            'abonnement'        => $abonnement,
            'type'              => $type,
            'prix_xof'          => $prixXof,
            'plan_gratuit_actif'=> $planGratuitActif,
            'mode_abonnement'   => $modeAbonnement,
            'duree_jours'       => $this->subscriptionService->getDureeJours(),
            'provider'          => $this->subscriptionService->getProvider(),
            'paytech_configured'=> $paytechConfigured,
            'intouch_payment_path' => $intouch_payment_path,
            'navActive'         => 'abonnement',
        ]);
    }

    /** POST : souscrire (gratuit ou redirection Wave). */
    public function souscrire(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/abonnement');
            return;
        }
        if (!\App\Core\Security::validateCsrf()) {
            $_SESSION['flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/abonnement');
            return;
        }
        $userId = (int) Auth::id();
        $role   = Auth::role();

        if ($role === 'expert') {
            $type = 'expert';
        } elseif ($role === 'professeur') {
            $type = 'professeur';
        } elseif ($role === 'etudiant') {
            $type = 'etudiant';
        } else {
            $type = 'client';
        }
        $result = $this->subscriptionService->souscrire($userId, $type, 'auto');
        if (!empty($result['redirect'])) {
            // Stocker la référence en session pour la vérifier au retour (protection CSRF du callback)
            if (!empty($result['client_reference'])) {
                $_SESSION['pending_abo_ref']     = $result['client_reference'];
                $_SESSION['pending_abo_user_id'] = $userId;
            }
            header('Location: ' . $result['redirect'], true, 302);
            exit;
        }
        if (!empty($result['error'])) {
            $_SESSION['flash_error'] = $result['error'];
        } else {
            $_SESSION['flash_success'] = 'Votre abonnement a bien été activé.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/abonnement');
    }

    /** Retour Wave/DigitalPaye après paiement. */
    public function callback(): void
    {
        $provider = isset($_GET['provider']) ? (string) $_GET['provider'] : '';
        $ref      = isset($_GET['ref'])      ? (string) $_GET['ref']      : '';
        $error    = !empty($_GET['error']);

        if ($ref === '') {
            $_SESSION['flash_error'] = 'Référence de paiement manquante.';
            $this->redirect(rtrim(BASE_URL, '/') . '/abonnement');
            return;
        }

        // Vérification CSRF du callback : la référence doit correspondre à celle stockée lors de la souscription
        // ET l'utilisateur connecté doit être le même que celui qui a initié le paiement.
        $pendingRef    = $_SESSION['pending_abo_ref']     ?? null;
        $pendingUserId = (int) ($_SESSION['pending_abo_user_id'] ?? 0);

        if ($pendingRef === null || $pendingRef !== $ref || $pendingUserId !== (int) Auth::id()) {
            $_SESSION['flash_error'] = 'Référence de paiement invalide ou expirée.';
            $this->redirect(rtrim(BASE_URL, '/') . '/abonnement');
            return;
        }

        // Consommer la référence (usage unique)
        unset($_SESSION['pending_abo_ref'], $_SESSION['pending_abo_user_id']);

        $result = $this->subscriptionService->confirmerPaiement($provider, $ref, !$error);
        if (!empty($result['error'])) {
            $_SESSION['flash_error'] = $result['error'];
        } else {
            $_SESSION['flash_success'] = 'Paiement enregistré. Votre abonnement est actif.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/abonnement');
    }
}
