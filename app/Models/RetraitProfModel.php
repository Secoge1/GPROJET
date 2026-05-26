<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class RetraitProfModel extends Model
{
    protected string $table = 'demandes_retrait_prof';

    /** Historique des demandes d'un professeur (par utilisateur_id). */
    public function getByUtilisateur(int $utilisateurId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE utilisateur_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchAll();
    }

    /** Crée une demande de retrait (wallet déjà débité avant l'appel). */
    public function create(int $utilisateurId, float $montant, string $numeroWave): int
    {
        return $this->insert([
            'utilisateur_id' => $utilisateurId,
            'montant'        => $montant,
            'numero_wave'    => $numeroWave,
            'statut'         => 'en_attente',
        ]);
    }

    /** Met à jour le statut (+ traite_at si traitee). */
    public function updateStatut(int $id, string $statut): void
    {
        if ($statut === 'traitee') {
            $this->db->prepare(
                "UPDATE {$this->table} SET statut = ?, traite_at = NOW() WHERE id = ?"
            )->execute([$statut, $id]);
        } else {
            $this->db->prepare(
                "UPDATE {$this->table} SET statut = ? WHERE id = ?"
            )->execute([$statut, $id]);
        }
    }

    /** Marque traitée + enregistre la référence de transfert (ex. ID opérateur). */
    public function marquerTraitee(int $id, string $reference): void
    {
        $this->db->prepare(
            "UPDATE {$this->table}
             SET statut = 'traitee', reference = ?, traite_at = NOW()
             WHERE id = ?"
        )->execute([$reference, $id]);
    }

    /** Toutes les demandes pour l'admin (avec nom du professeur). */
    public function getAllForAdmin(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            "SELECT drp.*, u.prenom, u.nom, u.email, u.pays
             FROM {$this->table} drp
             JOIN utilisateurs u ON u.id = drp.utilisateur_id
             ORDER BY drp.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /** Nombre de demandes en attente (pour badge admin). */
    public function countEnAttente(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'en_attente'"
        );
        return (int) $stmt->fetchColumn();
    }
}
