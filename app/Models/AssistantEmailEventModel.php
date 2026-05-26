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

    private function safeAlter(string $sql): void
    {
        try {
            $this->db->exec($sql);
        } catch (\Throwable $e) {
            // colonne déjà présente ou ALTER non applicable
        }
    }
}

