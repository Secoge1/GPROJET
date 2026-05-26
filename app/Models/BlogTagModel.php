<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BlogTagModel extends Model
{
    protected string $table = 'blog_tags';

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name");
        return $stmt->fetchAll() ?: [];
    }

    public function getByPostId(int $postId): array
    {
        $stmt = $this->db->prepare("SELECT t.* FROM {$this->table} t JOIN blog_post_tags bpt ON bpt.tag_id = t.id WHERE bpt.post_id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetchAll() ?: [];
    }
}
