<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Trésorerie plateforme : commissions + montants en escrow.
 * Un seul enregistrement (id=1).
 */
class SoldePlateformeModel extends Model
{
    protected string $table = 'solde_plateforme';

    private const ID_PLATEFORME = 1;

    public function get(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([self::ID_PLATEFORME]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->db->prepare("INSERT INTO {$this->table} (id, solde, devise) VALUES (?, 0, 'XOF')")->execute([self::ID_PLATEFORME]);
            return $this->get();
        }
        return $row;
    }

    public function getSolde(): float
    {
        return (float) $this->get()['solde'];
    }

    /** Crédite le solde plateforme (ex: client paie → argent reçu). */
    public function crediter(float $montant): bool
    {
        if ($montant <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET solde = solde + ? WHERE id = ?");
        $stmt->execute([$montant, self::ID_PLATEFORME]);
        return $stmt->rowCount() > 0;
    }

    /** Débite le solde plateforme (ex: libération vers expert ou remboursement client). */
    public function debiter(float $montant): bool
    {
        if ($montant <= 0) {
            return false;
        }
        $solde = $this->getSolde();
        if ($solde < $montant) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET solde = solde - ? WHERE id = ? AND solde >= ?");
        $stmt->execute([$montant, self::ID_PLATEFORME, $montant]);
        return $stmt->rowCount() > 0;
    }
}
