<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SessionAchievementModel extends Model
{
    protected string $table = 'session_achievements';

    public function getByIdWithExpert(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT sa.*, p.titre as expert_titre, u.prenom as expert_prenom, u.nom as expert_nom
                FROM {$this->table} sa
                JOIN profils_experts p ON p.id = sa.expert_id
                JOIN utilisateurs u ON u.id = p.utilisateur_id
                WHERE sa.id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createForReservation(int $reservationId, int $expertId, int $clientId, string $titreSession, ?int $note = null): int
    {
        try {
            $this->insert([
                'reservation_id' => $reservationId,
                'expert_id' => $expertId,
                'client_id' => $clientId,
                'titre_session' => $titreSession,
                'note' => $note,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
