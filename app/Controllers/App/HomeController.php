<?php
/**
 * GLOBALO - Version App Mobile - Accueil
 */

declare(strict_types=1);

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\UtilisateurModel;
use App\Models\ProfilExpertModel;
use App\Models\ProfilProfesseurModel;
use App\Models\CompetenceModel;
use App\Models\ReservationModel;

class HomeController extends Controller
{
    public function index(): void
    {
        $user = null;
        if (Auth::check()) {
            $userModel = new UtilisateurModel();
            $user = $userModel->find((int) Auth::id());
            if ($user) {
                $user = ['id' => $user['id'], 'role' => $user['role'], 'prenom' => $user['prenom'] ?? '', 'nom' => $user['nom'] ?? ''];
            }
        }

        $experts = [];
        $professeurs = [];
        $competences = [];
        $pendingPaymentReservations = [];
        if (Auth::check()) {
            $expertModel = new ProfilExpertModel();
            $experts = $expertModel->getListDisponibles(null, null, 3);
            $compModel = new CompetenceModel();
            $competences = $compModel->getActives();
            if (($user['role'] ?? '') === 'etudiant') {
                try {
                    $professeurs = (new ProfilProfesseurModel())->getListDisponibles(null, null, 3);
                } catch (\Throwable $e) {
                    $professeurs = [];
                }
            }
            if (($user['role'] ?? '') === 'client') {
                $resAll = (new ReservationModel())->getByClient((int) $user['id']);
                $pendingPaymentReservations = array_values(array_filter(
                    $resAll,
                    static fn($r) => ($r['statut'] ?? '') === 'acceptee'
                ));
            }
        }

        $this->render('index', [
            'pageTitle' => 'GLOBALO',
            'user' => $user,
            'experts' => $experts,
            'professeurs' => $professeurs,
            'competences' => array_slice($competences, 0, 8),
            'navActive' => 'accueil',
            'pending_payment_reservations' => $pendingPaymentReservations,
        ]);
    }
}
