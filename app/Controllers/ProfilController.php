<?php
/**
 * GLOBALO - Profil mobile : redirige vers l'espace compte selon le rôle.
 * Atteint via /app/profil (prefix App\\ → isApp() = true).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;

class ProfilController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect(rtrim(BASE_URL, '/') . '/auth/connexion');
            return;
        }

        $base = rtrim(BASE_URL, '/');

        switch (Auth::role()) {
            case 'client':
                $this->redirect($base . '/client/compte');
                break;
            case 'expert':
                $this->redirect($base . '/expert/compte');
                break;
            case 'etudiant':
                $this->redirect($base . '/etudiant/compte');
                break;
            default:
                $this->redirect($base . '/app');
        }
    }
}
