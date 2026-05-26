<?php
/**
 * GLOBALO - Manifest PWA (téléchargeable)
 * Génère le JSON du manifest avec l'URL de base correcte.
 */
declare(strict_types=1);

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
if (!function_exists('logo_url')) {
    require_once dirname(__DIR__) . '/app/bootstrap.php';
}

$base     = rtrim(BASE_URL, '/');
$iconSvg  = $base . '/assets/icons/icon.svg';
$logoPng  = $base . '/assets/images/logo.png';
$logoAff  = $base . '/assets/images/globalo-logo-affiche.png';

// Détermine si un logo personnalisé a été uploadé en admin
$logoCustomUrl = null;
try {
    $custom = (new \App\Models\ParametreModel())->get('logo_custom', '');
    if ($custom === '1') {
        $logoCustomUrl = $base . '/fichier/logo';
    }
} catch (\Throwable $e) {}

// Construction des icônes — plusieurs tailles pour couvrir tous les OS
$icons = [
    // Icône principale SVG (toutes tailles, any)
    [
        'src'     => $iconSvg,
        'sizes'   => 'any',
        'type'    => 'image/svg+xml',
        'purpose' => 'any',
    ],
    // Maskable (Android adaptive icon — safe zone interne)
    [
        'src'     => $iconSvg,
        'sizes'   => '512x512',
        'type'    => 'image/svg+xml',
        'purpose' => 'maskable',
    ],
    // PNG fallback pour iOS/Samsung qui ne supportent pas SVG manifest
    [
        'src'     => $logoPng,
        'sizes'   => '512x512',
        'type'    => 'image/png',
        'purpose' => 'any',
    ],
    [
        'src'     => $logoPng,
        'sizes'   => '192x192',
        'type'    => 'image/png',
        'purpose' => 'any',
    ],
];

// Si logo custom uploadé en admin, il remplace les icônes PNG
if ($logoCustomUrl) {
    $icons[] = [
        'src'     => $logoCustomUrl,
        'sizes'   => 'any',
        'type'    => 'image/png',
        'purpose' => 'any maskable',
    ];
}

// Cache-Control court pour forcer la MAJ chez les utilisateurs existants
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$manifest = [
    'name'             => 'Globalo — Experts & Assistance',
    'short_name'       => 'Globalo',
    'description'      => 'Plateforme d\'assistance professionnelle à la demande. Disponible à Bamako et en Afrique de l\'Ouest.',
    'start_url'        => $base . '/app',
    'scope'            => $base . '/',
    'display'          => 'standalone',
    'orientation'      => 'any',
    'background_color' => '#ffffff',
    'theme_color'      => '#16a34a',
    'lang'             => 'fr',
    'categories'       => ['business', 'productivity'],
    'icons'            => $icons,
    'screenshots'      => [],
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
