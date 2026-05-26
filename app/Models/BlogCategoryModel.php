<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BlogCategoryModel extends Model
{
    protected string $table = 'blog_categories';

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name");
        return $stmt->fetchAll() ?: [];
    }

    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
