<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Security;
use App\Models\MessageModel;
use App\Models\NotificationModel;
use App\Models\ProfilExpertModel;
use App\Models\ReservationModel;
use App\Models\PieceJointeModel;
use App\Services\UploadService;
use App\Services\MessageContentFilterService;

class MessagesController extends Controller
{
    public function list(): void
    {
        Auth::requireAuth();
        $reservationId = (int) ($_GET['reservation_id'] ?? 0);
        $afterId = (int) ($_GET['after_id'] ?? 0);
        if (!$this->canAccessReservation($reservationId)) {
            $this->json(['error' => 'Accès refusé'], 403);
            return;
        }
        $messageModel = new MessageModel();
        $messages = $messageModel->getByReservation($reservationId, $afterId > 0 ? $afterId : null);
        $pjModel = new PieceJointeModel();
        $userId = Auth::id();
        $baseUrl = rtrim(BASE_URL, '/');
        $out = [];
        foreach ($messages as $m) {
            $pieces = $pjModel->getByMessage((int) $m['id']);
            $piecesOut = [];
            foreach ($pieces as $p) {
                $piecesOut[] = [
                    'id' => (int) $p['id'],
                    'nom_fichier' => $p['nom_fichier'],
                    'url' => $baseUrl . '/fichier/piece/' . (int) $p['id'],
                ];
            }
            $out[] = [
                'id' => (int) $m['id'],
                'contenu' => $m['contenu'],
                'prenom' => $m['prenom'],
                'nom' => $m['nom'],
                'created_at' => $m['created_at'],
                'mine' => (int) $m['expediteur_id'] === $userId,
                'pieces' => $piecesOut,
            ];
        }
        $this->json(['messages' => $out]);
    }

    public function send(): void
    {
        Auth::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $contenu = Security::sanitizeString($_POST['contenu'] ?? '', 5000);
        if (!$reservationId) {
            $this->json(['error' => 'Données invalides'], 400);
            return;
        }
        if ($contenu !== '') {
            $filter = MessageContentFilterService::check($contenu);
            if (!$filter['allowed']) {
                $this->json(['error' => $filter['reason']], 400);
                return;
            }
        }
        $files = $this->collectUploadedFiles();
        if ($contenu === '' && empty($files)) {
            $this->json(['error' => 'Message ou pièce jointe requis'], 400);
            return;
        }
        if (!$this->canAccessReservation($reservationId)) {
            $this->json(['error' => 'Accès refusé'], 403);
            return;
        }
        $messageModel  = new MessageModel();
        $id            = $messageModel->create($reservationId, Auth::id(), $contenu);
        $this->notifyRecipientNewMessage($reservationId, $contenu);
        $uploadService = new UploadService();
        $pjModel       = new PieceJointeModel();
        $baseUrl       = rtrim(BASE_URL, '/');
        $piecesOut     = [];

        foreach ($files as $file) {
            $stored = $uploadService->store('messages', $file);
            if ($stored) {
                $pjId = $pjModel->create($id, $stored['name'], $stored['path'], $stored['size'], $stored['mime']);
                $piecesOut[] = [
                    'id'          => $pjId,
                    'nom_fichier' => $stored['name'],
                    'type_mime'   => $stored['mime'] ?? '',
                    'url'         => $baseUrl . '/fichier/piece/' . $pjId,
                ];
            }
        }

        $this->json([
            'success' => true,
            'id'      => $id,
            'contenu' => $contenu,
            'pieces'  => $piecesOut,
        ]);
    }

    /** @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}> */
    private function collectUploadedFiles(): array
    {
        $list = [];
        if (!empty($_FILES['pieces']) && is_array($_FILES['pieces']['name'])) {
            foreach ($_FILES['pieces']['name'] as $i => $name) {
                if ($name === '' || ($_FILES['pieces']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $list[] = [
                    'name' => $name,
                    'type' => $_FILES['pieces']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['pieces']['tmp_name'][$i] ?? '',
                    'error' => (int) ($_FILES['pieces']['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($_FILES['pieces']['size'][$i] ?? 0),
                ];
            }
        } elseif (!empty($_FILES['piece']) && ($_FILES['piece']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $list[] = $_FILES['piece'];
        }
        return $list;
    }

    private function canAccessReservation(int $reservationId): bool
    {
        $reservation = (new ReservationModel())->find($reservationId);
        if (!$reservation) {
            return false;
        }
        $userId = Auth::id();
        if ((int) $reservation['client_id'] === $userId) {
            return true;
        }
        $profil = (new \App\Models\ProfilExpertModel())->getByIdPublic((int) $reservation['expert_id']);
        return $profil && (int) $profil['utilisateur_id'] === $userId;
    }

    /** Notification interne + compteur pour alerte sonore hors conversation. */
    private function notifyRecipientNewMessage(int $reservationId, string $contenu): void
    {
        $reservation = (new ReservationModel())->find($reservationId);
        if (!$reservation) {
            return;
        }
        $senderId = (int) Auth::id();
        $recipientId = null;
        if ((int) $reservation['client_id'] === $senderId) {
            $prof = (new ProfilExpertModel())->getByIdPublic((int) $reservation['expert_id']);
            $recipientId = $prof ? (int) $prof['utilisateur_id'] : null;
        } else {
            $recipientId = (int) $reservation['client_id'];
        }
        if ($recipientId <= 0 || $recipientId === $senderId) {
            return;
        }
        $preview = $contenu !== '' ? mb_substr($contenu, 0, 200) : 'Nouveau message (pièce jointe)';
        $lien = rtrim(BASE_URL ?? '', '/') . '/messages/conversation/' . $reservationId;
        try {
            (new NotificationModel())->create(
                $recipientId,
                'message_chat',
                'Nouveau message',
                $preview,
                $lien
            );
        } catch (\Throwable $e) {
            // table absente ou erreur : ne pas bloquer l’envoi du message
        }
    }
}
