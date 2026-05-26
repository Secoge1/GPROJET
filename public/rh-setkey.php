<?php
/**
 * GLOBALO — Ajoute GEMINI_API_KEY dans .env du serveur
 * SUPPRIMER immédiatement après utilisation !
 */
$rootPath = dirname(__DIR__);
$envFile  = $rootPath . '/.env';
$key      = 'GEMINI_API_KEY';
$value    = 'AIzaSyBi4U6hv8AWluvYpOVSd-RduPkjizXD5q0';

echo '<style>body{font:15px monospace;background:#0f172a;color:#e2e8f0;padding:24px}
.ok{color:#34d399}.err{color:#f87171}</style>';
echo '<h2 style="color:#60a5fa">🔑 Ajout clé Gemini dans .env</h2>';

if (!is_file($envFile)) {
    echo '<span class="err">✗ Fichier .env introuvable à : ' . htmlspecialchars($envFile) . '</span>';
    exit;
}

$content = file_get_contents($envFile);

// Déjà configuré ?
if (strpos($content, $key . '=') !== false) {
    // Mettre à jour la valeur existante
    $content = preg_replace('/' . $key . '=.*/', $key . '=' . $value, $content);
    file_put_contents($envFile, $content);
    echo '<span class="ok">✓ Clé mise à jour dans .env</span><br>';
} else {
    // Ajouter après OPENAI_API_KEY ou à la fin
    if (strpos($content, 'OPENAI_API_KEY') !== false) {
        $content = preg_replace(
            '/(OPENAI_API_KEY=.*)/m',
            "$1\n\n# Google Gemini — IA RH\n" . $key . '=' . $value,
            $content
        );
    } else {
        $content .= "\n\n# Google Gemini — IA RH\n" . $key . '=' . $value . "\n";
    }
    file_put_contents($envFile, $content);
    echo '<span class="ok">✓ Clé ajoutée dans .env</span><br>';
}

// Vérification
$check = file_get_contents($envFile);
if (strpos($check, $value) !== false) {
    echo '<span class="ok">✓ Vérification OK — clé présente dans .env</span><br><br>';
    echo '<strong style="color:#34d399">✅ IA Gemini activée !</strong><br><br>';
    echo '<a href="/rh" style="background:#10b981;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block">→ Ouvrir l\'Espace RH</a>';
} else {
    echo '<span class="err">✗ Échec de l\'écriture — vérifiez les permissions sur .env</span><br>';
}

echo '<br><br><p style="color:#64748b;font-size:12px">⚠️ Supprimez ce fichier immédiatement : public/rh-setkey.php</p>';
