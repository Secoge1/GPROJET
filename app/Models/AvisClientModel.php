<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Avis expert → client (note 1-5 + commentaire) par réservation terminée. */
class AvisClientModel extends Model
{
    protected string $table = 'avis_clients';

    public function existsForReservation(int $reservationId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE reservation_id = ? LIMIT 1");
        $stmt->execute([$reservationId]);
        return (bool) $stmt->fetch();
    }

    public function createForReservation(int $reservationId, int $expertId, int $clientId, int $note, string $commentaire = ''): int
    {
        return $this->insert([
            'reservation_id' => $reservationId,
            'expert_id' => $expertId,
            'client_id' => $clientId,
            'note' => $note,
            'commentaire' => $commentaire,
        ]);
    }

    /** Liste des avis reçus par un client (expert → client), avec nom de l'expert. */
    public function getByClient(int $clientId, int $limit = 20): array
    {
        $sql = "SELECT a.*, p.titre AS expert_titre, u.prenom AS expert_prenom, u.nom AS expert_nom
                FROM {$this->table} a
                JOIN profils_experts p ON p.id = a.expert_id
                JOIN utilisateurs u ON u.id = p.utilisateur_id
                WHERE a.client_id = ? ORDER BY a.created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Avis reçu par le client pour une réservation donnée (un seul). */
    public function getByReservation(int $reservationId): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, u.prenom AS expert_prenom, u.nom AS expert_nom FROM {$this->table} a JOIN profils_experts p ON p.id = a.expert_id JOIN utilisateurs u ON u.id = p.utilisateur_id WHERE a.reservation_id = ? LIMIT 1");
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getNoteMoyenne(int $clientId): ?float
    {
        $stmt = $this->db->prepare("SELECT AVG(note) FROM {$this->table} WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $v = $stmt->fetchColumn();
        return $v !== null && $v !== false ? round((float) $v, 2) : null;
    }

    public function countByClient(int $clientId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE client_id = ?");
        $stmt->execute([$clientId]);
        return (int) $stmt->fetchColumn();
    }

    /** Nombre d'avis « mauvais » (note <= seuil) pour un client. Seuil par défaut 2 = 1 ou 2 étoiles. */
    public function countBadByClient(int $clientId, int $seuil = 2): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE client_id = ? AND note <= ?");
        $stmt->execute([$clientId, $seuil]);
        return (int) $stmt->fetchColumn();
    }
}
