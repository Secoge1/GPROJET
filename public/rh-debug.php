<?php
/**
 * GLOBALO — Diagnostic module RH
 * Accédez à : https://globalo.secogesarl.com/rh-debug.php
 * SUPPRIMER ce fichier après diagnostic !
 */
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);
define('APP_PATH',  $rootPath . DIRECTORY_SEPARATOR . 'app');

echo '<style>body{font:14px monospace;padding:20px;background:#0f172a;color:#e2e8f0}
.ok{color:#34d399}.err{color:#f87171}.warn{color:#fbbf24}h2{color:#60a5fa;margin:16px 0 8px}
pre{background:#1e293b;padding:10px;border-radius:6px;overflow-x:auto}</style>';

echo '<h1 style="color:#fff">🔍 Diagnostic RH — GLOBALO</h1>';

// 1. PHP version
$php = PHP_VERSION;
$phpOk = version_compare($php, '7.4', '>=');
echo "<h2>1. PHP</h2>";
echo "<span class='" . ($phpOk ? 'ok' : 'err') . "'>PHP $php " . ($phpOk ? '✓' : '✗ (< 7.4)') . "</span><br>";

// 2. Fichiers clés
echo "<h2>2. Fichiers RH</h2>";
$files = [
    'app/Controllers/RhController.php'      => 'RhController',
    'app/Controllers/Api/RhAiController.php'=> 'RhAiController (API)',
    'app/Services/RhAiService.php'          => 'RhAiService',
    'app/Models/RhModel.php'                => 'RhModel',
    'app/Core/Router.php'                   => 'Router (modifié)',
    'app/Core/Controller.php'               => 'Controller (modifié)',
    'public/assets/css/rh.css'             => 'rh.css',
];
foreach ($files as $path => $label) {
    $full = $rootPath . '/' . $path;
    $exists = is_file($full);
    $size   = $exists ? filesize($full) . ' bytes' : '—';
    echo "<span class='" . ($exists ? 'ok' : 'err') . "'>" . ($exists ? '✓' : '✗') . " $label ($size)</span><br>";
}

// 3. Dossier vues RH
echo "<h2>3. Dossier vues</h2>";
$folders = [
    'app/Views/desktop/Rh'  => 'Rh/ (correct)',
    'app/Views/desktop/RH'  => 'RH/ (mauvaise casse Linux)',
];
foreach ($folders as $rel => $label) {
    $full = $rootPath . '/' . $rel;
    $exists = is_dir($full);
    $cls = ($rel === 'app/Views/desktop/Rh') ? ($exists ? 'ok' : 'err') : ($exists ? 'err' : 'ok');
    echo "<span class='$cls'>" . ($exists ? '✓ EXISTE' : '✗ absent') . " — $label</span><br>";
}

// 4. Vues individuelles
echo "<h2>4. Vues RH</h2>";
$views = ['dashboard.php','inscriptions.php','profils.php','marketing.php','manager.php','_chat_widget.php'];
foreach (['Rh','RH'] as $folder) {
    $baseV = $rootPath . '/app/Views/desktop/' . $folder . '/';
    if (!is_dir($baseV)) continue;
    echo "<strong>Dans Views/desktop/$folder/</strong><br>";
    foreach ($views as $v) {
        $exists = is_file($baseV . $v);
        echo "<span class='" . ($exists ? 'ok' : 'err') . "'>&nbsp;&nbsp;" . ($exists ? '✓' : '✗') . " $v</span><br>";
    }
}

// 5. Vérification syntaxe PHP des fichiers clés
echo "<h2>5. Syntaxe PHP</h2>";
$phpFiles = [
    'app/Controllers/RhController.php',
    'app/Services/RhAiService.php',
    'app/Models/RhModel.php',
    'app/Core/Router.php',
    'app/Core/Controller.php',
];
foreach ($phpFiles as $f) {
    $full = $rootPath . '/' . $f;
    if (!is_file($full)) { echo "<span class='warn'>⚠ Absent: $f</span><br>"; continue; }
    $out = shell_exec('php -l ' . escapeshellarg($full) . ' 2>&1');
    $ok  = strpos($out, 'No syntax errors') !== false;
    echo "<span class='" . ($ok ? 'ok' : 'err') . "'>" . ($ok ? '✓' : '✗') . " $f</span>";
    if (!$ok) echo "<pre>$out</pre>";
    else echo "<br>";
}

// 6. Connexion BDD + tables RH
echo "<h2>6. Base de données</h2>";
try {
    require_once $rootPath . '/app/bootstrap.php';
    $db = App\Core\Database::getInstance();
    echo "<span class='ok'>✓ Connexion BDD OK</span><br>";

    $tables = ['rh_ia_logs','rh_ia_analyses','rh_notes','rh_marketing_recommandations'];
    foreach ($tables as $t) {
        try {
            $db->query("SELECT 1 FROM `$t` LIMIT 1");
            echo "<span class='ok'>✓ Table $t existe</span><br>";
        } catch (\Exception $e) {
            echo "<span class='err'>✗ Table $t ABSENTE — " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    }

    // Tables existantes utilisées
    $tablesExist = ['utilisateurs','profils_experts','profils_professeurs','user_tracking'];
    echo "<br><strong>Tables existantes :</strong><br>";
    foreach ($tablesExist as $t) {
        try {
            $c = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "<span class='ok'>✓ $t ($c lignes)</span><br>";
        } catch (\Exception $e) {
            echo "<span class='err'>✗ $t — " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    }
} catch (\Exception $e) {
    echo "<span class='err'>✗ Connexion BDD échouée : " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// 7. Vérifier Router modifié
echo "<h2>7. Route /rh dans Router</h2>";
$routerFile = $rootPath . '/app/Core/Router.php';
$routerContent = is_file($routerFile) ? file_get_contents($routerFile) : '';
$hasRhRoute = strpos($routerContent, "'rh'") !== false && strpos($routerContent, "controller = 'Rh'") !== false;
echo "<span class='" . ($hasRhRoute ? 'ok' : 'err') . "'>" . ($hasRhRoute ? '✓ Route /rh présente dans Router.php' : '✗ Route /rh ABSENTE dans Router.php (fichier non uploadé)') . "</span><br>";

$hasRhLayout = false;
$controllerFile = $rootPath . '/app/Core/Controller.php';
if (is_file($controllerFile)) {
    $ctrl = file_get_contents($controllerFile);
    $hasRhLayout = strpos($ctrl, "controller === 'Rh'") !== false;
}
echo "<span class='" . ($hasRhLayout ? 'ok' : 'err') . "'>" . ($hasRhLayout ? '✓ Layout Rh dans Controller.php' : '✗ Layout Rh ABSENT dans Controller.php') . "</span><br>";

// 8. Config IA
echo "<h2>8. Config IA — Clés détectées</h2>";
$iaKeys = ['OPENAI_API_KEY'=>'OpenAI', 'MISTRAL_API_KEY'=>'Mistral (actif)', 'GEMINI_API_KEY'=>'Gemini (désactivé)'];
$anyKey = false;
foreach ($iaKeys as $envKey => $name) {
    $val = defined($envKey) ? constant($envKey) : (getenv($envKey) ?: '');
    if ($val) {
        $anyKey = true;
        $masked = substr($val, 0, 6) . '...' . substr($val, -4);
        echo "<span class='ok'>✓ $name : <code>$masked</code></span><br>";
    } else {
        $cls = ($envKey === 'GEMINI_API_KEY') ? 'ok' : 'warn';
        echo "<span class='$cls'>" . ($envKey === 'GEMINI_API_KEY' ? '✓' : '⚠') . " $name non configuré</span><br>";
    }
}
if (!$anyKey) echo "<span class='warn'>⚠ Aucune clé IA — mode sans IA activé (normal)</span><br>";

// 9. Test appel Mistral en direct
echo "<h2>9. Test API Mistral (appel réel)</h2>";
$mistralKeyVal = defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : (getenv('MISTRAL_API_KEY') ?: '');
if ($mistralKeyVal === '') {
    echo "<span class='warn'>⚠ MISTRAL_API_KEY non disponible — test ignoré</span><br>";
} else {
    $endpoint = 'https://api.mistral.ai/v1/chat/completions';
    $payload  = json_encode([
        'model'      => 'mistral-small-latest',
        'max_tokens' => 10,
        'messages'   => [
            ['role' => 'system', 'content' => 'Tu réponds en un seul mot.'],
            ['role' => 'user',   'content' => 'Dis juste "OK".'],
        ],
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $mistralKeyVal,
        ],
    ]);
    $raw      = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<span class='" . ($httpCode === 200 ? 'ok' : 'err') . "'>HTTP Code : $httpCode</span><br>";

    if ($curlErr) {
        echo "<span class='err'>✗ Erreur cURL : " . htmlspecialchars($curlErr) . "</span><br>";
    } elseif (!$raw) {
        echo "<span class='err'>✗ Réponse vide</span><br>";
    } else {
        $data = json_decode($raw, true);
        $text = trim($data['choices'][0]['message']['content'] ?? '');
        if ($text !== '') {
            echo "<span class='ok'>✓ Mistral répond : <strong>" . htmlspecialchars($text) . "</strong></span><br>";
            echo "<span class='ok'>✓ L'IA est opérationnelle !</span><br>";
        } elseif (!empty($data['message'])) {
            echo "<span class='err'>✗ Erreur Mistral : " . htmlspecialchars($data['message']) . "</span><br>";
        } else {
            echo "<span class='warn'>⚠ Réponse inattendue :</span><br>";
            echo "<pre>" . htmlspecialchars($raw) . "</pre>";
        }
    }
}

echo "<br><p style='color:#64748b;font-size:12px'>⚠️ SUPPRIMER ce fichier après diagnostic : public/rh-debug.php</p>";
