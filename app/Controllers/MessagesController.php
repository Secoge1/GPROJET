<?php
/**
 * GLOBALO - Messagerie (conversation par réservation)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\MessageModel;
use App\Models\ReservationModel;
use App\Models\PieceJointeModel;
use App\Models\NotificationModel;

class MessagesController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $reservationModel = new ReservationModel();
        $userId = Auth::id();
        $role = Auth::role();
        if ($role === 'client') {
            $reservations = $reservationModel->getByClient($userId);
        } else {
            $profil = (new \App\Models\ProfilExpertModel())->getByUtilisateurId($userId);
            $reservations = $profil ? $reservationModel->getByExpert($profil['id']) : [];
        }
        $notifModel = new NotificationModel();
        $unreadConversationIds = $notifModel->getReservationIdsWithUnreadMessages((int) $userId);
        $notifModel->marquerLuesParType((int) $userId, 'message_chat');
        $convPrefix = $this->router->isApp() ? '/app/conversation' : '/messages/conversation';
        $this->render('index', [
            'pageTitle' => 'Messages - GLOBALO',
            'user' => ['id' => $userId, 'role' => $role],
            'reservations' => $reservations,
            'unreadConversationIds' => $unreadConversationIds,
            'messages_conversation_prefix' => $convPrefix,
        ]);
    }

    /** Conversation d'une réservation. */
    public function conversation(): void
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
        $isClient = (int)$reservation['client_id'] === $userId;
        $expertProfil = (new \App\Models\ProfilExpertModel())->find((int)$reservation['expert_id']);
        $isExpert = $expertProfil && (int)$expertProfil['utilisateur_id'] === $userId;
        if (!$isClient && !$isExpert) {
            $this->redirect(rtrim(BASE_URL, '/') . '/messages');
            return;
        }
        $messageModel = new MessageModel();
        $messages = $messageModel->getByReservation($reservationId);
        $pjModel = new PieceJointeModel();
        $baseUrl = rtrim(BASE_URL, '/');
        foreach ($messages as &$m) {
            $m['pieces'] = $pjModel->getByMessage((int) $m['id']);
            foreach ($m['pieces'] as &$p) {
                $p['url'] = $baseUrl . '/fichier/piece/' . (int) $p['id'];
            }
        }
        unset($m, $p);
        $messageModel->marquerLus($reservationId, $userId);
        (new NotificationModel())->marquerLuesMessageChatPourReservation((int) $userId, $reservationId);
        $convPrefix = $this->router->isApp() ? '/app/conversation' : '/messages/conversation';
        $this->render('conversation', [
            'pageTitle' => 'Conversation - GLOBALO',
            'user' => ['id' => $userId, 'role' => Auth::role()],
            'reservation' => $reservation,
            'messages' => $messages,
            'messages_conversation_prefix' => $convPrefix,
            'messages_list_url' => rtrim(BASE_URL ?? '', '/') . ($this->router->isApp() ? '/app/messages' : '/messages'),
        ]);
    }
}
