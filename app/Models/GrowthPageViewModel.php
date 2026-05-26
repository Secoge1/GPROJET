<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class GrowthPageViewModel extends Model
{
    protected string $table = 'growth_page_views';

    /**
     * Enregistre une vue de page SEO.
     * Peuple session_id pour permettre la déduplication des visites uniques.
     */
    public static function recordView(string $pageType, int $entityId): void
    {
        try {
            $db  = Database::getInstance();
            $sid = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $sid = substr(session_id(), 0, 64);
            }
            $stmt = $db->prepare(
                "INSERT INTO growth_page_views (page_type, entity_id, session_id, referer) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $pageType,
                $entityId,
                $sid,
                substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // table may not exist yet
        }
    }

    /**
     * Top experts par nombre de vues, avec nom, titre et slug en un seul SELECT.
     * Élimine le N+1 de l'ancien code du contrôleur.
     */
    public function getViewsByExpert(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    g.entity_id AS expert_id,
                    COUNT(*)    AS views,
                    COUNT(DISTINCT g.session_id) AS unique_sessions,
                    pe.titre    AS expert_titre,
                    pe.slug     AS slug,
                    u.prenom    AS expert_prenom,
                    u.nom       AS expert_nom
                FROM {$this->table} g
                LEFT JOIN profils_experts pe ON pe.id = g.entity_id
                LEFT JOIN utilisateurs u     ON u.id  = pe.utilisateur_id
                WHERE g.page_type = 'expert'
                GROUP BY g.entity_id, pe.titre, pe.slug, u.prenom, u.nom
                ORDER BY views DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Top missions/jobs par nombre de vues, avec titre et slug en un seul SELECT.
     */
    public function getViewsByJob(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    g.entity_id AS job_id,
                    COUNT(*)    AS views,
                    COUNT(DISTINCT g.session_id) AS unique_sessions,
                    d.titre     AS job_titre,
                    d.slug      AS slug
                FROM {$this->table} g
                LEFT JOIN demandes_assistance d ON d.id = g.entity_id
                WHERE g.page_type = 'job'
                GROUP BY g.entity_id, d.titre, d.slug
                ORDER BY views DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Top articles blog par nombre de vues (avec titre et slug si table blog_posts existe).
     */
    public function getViewsByBlog(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    g.entity_id AS blog_id,
                    COUNT(*)    AS views,
                    COUNT(DISTINCT g.session_id) AS unique_sessions,
                    COALESCE(b.titre, CONCAT('Article #', g.entity_id)) AS blog_titre,
                    b.slug      AS slug
                FROM {$this->table} g
                LEFT JOIN blog_posts b ON b.id = g.entity_id
                WHERE g.page_type = 'blog'
                GROUP BY g.entity_id, b.titre, b.slug
                ORDER BY views DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            // table blog_posts may not exist
            try {
                $stmt = $this->db->prepare("
                    SELECT entity_id AS blog_id, COUNT(*) AS views,
                           COUNT(DISTINCT session_id) AS unique_sessions,
                           CONCAT('Article #', entity_id) AS blog_titre, NULL AS slug
                    FROM {$this->table}
                    WHERE page_type = 'blog'
                    GROUP BY entity_id
                    ORDER BY views DESC
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                return $stmt->fetchAll();
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * Totaux groupés par type de page en une seule requête.
     */
    public function getTotalsByType(): array
    {
        $result = ['expert' => 0, 'job' => 0, 'blog' => 0];
        try {
            $stmt = $this->db->query(
                "SELECT page_type, COUNT(*) AS cnt FROM {$this->table} GROUP BY page_type"
            );
            while ($row = $stmt->fetch()) {
                $result[$row['page_type']] = (int) $row['cnt'];
            }
        } catch (\Throwable $e) {
        }
        return $result;
    }
}
