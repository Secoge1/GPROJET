<?php
/**
 * Affichage public : URL avatar (/uploads/), drapeau pays (emoji).
 */
declare(strict_types=1);

namespace App\Helpers;

final class PublicUserPresentation
{
    /** @var array<string, string> clé normalisée (sans accent, lower) => ISO 3166-1 alpha-2 */
    private const PAYS_TO_ISO2 = [
        'mali' => 'ML', 'ml' => 'ML',
        'senegal' => 'SN', 'sénégal' => 'SN', 'sn' => 'SN',
        "cote d'ivoire" => 'CI', 'côte d\'ivoire' => 'CI', "côte d'ivoire" => 'CI',
        'cote divoire' => 'CI', 'ivory coast' => 'CI', 'ci' => 'CI',
        'niger' => 'NE', 'ne' => 'NE',
        'france' => 'FR', 'fr' => 'FR',
        'belgique' => 'BE', 'be' => 'BE',
        'canada' => 'CA', 'ca' => 'CA',
        'maroc' => 'MA', 'ma' => 'MA',
        'tunisie' => 'TN', 'tn' => 'TN',
        'algerie' => 'DZ', 'algérie' => 'DZ', 'dz' => 'DZ',
        'togo' => 'TG', 'tg' => 'TG',
        'benin' => 'BJ', 'bénin' => 'BJ', 'bj' => 'BJ',
        'ghana' => 'GH', 'gh' => 'GH',
        'guinee' => 'GN', 'guinée' => 'GN', 'gn' => 'GN',
        'cameroun' => 'CM', 'cm' => 'CM',
        'rdc' => 'CD', 'congo' => 'CG',
        'usa' => 'US', 'united states' => 'US', 'états-unis' => 'US', 'etats-unis' => 'US',
    ];

    public static function defaultAvatarUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/assets/images/avatar-default.svg';
    }

    /** URL publique (fichiers sous /uploads/). Auth /fichier/user-avatar exclu pour les listes publiques. */
    public static function publicAvatarUrl(?string $avatarColumn, string $baseUrl): string
    {
        $path = $avatarColumn !== null ? trim((string) $avatarColumn) : '';
        if ($path === '') {
            return self::defaultAvatarUrl($baseUrl);
        }
        $norm = self::safeUploadRelativePath($path);
        if ($norm === null) {
            return self::defaultAvatarUrl($baseUrl);
        }

        return rtrim($baseUrl, '/') . '/uploads/' . $norm;
    }

    public static function hasUploadedAvatar(?string $avatarColumn): bool
    {
        return self::safeUploadRelativePath($avatarColumn) !== null;
    }

    /** Chemin relatif sécurisé sous uploads/ (pas de ..).
     *  Supprime aussi le préfixe "uploads/" stocké en base par d'anciennes versions. */
    public static function safeUploadRelativePath(?string $avatarColumn): ?string
    {
        if ($avatarColumn === null || trim((string) $avatarColumn) === '') {
            return null;
        }
        $norm = ltrim(str_replace('\\', '/', trim((string) $avatarColumn)), '/');
        if ($norm === '' || strpos($norm, '..') !== false) {
            return null;
        }
        // Normaliser le préfixe legacy "uploads/" enregistré en base
        if (stripos($norm, 'uploads/') === 0) {
            $norm = substr($norm, strlen('uploads/'));
        }
        if ($norm === '') {
            return null;
        }

        return $norm;
    }

    public static function normalizeCountryIso2(?string $pays): ?string
    {
        if ($pays === null) {
            return null;
        }
        $t = trim($pays);
        if ($t === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z]{2}$/', $t)) {
            return strtoupper($t);
        }
        $key = trim(preg_replace('/\s+/u', ' ', self::stripAccents(mb_strtolower($t))));
        return self::PAYS_TO_ISO2[$key] ?? null;
    }

    public static function countryFlagEmoji(?string $pays): string
    {
        $iso = self::normalizeCountryIso2($pays);
        if ($iso === null || !preg_match('/^[A-Z]{2}$/', $iso)) {
            return '';
        }
        $a = 0x1F1E6 + (ord($iso[0]) - 65);
        $b = 0x1F1E6 + (ord($iso[1]) - 65);
        if ($a < 0x1F1E6 || $a > 0x1F1FF || $b < 0x1F1E6 || $b > 0x1F1FF) {
            return '';
        }

        return self::utf8FromCodePoint($a) . self::utf8FromCodePoint($b);
    }

    /** Drapeaux régionaux (U+1F1E6…U+1F1FF) : repli si ext/mbstring indisponible. */
    private static function utf8FromCodePoint(int $cp): string
    {
        if (function_exists('mb_chr')) {
            $c = mb_chr($cp, 'UTF-8');
            return $c !== false ? $c : '';
        }
        if ($cp <= 0x7F) {
            return chr($cp);
        }
        if ($cp <= 0x7FF) {
            return chr(0xC0 | $cp >> 6) . chr(0x80 | $cp & 0x3F);
        }
        if ($cp <= 0xFFFF) {
            return chr(0xE0 | $cp >> 12)
                . chr(0x80 | ($cp >> 6) & 0x3F)
                . chr(0x80 | $cp & 0x3F);
        }
        if ($cp <= 0x10FFFF) {
            return chr(0xF0 | $cp >> 18)
                . chr(0x80 | ($cp >> 12) & 0x3F)
                . chr(0x80 | ($cp >> 6) & 0x3F)
                . chr(0x80 | $cp & 0x3F);
        }

        return '';
    }

    /** Libellé pour attribut title sur le drapeau */
    public static function countryLabel(?string $pays): string
    {
        if ($pays === null || trim($pays) === '') {
            return '';
        }
        $iso = self::normalizeCountryIso2($pays);

        return trim($pays) . ($iso ? ' (' . $iso . ')' : '');
    }

    private static function stripAccents(string $s): string
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== '') {
            return strtolower($t);
        }

        return strtolower($s);
    }
}
