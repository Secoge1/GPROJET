<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DemandeModel;
use App\Models\NotificationModel;
use App\Models\ReservationModel;

/**
 * Clôture d'une demande client : uniquement après validation explicite du client.
 */
class DemandeClotureService
{
    private DemandeModel $demandeModel;
    private ReservationModel $reservationModel;
    private NotificationModel $notificationModel;

    public function __construct(
        ?DemandeModel $demandeModel = null,
        ?ReservationModel $reservationModel = null,
        ?NotificationModel $notificationModel = null
    ) {
        $this->demandeModel = $demandeModel ?? new DemandeModel();
        $this->reservationModel = $reservationModel ?? new ReservationModel();
        $this->notificationModel = $notificationModel ?? new NotificationModel();
    }

    /**
     * Le client confirme que sa demande est résolue (prestation reçue et validée).
     *
     * @return array{ok: bool, message: string, demande_id?: int}
     */
    public function confirmerParClient(int $reservationId, int $clientUserId): array
    {
        $reservation = $this->reservationModel->find($reservationId);
        if (!$reservation || (int) $reservation['client_id'] !== $clientUserId) {
            return ['ok' => false, 'message' => 'Réservation non autorisée.'];
        }

        if (!in_array($reservation['statut'] ?? '', ['terminee', 'payee'], true)) {
            return ['ok' => false, 'message' => 'La prestation doit être terminée par l\'expert avant confirmation.'];
        }

        $demandeId = (int) ($reservation['demande_id'] ?? 0);
        if ($demandeId <= 0) {
            return ['ok' => false, 'message' => 'Aucune demande liée à cette réservation.'];
        }

        $demande = $this->demandeModel->find($demandeId);
        if (!$demande || (int) $demande['client_id'] !== $clientUserId) {
            return ['ok' => false, 'message' => 'Demande non autorisée.'];
        }

        if (($demande['statut'] ?? '') === 'terminee') {
            return ['ok' => true, 'message' => 'Cette demande est déjà marquée comme terminée.', 'demande_id' => $demandeId];
        }

        if (($demande['statut'] ?? '') === 'annulee') {
            return ['ok' => false, 'message' => 'Cette demande a été annulée.'];
        }

        $this->demandeModel->updateStatut($demandeId, 'terminee');

        $expertUserId = (int) ($reservation['expert_utilisateur_id'] ?? 0);
        if ($expertUserId <= 0) {
            try {
                $stmt = \App\Core\Database::getInstance()->prepare(
                    'SELECT utilisateur_id FROM profils_experts WHERE id = ? LIMIT 1'
                );
                $stmt->execute([(int) $reservation['expert_id']]);
                $expertUserId = (int) $stmt->fetchColumn();
            } catch (\Throwable $e) {
                $expertUserId = 0;
            }
        }

        if ($expertUserId > 0) {
            $base = rtrim(BASE_URL ?? '', '/');
            $this->notificationModel->create(
                $expertUserId,
                'demande_cloturee',
                'Demande clôturée par le client',
                'Le client a confirmé que sa demande « ' . ($demande['titre'] ?? '') . ' » est résolue.',
                $base . '/expert/reservations'
            );
        }

        return [
            'ok' => true,
            'message' => 'Merci. Votre demande est maintenant marquée comme terminée.',
            'demande_id' => $demandeId,
        ];
    }

    /** Indique si le client peut encore confirmer la clôture pour cette réservation. */
    public function peutConfirmerCloture(array $reservation, ?array $demande): bool
    {
        if (!$demande || ($demande['statut'] ?? '') === 'terminee' || ($demande['statut'] ?? '') === 'annulee') {
            return false;
        }

        return in_array($reservation['statut'] ?? '', ['terminee', 'payee'], true)
            && (int) ($reservation['demande_id'] ?? 0) > 0;
    }
}
