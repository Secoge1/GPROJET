<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ReservationModel extends Model
{
    protected string $table = 'reservations';

    public function getByClient(int $clientId, ?int $limit = null): array
    {
        $sql = "SELECT r.*, p.titre as expert_titre, u.prenom, u.nom
                FROM {$this->table} r
                JOIN profils_experts p ON p.id = r.expert_id
                JOIN utilisateurs u ON u.id = p.utilisateur_id
                WHERE r.client_id = ? ORDER BY r.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public function getByExpert(int $expertId, ?int $limit = null): array
    {
        $sql = "SELECT r.*, d.titre as demande_titre, u.prenom, u.nom
                FROM {$this->table} r
                JOIN demandes_assistance d ON d.id = r.demande_id
                JOIN utilisateurs u ON u.id = r.client_id
                WHERE r.expert_id = ? ORDER BY r.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT r.*, d.titre as demande_titre, d.client_id as demande_client_id,
                p.titre as expert_titre, pu.prenom as expert_prenom, pu.nom as expert_nom,
                cu.prenom as client_prenom, cu.nom as client_nom
                FROM {$this->table} r
                JOIN demandes_assistance d ON d.id = r.demande_id
                JOIN profils_experts p ON p.id = r.expert_id
                JOIN utilisateurs pu ON pu.id = p.utilisateur_id
                JOIN utilisateurs cu ON cu.id = r.client_id
                WHERE r.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $data['statut'] = 'en_attente';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    public function updateStatut(int $id, string $statut): bool
    {
        $allowed = ['en_attente', 'acceptee', 'en_cours', 'terminee', 'annulee', 'payee'];
        if (!in_array($statut, $allowed, true)) {
            return false;
        }
        return $this->update($id, ['statut' => $statut]);
    }

    /**
     * Réservation active liée à une demande (hors annulées).
     * Une réservation annulée ne doit pas bloquer la création d'une nouvelle proposition/réservation.
     */
    public function getByDemandeId(int $demandeId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE demande_id = ? AND statut != 'annulee' ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$demandeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Toutes les réservations liées à une demande (y compris annulées) — usage historique/admin.
     */
    public function getAllByDemandeId(int $demandeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE demande_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$demandeId]);
        return $stmt->fetchAll() ?: [];
    }

    /** IDs profils experts déjà réservés par ce client (hors annulations). */
    public function getExpertProfilIdsUsedByClient(int $clientId): array
    {
        $sql = "SELECT DISTINCT r.expert_id FROM {$this->table} r
                INNER JOIN demandes_assistance d ON d.id = r.demande_id
                WHERE d.client_id = ? AND r.statut != 'annulee'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        $ids = [];
        while ($v = $stmt->fetchColumn()) {
            $ids[] = (int) $v;
        }

        return $ids;
    }

    public function getByExpertWithStatut(int $expertId, ?string $statut = null, ?int $limit = null): array
    {
        $sql = "SELECT r.*, d.titre as demande_titre, cu.prenom, cu.nom
                FROM {$this->table} r
                JOIN demandes_assistance d ON d.id = r.demande_id
                JOIN utilisateurs cu ON cu.id = r.client_id
                WHERE r.expert_id = ?";
        $params = [$expertId];
        if ($statut !== null) {
            $sql .= " AND r.statut = ?";
            $params[] = $statut;
        }
        $sql .= " ORDER BY r.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Dernières réservations pour le tableau de bord admin. */
    public function getRecentForAdmin(int $limit = 10): array
    {
        $sql = "SELECT r.id, r.statut, r.created_at, r.montant_total,
                d.titre as demande_titre,
                cu.email as client_email, cu.prenom as client_prenom, cu.nom as client_nom,
                pe.titre as expert_titre
                FROM {$this->table} r
                JOIN demandes_assistance d ON d.id = r.demande_id
                JOIN profils_experts pe ON pe.id = r.expert_id
                JOIN utilisateurs cu ON cu.id = r.client_id
                ORDER BY r.created_at DESC LIMIT " . (int) $limit;
        return $this->db->query($sql)->fetchAll();
    }
}
