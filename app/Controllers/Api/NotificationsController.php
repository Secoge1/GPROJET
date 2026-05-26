<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\NotificationModel;

/**
 * API légère pour le polling des notifications (son / badge côté client).
 */
class NotificationsController extends Controller
{
    /** GET /api/notifications/count — nombre + dernière non lue (son / notification navigateur). */
    public function count(): void
    {
        Auth::requireAuth();
        $uid = (int) Auth::id();
        $model = new NotificationModel();
        $n = $model->countNonLues($uid);
        $payload = [
            'ok'    => true,
            'count' => $n,
            'badges' => [
                'messages'      => $model->countNonLuesByType($uid, 'message_chat'),
                'reservations'  => $model->countNonLuesByTypes($uid, NotificationModel::typesReservationOuMission()),
            ],
        ];
        $last = $model->getLastNonLue($uid);
        if ($last) {
            $payload['last'] = [
                'type'    => (string) ($last['type'] ?? ''),
                'titre'   => (string) ($last['titre'] ?? ''),
                'contenu' => (string) ($last['contenu'] ?? ''),
                'lien'    => (string) ($last['lien'] ?? ''),
            ];
        }
        $this->json($payload);
    }
}
