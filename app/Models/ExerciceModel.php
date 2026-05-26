<?php
/**
 * GLOBALO - Modèle Exercices étudiants
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ExerciceModel extends Model
{
    protected string $table = 'exercices';

    /** Crée un exercice. Retourne l'ID créé. */
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    /** Récupère les exercices d'un étudiant. */
    public function getByEtudiant(int $etudiantId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT e.*, mu.nom AS matiere_nom, mu.categorie AS matiere_categorie, mu.slug AS matiere_slug
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                WHERE e.etudiant_id = ?
                ORDER BY e.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etudiantId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /** Récupère les exercices par matière pour un étudiant. */
    public function getByEtudiantAndMatiere(int $etudiantId, int $matiereId): array
    {
        $sql = "SELECT e.*, mu.nom AS matiere_nom
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                WHERE e.etudiant_id = ? AND e.matiere_id = ?
                ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etudiantId, $matiereId]);
        return $stmt->fetchAll();
    }

    /** Récupère un exercice avec ses détails (vérifie l'appartenance à l'étudiant). */
    public function getByIdForEtudiant(int $id, int $etudiantId): ?array
    {
        $sql = "SELECT e.*, mu.nom AS matiere_nom, mu.filiere AS matiere_filiere, mu.categorie AS matiere_categorie
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                WHERE e.id = ? AND e.etudiant_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $etudiantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Statistiques rapides pour le dashboard étudiant. */
    public function getStats(int $etudiantId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(statut = 'ouvert')    AS ouverts,
                    SUM(statut IN ('en_cours','correction_livree'))  AS en_cours,
                    SUM(statut = 'resolu')    AS resolus,
                    SUM(statut = 'annule')    AS annules,
                    ROUND(AVG(CASE WHEN note_finale IS NOT NULL THEN note_finale END), 2) AS moyenne_notes
                FROM exercices
                WHERE etudiant_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etudiantId]);
        return $stmt->fetch() ?: [];
    }

    /** Exercices ouverts (tous étudiants) pour les experts/tuteurs. */
    public function getOuverts(int $limit = 20, ?int $matiereId = null): array
    {
        $where = "e.statut = 'ouvert'";
        $params = [];
        if ($matiereId) {
            $where .= " AND e.matiere_id = ?";
            $params[] = $matiereId;
        }
        $params[] = $limit;
        $sql = "SELECT e.*, mu.nom AS matiere_nom, mu.categorie AS matiere_categorie,
                       u.prenom, u.nom AS etudiant_nom
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                JOIN utilisateurs u ON u.id = e.etudiant_id
                WHERE {$where}
                ORDER BY e.urgence DESC, e.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Compte les exercices ouverts urgents d'un étudiant. */
    public function countUrgents(int $etudiantId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM exercices WHERE etudiant_id = ? AND statut = 'ouvert' AND urgence IN ('urgent','tres_urgent')"
        );
        $stmt->execute([$etudiantId]);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // MÉTHODES PROFESSEUR — correction et prise en charge
    // =========================================================================

    /**
     * Exercices ouverts filtrés par les matières du professeur.
     * Si le professeur n'a pas de matières, retourne tous les exercices ouverts.
     */
    public function getOuvertsPourProfesseur(array $matiereIds, int $limit = 50, int $profilProfesseurId = 0): array
    {
        $propCols    = '';
        $propLeading = [];
        if ($profilProfesseurId > 0) {
            $propCols = ",
                (SELECT ep.id FROM exercice_propositions ep
                 WHERE ep.exercice_id = e.id AND ep.profil_professeur_id = ? LIMIT 1) AS ma_proposition_id,
                (SELECT ep.statut FROM exercice_propositions ep
                 WHERE ep.exercice_id = e.id AND ep.profil_professeur_id = ? LIMIT 1) AS ma_proposition_statut";
            $propLeading = [$profilProfesseurId, $profilProfesseurId];
        }

        // Tous les exercices ouverts sont visibles par tous les professeurs (pas de filtre matières)
        $params = array_merge($propLeading, [$limit]);
        $sql = "SELECT e.*, mu.nom AS matiere_nom, mu.categorie AS matiere_categorie,
                       u.prenom, u.nom AS etudiant_nom{$propCols}
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                JOIN utilisateurs u ON u.id = e.etudiant_id
                WHERE e.statut = 'ouvert' AND e.expert_id IS NULL
                ORDER BY e.urgence DESC, e.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Exercices pris en charge (en_cours ou résolus) par un professeur. */
    public function getEnChargeProfesseur(int $profUserId, int $limit = 20): array
    {
        $sql = "SELECT e.*, mu.nom AS matiere_nom,
                       u.prenom, u.nom AS etudiant_nom
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                JOIN utilisateurs u ON u.id = e.etudiant_id
                WHERE e.expert_id = ?
                ORDER BY FIELD(e.statut,'en_cours','correction_livree','resolu','annule'), e.updated_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$profUserId, $limit]);
        return $stmt->fetchAll();
    }

    /** Récupère un exercice assigné à un professeur donné (pour le formulaire de correction). */
    public function getEnChargePourProfesseur(int $id, int $profUserId): ?array
    {
        $sql = "SELECT e.*, mu.nom AS matiere_nom,
                       u.prenom AS etudiant_prenom, u.nom AS etudiant_nom
                FROM exercices e
                LEFT JOIN matieres_universitaires mu ON mu.id = e.matiere_id
                JOIN utilisateurs u ON u.id = e.etudiant_id
                WHERE e.id = ? AND e.expert_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $profUserId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Exercices bloqués en 'en_cours' depuis plus de N jours sans expert assigné
     * (anomalie de données) OU dont l'expert n'existe plus.
     * Retourne les lignes à réinitialiser.
     */
    public function getOrphelins(int $delaiJours = 7): array
    {
        $sql = "SELECT e.*, u.prenom AS etudiant_prenom, u.nom AS etudiant_nom
                FROM exercices e
                JOIN utilisateurs u ON u.id = e.etudiant_id
                WHERE e.statut = 'en_cours'
                  AND (
                      e.expert_id IS NULL
                      OR NOT EXISTS (SELECT 1 FROM utilisateurs ux WHERE ux.id = e.expert_id AND ux.actif = 1)
                      OR e.updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                  )
                ORDER BY e.updated_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$delaiJours]);
        return $stmt->fetchAll();
    }

    /**
     * Remet un exercice bloqué à l'état 'ouvert' (reset orphelin).
     * Condition : statut = 'en_cours'. Retourne true si la mise à jour a eu lieu.
     */
    public function resetVersOuvert(int $exerciceId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE exercices
             SET expert_id = NULL, statut = 'ouvert', updated_at = NOW()
             WHERE id = ? AND statut = 'en_cours'"
        );
        $stmt->execute([$exerciceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Professeur prend en charge un exercice ouvert.
     * Condition atomique : statut = 'ouvert' AND expert_id IS NULL (empêche double prise).
     */
    public function prendreEnCharge(int $exerciceId, int $profUserId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE exercices SET expert_id = ?, statut = 'en_cours', updated_at = NOW()
             WHERE id = ? AND statut = 'ouvert' AND expert_id IS NULL"
        );
        $stmt->execute([$profUserId, $exerciceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Professeur soumet la correction : statut `correction_livree` (l'étudiant confirme ensuite `resolu`).
     * Si le prix est 0, paiement non requis.
     */
    public function soumettreSolution(
        int    $exerciceId,
        int    $profUserId,
        string $solution,
        string $commentaire,
        ?float $note,
        float  $prix
    ): bool {
        $paiementStatut = $prix <= 0 ? 'non_requis' : 'en_attente';
        $stmt = $this->db->prepare(
            "UPDATE exercices
             SET solution           = ?,
                 commentaire_expert = ?,
                 note_finale        = ?,
                 statut             = 'correction_livree',
                 paiement_statut    = ?,
                 prix_correction    = ?,
                 updated_at         = NOW()
             WHERE id = ? AND expert_id = ? AND statut = 'en_cours'"
        );
        $stmt->execute([
            $solution,
            $commentaire !== '' ? $commentaire : null,
            $note,
            $paiementStatut,
            $prix,
            $exerciceId,
            $profUserId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** Marque la correction comme payée et enregistre la référence de transaction. */
    public function marquerPaye(int $exerciceId, string $reference = ''): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE exercices
             SET paiement_statut    = 'paye',
                 paiement_reference = ?,
                 updated_at         = NOW()
             WHERE id = ? AND paiement_statut = 'en_attente'"
        );
        $stmt->execute([$reference ?: null, $exerciceId]);
        return $stmt->rowCount() > 0;
    }
}
