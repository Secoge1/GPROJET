<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Journal des emails d'assistance envoyés automatiquement.
 */
class AssistantEmailEventModel extends Model
{
    protected string $table = 'assistant_email_events';

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT UNSIGNED NOT NULL,
            reason_code VARCHAR(64) NOT NULL,
            payload TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            recipient_email VARCHAR(190) NULL,
            recipient_name VARCHAR(190) NULL,
            subject VARCHAR(255) NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_reason_sent (utilisateur_id, reason_code, sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->exec($sql);

        // Migration légère pour instances déjà existantes.
        $this->safeAlter("ALTER TABLE {$this->table} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'sent' AFTER payload");
        $this->safeAlter("ALTER TABLE {$this->table} ADD COLUMN recipient_email VARCHAR(190) NULL AFTER status");
        $this->safeAlter("ALTER TABLE {$this->table} ADD COLUMN recipient_name VARCHAR(190) NULL AFTER recipient_email");
        $this->safeAlter("ALTER TABLE {$this->table} ADD COLUMN subject VARCHAR(255) NULL AFTER recipient_name");
    }

    public function wasSentRecently(int $userId, string $reasonCode, int $hours = 72): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table}
             WHERE utilisateur_id = ? AND reason_code = ? AND status IN ('sent','resent')
               AND sent_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             LIMIT 1"
        );
        $stmt->execute([$userId, $reasonCode, $hours]);
        return (bool) $stmt->fetchColumn();
    }

    public function logSent(
        int $userId,
        string $reasonCode,
        array $payload = [],
        ?string $recipientEmail = null,
        ?string $recipientName = null,
        ?string $subject = null,
        string $status = 'sent'
    ): void
    {
        $this->insert([
            'utilisateur_id' => $userId,
            'reason_code' => $reasonCode,
            'payload' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'status' => $status,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function getPaginated(int $offset, int $limit): array
    {
        $sql = "SELECT e.*, u.email AS user_email, u.prenom, u.nom
                FROM {$this->table} e
                LEFT JOIN utilisateurs u ON u.id = e.utilisateur_id
                ORDER BY e.sent_at DESC
                LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markResolved(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function markResent(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'resent' WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RAPPORT RELANCES ARIA & PROFIA
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Statistiques globales des relances ARIA et PROFIA.
     *
     * @return array{total:int,total_aria:int,total_profia:int,envoyes:int,echecs:int,ce_mois:int,cette_semaine:int}
     */
    public function getRelancesStats(): array
    {
        try {
            $stats = [];
            $stats['total'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%'"
            )->fetchColumn();
            $stats['total_aria'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'aria_%'"
            )->fetchColumn();
            $stats['total_profia'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'profia_%'"
            )->fetchColumn();
            $stats['envoyes'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE status IN ('sent','resent') AND (reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%')"
            )->fetchColumn();
            $stats['echecs'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE status = 'failed' AND (reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%')"
            )->fetchColumn();
            $stats['ce_mois'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE (reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%') AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )->fetchColumn();
            $stats['cette_semaine'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE (reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%') AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )->fetchColumn();
            return $stats;
        } catch (\Throwable $e) {
            return ['total' => 0, 'total_aria' => 0, 'total_profia' => 0, 'envoyes' => 0, 'echecs' => 0, 'ce_mois' => 0, 'cette_semaine' => 0];
        }
    }

    /**
     * Compte les relances filtrées par agent ('aria', 'profia', ou '' pour tous).
     */
    public function countRelances(string $agent = ''): int
    {
        try {
            if ($agent === 'aria') {
                return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'aria_%'")->fetchColumn();
            }
            if ($agent === 'profia') {
                return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'profia_%'")->fetchColumn();
            }
            return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE reason_code LIKE 'aria_%' OR reason_code LIKE 'profia_%'")->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Liste paginée des relances ARIA et/ou PROFIA.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRelancesPaginated(int $offset, int $limit, string $agent = ''): array
    {
        try {
            if ($agent === 'aria') {
                $where = "WHERE (e.reason_code LIKE 'aria_%')";
            } elseif ($agent === 'profia') {
                $where = "WHERE (e.reason_code LIKE 'profia_%')";
            } else {
                $where = "WHERE (e.reason_code LIKE 'aria_%' OR e.reason_code LIKE 'profia_%')";
            }
            $sql = "SELECT e.*, u.email AS user_email, u.prenom, u.nom, u.role
                    FROM {$this->table} e
                    LEFT JOIN utilisateurs u ON u.id = e.utilisateur_id
                    {$where}
                    ORDER BY e.sent_at DESC
                    LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeAlter(string $sql): void
    {
        try {
            $this->db->exec($sql);
        } catch (\Throwable $e) {
            // colonne déjà présente ou ALTER non applicable
        }
    }
}

