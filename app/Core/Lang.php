<?php
/**
 * GLOBALO - Gestion des langues (FR / EN)
 */

declare(strict_types=1);

namespace App\Core;

class Lang
{
    private static ?string $locale = null;
    private static array $loaded = [];
    private static array $available = ['fr', 'en'];

    public static function init(): void
    {
        if (self::$locale !== null) {
            return;
        }
        // 1. Paramètre GET ?lang= en priorité (changement manuel)
        $requested = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : null;
        if ($requested && in_array($requested, self::$available, true)) {
            self::$locale = $requested;
            $_SESSION['lang'] = $requested;
            setcookie('lang', $requested, time() + 86400 * 365, '/');
            return;
        }
        // 2. Session (choix utilisateur déjà fait)
        if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], self::$available, true)) {
            self::$locale = $_SESSION['lang'];
            return;
        }
        // 3. Cookie
        if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], self::$available, true)) {
            self::$locale = $_COOKIE['lang'];
            $_SESSION['lang'] = self::$locale;
            return;
        }
        // 4. Défaut config (priorité sur la préférence navigateur pour que le site soit en français par défaut)
        self::$locale = defined('DEFAULT_LANG') ? (DEFAULT_LANG === 'en' ? 'en' : 'fr') : 'fr';
    }

    public static function setLocale(string $code): void
    {
        $code = strtolower($code);
        if (in_array($code, self::$available, true)) {
            self::$locale = $code;
            $_SESSION['lang'] = $code;
            setcookie('lang', $code, time() + 86400 * 365, '/');
        }
    }

    public static function getLocale(): string
    {
        if (self::$locale === null) {
            self::init();
        }
        return self::$locale ?? 'fr';
    }

    public static function getAvailable(): array
    {
        return self::$available;
    }

    /**
     * Traduction par clé. Clés au format "section.key" ou "key".
     */
    public static function t(string $key, array $replace = []): string
    {
        $locale = self::getLocale();
        if (!isset(self::$loaded[$locale])) {
            $path = defined('APP_PATH') ? APP_PATH . '/Lang/' . $locale . '.php' : __DIR__ . '/../Lang/' . $locale . '.php';
            self::$loaded[$locale] = is_file($path) ? (require $path) : [];
        }
        $value = self::$loaded[$locale][$key] ?? $key;
        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, (string) $v, $value);
        }
        return $value;
    }
}
