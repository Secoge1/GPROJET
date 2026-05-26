<?php
/**
 * GLOBALO - Modèle Matières universitaires
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MatiereModel extends Model
{
    protected string $table = 'matieres_universitaires';

    /** Toutes les matières actives, triées par catégorie puis ordre. */
    public function getActives(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM matieres_universitaires WHERE actif = 1 ORDER BY categorie, ordre, nom'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Matières actives groupées par catégorie. */
    public function getActivesGrouped(): array
    {
        $matieres = $this->getActives();
        $grouped = [];
        foreach ($matieres as $m) {
            $grouped[$m['categorie']][] = $m;
        }
        return $grouped;
    }

    /** Chercher une matière par slug. */
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM matieres_universitaires WHERE slug = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Récupère plusieurs matières par leurs IDs. */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM matieres_universitaires WHERE id IN ({$placeholders}) AND actif = 1");
        $stmt->execute(array_map('intval', $ids));
        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchSuggestions(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if ($q === '' || $limit < 1) {
            return [];
        }
        $like = '%' . $q . '%';
        $lim  = min(25, $limit);
        $stmt = $this->db->prepare(
            "SELECT id, nom, slug, categorie FROM {$this->table}
             WHERE actif = 1 AND (nom LIKE ? OR categorie LIKE ? OR slug LIKE ?)
             ORDER BY nom ASC
             LIMIT {$lim}"
        );
        $stmt->execute([$like, $like, $like]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFeaturedForSearch(int $limit = 8): array
    {
        if ($limit < 1) {
            return [];
        }
        $lim  = min(25, $limit);
        $stmt = $this->db->prepare(
            "SELECT id, nom, slug, categorie FROM {$this->table}
             WHERE actif = 1 ORDER BY categorie, ordre, nom LIMIT {$lim}"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }
}
