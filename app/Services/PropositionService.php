<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DemandeModel;
use App\Models\DemandePropositionModel;
use App\Models\ExerciceModel;
use App\Models\ExercicePropositionModel;
use App\Models\NotificationModel;
use App\Models\ProfilExpertModel;
use App\Models\ReservationModel;

/**
 * Acceptation / refus des propositions (client ↔ expert, étudiant ↔ professeur).
 */
class PropositionService
{
    private DemandePropositionModel $demandePropModel;
    private ExercicePropositionModel $exercicePropModel;
    private DemandeModel $demandeModel;
    private ReservationModel $reservationModel;
    private ExerciceModel $exerciceModel;
    private NotificationModel $notificationModel;

    public function __construct(
        ?DemandePropositionModel $demandePropModel = null,
        ?ExercicePropositionModel $exercicePropModel = null,
        ?DemandeModel $demandeModel = null,
        ?ReservationModel $reservationModel = null,
        ?ExerciceModel $exerciceModel = null,
        ?NotificationModel $notificationModel = null
    ) {
        $this->demandePropModel = $demandePropModel ?? new DemandePropositionModel();
        $this->exercicePropModel = $exercicePropModel ?? new ExercicePropositionModel();
        $this->demandeModel = $demandeModel ?? new DemandeModel();
        $this->reservationModel = $reservationModel ?? new ReservationModel();
        $this->exerciceModel = $exerciceModel ?? new ExerciceModel();
        $this->notificationModel = $notificationModel ?? new NotificationModel();
    }

    /**
     * Client accepte une proposition expert → crée une réservation en attente.
     *
     * @return array{ok: bool, message: string, reservation_id?: int}
     */
    public function accepterPropositionDemande(int $propositionId, int $clientUserId, bool $isApp = false): array
    {
        $prop = $this->demandePropModel->find($propositionId);
        if (!$prop || ($prop['statut'] ?? '') !== 'en_attente') {
            return ['ok' => false, 'message' => 'Proposition introuvable ou déjà traitée.'];
        }

        $demande = $this->demandeModel->find((int) $prop['demande_id']);
        if (!$demande || (int) $demande['client_id'] !== $clientUserId) {
            return ['ok' => false, 'message' => 'Demande non autorisée.'];
        }
        if (($demande['statut'] ?? '') !== 'ouverte') {
            return ['ok' => false, 'message' => 'Cette demande n\'accepte plus de propositions.'];
        }

        if ($this->reservationModel->getByDemandeId((int) $demande['id'])) {
            return ['ok' => false, 'message' => 'Une réservation existe déjà pour cette demande.'];
        }

        $expertProfil = (new ProfilExpertModel())->getByIdPublic((int) $prop['expert_id']);
        if (!$expertProfil || empty($expertProfil['disponible'])) {
            return ['ok' => false, 'message' => 'Cet expert n\'est plus disponible.'];
        }

        $duree = max(0.5, (float) ($demande['duree_estimee_heures'] ?? 1));
        // tarif_propose = forfait total proposé par l'expert (pas tarif horaire)
        $montant = round(max(0.0, (float) $prop['tarif_propose']), 2);
        if ($montant <= 0) {
            $montant = round($duree * max(0.0, (float) ($expertProfil['tarif_horaire'] ?? 0)), 2);
        }
        if ($montant <= 0) {
            return ['ok' => false, 'message' => 'Tarif de la proposition invalide.'];
        }
        $tarifHoraire = $duree > 0 ? round($montant / $duree, 2) : $montant;

        $delaiJours = max(1, min(90, (int) ($prop['delai_jours'] ?? 3)));
        $dateDebut = (new \DateTime())->modify('+' . $delaiJours . ' days')->setTime(14, 0, 0);

        $reservationId = $this->reservationModel->create([
            'demande_id'        => (int) $demande['id'],
            'expert_id'         => (int) $prop['expert_id'],
            'client_id'         => $clientUserId,
            'date_debut_prevue' => $dateDebut->format('Y-m-d H:i:s'),
            'duree_heures'      => $duree,
            'tarif_horaire'     => $tarifHoraire,
            'montant_total'     => $montant,
        ]);

        $this->demandePropModel->updateStatut($propositionId, 'acceptee');
        $this->demandePropModel->refuserAutresPourDemande((int) $demande['id'], $propositionId);

        // La demande passe en "en_cours" dès qu'une proposition est acceptée et une réservation créée.
        // Elle ne sera marquée "terminee" qu'après confirmation explicite du client.
        $this->demandeModel->updateStatut((int) $demande['id'], 'en_cours');

        $base = rtrim(BASE_URL ?? '', '/');
        $expertUserId = (int) ($prop['expert_utilisateur_id'] ?? 0);
        if ($expertUserId > 0) {
            $lien = ($isApp ? '/app/expert-reservations' : '/expert/reservations') . '?r=' . $reservationId;
            $this->notificationModel->create(
                $expertUserId,
                'proposition_acceptee',
                'Proposition acceptée',
                'Le client a choisi votre proposition pour : ' . ($demande['titre'] ?? 'la demande'),
                $base . $lien
            );
        }

        return [
            'ok' => true,
            'message' => 'Expert sélectionné. La demande est maintenant en cours. Elle sera marquée terminée après votre confirmation une fois la prestation reçue.',
            'reservation_id' => $reservationId,
        ];
    }

    public function refuserPropositionDemande(int $propositionId, int $clientUserId): array
    {
        $prop = $this->demandePropModel->find($propositionId);
        if (!$prop || ($prop['statut'] ?? '') !== 'en_attente') {
            return ['ok' => false, 'message' => 'Proposition introuvable ou déjà traitée.'];
        }
        $demande = $this->demandeModel->find((int) $prop['demande_id']);
        if (!$demande || (int) $demande['client_id'] !== $clientUserId) {
            return ['ok' => false, 'message' => 'Demande non autorisée.'];
        }

        $this->demandePropModel->updateStatut($propositionId, 'refusee');

        $expertUserId = (int) ($prop['expert_utilisateur_id'] ?? 0);
        if ($expertUserId > 0) {
            $this->notificationModel->create(
                $expertUserId,
                'proposition_refusee',
                'Proposition non retenue',
                'Votre proposition pour « ' . ($demande['titre'] ?? '') . ' » n\'a pas été retenue.',
                ''
            );
        }

        return ['ok' => true, 'message' => 'Proposition refusée.'];
    }

    /**
     * Étudiant accepte une proposition professeur → prise en charge de l'exercice.
     */
    public function accepterPropositionExercice(int $propositionId, int $etudiantUserId, string $basePath = '/etudiant'): array
    {
        $prop = $this->exercicePropModel->find($propositionId);
        if (!$prop || ($prop['statut'] ?? '') !== 'en_attente') {
            return ['ok' => false, 'message' => 'Proposition introuvable ou déjà traitée.'];
        }

        $exercice = $this->exerciceModel->getByIdForEtudiant((int) $prop['exercice_id'], $etudiantUserId);
        if (!$exercice || ($exercice['statut'] ?? '') !== 'ouvert') {
            return ['ok' => false, 'message' => 'Exercice non disponible.'];
        }

        $profUserId = (int) ($prop['prof_utilisateur_id'] ?? 0);
        if ($profUserId <= 0) {
            return ['ok' => false, 'message' => 'Professeur introuvable.'];
        }

        if (!$this->exerciceModel->prendreEnCharge((int) $exercice['id'], $profUserId)) {
            return ['ok' => false, 'message' => 'Impossible d\'assigner cet exercice (déjà pris en charge).'];
        }

        $prix = max(0.0, (float) $prop['tarif_propose']);
        if ($prix > 0) {
            $this->exerciceModel->update((int) $exercice['id'], [
                'prix_correction' => $prix,
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        $this->exercicePropModel->updateStatut($propositionId, 'acceptee');
        $this->exercicePropModel->refuserAutresPourExercice((int) $exercice['id'], $propositionId);

        $base = rtrim(BASE_URL ?? '', '/');
        $this->notificationModel->create(
            $profUserId,
            'proposition_acceptee',
            'Proposition acceptée',
            'Un étudiant a choisi votre proposition pour : ' . ($exercice['titre'] ?? 'l\'exercice'),
            $base . $basePath . '/corriger/' . (int) $exercice['id']
        );

        return [
            'ok'      => true,
            'message' => 'Professeur sélectionné. L\'exercice est en cours de correction ; il ne sera marqué résolu qu\'après votre confirmation une fois la correction reçue.',
        ];
    }

    public function refuserPropositionExercice(int $propositionId, int $etudiantUserId): array
    {
        $prop = $this->exercicePropModel->find($propositionId);
        if (!$prop || ($prop['statut'] ?? '') !== 'en_attente') {
            return ['ok' => false, 'message' => 'Proposition introuvable ou déjà traitée.'];
        }
        $exercice = $this->exerciceModel->getByIdForEtudiant((int) $prop['exercice_id'], $etudiantUserId);
        if (!$exercice) {
            return ['ok' => false, 'message' => 'Exercice non autorisé.'];
        }

        $this->exercicePropModel->updateStatut($propositionId, 'refusee');

        $profUserId = (int) ($prop['prof_utilisateur_id'] ?? 0);
        if ($profUserId > 0) {
            $this->notificationModel->create(
                $profUserId,
                'proposition_refusee',
                'Proposition non retenue',
                'Votre proposition pour « ' . ($exercice['titre'] ?? '') . ' » n\'a pas été retenue.',
                ''
            );
        }

        return ['ok' => true, 'message' => 'Proposition refusée.'];
    }
}
