<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Mode urgence : une demande est diffusée aux experts, le premier qui accepte obtient la mission.
 */
class MissionUrgenceModel extends Model
{
    protected string $table = 'mission_urgence';

    public function createForDemande(int $demandeId): int
    {
        return $this->insert([
            'demande_id' => $demandeId,
            'statut' => 'en_attente',
        ]);
    }

    public function getByDemandeId(int $demandeId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE demande_id = ? LIMIT 1");
        $stmt->execute([$demandeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Missions en attente d'acceptation (pour les experts).
     * Filtre par compétence si competenceId > 0, sinon toutes.
     */
    public function getEnAttentePourExpert(int $expertProfilId, ?int $competenceId = null): array
    {
        $sql = "SELECT m.*, d.titre, d.description, d.client_id, d.competence_id, d.duree_estimee_heures,
                       c.nom as competence_nom, u.prenom as client_prenom, u.nom as client_nom
                FROM {$this->table} m
                JOIN demandes_assistance d ON d.id = m.demande_id
                LEFT JOIN competences c ON c.id = d.competence_id
                JOIN utilisateurs u ON u.id = d.client_id
                WHERE m.statut = 'en_attente'
                AND (d.competence_id IS NULL OR EXISTS (
                    SELECT 1 FROM expert_competences ec WHERE ec.expert_id = ? AND ec.competence_id = d.competence_id
                ))";
        $params = [$expertProfilId];
        if ($competenceId > 0) {
            $sql .= " AND d.competence_id = ?";
            $params[] = $competenceId;
        }
        $sql .= " ORDER BY m.created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Accepter la mission (premier qui gagne). Retourne true si cet expert a été attribué.
     */
    public function accepter(int $demandeId, int $expertProfilId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET expert_id = ?, statut = 'acceptee', accepte_at = NOW() WHERE demande_id = ? AND statut = 'en_attente' LIMIT 1"
        );
        $stmt->execute([$expertProfilId, $demandeId]);
        return $stmt->rowCount() > 0;
    }
}
