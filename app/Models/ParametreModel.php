<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ParametreModel extends Model
{
    protected string $table = 'parametres';

    protected string $primaryKey = 'cle';

    public function get(string $cle, ?string $defaut = null): ?string
    {
        $stmt = $this->db->prepare("SELECT valeur FROM {$this->table} WHERE cle = ? LIMIT 1");
        $stmt->execute([$cle]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string) $v : $defaut;
    }

    public function getCommissionPercent(): float
    {
        $v = $this->get('commission_pourcent', (string) COMMISSION_DEFAULT);
        return (float) $v;
    }

    public function set(string $cle, string $valeur): void
    {
        $this->db->prepare("INSERT INTO {$this->table} (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?, updated_at = NOW()")
            ->execute([$cle, $valeur, $valeur]);
    }
}
