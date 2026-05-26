<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\ReservationModel;
use App\Models\ProfilExpertModel;

/**
 * Session de travail (visio / partage d'écran) — structure de base.
 * WebRTC pourra être intégré ultérieurement (Jitsi, PeerJS, etc.).
 */
class SessionController extends Controller
{
    /** Salle de session pour une réservation (client ou expert). */
    public function room(): void
    {
        Auth::requireAuth();
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        $reservation = (new ReservationModel())->find($reservationId);
        if (!$reservation) {
            $this->redirect(rtrim(BASE_URL, '/') . '/messages');
            return;
        }
        $userId = Auth::id();
        $isClient = (int) $reservation['client_id'] === $userId;
        $profil = (new ProfilExpertModel())->getByIdPublic((int) $reservation['expert_id']);
        $isExpert = $profil && (int) $profil['utilisateur_id'] === $userId;
        if (!$isClient && !$isExpert) {
            $this->redirect(rtrim(BASE_URL, '/') . '/messages');
            return;
        }
        $statut = $reservation['statut'] ?? '';
        $canStart = in_array($statut, ['acceptee', 'en_cours'], true);
        $this->render('room', [
            'pageTitle' => 'Session - Réservation #' . $reservationId . ' - GLOBALO',
            'user' => ['id' => $userId, 'role' => Auth::role()],
            'reservation' => $reservation,
            'can_start' => $canStart,
        ]);
    }
}
