<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BlogPostModel extends Model
{
    protected string $table = 'blog_posts';

    public function getPublishedList(?int $categoryId = null, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug FROM {$this->table} p
                LEFT JOIN blog_categories c ON c.id = p.category_id
                WHERE p.published_at IS NOT NULL AND p.published_at <= NOW()";
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        $sql .= " ORDER BY p.published_at DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        $stmt = $params ? $this->db->prepare($sql) : $this->db->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll() ?: [];
    }

    public function getBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM {$this->table} p
                    LEFT JOIN blog_categories c ON c.id = p.category_id
                    WHERE p.slug = ? AND p.published_at IS NOT NULL AND p.published_at <= NOW() LIMIT 1");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTagsForPost(int $postId): array
    {
        $tagModel = new BlogTagModel();
        return $tagModel->getByPostId($postId);
    }

    public function getRelatedByCategory(int $categoryId, int $excludePostId, int $limit = 3): array
    {
        if ($categoryId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT id, title, slug, published_at FROM {$this->table} WHERE category_id = ? AND id != ? AND published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC LIMIT ?");
        $stmt->execute([$categoryId, $excludePostId, $limit]);
        return $stmt->fetchAll() ?: [];
    }
}
