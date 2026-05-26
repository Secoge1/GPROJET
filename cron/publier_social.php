<?php
/**
 * GLOBALO — Cron de publication sociale automatique IA.
 *
 * Usage serveur Linux :
 *   # Lundi, mercredi, vendredi, samedi à 9h00 (heure locale serveur)
 *   0 9 * * 1,3,5,6 php /var/www/globalo/cron/publier_social.php >> /var/log/globalo_social.log 2>&1
 *
 * Usage GitHub Actions :
 *   curl -X POST https://globalo.secogesarl.com/api/cron/social \
 *        -H "X-Cron-Secret: VOTRE_SECRET"
 */

declare(strict_types=1);

// Chargement bootstrap
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);
define('APP_PATH',  $rootPath . DIRECTORY_SEPARATOR . 'app');

require_once APP_PATH . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Sécurité : uniquement depuis CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script ne peut être exécuté que depuis le CLI.');
}

use App\Services\SocialPublisherService;

$debut = date('Y-m-d H:i:s');
echo "[{$debut}] GLOBALO Social Publisher — démarrage\n";

$sujetForce = $argv[1] ?? null; // php publier_social.php "Mon sujet custom"

try {
    $publisher = new SocialPublisherService();
    $result    = $publisher->publierAuto($sujetForce);

    if (!empty($result['skipped'])) {
        echo "[SKIP] {$result['raison']}\n";
        exit(0);
    }

    if (!empty($result['error'])) {
        echo "[ERR] {$result['error']}\n";
        exit(1);
    }

    echo "[SUJET] {$result['sujet']}\n";
    echo "[CONTENU]\n" . $result['contenu'] . "\n\n";

    foreach ($result['publications'] as $reseau => $pub) {
        $status = $pub['ok'] ? 'OK' : 'ERREUR';
        $id     = $pub['post_id'] ?? '-';
        $err    = $pub['error']   ?? '';
        echo "[{$reseau}] {$status} — Post ID: {$id}" . ($err ? " — {$err}" : '') . "\n";
    }

    $fin = date('Y-m-d H:i:s');
    echo "[{$fin}] Publication terminée.\n";
    exit(0);

} catch (\Throwable $e) {
    $fin = date('Y-m-d H:i:s');
    echo "[{$fin}] EXCEPTION: " . $e->getMessage() . "\n";
    exit(1);
}
