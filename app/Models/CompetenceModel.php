<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CompetenceModel extends Model
{
    protected string $table = 'competences';

    public function getActives(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY categorie, nom";
        return $this->db->query($sql)->fetchAll();
    }

    /** Find competence by slug or by name (LIKE). For chatbot NL mapping. */
    public function findBySlugOrName(string $term): ?array
    {
        $term = trim($term);
        if ($term === '') {
            return null;
        }
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($term));
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE actif = 1 AND (slug = ? OR nom LIKE ?) LIMIT 1");
        $stmt->execute([$slug, '%' . $term . '%']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Return all active competences for intent context (id, nom, slug). */
    public function getActivesForChatbot(): array
    {
        $sql = "SELECT id, nom, slug, categorie FROM {$this->table} WHERE actif = 1 ORDER BY nom";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchSuggestions(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '' || $limit < 1) {
            return [];
        }
        $like = '%' . $q . '%';
        $lim  = min(30, $limit);
        $stmt = $this->db->prepare(
            "SELECT id, nom, slug, categorie FROM {$this->table}
             WHERE actif = 1 AND (nom LIKE ? OR categorie LIKE ? OR slug LIKE ?)
             ORDER BY (nom LIKE ?) DESC, nom ASC
             LIMIT {$lim}"
        );
        $prefix = $q . '%';
        $stmt->execute([$like, $like, $like, $prefix]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFeaturedForSearch(int $limit = 10): array
    {
        if ($limit < 1) {
            return [];
        }
        $lim  = min(30, $limit);
        $stmt = $this->db->prepare(
            "SELECT id, nom, slug, categorie FROM {$this->table}
             WHERE actif = 1 ORDER BY categorie, nom LIMIT {$lim}"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }
}
