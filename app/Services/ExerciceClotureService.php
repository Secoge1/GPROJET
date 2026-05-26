<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExerciceModel;
use App\Models\NotificationModel;

/**
 * Clôture d'un exercice côté étudiant : uniquement après validation explicite
 * (la soumission du professeur ne marque pas l'exercice comme résolu).
 */
class ExerciceClotureService
{
    private ExerciceModel $exerciceModel;
    private NotificationModel $notificationModel;

    public function __construct(
        ?ExerciceModel $exerciceModel = null,
        ?NotificationModel $notificationModel = null
    ) {
        $this->exerciceModel = $exerciceModel ?? new ExerciceModel();
        $this->notificationModel = $notificationModel ?? new NotificationModel();
    }

    /**
     * @return array{ok: bool, message: string, exercice_id?: int}
     */
    public function confirmerParEtudiant(int $exerciceId, int $etudiantUserId): array
    {
        $ex = $this->exerciceModel->getByIdForEtudiant($exerciceId, $etudiantUserId);
        if (!$ex) {
            return ['ok' => false, 'message' => 'Exercice non autorisé.'];
        }

        if (($ex['statut'] ?? '') === 'resolu') {
            return [
                'ok'       => true,
                'message'  => 'Cet exercice est déjà marqué comme résolu.',
                'exercice_id' => $exerciceId,
            ];
        }

        if (($ex['statut'] ?? '') !== 'correction_livree') {
            return ['ok' => false, 'message' => 'Aucune correction à valider pour le moment.'];
        }

        $ps = (string) ($ex['paiement_statut'] ?? '');
        if (!in_array($ps, ['paye', 'non_requis'], true)) {
            return ['ok' => false, 'message' => 'Débloquez d\'abord la correction (paiement) avant de confirmer.'];
        }

        $updated = $this->exerciceModel->update($exerciceId, [
            'statut'     => 'resolu',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$updated) {
            return ['ok' => false, 'message' => 'Impossible de finaliser pour le moment. Réessayez.'];
        }

        $profUserId = (int) ($ex['expert_id'] ?? 0);
        if ($profUserId > 0) {
            $base = rtrim(BASE_URL ?? '', '/');
            $this->notificationModel->create(
                $profUserId,
                'exercice_valide_etudiant',
                'Exercice validé par l\'étudiant',
                'L\'étudiant a confirmé que sa demande « ' . ($ex['titre'] ?? '') . ' » est résolue.',
                $base . '/professeur/exercices-disponibles'
            );
        }

        return [
            'ok'            => true,
            'message'       => 'Merci. Votre exercice est maintenant marqué comme résolu.',
            'exercice_id'   => $exerciceId,
        ];
    }

    public function peutConfirmer(array $exercice): bool
    {
        if (($exercice['statut'] ?? '') !== 'correction_livree') {
            return false;
        }
        $ps = (string) ($exercice['paiement_statut'] ?? '');

        return in_array($ps, ['paye', 'non_requis'], true);
    }
}
