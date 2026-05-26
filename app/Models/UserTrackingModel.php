<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Journal des actions / pages vues pour le tracking utilisateurs. */
class UserTrackingModel extends Model
{
    protected string $table = 'user_tracking';

    public function log(?int $utilisateurId, string $action = 'page_view', ?string $page = null): void
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        [$navigateur, $appareil] = self::parseUserAgent($ua);
        $pays = self::getCountryFromRequest();

        $data = [
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'page' => $page,
            'ip' => $ip,
            'user_agent' => $ua ?: null,
        ];
        if ($this->hasTrackingExtraColumns()) {
            $data['pays'] = $pays;
            $data['appareil'] = $appareil;
            $data['navigateur'] = $navigateur;
        }
        $this->insert($data);
    }

    /** Dernières activités pour l'admin (par utilisateur ou global). */
    public function getRecent(int $limit = 100, ?int $utilisateurId = null): array
    {
        $sql = "SELECT t.*, u.email, u.nom, u.prenom FROM {$this->table} t
                LEFT JOIN utilisateurs u ON u.id = t.utilisateur_id
                WHERE 1=1";
        $params = [];
        if ($utilisateurId !== null) {
            $sql .= " AND t.utilisateur_id = ?";
            $params[] = $utilisateurId;
        }
        $sql .= " ORDER BY t.created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $ua = $row['user_agent'] ?? '';
            if ($ua !== '' && (empty($row['navigateur']) && empty($row['appareil']))) {
                [$nav, $app] = self::parseUserAgent($ua);
                $row['navigateur'] = $row['navigateur'] ?? $nav;
                $row['appareil'] = $row['appareil'] ?? $app;
            }
            $row['pays'] = $row['pays'] ?? null;
            $row['appareil'] = $row['appareil'] ?? null;
            $row['navigateur'] = $row['navigateur'] ?? null;
        }
        unset($row);

        return $rows;
    }

    /** Version publique pour le debug admin. */
    public function hasTrackingExtraColumnsPublic(): bool
    {
        return $this->hasTrackingExtraColumns();
    }

    /** Vérifie si la table a les colonnes pays/appareil/navigateur — et les crée si absentes. */
    private function hasTrackingExtraColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'pays'");
            if ($stmt->rowCount() > 0) {
                $has = true;
                return true;
            }
            // Colonnes absentes → migration automatique
            $this->db->exec(
                "ALTER TABLE {$this->table}
                 ADD COLUMN `pays`       VARCHAR(2)   NULL DEFAULT NULL AFTER `ip`,
                 ADD COLUMN `appareil`   VARCHAR(30)  NULL DEFAULT NULL AFTER `pays`,
                 ADD COLUMN `navigateur` VARCHAR(50)  NULL DEFAULT NULL AFTER `appareil`"
            );
            $has = true;
        } catch (\Throwable $e) {
            $has = false;
        }
        return $has;
    }

    /** Retourne le pays depuis les headers (Cloudflare, GeoIP, ou fallback ip-api.com). */
    public static function getCountryFromRequest(): ?string
    {
        // Priorité 1 : en-tête Cloudflare ou module Apache GeoIP
        $code = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['GEOIP_COUNTRY_CODE'] ?? null;
        if ($code !== null && $code !== '' && $code !== 'XX') {
            return strtoupper(substr((string) $code, 0, 2));
        }

        // Priorité 2 : détection via ip-api.com (fallback, résultat mis en cache en session)
        $raw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip  = trim(explode(',', $raw)[0]);

        // IPs locales / privées → pas de lookup
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1'
            || strncmp($ip, '192.168.', 8) === 0
            || strncmp($ip, '10.',      3) === 0
            || strncmp($ip, '172.',     4) === 0) {
            return null;
        }

        // Cache session (valable 24 h)
        if (session_status() === PHP_SESSION_ACTIVE) {
            $cached = $_SESSION['_geo'][$ip] ?? null;
            if ($cached !== null) {
                return $cached === '' ? null : $cached;
            }
        }

        $country = null;
        $ctx  = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $body = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
        if ($body) {
            $json = @json_decode($body, true);
            if (!empty($json['countryCode'])) {
                $country = strtoupper(substr($json['countryCode'], 0, 2));
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_geo'][$ip] = $country ?? '';
        }

        return $country;
    }

    /** Parse le User-Agent et retourne [navigateur, appareil]. */
    public static function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return ['—', '—'];
        }
        $ua = ' ' . $ua . ' ';
        $navigateur = '—';
        if (stripos($ua, 'Edg/') !== false) {
            $navigateur = 'Edge';
        } elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            $navigateur = 'Opera';
        } elseif (stripos($ua, 'Chrome') !== false && stripos($ua, 'Chromium') === false) {
            $navigateur = 'Chrome';
        } elseif (stripos($ua, 'Firefox') !== false || stripos($ua, 'FxiOS') !== false) {
            $navigateur = 'Firefox';
        } elseif (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
            $navigateur = 'Safari';
        } elseif (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident/') !== false) {
            $navigateur = 'Internet Explorer';
        }

        $appareil = 'Desktop';
        if (preg_match('/\b(Mobile|Android|iPhone|webOS|BlackBerry|IEMobile|Opera Mini)\b/i', $ua)) {
            $appareil = 'Mobile';
        }
        if (preg_match('/\b(iPad|Tablet|PlayBook|Silk)\b/i', $ua)) {
            $appareil = 'Tablette';
        }

        return [$navigateur, $appareil];
    }

    /** Nombre de pages vues aujourd'hui. */
    public function getTodayPageViews(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE DATE(created_at) = CURDATE()"
            );
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Nombre de visiteurs uniques aujourd'hui (par IP). */
    public function getUniqueVisitorsToday(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT ip) FROM {$this->table} WHERE DATE(created_at) = CURDATE() AND ip IS NOT NULL"
            );
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Vues par jour sur les 7 derniers jours. Retourne [['date' => 'YYYY-MM-DD', 'views' => n], ...]. */
    public function getVisitsLast7Days(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT DATE(created_at) AS day, COUNT(*) AS views
                 FROM {$this->table}
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY day ORDER BY day ASC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Statistiques par pays (code ISO 2). Retourne [['pays' => 'SN', 'visits' => n], ...]. */
    public function getCountryStats(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pays, COUNT(*) AS visits
                 FROM {$this->table}
                 WHERE pays IS NOT NULL AND pays != ''
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY pays ORDER BY visits DESC LIMIT 50"
            );
            $stmt->execute([$days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Statistiques navigateurs et appareils. */
    public function getDeviceAndBrowserStats(int $days = 30): array
    {
        $result = ['browsers' => [], 'devices' => []];
        try {
            $stmt = $this->db->prepare(
                "SELECT user_agent FROM {$this->table}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 2000"
            );
            $stmt->execute([$days]);
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $browsers = [];
            $devices = [];
            foreach ($rows as $ua) {
                [$browser, $device] = self::parseUserAgent((string) $ua);
                $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;
                $devices[$device]   = ($devices[$device]   ?? 0) + 1;
            }
            arsort($browsers);
            arsort($devices);
            $result['browsers'] = $browsers;
            $result['devices']  = $devices;
        } catch (\Throwable $e) {
        }
        return $result;
    }

    /** Supprime plusieurs entrées par IDs. Retourne le nombre supprimé. */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        $ids = array_map('intval', array_filter($ids, function ($id) { return $id > 0; }));
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($ids));
        return $stmt->rowCount();
    }
}
