<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MessageModel extends Model
{
    protected string $table = 'messages';

    public function getByReservation(int $reservationId, ?int $afterId = null): array
    {
        $sql = "SELECT m.*, u.prenom, u.nom FROM {$this->table} m
                JOIN utilisateurs u ON u.id = m.expediteur_id
                WHERE m.reservation_id = ?";
        $params = [$reservationId];
        if ($afterId !== null && $afterId > 0) {
            $sql .= " AND m.id > ?";
            $params[] = $afterId;
        }
        $sql .= " ORDER BY m.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(int $reservationId, int $expediteurId, string $contenu): int
    {
        return $this->insert([
            'reservation_id' => $reservationId,
            'expediteur_id' => $expediteurId,
            'contenu' => $contenu,
        ]);
    }

    public function marquerLus(int $reservationId, int $excludeUserId): void
    {
        $this->db->prepare("UPDATE {$this->table} SET lu = 1 WHERE reservation_id = ? AND expediteur_id != ?")
            ->execute([$reservationId, $excludeUserId]);
    }
}
