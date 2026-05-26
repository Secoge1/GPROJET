<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PieceJointeModel extends Model
{
    protected string $table = 'pieces_jointes';

    /** @return array<int, array> */
    public function getByMessage(int $messageId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE message_id = ? ORDER BY id ASC");
        $stmt->execute([$messageId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array> Indexé par message_id */
    public function getByReservation(int $reservationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pj.* FROM {$this->table} pj
             JOIN messages m ON m.id = pj.message_id
             WHERE m.reservation_id = ? ORDER BY pj.id ASC"
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function create(int $messageId, string $nomFichier, string $chemin, int $taille, ?string $typeMime = null): int
    {
        return (int) $this->insert([
            'message_id' => $messageId,
            'nom_fichier' => $nomFichier,
            'chemin' => $chemin,
            'taille' => $taille,
            'type_mime' => $typeMime,
        ]);
    }
}
