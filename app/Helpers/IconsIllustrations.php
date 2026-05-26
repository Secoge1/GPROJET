<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Charge le CSV d'illustrations des icônes système (data/icons_illustrations.csv).
 * Chaque ligne : id, label_fr, illustration_path, emoji_fallback, context
 */
class IconsIllustrations
{
    private static ?array $index = null;

    private static function load(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }
        $path = defined('ROOT_PATH') ? ROOT_PATH . '/data/icons_illustrations.csv' : dirname(__DIR__, 2) . '/data/icons_illustrations.csv';
        self::$index = [];
        if (!is_file($path)) {
            return self::$index;
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return self::$index;
        }
        $headers = fgetcsv($handle, 0, ',');
        if ($headers === false) {
            fclose($handle);
            return self::$index;
        }
        while (($row = fgetcsv($handle, 0, ',')) !== false && count($row) >= 4) {
            $id = trim($row[0] ?? '');
            if ($id === '') {
                continue;
            }
            self::$index[$id] = [
                'id'                 => $id,
                'label_fr'           => trim($row[1] ?? ''),
                'illustration_path'  => trim($row[2] ?? ''),
                'emoji_fallback'     => trim($row[3] ?? ''),
                'context'            => trim($row[4] ?? ''),
            ];
        }
        fclose($handle);
        return self::$index;
    }

    /**
     * Retourne les données d'une icône par son id.
     */
    public static function get(string $id): array
    {
        $index = self::load();
        return $index[$id] ?? [
            'id'                 => $id,
            'label_fr'           => '',
            'illustration_path'  => '',
            'emoji_fallback'     => '',
            'context'            => '',
        ];
    }

    /**
     * Retourne true si une illustration (fichier) est configurée et existe.
     */
    public static function hasIllustration(string $id, ?string $basePath = null): bool
    {
        $row = self::get($id);
        $path = $row['illustration_path'] ?? '';
        if ($path === '') {
            return false;
        }
        $base = $basePath ?? (defined('ROOT_PATH') ? ROOT_PATH . '/public' : dirname(__DIR__, 2) . '/public');
        $full = $base . '/' . str_replace('\\', '/', $path);
        return is_file($full);
    }

    /**
     * Retourne l'URL publique de l'illustration ou null.
     */
    public static function illustrationUrl(string $id, string $baseUrl = ''): ?string
    {
        $row = self::get($id);
        $path = $row['illustration_path'] ?? '';
        if ($path === '') {
            return null;
        }
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl . '/' . str_replace('\\', '/', $path);
    }
}
