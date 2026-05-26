<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DemandeRetraitModel extends Model
{
    protected string $table = 'demandes_retrait';

    public function getByExpert(int $expertId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE expert_id = ? ORDER BY created_at DESC");
        $stmt->execute([$expertId]);
        return $stmt->fetchAll();
    }

    public function create(int $expertId, float $montant, string $iban = ''): int
    {
        return $this->insert([
            'expert_id' => $expertId,
            'montant' => $montant,
            'iban' => $iban,
            'statut' => 'en_attente',
        ]);
    }

    public function getAllForAdmin(int $limit = 200): array
    {
        $stmt = $this->db->prepare("
            SELECT dr.*, pe.titre as expert_titre, pe.slug as expert_slug,
                   u.id as expert_utilisateur_id,
                   u.prenom as expert_prenom, u.nom as expert_nom, u.email as expert_email
            FROM {$this->table} dr
            JOIN profils_experts pe ON pe.id = dr.expert_id
            JOIN utilisateurs u ON u.id = pe.utilisateur_id
            ORDER BY dr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function updateStatut(int $id, string $statut): void
    {
        try {
            $this->db->prepare("UPDATE {$this->table} SET statut = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$statut, $id]);
        } catch (\Throwable $e) {
            $this->db->prepare("UPDATE {$this->table} SET statut = ? WHERE id = ?")
                ->execute([$statut, $id]);
        }
    }

    public function countEnAttente(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE statut = 'en_attente'");
        return (int) $stmt->fetchColumn();
    }
}
