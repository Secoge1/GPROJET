<?php
/**
 * GLOBALO - App mobile : Missions / réservations (redirige selon le rôle)
 */

declare(strict_types=1);

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;

class MissionsController extends Controller
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
        if (Auth::role() === 'expert') {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/client/reservations');
    }
}
