<?php
/**
 * GLOBALO — Diagnostic SMTP production (navigateur)
 * Accès : https://globalo.secogesarl.com/scripts/diag_smtp.php?s=globalo2026smtp
 *
 * Ce script teste la connexion SMTP étape par étape et affiche le résultat exact.
 * SUPPRIMER ce fichier après diagnostic.
 */
declare(strict_types=1);
define('DIAG_START', microtime(true));

// ── Protection ───────────────────────────────────────────────────────────────
const DIAG_SMTP_SECRET = 'globalo2026smtp';

$given = trim((string) ($_GET['s'] ?? ''));
if (!hash_equals(DIAG_SMTP_SECRET, $given)) {
    http_response_code(403);
    exit('Accès refusé. Ajoutez ?s=globalo2026smtp à l\'URL.');
}

// Récupérer l'email de test dès le début (utilisé dans sections 4 et 6)
$testTo = trim((string) ($_GET['to'] ?? ''));

// ── Bootstrap minimal (charge .env + config) ─────────────────────────────────
$root = dirname(__DIR__);
if (is_file($root . '/app/bootstrap.php')) {
    require_once $root . '/app/bootstrap.php';
}
if (is_file($root . '/config/config.php')) {
    require_once $root . '/config/config.php';
}

// ── Helpers d'affichage ───────────────────────────────────────────────────────
function diag_ok(string $msg): void   { echo "<div class='ok'>✅ {$msg}</div>\n"; }
function diag_err(string $msg): void  { echo "<div class='err'>❌ {$msg}</div>\n"; }
function diag_warn(string $msg): void { echo "<div class='warn'>⚠️  {$msg}</div>\n"; }
function diag_info(string $msg): void { echo "<div class='info'>ℹ️  {$msg}</div>\n"; }
function diag_code(string $msg): void { echo "<div class='code'>" . htmlspecialchars($msg) . "</div>\n"; }
function section(string $title): void { echo "<h2>{$title}</h2>\n"; }

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnostic SMTP — GLOBALO</title>
<style>
*{box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;margin:0;padding:24px 16px;color:#0f172a;font-size:14px}
.wrap{max-width:820px;margin:0 auto;background:#fff;border-radius:14px;padding:28px 32px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
h1{font-size:1.4rem;margin:0 0 6px;color:#0f172a}
.subtitle{color:#64748b;font-size:.85rem;margin:0 0 24px}
h2{font-size:.95rem;font-weight:700;color:#334155;margin:28px 0 10px;padding-bottom:6px;border-bottom:1px solid #e2e8f0}
.ok  {background:#f0fdf4;color:#166534;border:1px solid #86efac;border-radius:8px;padding:8px 12px;margin:5px 0}
.err {background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;border-radius:8px;padding:8px 12px;margin:5px 0;word-break:break-all}
.warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;margin:5px 0}
.info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;border-radius:8px;padding:8px 12px;margin:5px 0}
.code{background:#1e293b;color:#e2e8f0;border-radius:8px;padding:10px 14px;margin:5px 0;font-family:monospace;font-size:.8rem;white-space:pre-wrap;word-break:break-all}
.badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:.75rem;font-weight:700}
.badge-ok{background:#dcfce7;color:#15803d}
.badge-err{background:#fee2e2;color:#dc2626}
form{margin-top:20px;padding:16px 18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}
label{display:block;font-size:.82rem;font-weight:600;color:#334155;margin-bottom:5px}
input[type=email]{width:100%;padding:.55rem .85rem;border:1.5px solid #cbd5e1;border-radius:8px;font-size:13px;margin-bottom:10px}
button{padding:.55rem 1.2rem;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.delete-warn{margin-top:28px;padding:12px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;font-size:12px;color:#991b1b}
</style>
</head>
<body>
<div class="wrap">
<h1>🔧 Diagnostic SMTP — GLOBALO</h1>
<p class="subtitle">Analyse complète de la configuration et de la connexion SMTP</p>

<?php

// ════════════════════════════════════════════════════════════════════════
// 1. CONFIGURATION PHP
// ════════════════════════════════════════════════════════════════════════
section('1. Configuration PHP');

diag_info('Version PHP : ' . PHP_VERSION);

if (extension_loaded('openssl')) {
    diag_ok('Extension OpenSSL chargée — ' . OPENSSL_VERSION_TEXT);
} else {
    diag_err('Extension OpenSSL NON chargée — impossible d\'utiliser TLS/SSL.');
}

if (function_exists('stream_socket_client')) {
    diag_ok('stream_socket_client() disponible');
} else {
    diag_err('stream_socket_client() NON disponible — connexion SMTP impossible.');
}

$wrappers = stream_get_wrappers();
if (in_array('ssl', $wrappers, true)) {
    diag_ok('Wrapper SSL disponible dans stream_get_wrappers()');
} else {
    diag_err('Wrapper SSL absent de stream_get_wrappers()');
}

// ════════════════════════════════════════════════════════════════════════
// 2. PARAMÈTRES SMTP DÉTECTÉS
// ════════════════════════════════════════════════════════════════════════
section('2. Paramètres SMTP détectés');

// Lecture identique à MailerService
function diag_get_param(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (class_exists(\App\Models\ParametreModel::class)) {
        $val = (new \App\Models\ParametreModel())->get($key, null);
        if ($val !== null && $val !== '') return (string) $val;
    }
    return $default;
}

$smtpHost   = diag_get_param('smtp_host',   diag_get_param('SMTP_HOST',   ''));
$smtpPort   = (int) diag_get_param('smtp_port', diag_get_param('SMTP_PORT', '587'));
$smtpUser   = diag_get_param('smtp_user',   diag_get_param('SMTP_USER',   ''));
$smtpPassRaw = getenv('SMTP_PASS');
if ($smtpPassRaw === false || $smtpPassRaw === '') {
    $smtpPassRaw = '';
    if (class_exists(\App\Models\ParametreModel::class)) {
        $dbPass = (new \App\Models\ParametreModel())->get('smtp_pass', null);
        $smtpPassRaw = $dbPass ?? '';
    }
}
$smtpSecureRaw = diag_get_param('smtp_secure', diag_get_param('SMTP_SECURE', ''));
$mailFrom      = getenv('MAIL_FROM') ?: diag_get_param('mail_from', diag_get_param('plateforme_email', ''));

diag_code("SMTP_HOST   : " . ($smtpHost ?: '(vide)'));
diag_code("SMTP_PORT   : " . $smtpPort);
diag_code("SMTP_USER   : " . ($smtpUser ?: '(vide)'));
diag_code("SMTP_PASS   : " . ($smtpPassRaw !== '' ? str_repeat('•', min(strlen($smtpPassRaw), 8)) . ' (' . strlen($smtpPassRaw) . ' caractères)' : '(VIDE ⚠️)'));
diag_code("SMTP_SECURE : " . ($smtpSecureRaw ?: '(vide)'));
diag_code("MAIL_FROM   : " . ($mailFrom ?: '(vide)'));

if ($smtpHost === '') {
    diag_err('SMTP_HOST vide — SMTP non configuré. Renseignez smtp_host dans Admin → Paramètres.');
}
if ($smtpPassRaw === '') {
    diag_err('SMTP_PASS vide — authentification impossible. Ajoutez le mot de passe dans Admin → Paramètres ou dans .env.');
}
if ($smtpUser === '') {
    diag_err('SMTP_USER vide — identifiant manquant.');
}

$implicitTls = (($smtpSecureRaw === 'tls' || $smtpSecureRaw === '1') && $smtpPort === 465);
$startTls    = (($smtpSecureRaw === 'tls' || $smtpSecureRaw === '1') && !$implicitTls);

if ($implicitTls) {
    diag_info("Mode chiffrement : SSL/SMTPS implicite (tls:// sur port 465)");
} elseif ($startTls) {
    diag_info("Mode chiffrement : STARTTLS (port {$smtpPort})");
} else {
    diag_warn("Mode chiffrement : aucun (connexion non chiffrée — déconseillé)");
}

// ════════════════════════════════════════════════════════════════════════
// 3. TEST DE CONNEXION TCP (sans TLS)
// ════════════════════════════════════════════════════════════════════════
section('3. Test de connexion TCP brute (sans TLS)');

if ($smtpHost !== '') {
    $errno = 0; $errstr = '';
    $sock = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 8);
    if ($sock) {
        // Timeout de lecture court : sur port 465 (SMTPS implicite), le serveur
        // envoie du TLS binaire sans saut de ligne — fgets() bloquerait 60s sans ça.
        stream_set_timeout($sock, 3);
        $banner = fgets($sock, 512);
        $tmeta  = stream_get_meta_data($sock);
        fclose($sock);
        diag_ok("Connexion TCP réussie sur {$smtpHost}:{$smtpPort}");
        $bannerStr = ($tmeta['timed_out'] ?? false)
            ? '(port SMTPS — pas de bannière texte en TCP brut, normal)'
            : trim((string) $banner);
        diag_code("Bannière serveur : " . $bannerStr);
    } else {
        diag_err("Connexion TCP impossible sur {$smtpHost}:{$smtpPort} — [{$errno}] {$errstr}");
        diag_warn("Le serveur est inaccessible ou le port est bloqué par le pare-feu de l'hébergement.");
    }
} else {
    diag_warn('Test TCP ignoré — SMTP_HOST vide.');
}

// Helper lecture SMTP (utilisé dans section 4 et 6)
$this_smtp_line = function($sock, ?string $cmd): string {
    if ($cmd !== null) { fwrite($sock, $cmd . "\r\n"); }
    $r = '';
    $t = microtime(true);
    while (microtime(true) - $t < 9) {
        $s = fgets($sock, 512);
        if ($s === false) break;
        $r .= $s;
        if (strlen($s) >= 4 && $s[3] === ' ') break;
    }
    return $r;
};

// ════════════════════════════════════════════════════════════════════════
// 4. TEST DE CONNEXION TLS (stream_socket_client)
// ════════════════════════════════════════════════════════════════════════
section('4. Test de connexion TLS (stream_socket_client)');

if ($smtpHost !== '' && extension_loaded('openssl')) {
    $prefix  = $implicitTls ? 'tls://' : '';
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ]);
    $errno = 0; $errstr = '';
    $sock2 = @stream_socket_client(
        $prefix . $smtpHost . ':' . $smtpPort,
        $errno, $errstr, 10,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if ($sock2) {
        diag_ok("Connexion TLS réussie sur {$prefix}{$smtpHost}:{$smtpPort}");

        stream_set_timeout($sock2, 8);

        $banner = $this_smtp_line($sock2, null);
        diag_code("Bannière : " . trim($banner));

        $ehlo = $this_smtp_line($sock2, 'EHLO diagnostic');
        diag_code("EHLO response :\n" . trim($ehlo));

        if ($startTls) {
            $stResp = $this_smtp_line($sock2, 'STARTTLS');
            diag_code("STARTTLS response : " . trim($stResp));
            if (strpos($stResp, '220') !== false) {
                $crypto = stream_socket_enable_crypto($sock2, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $crypto ? diag_ok('Négociation TLS (STARTTLS) réussie') : diag_err('Négociation TLS (STARTTLS) échouée');
                if ($crypto) {
                    $ehlo2 = $this_smtp_line($sock2, 'EHLO diagnostic');
                    diag_code("EHLO post-TLS :\n" . trim($ehlo2));
                }
            }
        }

        if ($smtpUser !== '' && $smtpPassRaw !== '') {
            $authResp = $this_smtp_line($sock2, 'AUTH LOGIN');
            diag_code("AUTH LOGIN response : " . trim($authResp));
            $userResp = $this_smtp_line($sock2, base64_encode($smtpUser));
            diag_code("Username response : " . trim($userResp));
            $passResp = $this_smtp_line($sock2, base64_encode($smtpPassRaw));
            diag_code("Password response : " . trim($passResp));
            if (strpos($passResp, '235') !== false) {
                diag_ok('Authentification SMTP réussie ✅');
            } else {
                diag_err('Authentification SMTP échouée — ' . trim($passResp));
            }
        } else {
            diag_warn('Test AUTH ignoré — identifiant ou mot de passe vide.');
        }

        // ── Transaction complète : MAIL FROM → RCPT TO → DATA ───────────────
        if ($smtpUser !== '' && $smtpPassRaw !== '' && $testTo !== '' && filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            echo "<h3 style='font-size:.85rem;color:#334155;margin:14px 0 6px;'>Transaction complète (MAIL FROM → DATA)</h3>\n";
            if (ob_get_level()) { ob_flush(); } flush();

            stream_set_timeout($sock2, 8);

            $mailFrom = $mailFrom ?: $smtpUser;

            $rf = $this_smtp_line($sock2, "MAIL FROM:<{$mailFrom}>");
            diag_code("MAIL FROM response : " . trim($rf));
            if (ob_get_level()) { ob_flush(); } flush();

            if (strpos($rf, '250') !== false) {
                $rr = $this_smtp_line($sock2, "RCPT TO:<{$testTo}>");
                diag_code("RCPT TO response  : " . trim($rr));
                if (ob_get_level()) { ob_flush(); } flush();

                if (strpos($rr, '250') !== false || strpos($rr, '251') !== false) {
                    $rd = $this_smtp_line($sock2, 'DATA');
                    diag_code("DATA response     : " . trim($rd));
                    if (ob_get_level()) { ob_flush(); } flush();

                    if (strpos($rd, '354') !== false) {
                        $body  = "Subject: Test Diagnostic GLOBALO\r\n";
                        $body .= "From: {$mailFrom}\r\n";
                        $body .= "To: {$testTo}\r\n";
                        $body .= "MIME-Version: 1.0\r\n";
                        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
                        $body .= "Test SMTP diagnostic GLOBALO.\r\n.\r\n";
                        fwrite($sock2, $body);
                        if (ob_get_level()) { ob_flush(); } flush();
                        diag_info("Corps email envoyé — attente réponse serveur (max 8s)...");
                        if (ob_get_level()) { ob_flush(); } flush();

                        $rFinal = $this_smtp_line($sock2, null);
                        $meta   = stream_get_meta_data($sock2);
                        if ($meta['timed_out'] ?? false) {
                            diag_err("⏱ TIMEOUT : le serveur n'a pas répondu dans les 8 secondes après DATA. C'est ici que MailerService bloque.");
                        } elseif (strpos($rFinal, '250') !== false) {
                            diag_ok("✅ Email envoyé avec succès ! Réponse : " . trim($rFinal));
                        } else {
                            diag_err("Réponse après DATA : " . trim($rFinal));
                        }
                        if (ob_get_level()) { ob_flush(); } flush();
                    } else {
                        diag_err("DATA refusé.");
                    }
                } else {
                    diag_err("RCPT TO refusé : " . trim($rr));
                }
            } else {
                diag_err("MAIL FROM refusé : " . trim($rf));
            }
        }

        fwrite($sock2, "QUIT\r\n");
        fclose($sock2);
    } else {
        diag_err("Connexion TLS impossible : [{$errno}] {$errstr}");

        // Essai sans TLS pour diagnositquer
        diag_info("Tentative sans TLS pour isoler le problème...");
        $sock3 = @stream_socket_client(
            $smtpHost . ':' . $smtpPort,
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT
        );
        $sock3
            ? diag_warn("Connexion sans TLS réussie → le problème vient du TLS (certificat, version, etc.)")
            : diag_err("Connexion sans TLS aussi impossible → le port {$smtpPort} est bloqué par le pare-feu.");
        if ($sock3) fclose($sock3);
    }
} else {
    diag_warn('Test TLS ignoré — SMTP_HOST vide ou OpenSSL absent.');
}

// ════════════════════════════════════════════════════════════════════════
// 5. VÉRIFICATION DE LA VERSION DE MailerService.php
// ════════════════════════════════════════════════════════════════════════
section('5. Version de MailerService.php déployée');

$mailerFile = $root . '/app/Services/MailerService.php';
if (!is_file($mailerFile)) {
    diag_err('Fichier MailerService.php introuvable !');
} else {
    $mailerSrc = file_get_contents($mailerFile);
    // Vérification des correctifs critiques
    $checks = [
        'verify_peer'               => ['SSL permissif (verify_peer=false)',                  true],
        'applySmtpDotStuffing'      => ['Dot-stuffing RFC 5321',                              true],
        'smtpEhloHostname'          => ['EHLO hostname dynamique',                            true],
        'stream_set_timeout'        => ['Timeout socket (stream_set_timeout)',                 true],
        'while ($written < $total)' => ['smtpWrite() en boucle (correctif deadlock)',         true],
        'unread_bytes'              => ['smtpLine() robuste (unread_bytes + stream_select)',   true],
    ];
    foreach ($checks as $needle => [$label, $required]) {
        if (strpos($mailerSrc, $needle) !== false) {
            diag_ok("{$label} ✅");
        } else {
            $required
                ? diag_err("{$label} ABSENT ❌ — uploadez la dernière version de MailerService.php !")
                : diag_warn("{$label} absent — version ancienne.");
        }
    }
    $mtime = filemtime($mailerFile);
    diag_info('Dernière modification du fichier : ' . date('d/m/Y H:i:s', $mtime));
}

// ════════════════════════════════════════════════════════════════════════
// 6. TEST D'ENVOI VIA MAILERSERVICE (avec capture des erreurs PHP)
// ════════════════════════════════════════════════════════════════════════
section('6. Test d\'envoi via MailerService');
// Flush immédiat : affiche le titre même si la suite bloque
if (ob_get_level()) { ob_flush(); } flush();

// Étendre la limite d'exécution PHP le plus tôt possible
@set_time_limit(120);
$scriptStartTime = defined('DIAG_START') ? DIAG_START : microtime(true);
diag_info('Temps écoulé depuis le début : ' . round(microtime(true) - $scriptStartTime, 1) . 's — max_execution_time : ' . ini_get('max_execution_time') . 's');
if (ob_get_level()) { ob_flush(); } flush();

if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
    echo '<form method="GET">';
    echo '<input type="hidden" name="s" value="' . htmlspecialchars(DIAG_SMTP_SECRET) . '">';
    echo '<label for="to">Adresse email de destination (pour tester MailerService) :</label>';
    echo '<input type="email" id="to" name="to" placeholder="votre@email.com" required>';
    echo '<button type="submit">Envoyer via MailerService</button>';
    echo '</form>';
} else {

    // Capturer les erreurs PHP générées pendant l'envoi
    $phpErrors = [];
    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) use (&$phpErrors): bool {
        // Ignorer les erreurs supprimées par l'opérateur @
        if (!(error_reporting() & $errno)) { return true; }
        $phpErrors[] = "[{$errno}] {$errstr} en {$errfile} ligne {$errline}";
        return true;
    });

    // Rediriger error_log vers un fichier temporaire pour capture
    $diagLogFile = sys_get_temp_dir() . '/globalo_diag_smtp_' . getmypid() . '.log';
    @unlink($diagLogFile);
    $prevLog = ini_get('error_log');
    ini_set('error_log', $diagLogFile);

    if (!class_exists(\App\Services\MailerService::class)) {
        diag_err('Classe MailerService introuvable — bootstrap non chargé.');
        if (ob_get_level()) { ob_flush(); } flush();
    } else {
        diag_info('Instanciation de MailerService...');
        if (ob_get_level()) { ob_flush(); } flush();

        $mailer = new \App\Services\MailerService();

        diag_info('MailerService instancié. Vérification SMTP...');
        if (ob_get_level()) { ob_flush(); } flush();

        if (!$mailer->isSmtpConfigured()) {
            diag_err('MailerService : SMTP non configuré (smtpHost vide).');
            if (ob_get_level()) { ob_flush(); } flush();
        } else {

            // ── 6a. Test plain text (email court, sans HTML) ────────────────
            diag_info("6a. Envoi d'un email texte court via MailerService vers : {$testTo}");
            if (ob_get_level()) { ob_flush(); } flush();

            $t0 = microtime(true);
            $sentPlain = $mailer->send($testTo, 'Test court GLOBALO', 'Email de test court.');
            $e0 = round(microtime(true) - $t0, 2);
            if (ob_get_level()) { ob_flush(); } flush();

            if ($sentPlain) {
                diag_ok("6a ✅ Email court envoyé ({$e0}s) — smtpLine() fonctionne.");
            } else {
                diag_err("6a ❌ Email court échoué ({$e0}s) — smtpLine() bloque même sur email court.");
            }
            if (ob_get_level()) { ob_flush(); } flush();

            // ── 6b. Test HTML complet ───────────────────────────────────────
            diag_info("6b. Envoi d'un email HTML via MailerService vers : {$testTo}");
            if (ob_get_level()) { ob_flush(); } flush();

            $html = '<p>Bonjour,</p><p>Ceci est un <strong>email de test de diagnostic</strong> envoyé depuis GLOBALO.</p><p>Si vous recevez ce message, la configuration SMTP fonctionne correctement. ✅</p>';
            $t1   = microtime(true);
            $sentHtml = $mailer->sendHtml($testTo, 'Test SMTP Diagnostic — GLOBALO', $html);
            $e1   = round(microtime(true) - $t1, 2);
            if (ob_get_level()) { ob_flush(); } flush();

            if ($sentHtml) {
                diag_ok("6b ✅ Email HTML envoyé ({$e1}s) — configuration opérationnelle !");
            } else {
                diag_err("6b ❌ sendHtml() a retourné false ({$e1}s).");
            }
            if (ob_get_level()) { ob_flush(); } flush();
        }
    }

    // Restaurer error_log
    ini_set('error_log', $prevLog);
    restore_error_handler();

    // Afficher les erreurs PHP capturées
    if (!empty($phpErrors)) {
        diag_warn("Erreurs PHP pendant l'envoi :");
        foreach ($phpErrors as $e) { diag_code($e); }
        if (ob_get_level()) { ob_flush(); } flush();
    }

    // Lire le log capturé
    if (is_file($diagLogFile)) {
        $logContent = trim((string) file_get_contents($diagLogFile));
        if ($logContent !== '') {
            diag_warn("Messages error_log (détail des échecs SMTP) :");
            diag_code($logContent);
        }
        @unlink($diagLogFile);
        if (ob_get_level()) { ob_flush(); } flush();
    }
}

// ════════════════════════════════════════════════════════════════════════
// 7. RÉSUMÉ
// ════════════════════════════════════════════════════════════════════════
section('7. Résumé des variables d\'environnement');

$envKeys = ['GLOBALO_ENV', 'BASE_URL', 'SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_SECURE', 'MAIL_FROM'];
foreach ($envKeys as $k) {
    $v = getenv($k);
    $display = ($v !== false && $v !== '') ? $v : '(non défini dans .env — utilise valeur DB ou défaut)';
    // Masquer les mots de passe
    if (str_contains(strtolower($k), 'pass') || str_contains(strtolower($k), 'secret')) {
        $display = $v !== false && $v !== '' ? str_repeat('•', min(strlen((string)$v), 8)) : '(vide)';
    }
    diag_code("{$k} = {$display}");
}

// SMTP_PASS séparé
$rawPass = getenv('SMTP_PASS');
diag_code("SMTP_PASS = " . ($rawPass !== false && $rawPass !== '' ? '(défini dans .env — ' . strlen((string)$rawPass) . ' car.)' : '(non défini dans .env)'));

?>

<div class="delete-warn">
    ⚠️ <strong>Sécurité :</strong> Supprimez ce fichier (<code>scripts/diag_smtp.php</code>) après le diagnostic.
</div>

</div><!-- .wrap -->
</body>
</html>
