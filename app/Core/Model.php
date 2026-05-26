<?php
/**
 * GLOBALO - Modèle de base (accès BDD)
 */

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $this->sanitizeOrder($orderBy);
        }
        return $this->db->query($sql)->fetchAll();
    }

    protected function sanitizeOrder(string $order): string
    {
        return preg_replace('/[^a-z0-9_,\s]/i', '', $order) ?: $this->primaryKey;
    }

    public function insert(array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );
        $this->db->prepare($sql)->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "{$col} = ?";
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s = ?', $this->table, implode(', ', $sets), $this->primaryKey);
        $values = array_values($data);
        $values[] = $id;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
