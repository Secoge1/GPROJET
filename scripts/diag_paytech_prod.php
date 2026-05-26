<?php
/**
 * GLOBALO — Diagnostic PayTech production (navigateur)
 * Accès : https://globalo.secogesarl.com/scripts/diag_paytech_prod.php?s=VOTRE_SECRET
 *
 * Le DIAGNOSTIC_SECRET doit être défini dans .htaccess :
 *   SetEnv DIAGNOSTIC_SECRET votre_secret_ici
 * Supprimer ce fichier après diagnostic.
 */
declare(strict_types=1);

// ── Protection ──────────────────────────────────────────────────────
// Secret temporaire alphanumérique (sans caractères spéciaux pour Apache/ModSecurity)
const DIAG_TEMP_SECRET = 'globalo2026diag';

$dsec  = trim((string) (getenv('DIAGNOSTIC_SECRET') ?: ($_SERVER['DIAGNOSTIC_SECRET'] ?? '')));
$given = trim((string) ($_GET['s'] ?? ''));

// Accepter le secret serveur OU le secret temporaire codé en dur
$allowed = ($dsec !== '' && hash_equals($dsec, $given))
        || ($given !== '' && hash_equals(DIAG_TEMP_SECRET, $given));

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Accès refusé.\n";
    echo "Accédez avec : ?s=globalo2026diag\n";
    echo "Reçu : '" . htmlspecialchars($given) . "' (" . strlen($given) . " chars)\n";
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');

$root = dirname(__DIR__);
// Chargement bootstrap (sans sortie)
ob_start();
try {
    require_once $root . '/app/bootstrap.php';
} catch (Throwable $bt) {
    ob_end_clean();
    echo "ERREUR bootstrap: " . $bt->getMessage() . "\n";
    exit(1);
}
ob_end_clean();

$nl  = "\n";
$sep = str_repeat('=', 64) . $nl;

echo $sep;
echo "GLOBALO — Diagnostic PayTech prod — " . date('Y-m-d H:i:s T') . $nl;
echo "PHP " . PHP_VERSION . " | Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? '?') . $nl;
echo $sep;

// ── 1. Clés et variables d'environnement ──────────────────────
echo $nl . "[1] Variables d'environnement PayTech" . $nl;

$sources = [];
$key = '';
$sec = '';

// Source getenv()
$gKey = getenv('PAYTECH_API_KEY');
$gSec = getenv('PAYTECH_API_SECRET');
if ($gKey !== false && trim($gKey) !== '') {
    $key = trim($gKey);
    $sources[] = 'getenv()';
}
if ($gSec !== false && trim($gSec) !== '') {
    $sec = trim($gSec);
}

// Source $_SERVER
if ($key === '' && !empty($_SERVER['PAYTECH_API_KEY'])) {
    $key = trim((string) $_SERVER['PAYTECH_API_KEY']);
    $sources[] = '$_SERVER';
}
if ($sec === '' && !empty($_SERVER['PAYTECH_API_SECRET'])) {
    $sec = trim((string) $_SERVER['PAYTECH_API_SECRET']);
}

// Source constante PHP (chargée par config.php via _intouch_env)
if ($key === '' && defined('PAYTECH_API_KEY')) {
    $key = trim(PAYTECH_API_KEY);
    $sources[] = 'define(PAYTECH_API_KEY)';
}
if ($sec === '' && defined('PAYTECH_API_SECRET')) {
    $sec = trim(PAYTECH_API_SECRET);
}

$keyOk = $key !== '';
$secOk = $sec !== '';

echo "  PAYTECH_API_KEY    : " . ($keyOk ? substr($key, 0, 8) . '…(' . strlen($key) . 'c) via ' . implode(',', $sources) : '⚠ VIDE') . $nl;
echo "  PAYTECH_API_SECRET : " . ($secOk ? substr($sec, 0, 8) . '…(' . strlen($sec) . 'c)' : '⚠ VIDE') . $nl;
echo "  PAYTECH_ENV        : " . (getenv('PAYTECH_ENV') ?: (defined('PAYTECH_ENV') ? PAYTECH_ENV : '?')) . $nl;
echo "  BASE_URL           : " . (defined('BASE_URL') ? BASE_URL : '⚠ non défini') . $nl;
echo "  GLOBALO_ENV        : " . (defined('ENV') ? ENV : '?') . $nl;

// ── 2. .env présent sur le serveur ?
echo $nl . "[2] Fichier .env" . $nl;
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    echo "  ✔ .env présent (" . filesize($envFile) . " octets)" . $nl;
    // Chercher les clés dans .env sans exposer les valeurs
    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, 'PAYTECH_') === 0) {
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $v = trim($v, " \t\"'");
            echo "  .env $k: " . (strlen($v) > 0 ? strlen($v) . ' chars' : '⚠ VIDE') . $nl;
        }
    }
} else {
    echo "  ⚠ .env ABSENT du serveur" . $nl;
    echo "  ➤ Sans .env, les clés doivent venir de SetEnv dans .htaccess" . $nl;
}

// ── 3. .htaccess racine lisible ?
echo $nl . "[3] .htaccess (SetEnv)" . $nl;
$htRoot = $root . DIRECTORY_SEPARATOR . '.htaccess';
if (is_file($htRoot)) {
    $htLines = file($htRoot, FILE_IGNORE_NEW_LINES) ?: [];
    $found = 0;
    foreach ($htLines as $line) {
        if (stripos($line, 'SetEnv') !== false && stripos($line, 'PAYTECH') !== false) {
            $found++;
            // Masquer la valeur
            $masked = preg_replace('/(PAYTECH_\w+\s+)"?([a-f0-9]{8})[a-f0-9]+"?/', '$1$2…', $line);
            echo "  $masked" . $nl;
        }
    }
    echo "  SetEnv PAYTECH_* trouvés : $found" . $nl;
} else {
    echo "  ⚠ .htaccess racine ABSENT" . $nl;
}

// ── 4. Extension cURL
echo $nl . "[4] Extension cURL" . $nl;
if (!extension_loaded('curl')) {
    echo "  ✘ cURL non chargé" . $nl;
    exit(1);
}
echo "  ✔ cURL " . (curl_version()['version'] ?? '?') . " / " . (curl_version()['ssl_version'] ?? '?') . $nl;
$caInfo = ini_get('curl.cainfo');
echo "  curl.cainfo : " . ($caInfo ?: '(non défini — OS bundle)') . $nl;

// ── 5. Connectivité
echo $nl . "[5] Connectivité vers paytech.sn" . $nl;
$ch = curl_init('https://paytech.sn');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr !== '') {
    echo "  ✘ cURL error: $curlErr" . $nl;
    echo "  ➤ Outbound HTTPS bloqué ou bundle CA manquant sur ce serveur." . $nl;
} else {
    echo "  ✔ HTTP $httpCode — connexion OK" . $nl;
}

// ── 6. Appel API PayTech
echo $nl . "[6] POST API PayTech /payment/request-payment" . $nl;
if (!$keyOk || !$secOk) {
    echo "  ⚠ Ignoré — clés vides." . $nl;
} elseif ($curlErr !== '') {
    echo "  ⚠ Ignoré — connectivité KO." . $nl;
} else {
    $env     = getenv('PAYTECH_ENV') ?: (defined('PAYTECH_ENV') ? PAYTECH_ENV : 'prod');
    $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : 'https://globalo.secogesarl.com', '/');
    $refCmd  = 'diag_prod_' . time();
    $cf      = base64_encode((string) json_encode(['payment_id' => $refCmd, 'type' => 'diag']));
    $payload = http_build_query([
        'item_name'    => 'Diagnostic GLOBALO Prod',
        'item_price'   => 2500,
        'currency'     => 'XOF',
        'ref_command'  => $refCmd,
        'command_name' => 'Diagnostic PayTech',
        'env'          => $env,
        'ipn_url'      => $baseUrl . '/paytech/callback',
        'success_url'  => $baseUrl . '/paytech/paiement-reussi',
        'cancel_url'   => $baseUrl . '/paytech/paiement-annule',
        'custom_field' => $cf,
    ]);

    $ch = curl_init('https://paytech.sn/api/payment/request-payment');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'API_KEY: '    . $key,
            'API_SECRET: ' . $sec,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    echo "  HTTP code   : $code" . $nl;
    if ($cerr !== '') {
        echo "  ✘ cURL error: $cerr" . $nl;
    } else {
        echo "  Réponse brute: " . $raw . $nl;
        $resp = json_decode((string) $raw, true);
        if (!is_array($resp)) {
            echo "  ✘ JSON invalide" . $nl;
        } else {
            $s = $resp['success'] ?? null;
            $ok = ($s === true || $s === 1 || $s === '1') || (is_numeric($s) && (int) round((float) $s) === 1);
            echo "  success=" . json_encode($s) . " → " . ($ok ? '✔ succès' : '✘ ÉCHEC') . $nl;
            if ($ok) {
                $token = $resp['token'] ?? ($resp['data']['token'] ?? '');
                $rurl  = $resp['redirect_url'] ?? $resp['redirectUrl'] ?? ($resp['data']['redirect_url'] ?? '');
                if ($rurl === '' && $token !== '') {
                    $rurl = 'https://paytech.sn/payment/checkout/' . rawurlencode((string) $token);
                }
                echo "  token       : $token" . $nl;
                echo "  redirect_url: $rurl" . $nl;
                echo "  " . ($rurl !== '' && stripos($rurl, 'http') === 0 ? '✔ URL valide — la redirection fonctionnerait' : '✘ URL vide') . $nl;
            } else {
                $errs = is_array($resp['errors'] ?? null) ? implode(' | ', $resp['errors']) : ($resp['message'] ?? '');
                echo "  Erreur API  : $errs" . $nl;
                if (stripos($errs, 'auth') !== false || stripos($errs, 'key') !== false || $code === 401) {
                    echo "  ➤ CLÉS INVALIDES — vérifiez PAYTECH_API_KEY / PAYTECH_API_SECRET sur le tableau de bord PayTech." . $nl;
                }
            }
        }
    }
}

// ── 7. Test base de données (table transactions + CSRF session)
echo $nl . "[7] Test base de données" . $nl;
try {
    $pdo = null;
    // Connexion directe sans autoload complet
    $dbHost = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
    $dbName = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : '');
    $dbUser = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
    $dbPass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "  ✔ Connexion DB OK (MySQL " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ")" . $nl;

    // Vérifier la table transactions
    $colRows = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_ASSOC);
    $cols    = array_column($colRows, 'Field');
    echo "  Colonnes présentes : " . implode(', ', $cols) . $nl;

    $required = ['payment_id', 'user_id', 'amount', 'platform_fee', 'total_amount', 'currency',
                 'phone', 'provider', 'status', 'type', 'abonnement_type', 'ip_address', 'user_agent', 'notes'];
    $missing  = array_diff($required, $cols);
    if (empty($missing)) {
        echo "  ✔ Table transactions : toutes les colonnes requises présentes (y compris notes)" . $nl;
    } else {
        echo "  ✘ Colonnes manquantes : " . implode(', ', $missing) . $nl;
        if (in_array('notes', $missing, true)) {
            echo "  ➤ COLONNE notes ABSENTE — c'est la cause du crash après l'appel API PayTech !" . $nl;
            echo "  ➤ SQL de correction : ALTER TABLE transactions ADD COLUMN notes TEXT NULL AFTER user_agent;" . $nl;
        } else {
            echo "  ➤ CAUSE : schéma BDD incomplet — INSERT échouera à chaque paiement" . $nl;
        }
    }

    // Tester un INSERT fictif avec un user_id existant (le dernier user PayTech connu)
    $lastUserRow = $pdo->query("SELECT user_id FROM transactions WHERE provider='paytech' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $testUserId  = $lastUserRow['user_id'] ?? null;
    $fakeId = 'diag_' . bin2hex(random_bytes(4));
    if ($testUserId !== null) {
        try {
            $pdo->prepare("INSERT INTO transactions (payment_id, user_id, amount, platform_fee, total_amount, currency, phone, provider, status, type, abonnement_type) VALUES (?,?,2500,0,2500,'XOF','0000000000','paytech','pending','diag','client')")->execute([$fakeId, $testUserId]);
            // Tester aussi l'UPDATE notes (le point de rupture suspecté)
            if (!in_array('notes', $missing, true)) {
                try {
                    $pdo->prepare("UPDATE transactions SET notes = ? WHERE payment_id = ? LIMIT 1")->execute(['diag_test_note', $fakeId]);
                    echo "  ✔ UPDATE notes OK — la colonne notes fonctionne" . $nl;
                } catch (PDOException $ne) {
                    echo "  ✘ UPDATE notes ÉCHOUE : " . $ne->getMessage() . $nl;
                    echo "  ➤ C'est la cause exacte du crash avant la redirection PayTech !" . $nl;
                }
            }
            $pdo->prepare("DELETE FROM transactions WHERE payment_id = ?")->execute([$fakeId]);
            echo "  ✔ INSERT/DELETE test OK (user_id=$testUserId)" . $nl;
        } catch (PDOException $ie) {
            echo "  ✘ INSERT échoue : " . $ie->getMessage() . $nl;
            echo "  ➤ CAUSE PROBABLE : contrainte FK ou colonne manquante" . $nl;
        }
    } else {
        echo "  ⚠ Aucun user_id PayTech trouvé pour le test INSERT" . $nl;
    }

    // Vérifier les 5 dernières transactions PayTech (avec notes)
    $rows = $pdo->query("SELECT payment_id, user_id, status, type, notes, created_at FROM transactions WHERE provider='paytech' ORDER BY id DESC LIMIT 5")->fetchAll();
    if ($rows) {
        echo "  Dernières transactions PayTech :" . $nl;
        foreach ($rows as $r) {
            $notesPreview = $r['notes'] ? substr((string)$r['notes'], 0, 60) : '(vide)';
            echo "    [{$r['status']}] {$r['payment_id']} user={$r['user_id']} type={$r['type']} notes={$notesPreview} @{$r['created_at']}" . $nl;
        }
    } else {
        echo "  Aucune transaction PayTech trouvée en base" . $nl;
        echo "  ➤ Aucun paiement n'a encore atteint createTransaction()" . $nl;
    }

    // Migration optionnelle : ajouter colonne notes si absente
    if (!empty($missing) && in_array('notes', $missing, true) && isset($_GET['migrate']) && $_GET['migrate'] === '1') {
        echo $nl . "[7b] Migration automatique : ajout colonne notes" . $nl;
        try {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN notes TEXT NULL AFTER user_agent");
            echo "  ✔ ALTER TABLE OK — colonne notes ajoutée !" . $nl;
        } catch (PDOException $me) {
            echo "  ✘ ALTER TABLE échoue : " . $me->getMessage() . $nl;
        }
    } elseif (!empty($missing) && in_array('notes', $missing, true)) {
        echo "  ➤ Pour appliquer la migration automatiquement, ajoutez &migrate=1 à l'URL." . $nl;
    }
} catch (PDOException $e) {
    echo "  ✘ Erreur DB : " . $e->getMessage() . $nl;
}

// ── 8. Test session et CSRF
echo $nl . "[8] Session et CSRF" . $nl;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "  session_id  : " . session_id() . $nl;
echo "  session_save_path : " . session_save_path() . $nl;
echo "  session writable  : " . (is_writable(session_save_path() ?: sys_get_temp_dir()) ? '✔ oui' : '✘ NON') . $nl;
$testToken = bin2hex(random_bytes(8));
$_SESSION['_diag_test'] = $testToken;
echo "  CSRF test   : token écrit en session '" . substr($testToken, 0, 8) . "…'" . $nl;

// ── 9. Logs PHP récents (erreurs PayTech)
echo $nl . "[9] Dernières lignes error_log PHP (filtrées PayTech)" . $nl;
$errorLog = ini_get('error_log');
echo "  error_log : " . ($errorLog ?: '(non défini)') . $nl;
if ($errorLog && is_readable($errorLog)) {
    $lines = array_slice(file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -200);
    $found = 0;
    foreach (array_reverse($lines) as $line) {
        if (stripos($line, 'paytech') !== false || stripos($line, 'PayTech') !== false) {
            echo "  " . substr($line, 0, 200) . $nl;
            if (++$found >= 10) break;
        }
    }
    if ($found === 0) {
        echo "  (aucune ligne PayTech dans les 200 dernières lignes)" . $nl;
    }
} else {
    echo "  (error_log non accessible depuis ce script)" . $nl;
}

echo $nl . $sep;
echo "Diagnostic terminé. Supprimez ce fichier après utilisation." . $nl;
