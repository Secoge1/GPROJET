<?php
/**
 * GLOBALO - Authentification et autorisation
 */

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const SESSION_USER_KEY = 'user_id';
    private const SESSION_ROLE_KEY = 'user_role';

    /** Rôle applicatif normalisé (client, expert, professeur, etudiant, admin). */
    public static function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        if (in_array($role, ['client', 'expert', 'professeur', 'etudiant', 'admin'], true)) {
            return $role;
        }

        return 'client';
    }

    public static function login(int $userId, string $role): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = $userId;
        $_SESSION[self::SESSION_ROLE_KEY] = self::normalizeRole($role);
        $now = time();
        $_SESSION['last_activity'] = $now;
        $_SESSION['_last_activity'] = $now;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        if (empty($_SESSION[self::SESSION_USER_KEY])) {
            return false;
        }
        if (SESSION_LIFETIME > 0 && !empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        $now = time();
        $_SESSION['last_activity'] = $now;
        $_SESSION['_last_activity'] = $now;
        return true;
    }

    /** URL du tableau de bord selon le rôle (post-connexion, accès refusé à un espace). */
    public static function dashboardUrl(?string $role = null): string
    {
        $base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
        $role = self::normalizeRole((string) ($role ?? self::role() ?? ''));
        if ($role === 'admin') {
            return $base . '/admin';
        }
        if ($role === 'expert') {
            return $base . '/expert';
        }
        if ($role === 'professeur') {
            return $base . '/professeur';
        }
        if ($role === 'etudiant') {
            return $base . '/etudiant';
        }
        if ($role === 'client') {
            return $base . '/client';
        }

        return $base . '/';
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::SESSION_USER_KEY] ?? null;
        return $id !== null ? (int) $id : null;
    }

    public static function role(): ?string
    {
        return isset($_SESSION[self::SESSION_ROLE_KEY]) ? (string) $_SESSION[self::SESSION_ROLE_KEY] : null;
    }

    public static function isClient(): bool
    {
        return self::role() === 'client';
    }

    public static function isExpert(): bool
    {
        return self::role() === 'expert';
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isEtudiant(): bool
    {
        return self::role() === 'etudiant';
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (self::isApiRequest()) {
                header('Content-Type: application/json', true, 401);
                echo json_encode(['error' => 'Non authentifié']);
                exit;
            }
            header('Location: ' . rtrim(BASE_URL, '/') . '/auth/connexion');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!in_array(self::role(), $roles, true)) {
            if (self::isApiRequest()) {
                header('Content-Type: application/json', true, 403);
                echo json_encode(['error' => 'Accès refusé']);
                exit;
            }
            header('Location: ' . self::dashboardUrl());
            exit;
        }
    }

    private static function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
}
