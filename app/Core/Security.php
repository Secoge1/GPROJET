<?php
/**
 * GLOBALO - Sécurité (CSRF, XSS, validation)
 */

declare(strict_types=1);

namespace App\Core;

class Security
{
    public static function generateCsrfToken(): string
    {
        $key = CSRF_TOKEN_NAME;
        $tok = $_SESSION[$key] ?? null;
        if (!is_string($tok) || $tok === '') {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[$key];
    }

    public static function getCsrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="' . htmlspecialchars(CSRF_TOKEN_NAME) . '" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '">';
    }

    public static function validateCsrf(): bool
    {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($_SESSION[CSRF_TOKEN_NAME]) && hash_equals((string) $_SESSION[CSRF_TOKEN_NAME], (string) $token);
    }

    public static function escape(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function sanitizeString(?string $value, int $maxLength = 0): string
    {
        if ($value === null) {
            return '';
        }
        $value = trim(strip_tags($value));
        if ($maxLength > 0) {
            $value = mb_substr($value, 0, $maxLength);
        }
        return $value;
    }

    public static function sanitizeEmail(?string $value): string
    {
        $value = filter_var(trim((string) $value), FILTER_SANITIZE_EMAIL);
        return $value ?: '';
    }

    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rate Limiting — protection brute-force (basé sur session + cache fichier)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'IP est bloquée pour une action donnée.
     * Stocke les tentatives dans $_SESSION + fichier cache partagé.
     *
     * @param string $action     Clé unique ex. 'login', 'reset'
     * @param int    $maxTries   Nombre maximal de tentatives autorisées
     * @param int    $windowSecs Fenêtre de temps en secondes
     * @return bool  true = bloqué, false = autorisé
     */
    public static function isRateLimited(string $action, int $maxTries = 5, int $windowSecs = 900): bool
    {
        $key   = 'rl_' . $action . '_' . self::getClientIp();
        $store = self::getRateLimitStore();

        $now = time();
        $entry = $store[$key] ?? ['count' => 0, 'since' => $now];

        // Réinitialise la fenêtre si elle est expirée
        if ($now - $entry['since'] >= $windowSecs) {
            $entry = ['count' => 0, 'since' => $now];
        }

        return $entry['count'] >= $maxTries;
    }

    /**
     * Enregistre une tentative échouée.
     *
     * @param string $action
     * @param int    $windowSecs
     */
    public static function recordFailedAttempt(string $action, int $windowSecs = 900): void
    {
        $key   = 'rl_' . $action . '_' . self::getClientIp();
        $store = self::getRateLimitStore();
        $now   = time();

        $entry = $store[$key] ?? ['count' => 0, 'since' => $now];
        if ($now - $entry['since'] >= $windowSecs) {
            $entry = ['count' => 0, 'since' => $now];
        }
        $entry['count']++;
        $store[$key] = $entry;
        self::saveRateLimitStore($store);
    }

    /** Remet à zéro le compteur après un succès. */
    public static function clearRateLimit(string $action): void
    {
        $key   = 'rl_' . $action . '_' . self::getClientIp();
        $store = self::getRateLimitStore();
        unset($store[$key]);
        self::saveRateLimitStore($store);
    }

    /** Retourne le nombre de tentatives restantes. */
    public static function remainingAttempts(string $action, int $maxTries = 5, int $windowSecs = 900): int
    {
        $key   = 'rl_' . $action . '_' . self::getClientIp();
        $store = self::getRateLimitStore();
        $now   = time();
        $entry = $store[$key] ?? ['count' => 0, 'since' => $now];
        if ($now - $entry['since'] >= $windowSecs) {
            return $maxTries;
        }
        return max(0, $maxTries - (int) $entry['count']);
    }

    // ── Helpers privés ───────────────────────────────────────────────────────

    private static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function getRateLimitFile(): string
    {
        $dir = defined('CACHE_PATH') ? CACHE_PATH : (defined('ROOT_PATH') ? ROOT_PATH . '/cache' : sys_get_temp_dir());
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir . '/ratelimit.json';
    }

    private static function getRateLimitStore(): array
    {
        $file = self::getRateLimitFile();
        if (!is_file($file)) {
            return [];
        }
        $data = @json_decode((string) @file_get_contents($file), true);
        if (!is_array($data)) {
            return [];
        }
        // Nettoyage : supprimer les entrées de plus de 2h
        $cutoff = time() - 7200;
        foreach ($data as $k => $v) {
            if (($v['since'] ?? 0) < $cutoff) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    private static function saveRateLimitStore(array $store): void
    {
        $file = self::getRateLimitFile();
        @file_put_contents($file, json_encode($store), LOCK_EX);
    }
}
