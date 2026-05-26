<?php
/**
 * GLOBALO - API REST - Auth (exemple)
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Models\UtilisateurModel;

class AuthController extends Controller
{
    public function index(): void
    {
        $this->json(['message' => 'API GLOBALO', 'version' => '1.0']);
    }

    public function me(): void
    {
        Auth::requireAuth();
        $userModel = new UtilisateurModel();
        $user = $userModel->find(Auth::id());
        if (!$user) {
            $this->json(['error' => 'Utilisateur introuvable'], 404);
            return;
        }
        unset($user['mot_de_passe'], $user['token_verification'], $user['token_reinitialisation']);
        $this->json($user);
    }
}
