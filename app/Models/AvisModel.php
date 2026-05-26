<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AvisModel extends Model
{
    protected string $table = 'avis_notes';

    public function getByExpert(int $expertId, int $limit = 20): array
    {
        $sql = "SELECT a.*, u.prenom, u.nom FROM {$this->table} a
                JOIN utilisateurs u ON u.id = a.client_id
                WHERE a.expert_id = ? ORDER BY a.created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertId, $limit]);
        return $stmt->fetchAll();
    }

    public function getNoteMoyenne(int $expertId): ?float
    {
        $stmt = $this->db->prepare("SELECT AVG(note) FROM {$this->table} WHERE expert_id = ?");
        $stmt->execute([$expertId]);
        $v = $stmt->fetchColumn();
        return $v !== null ? round((float) $v, 2) : null;
    }

    public function countByExpert(int $expertId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE expert_id = ?");
        $stmt->execute([$expertId]);
        return (int) $stmt->fetchColumn();
    }

    /** Nombre d'avis « mauvais » (note <= seuil) pour un expert. Seuil par défaut 2 = 1 ou 2 étoiles. */
    public function countBadByExpert(int $expertId, int $seuil = 2): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE expert_id = ? AND note <= ?");
        $stmt->execute([$expertId, $seuil]);
        return (int) $stmt->fetchColumn();
    }

    public function createForReservation(int $reservationId, int $clientId, int $expertId, int $note, string $commentaire = ''): int
    {
        return $this->insert([
            'reservation_id' => $reservationId,
            'client_id' => $clientId,
            'expert_id' => $expertId,
            'note' => $note,
            'commentaire' => $commentaire,
        ]);
    }

    public function existsForReservation(int $reservationId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE reservation_id = ? LIMIT 1");
        $stmt->execute([$reservationId]);
        return (bool) $stmt->fetch();
    }

    /** Met à jour note_moyenne et nombre_avis sur profils_experts */
    public function updateExpertStats(int $expertId): void
    {
        $moy = $this->getNoteMoyenne($expertId);
        $nb = $this->countByExpert($expertId);
        $this->db->prepare("UPDATE profils_experts SET note_moyenne = ?, nombre_avis = ? WHERE id = ?")
            ->execute([$moy, $nb, $expertId]);
    }
}
