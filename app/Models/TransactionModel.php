<?php
/**
 * GLOBALO - Modèle des transactions Mobile Money
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TransactionModel extends Model
{
    protected string $table = 'transactions';

    // ---------------------------------------------------------------
    // Création
    // ---------------------------------------------------------------

    /**
     * Crée une nouvelle transaction en statut "pending".
     * Retourne le payment_id généré.
     */
    public function createTransaction(
        int    $userId,
        float  $amount,
        float  $platformFee,
        string $phone,
        string $type = 'abonnement',
        string $abonnementType = 'client',
        string $provider = 'wave',
        string $ipAddress = '',
        string $userAgent = ''
    ): string {
        $paymentId   = $this->generatePaymentId();
        $totalAmount = round($amount + $platformFee, 2);

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (payment_id, user_id, amount, platform_fee, total_amount, currency,
                 phone, provider, status, type, abonnement_type, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, 'XOF', ?, ?, 'pending', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paymentId, $userId, $amount, $platformFee, $totalAmount,
            $phone, $provider, $type, $abonnementType,
            $ipAddress ?: null,
            $userAgent ? substr($userAgent, 0, 255) : null,
        ]);

        $this->log((int) $this->db->lastInsertId(), 'created', $userId, 'user');
        return $paymentId;
    }

    // ---------------------------------------------------------------
    // Lecture
    // ---------------------------------------------------------------

    public function findByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.prenom, u.nom, u.email
             FROM {$this->table} t
             LEFT JOIN utilisateurs u ON u.id = t.user_id
             WHERE t.payment_id = ?
             LIMIT 1"
        );
        $stmt->execute([$paymentId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByTransactionCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE transaction_code = ? LIMIT 1"
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Historique paiements d'un utilisateur. */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Toutes les transactions pour l'admin, avec infos utilisateur. */
    public function getAllForAdmin(
        ?string $status = null,
        ?string $provider = null,
        int $limit = 200
    ): array {
        $where  = [];
        $params = [];

        if ($status !== null) {
            $where[]  = "t.status = ?";
            $params[] = $status;
        }
        if ($provider !== null) {
            $where[]  = "t.provider = ?";
            $params[] = $provider;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $params[]    = $limit;

        $stmt = $this->db->prepare("
            SELECT t.*, u.prenom, u.nom, u.email
            FROM {$this->table} t
            JOIN utilisateurs u ON u.id = t.user_id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Transactions en attente de validation. */
    public function getPending(): array
    {
        return $this->getAllForAdmin('pending');
    }

    /** Statistiques agrégées pour le dashboard admin. */
    public function getStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'pending')  AS pending,
                SUM(status = 'success')  AS success,
                SUM(status = 'failed')   AS failed,
                COALESCE(SUM(CASE WHEN status='success' THEN total_amount ELSE 0 END), 0)   AS total_collecte,
                COALESCE(SUM(CASE WHEN status='success' THEN platform_fee ELSE 0 END), 0)   AS total_commission
            FROM {$this->table}
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    /** Mobile Money / Wave : pending sans code transaction saisi. */
    public function countPendingSansCode(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending' AND (transaction_code IS NULL OR transaction_code = '')"
            );

            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Mobile Money / Wave : code saisi, en attente de validation admin. */
    public function countPendingAValider(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending' AND transaction_code IS NOT NULL AND transaction_code != ''"
            );

            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ---------------------------------------------------------------
    // Mutations
    // ---------------------------------------------------------------

    /**
     * Enregistre le code de transaction Wave soumis par l'utilisateur.
     * Protection anti-double paiement : si le code existe déjà, retourne false.
     */
    public function submitTransactionCode(string $paymentId, string $code): bool
    {
        // Vérifier unicité du code (anti-fraude)
        if ($this->findByTransactionCode($code) !== null) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET transaction_code = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending' AND transaction_code IS NULL
        ");
        $stmt->execute([$code, $paymentId]);

        if ($stmt->rowCount() > 0) {
            $tx = $this->findByPaymentId($paymentId);
            if ($tx) {
                $this->log((int)$tx['id'], 'code_submitted', (int)$tx['user_id'], 'user', ['code' => $code]);
            }
            return true;
        }
        return false;
    }

    /**
     * Remplace un code placeholder (ex. ITP-*) par une référence saisie par l’utilisateur (InTouch).
     */
    public function replacePlaceholderTransactionCode(string $paymentId, string $newCode): bool
    {
        if ($this->findByTransactionCode($newCode) !== null) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET transaction_code = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending'
              AND transaction_code LIKE 'ITP-%'
        ");
        $stmt->execute([$newCode, $paymentId]);
        if ($stmt->rowCount() > 0) {
            $tx = $this->findByPaymentId($paymentId);
            if ($tx) {
                $this->log((int) $tx['id'], 'code_submitted', (int) $tx['user_id'], 'user', ['code' => $newCode]);
            }

            return true;
        }

        return false;
    }

    /**
     * Validation admin → status = success.
     * Retourne la transaction complète pour permettre l'activation de l'abonnement.
     */
    public function validate(string $paymentId, int $adminId, string $notes = ''): ?array
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'success', validated_by = ?, validated_at = NOW(),
                notes = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending' AND transaction_code IS NOT NULL
        ");
        $stmt->execute([$adminId, $notes ?: null, $paymentId]);

        if ($stmt->rowCount() === 0) {
            return null;
        }
        $tx = $this->findByPaymentId($paymentId);
        if ($tx) {
            $this->log((int)$tx['id'], 'validated', $adminId, 'admin', ['notes' => $notes]);
        }
        return $tx;
    }

    /**
     * Refus admin → status = failed.
     */
    public function refuse(string $paymentId, int $adminId, string $notes = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'failed', validated_by = ?, validated_at = NOW(),
                notes = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending'
        ");
        $stmt->execute([$adminId, $notes ?: null, $paymentId]);

        if ($stmt->rowCount() > 0) {
            $tx = $this->findByPaymentId($paymentId);
            if ($tx) {
                $this->log((int)$tx['id'], 'refused', $adminId, 'admin', ['notes' => $notes]);
            }
            return true;
        }
        return false;
    }

    /** Expire les transactions en attente depuis plus de 48h (cron). */
    public function expireOld(int $heures = 48): int
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'failed', notes = 'Expirée automatiquement', updated_at = NOW()
            WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$heures]);
        return $stmt->rowCount();
    }

    // ---------------------------------------------------------------
    // Logs (audit trail immuable)
    // ---------------------------------------------------------------

    public function log(
        int    $transactionId,
        string $action,
        ?int   $actorId = null,
        string $actorType = 'system',
        array  $meta = []
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO transaction_logs (transaction_id, action, actor_id, actor_type, meta)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $transactionId,
                $action,
                $actorId,
                $actorType,
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            // Ne pas bloquer l'application si la table de log est absente
        }
    }

    public function getLogsForTransaction(int $transactionId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, u.prenom, u.nom
                FROM transaction_logs l
                LEFT JOIN utilisateurs u ON u.id = l.actor_id
                WHERE l.transaction_id = ?
                ORDER BY l.created_at ASC
            ");
            $stmt->execute([$transactionId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Génère un payment_id unique et non-devinable.
     * Format : INT-{timestamp36}-{random8hex}
     */
    private function generatePaymentId(): string
    {
        do {
            $id = 'INT-' . strtoupper(base_convert((string) time(), 10, 36))
                . '-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->paymentIdExists($id));

        return $id;
    }

    /**
     * Confirmation automatique InTouch (webhook) : pending → success sans validation admin.
     */
    public function finalizeIntouchSuccess(string $paymentId, string $note = ''): ?array
    {
        $extCode = 'ITX-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt    = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'success', validated_by = NULL, validated_at = NOW(),
                transaction_code = COALESCE(NULLIF(TRIM(transaction_code), ''), ?),
                notes = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending' AND provider = 'intouch'
        ");
        $stmt->execute([$extCode, $note !== '' ? $note : null, $paymentId]);
        if ($stmt->rowCount() === 0) {
            return null;
        }
        $tx = $this->findByPaymentId($paymentId);
        if ($tx) {
            $this->log((int) $tx['id'], 'intouch_callback_success', null, 'system', ['note' => $note]);
        }
        return $tx;
    }

    /**
     * Confirmation automatique PayTech (IPN) : pending → success sans validation admin.
     */
    public function finalizePayTechSuccess(string $paymentId, string $note = ''): ?array
    {
        $extCode = 'PTX-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt    = $this->db->prepare("
            UPDATE {$this->table}
            SET status = 'success', validated_by = NULL, validated_at = NOW(),
                transaction_code = COALESCE(NULLIF(TRIM(transaction_code), ''), ?),
                notes = ?, updated_at = NOW()
            WHERE payment_id = ? AND status = 'pending' AND provider = 'paytech'
        ");
        $stmt->execute([$extCode, $note !== '' ? $note : null, $paymentId]);
        if ($stmt->rowCount() === 0) {
            return null;
        }
        $tx = $this->findByPaymentId($paymentId);
        if ($tx) {
            $this->log((int) $tx['id'], 'paytech_ipn_success', null, 'system', ['note' => $note]);
        }
        return $tx;
    }

    private function paymentIdExists(string $paymentId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE payment_id = ? LIMIT 1"
        );
        $stmt->execute([$paymentId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Supprimer une transaction par son ID (admin seulement). */
    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprimer plusieurs transactions par leur ID.
     * Seules les transactions dont le statut est 'failed' ou 'pending' sont supprimables
     * pour éviter d'effacer un historique de paiement validé.
     * @param int[] $ids
     */
    public function deleteByIds(array $ids, bool $onlyNonSuccess = true): int
    {
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $whereStatus  = $onlyNonSuccess ? " AND status != 'success'" : '';
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id IN ({$placeholders}){$whereStatus}"
        );
        $stmt->execute(array_values($ids));
        return $stmt->rowCount();
    }
}
