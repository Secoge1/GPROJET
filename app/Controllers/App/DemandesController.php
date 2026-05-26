<?php
/**
 * GLOBALO - App mobile : liste et détail des demandes client
 */

declare(strict_types=1);

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\DemandeModel;
use App\Models\DemandePropositionModel;
use App\Models\UtilisateurModel;
use App\Models\ReservationModel;
use App\Services\DemandeRecommendationService;
use App\Services\DemandeClotureService;

class DemandesController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
    }

    public function index(): void
    {
        Auth::requireRole('client');
        $params = $this->router->getParams();
        $first = $params[0] ?? null;
        if ($first !== null && $first !== '' && is_numeric($first)) {
            $this->demandeDetail((int) $first);
            return;
        }
        $clientId = (int) Auth::id();
        $userRow = (new UtilisateurModel())->find($clientId);
        $demandes = (new DemandeModel())->getByClient($clientId);
        $demandes = is_array($demandes) ? $demandes : [];
        $this->render('demandes', [
            'pageTitle' => 'Mes demandes - GLOBALO',
            'navActive' => 'demandes',
            'user' => [
                'id' => $clientId,
                'role' => 'client',
                'prenom' => (is_array($userRow) && isset($userRow['prenom'])) ? $userRow['prenom'] : '',
            ],
            'demandes' => $demandes,
            'client_base_path' => '/app',
        ]);
    }

    private function demandeDetail(int $demandeId): void
    {
        $demandeModel = new DemandeModel();
        $demande = $demandeModel->find($demandeId);
        if (!$demande || (int) $demande['client_id'] !== (int) Auth::id()) {
            $base = rtrim(BASE_URL ?? '', '/');
            header('Location: ' . $base . '/app/demandes', true, 302);
            exit;
        }
        $reservationModel = new ReservationModel();
        $reservation = $reservationModel->getByDemandeId($demandeId);
        $userRow = (new UtilisateurModel())->find((int) Auth::id());
        $base = rtrim(BASE_URL ?? '', '/');

        $showWelcome = false;
        if (!empty($_SESSION['_flash_demande_welcome']) && (int) $_SESSION['_flash_demande_welcome'] === $demandeId) {
            $showWelcome = true;
            unset($_SESSION['_flash_demande_welcome']);
        }

        $recommendations = null;
        if (($demande['statut'] ?? '') === 'ouverte') {
            try {
                $recommendations = (new DemandeRecommendationService($reservationModel))->build($demande, (int) Auth::id());
            } catch (\Throwable $e) {
                error_log('[DemandeRecommendationService] ' . $e->getMessage());
                $recommendations = null;
            }
        }

        $propositions = [];
        try {
            $propositions = (new DemandePropositionModel())->getByDemande($demandeId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] propositions demande (app): ' . $e->getMessage());
        }

        $this->render('demande_detail', [
            'pageTitle'               => 'Détail demande - GLOBALO',
            'navActive'               => 'demandes',
            'user'                    => [
                'id'     => Auth::id(),
                'role'   => 'client',
                'prenom' => (is_array($userRow) && isset($userRow['prenom'])) ? $userRow['prenom'] : '',
            ],
            'demande'                 => $demande,
            'reservation'             => $reservation,
            'demandesListUrl'         => $base . '/app/demandes',
            'client_base_path'        => '/app',
            'demande_recommendations' => $recommendations,
            'demande_welcome_hint'    => $showWelcome,
            'propositions'            => $propositions,
            'can_choose_proposition'  => ($demande['statut'] ?? '') === 'ouverte' && !$reservation,
            'can_confirm_demande'     => $reservation ? (new DemandeClotureService())->peutConfirmerCloture($reservation, $demande) : false,
        ]);
    }
}
