<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Router;
use App\Core\Security;
use App\Models\ProfilExpertModel;
use App\Models\ProfilProfesseurModel;
use App\Services\PrestataireDisponibilitePromptService;

class PrestataireDisponibiliteController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('expert', 'professeur');
    }

    /** POST — active ou désactive la disponibilité (JSON). */
    public function disponibilite(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
            return;
        }
        if (!Security::validateCsrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Session expirée. Rechargez la page.']);
            return;
        }

        $role = (string) Auth::role();
        $userId = (int) Auth::id();
        $disponible = isset($_POST['disponible']) && (string) $_POST['disponible'] === '1';

        if ($role === 'expert') {
            $profil = (new ProfilExpertModel())->getByUtilisateurId($userId);
            if (!$profil || empty($profil['valide_par_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'Profil expert non validé.']);
                return;
            }
            (new ProfilExpertModel())->update((int) $profil['id'], ['disponible' => $disponible ? 1 : 0]);
            PrestataireDisponibilitePromptService::markRappelVu('expert', (int) $profil['id']);
            echo json_encode([
                'ok'         => true,
                'disponible' => $disponible,
                'message'    => $disponible
                    ? 'Vous êtes maintenant visible comme disponible.'
                    : 'Vous êtes passé en mode hors ligne.',
            ]);
            return;
        }

        if ($role === 'professeur') {
            $profil = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
            if (!$profil || empty($profil['valide_par_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'Profil professeur non validé.']);
                return;
            }
            (new ProfilProfesseurModel())->updateProfil((int) $profil['id'], ['disponible' => $disponible ? 1 : 0]);
            PrestataireDisponibilitePromptService::markRappelVu('professeur', (int) $profil['id']);
            echo json_encode([
                'ok'         => true,
                'disponible' => $disponible,
                'message'    => $disponible
                    ? 'Vous êtes maintenant visible pour les réservations.'
                    : 'Vous êtes passé en mode hors ligne.',
            ]);
            return;
        }

        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Rôle non autorisé.']);
    }

    /** POST — fermer le rappel sans activer la disponibilité (JSON). */
    public function rappel(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
            return;
        }
        if (!Security::validateCsrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Session expirée. Rechargez la page.']);
            return;
        }

        $role = (string) Auth::role();
        $userId = (int) Auth::id();

        if ($role === 'expert') {
            $profil = (new ProfilExpertModel())->getByUtilisateurId($userId);
            if ($profil && !empty($profil['valide_par_admin'])) {
                PrestataireDisponibilitePromptService::markRappelVu('expert', (int) $profil['id']);
            }
        } elseif ($role === 'professeur') {
            $profil = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
            if ($profil && !empty($profil['valide_par_admin'])) {
                PrestataireDisponibilitePromptService::markRappelVu('professeur', (int) $profil['id']);
            }
        }

        echo json_encode(['ok' => true]);
    }
}
