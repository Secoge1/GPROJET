<?php
/**
 * GLOBALO — Générateur de clés VAPID (EC P-256)
 * Exécutez en ligne de commande : php generate_vapid_keys.php
 * Puis copiez les valeurs dans votre .htaccess ou variables d'environnement serveur.
 * SUPPRIMEZ ce fichier après utilisation (ne pas laisser accessible en ligne).
 */

// Sécurité : ne pas exécuter depuis le navigateur en production
if (isset($_SERVER['HTTP_HOST'])) {
    http_response_code(403);
    exit('Ce script est réservé à la ligne de commande.');
}

if (!extension_loaded('openssl')) {
    die("Extension OpenSSL requise.\n");
}

$key     = openssl_pkey_new(['ec' => ['curve_name' => 'prime256v1']]);
$details = openssl_pkey_get_details($key);

if (!$key || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
    die("Erreur de génération de clé. Vérifiez votre configuration OpenSSL.\n");
}

$pubFull  = chr(0x04) . $details['ec']['x'] . $details['ec']['y'];
$privRaw  = $details['ec']['d'];

function b64u($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$pub  = b64u($pubFull);
$priv = b64u($privRaw);

echo "\n";
echo "══════════════════════════════════════════════════════════\n";
echo "  GLOBALO — Clés VAPID générées avec succès\n";
echo "══════════════════════════════════════════════════════════\n\n";
echo "  Clé publique  (" . strlen($pubFull) . " octets):\n";
echo "  VAPID_PUBLIC_KEY={$pub}\n\n";
echo "  Clé privée (" . strlen($privRaw) . " octets — CONFIDENTIELLE) :\n";
echo "  VAPID_PRIVATE_KEY={$priv}\n\n";
echo "──────────────────────────────────────────────────────────\n";
echo "  Ajoutez dans votre .htaccess (racine) :\n\n";
echo "  SetEnv VAPID_PUBLIC_KEY  {$pub}\n";
echo "  SetEnv VAPID_PRIVATE_KEY {$priv}\n\n";
echo "  ⚠  Supprimez ce fichier après utilisation !\n";
echo "══════════════════════════════════════════════════════════\n\n";
