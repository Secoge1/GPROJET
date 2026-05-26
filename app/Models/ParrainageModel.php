<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ParrainageModel extends Model
{
    protected string $table = 'parrainages';

    public function generateCode(): string
    {
        return bin2hex(random_bytes(8));
    }

    public function getByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Résolution par code type GLOBALO-12345 (nécessite la colonne referral_code). */
    public function getByReferralCode(string $referralCode): ?array
    {
        if ($referralCode === '') {
            return null;
        }
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE referral_code = ? OR code = ? LIMIT 1");
            $stmt->execute([$referralCode, $referralCode]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ? LIMIT 1");
            $stmt->execute([$referralCode]);
            return $stmt->fetch() ?: null;
        }
    }

    public function getOrCreateForUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parrain_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        $code = $this->generateCode();
        while ($this->getByCode($code)) {
            $code = $this->generateCode();
        }
        $this->insert(['parrain_id' => $userId, 'code' => $code, 'statut' => 'envoye']);
        $newId = (int) $this->db->lastInsertId();
        if ($newId > 0) {
            try {
                $refCode = 'GLOBALO-' . str_pad((string) $newId, 5, '0', STR_PAD_LEFT);
                $this->db->prepare("UPDATE {$this->table} SET referral_code = ? WHERE id = ?")->execute([$refCode, $newId]);
            } catch (\Throwable $e) {
                // referral_code column may not exist yet
            }
        }
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }

    public function getReferralLink(int $userId): string
    {
        $row = $this->getOrCreateForUser($userId);
        $base = rtrim(BASE_URL ?? '', '/');
        $code = $row['referral_code'] ?? $row['code'] ?? '';
        return $base . '/auth/inscription?ref=' . urlencode($code);
    }

    /** Code affiché à l'utilisateur (GLOBALO-12345). */
    public function getReferralCodeForUser(int $userId): string
    {
        $row = $this->getOrCreateForUser($userId);
        return $row['referral_code'] ?? $row['code'] ?? '';
    }

    public function registerFilleul(string $code, int $filleulUserId): bool
    {
        // Accepter soit le code hex, soit le referral_code (GLOBALO-00008)
        $row = $this->getByReferralCode($code);
        if (!$row || $row['filleul_id'] !== null) {
            return false;
        }
        // Empêcher l'auto-parrainage
        if ((int) $row['parrain_id'] === $filleulUserId) {
            return false;
        }
        $this->update((int) $row['id'], [
            'filleul_id' => $filleulUserId,
            'statut' => 'inscrit',
            'inscrit_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function getStatsForUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parrain_id = ? AND statut IN ('inscrit', 'recompense_parrain', 'recompense_filleul')");
        $stmt->execute([$userId]);
        $invites = (int) $stmt->fetchColumn();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parrain_id = ? AND statut IN ('recompense_parrain', 'recompense_filleul')");
        $stmt->execute([$userId]);
        $recompenses = (int) $stmt->fetchColumn();
        return ['invites' => $invites, 'recompenses' => $recompenses];
    }

    public function getAdminStats(): array
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE statut = 'inscrit'");
        $totalInscrits = (int) $stmt->fetchColumn();
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE statut IN ('recompense_parrain', 'recompense_filleul')");
        $totalRecompenses = (int) $stmt->fetchColumn();
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $total = (int) $stmt->fetchColumn();
        return ['total_parrainages' => $total, 'total_inscrits' => $totalInscrits, 'total_recompenses' => $totalRecompenses];
    }
}
