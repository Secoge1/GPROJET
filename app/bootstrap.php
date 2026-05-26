<?php
/**
 * GLOBALO - Bootstrap (chargement config + autoload)
 */

declare(strict_types=1);

// ── Fichier .env à la racine du projet (facultatif, non versionné) ──────────
// Charge KEY=VALUE avant config.php pour PAYTECH_*, BASE_URL, DB_*, etc.
if (!function_exists('globalo_load_dotenv')) {
    /**
     * Charge KEY=VALUE. Les éditeurs / hébergeurs coupent parfois les longues valeurs
     * (ex. PAYTECH_API_SECRET) sur plusieurs lignes physiques : une ligne sans "=" est
     * alors fusionnée à la fin de la variable définie juste au-dessus.
     */
    function globalo_load_dotenv(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        $raw = file($path, FILE_IGNORE_NEW_LINES);
        if ($raw === false) {
            return;
        }
        $lastKey = null;
        foreach ($raw as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                if ($lastKey !== null) {
                    $val = (string) ($_ENV[$lastKey] ?? '') . $line;
                    putenv($lastKey . '=' . $val);
                    $_ENV[$lastKey]     = $val;
                    $_SERVER[$lastKey] = $val;
                }
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if ($key === '' || strpos($key, ' ') !== false) {
                $lastKey = null;
                continue;
            }
            if ($val !== '') {
                $len = strlen($val);
                if ($len >= 2) {
                    $q = $val[0];
                    if (($q === '"' || $q === "'") && $val[$len - 1] === $q) {
                        $val = substr($val, 1, $len - 2);
                    }
                }
            }
            putenv($key . '=' . $val);
            $_ENV[$key]     = $val;
            $_SERVER[$key] = $val;
            $lastKey        = $key;
        }
    }
}

$_globaloRootForEnv = dirname(__DIR__);
$_globaloEnvFile    = $_globaloRootForEnv . DIRECTORY_SEPARATOR . '.env';
if (is_file($_globaloEnvFile)) {
    globalo_load_dotenv($_globaloEnvFile);
}
unset($_globaloRootForEnv, $_globaloEnvFile);

// ── Polyfills PHP 7.4 → PHP 8.0+ ─────────────────────────────────────────────
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || (strlen($needle) <= strlen($haystack) && substr($haystack, -strlen($needle)) === $needle);
    }
}

$_vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($_vendorAutoload)) {
    require_once $_vendorAutoload;
}
unset($_vendorAutoload);

require_once dirname(__DIR__) . '/config/config.php';

require_once APP_PATH . '/Core/Lang.php';
\App\Core\Lang::init();
if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Core\Lang::t($key, $replace);
    }
}
if (!function_exists('icon_illustration')) {
    /** Retourne les données d'une icône (illustration_path, emoji_fallback, label_fr) depuis data/icons_illustrations.csv */
    function icon_illustration(string $id): array {
        return \App\Helpers\IconsIllustrations::get($id);
    }
}
if (!function_exists('devise')) {
    /** Devise monétaire de la plateforme (ex. XOF pour FCFA Afrique de l'Ouest). */
    function devise(): string {
        try {
            return (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
        } catch (\Throwable $e) {
            return 'XOF';
        }
    }
}
if (!function_exists('logo_url')) {
    /** URL du logo de la plateforme (logo personnalisé uploadé en admin ou logo par défaut). */
    function logo_url(): string {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        try {
            $custom = (new \App\Models\ParametreModel())->get('logo_custom', '');
            if ($custom === '1') {
                return $base . '/fichier/logo';
            }
        } catch (\Throwable $e) {
            // Table parametres peut être absente
        }
        $imgDir = defined('ROOT_PATH') ? ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR : '';
        foreach (['logo.png', 'logo.svg', 'logo.jpg', 'logo.webp'] as $f) {
            if ($imgDir !== '' && is_file($imgDir . $f)) {
                return $base . '/assets/images/' . $f;
            }
        }
        return $base . '/assets/icons/icon.svg';
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
