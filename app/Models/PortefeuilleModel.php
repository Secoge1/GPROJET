<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PortefeuilleModel extends Model
{
    protected string $table = 'portefeuilles';

    public function getByUtilisateur(int $utilisateurId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE utilisateur_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getOrCreateForUser(int $utilisateurId): array
    {
        $p = $this->getByUtilisateur($utilisateurId);
        if ($p) {
            return $p;
        }
        $this->db->prepare("INSERT INTO {$this->table} (utilisateur_id, solde, devise) VALUES (?, 0, 'XOF')")
            ->execute([$utilisateurId]);
        $p = $this->getByUtilisateur($utilisateurId);
        return $p ?: [];
    }

    public function getSolde(int $utilisateurId): float
    {
        $p = $this->getOrCreateForUser($utilisateurId);
        return (float) ($p['solde'] ?? 0);
    }

    /** Débite le portefeuille (montant > 0). Retourne true si solde suffisant et opération faite. */
    public function debiter(int $utilisateurId, float $montant, string $reference = ''): bool
    {
        if ($montant <= 0) {
            return false;
        }
        $this->getOrCreateForUser($utilisateurId);
        // Décrémentation atomique : WHERE solde >= ? empêche tout découvert même en cas de requêtes concurrentes.
        $stmt = $this->db->prepare("UPDATE {$this->table} SET solde = solde - ? WHERE utilisateur_id = ? AND solde >= ?");
        $stmt->execute([$montant, $utilisateurId, $montant]);
        return $stmt->rowCount() > 0;
    }

    /** Crédite le portefeuille (montant > 0). */
    public function crediter(int $utilisateurId, float $montant): bool
    {
        if ($montant <= 0) {
            return false;
        }
        $this->getOrCreateForUser($utilisateurId);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET solde = solde + ? WHERE utilisateur_id = ?");
        $stmt->execute([$montant, $utilisateurId]);
        return $stmt->rowCount() > 0;
    }
}
