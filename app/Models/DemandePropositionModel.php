<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DemandePropositionModel extends Model
{
    protected string $table = 'demande_propositions';

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['statut'] = 'en_attente';
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return $this->insert($data);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT p.*, pe.titre AS expert_titre, pe.tarif_horaire AS expert_tarif_defaut,
                       u.prenom AS expert_prenom, u.nom AS expert_nom, u.avatar AS expert_avatar,
                       u.id AS expert_utilisateur_id
                FROM {$this->table} p
                JOIN profils_experts pe ON pe.id = p.expert_id
                JOIN utilisateurs u ON u.id = pe.utilisateur_id
                WHERE p.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function getByDemande(int $demandeId, ?string $statut = null): array
    {
        $sql = "SELECT p.*, pe.titre AS expert_titre, u.prenom AS expert_prenom, u.nom AS expert_nom,
                       u.avatar AS expert_avatar, pe.valide_par_admin AS expert_valide
                FROM {$this->table} p
                JOIN profils_experts pe ON pe.id = p.expert_id
                JOIN utilisateurs u ON u.id = pe.utilisateur_id
                WHERE p.demande_id = ?";
        $params = [$demandeId];
        if ($statut !== null) {
            $sql .= ' AND p.statut = ?';
            $params[] = $statut;
        }
        $sql .= ' ORDER BY p.statut = \'en_attente\' DESC, p.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getByDemandeAndExpert(int $demandeId, int $expertProfilId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE demande_id = ? AND expert_id = ? LIMIT 1"
        );
        $stmt->execute([$demandeId, $expertProfilId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function countEnAttenteByDemande(int $demandeId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE demande_id = ? AND statut = 'en_attente'"
        );
        $stmt->execute([$demandeId]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['en_attente', 'acceptee', 'refusee', 'retiree'], true)) {
            return false;
        }

        return $this->update($id, ['statut' => $statut, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function refuserAutresPourDemande(int $demandeId, int $exceptId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET statut = 'refusee', updated_at = NOW()
             WHERE demande_id = ? AND id != ? AND statut = 'en_attente'"
        );
        $stmt->execute([$demandeId, $exceptId]);
    }
}
