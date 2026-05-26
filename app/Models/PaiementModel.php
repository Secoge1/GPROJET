<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PaiementModel extends Model
{
    protected string $table = 'paiements';

    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    public function getByClient(int $clientId, ?int $limit = null): array
    {
        $sql = "SELECT p.*, r.id as reservation_id FROM {$this->table} p
                LEFT JOIN reservations r ON r.id = p.reservation_id
                WHERE p.client_id = ? ORDER BY p.created_at DESC";
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public function getByExpert(int $expertId, ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE expert_id = ? AND type IN ('paiement_session', 'retrait') ORDER BY created_at DESC";
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertId]);
        return $stmt->fetchAll();
    }

    public function getTotalGainsExpert(int $expertId): float
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant_net_expert), 0) FROM {$this->table} WHERE expert_id = ? AND type = 'paiement_session' AND statut = 'effectue'");
        $stmt->execute([$expertId]);
        return (float) $stmt->fetchColumn();
    }

    /** Paiement lié à une réservation (pour escrow). */
    public function getByReservation(int $reservationId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE reservation_id = ? AND type = 'paiement_session' LIMIT 1");
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Retrouve un paiement par sa référence externe (ex: transaction_id fournisseur). */
    public function getByReferenceExterne(string $reference): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE reference_externe = ? LIMIT 1");
        $stmt->execute([$reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Met à jour le statut d'un paiement. */
    public function updateStatut(int $id, string $statut): void
    {
        $this->db->prepare("UPDATE {$this->table} SET statut = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$statut, $id]);
    }

    /**
     * Compteurs pour le tableau de bord admin (table paiements).
     *
     * @return array<string, int>
     */
    public function getAdminDashboardStats(): array
    {
        $out = [
            'session_en_attente'   => 0,
            'session_effectue'     => 0,
            'session_escrow_bloque'=> 0,
            'depot_en_attente'     => 0,
            'depot_effectue'       => 0,
            'retrait_en_attente'   => 0,
            'retrait_effectue'     => 0,
            'echoue'               => 0,
            'annule'               => 0,
            'rembourse'            => 0,
        ];
        try {
            $out['session_en_attente'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'paiement_session' AND statut = 'en_attente'"
            )->fetchColumn();
            $out['session_effectue'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'paiement_session' AND statut = 'effectue'"
            )->fetchColumn();
            $out['depot_en_attente'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'depot' AND statut = 'en_attente'"
            )->fetchColumn();
            $out['depot_effectue'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'depot' AND statut = 'effectue'"
            )->fetchColumn();
            $out['retrait_en_attente'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'retrait' AND statut = 'en_attente'"
            )->fetchColumn();
            $out['retrait_effectue'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'retrait' AND statut = 'effectue'"
            )->fetchColumn();
            $out['echoue'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'echoue'"
            )->fetchColumn();
            $out['annule'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'annule'"
            )->fetchColumn();
            $out['rembourse'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'rembourse' OR type = 'remboursement'"
            )->fetchColumn();
        } catch (\Throwable $e) {
            return $out;
        }
        try {
            $this->db->query("SELECT statut_escrow FROM {$this->table} LIMIT 1");
            $out['session_escrow_bloque'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE type = 'paiement_session' AND statut_escrow = 'bloque'"
            )->fetchColumn();
        } catch (\Throwable $e) {
            // migration escrow non appliquée
        }

        return $out;
    }
}
