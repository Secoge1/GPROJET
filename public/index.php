<?php
/**
 * GLOBALO - Point d'entrée unique
 */

declare(strict_types=1);

// Chemins absolus depuis ce script (évite les vues introuvables)
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);
define('APP_PATH', $rootPath . DIRECTORY_SEPARATOR . 'app');

// ── Mode Maintenance ──────────────────────────────────────────────────────────
// Activation : créer le fichier .maintenance à la racine du projet.
// Contenu optionnel JSON : {"message":"...","eta":"2026-01-01 14:00","progress":60}
$_maintenanceFlag = $rootPath . DIRECTORY_SEPARATOR . '.maintenance';
$_appFolderEmpty  = !is_dir(APP_PATH) || !is_file(APP_PATH . DIRECTORY_SEPARATOR . 'bootstrap.php');

if (is_file($_maintenanceFlag) || $_appFolderEmpty) {
    $maintenancePage = __DIR__ . DIRECTORY_SEPARATOR . 'maintenance.php';
    if (is_file($maintenancePage)) {
        require $maintenancePage;
    } else {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Maintenance</title></head><body style="font-family:sans-serif;text-align:center;padding:4rem"><h1>🔧 Maintenance en cours</h1><p>Nous serons de retour très bientôt.</p></body></html>';
    }
    exit;
}
unset($_maintenanceFlag, $_appFolderEmpty);

require_once APP_PATH . DIRECTORY_SEPARATOR . 'bootstrap.php';

// ── En-têtes HTTP de sécurité (fallback si mod_headers Apache absent) ─────────
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // HSTS : uniquement en HTTPS
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=(self)');
    // Permet à PHP de détecter un terminal mobile via Sec-CH-UA-Mobile (requêtes suivantes).
    header('Accept-CH: Sec-CH-UA-Mobile, Sec-CH-UA-Platform');
    // CSP : politique de sécurité du contenu (fallback si mod_headers absent)
    // CSP — TouchPay/InTouch : la config défaut utilise touchpay.gutouch.net alors que cette politique autorisait seulement .com (blocage du script en prod).
    // PayTech (paytech.sn) : inclus pour éviter tout blocage navigateur pendant / après redirection vers la passerelle.
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://snap.licdn.com https://www.googleadservices.com https://touchpay.gutouch.com https://touchpay.gutouch.net https://paytech.sn; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://paytech.sn; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self' https://api.openai.com https://www.google-analytics.com https://stats.g.doubleclick.net https://www.facebook.com https://api.qrserver.com https://api.gutouch.com https://apidist.gutouch.net https://touchpay.gutouch.com https://touchpay.gutouch.net https://paytech.sn; frame-src 'self' https://touchpay.gutouch.com https://touchpay.gutouch.net https://paytech.sn; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self' https://pay.wave.com https://touchpay.gutouch.com https://paytech.sn;");
}

// ── Durcissement session ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_ACTIVE) {
    // Timeout d'inactivité : déconnecter après SESSION_LIFETIME
    $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 86400;
    $last = $_SESSION['last_activity'] ?? $_SESSION['_last_activity'] ?? null;
    if ($last !== null && (time() - (int) $last) > $lifetime) {
        session_unset();
        session_destroy();
        session_start();
    }
    $now = time();
    $_SESSION['last_activity'] = $now;
    $_SESSION['_last_activity'] = $now;
}

use App\Core\Router;
use App\Core\Controller;
use App\Core\Security;

try {
    $router = new Router();
    // Le préfixe 'App\\' sert uniquement à détecter le mode mobile (isApp()),
    // il ne correspond PAS à un sous-espace de noms de contrôleur.
    $controllerPath = str_replace('App\\', '', $router->getController());
    $controllerClass = 'App\Controllers\\' . $controllerPath . 'Controller';

    if (!class_exists($controllerClass)) {
        $controllerClass = 'App\Controllers\HomeController';
    }

    $controller = new $controllerClass($router);
    $action = $router->getAction();
    $method = 'index';

    if (method_exists($controller, $action)) {
        $method = $action;
    } elseif (method_exists($controller, 'index')) {
        $method = 'index';
    }

    // Vérification CSRF pour les requêtes POST (sauf API et webhooks tiers)
    // Webhooks sans CSRF : InTouch (/intouch/callback), PayTech IPN (/paytech/callback) — signés par le fournisseur.
    // $controllerPath aligne aussi App\Paytech / App\Intouch (/app/paytech-*) avec Paytech pour les redirections CSRF.
    $isExternalWebhook = (
        ($controllerPath === 'Intouch' && $router->getAction() === 'callback')
        || ($controllerPath === 'Paytech' && $router->getAction() === 'callback')
    );
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$router->isApi() && !$isExternalWebhook && !Security::validateCsrf()) {
        // Ne pas lancer d'exception : en production cela affichait une page 500 générique
        // (ex. POST /wave/initier avec session expirée ou onglet dupliqué).
        $_SESSION['flash_error'] = 'Session expirée. Veuillez réessayer.';
        $base = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $act  = $router->getAction();
        $location = $base !== '' ? $base . '/' : '/';
        if ($controllerPath === 'Auth' && in_array($act, ['connexion', 'inscription', 'motDePasseOublie', 'reinitialiser'], true)) {
            $location = $base . '/auth/' . ($act === 'motDePasseOublie' ? 'mot-de-passe-oublie' : ($act === 'reinitialiser' ? 'reinitialiser' : $act));
        }
        if ($controllerPath === 'Paytech') {
            if ($act === 'initier') {
                $abo = trim((string) ($_POST['abonnement_type'] ?? ''));
                if ($abo === '') {
                    $abo = 'client';
                }
                $location = $base . ($router->isApp() ? '/app/paytech-abonnement' : '/paytech/checkout/') . rawurlencode($abo);
            } elseif ($act === 'initierPaiementSession') {
                $resId = max(0, (int) ($_POST['reservation_id'] ?? 0));
                if ($resId > 0) {
                    $location = $base . ($router->isApp()
                        ? '/app/paytech-session/' . $resId
                        : '/paytech/paiement-session/' . $resId);
                } else {
                    $location = $base . ($router->isApp() ? '/app/reservations' : '/client/reservations');
                }
            } elseif ($act === 'initierDepot') {
                $role = \App\Core\Auth::role();
                if ($role === 'expert') {
                    $location = $base . '/paytech/depot';
                } elseif ($role === 'etudiant') {
                    $location = $base . '/etudiant/portefeuille';
                } elseif ($role === 'professeur') {
                    $location = $base . '/professeur/portefeuille';
                } elseif ($router->isApp()) {
                    $location = $base . '/app/portefeuille';
                } else {
                    $location = $base . '/client/portefeuille';
                }
            }
        } elseif ($controllerPath === 'Intouch') {
            if ($act === 'initier') {
                $location = $base . ($router->isApp() ? '/app/abonnement' : '/abonnement');
            } elseif ($act === 'initierDepot') {
                $role = \App\Core\Auth::role();
                if ($role === 'expert') {
                    $location = $base . '/expert/revenus';
                } elseif ($role === 'etudiant') {
                    $location = $base . '/etudiant/portefeuille';
                } elseif ($role === 'professeur') {
                    $location = $base . '/professeur/portefeuille';
                } elseif ($router->isApp()) {
                    $location = $base . '/app/portefeuille';
                } else {
                    $location = $base . '/client/portefeuille';
                }
            } elseif ($act === 'soumettre') {
                $pid = trim((string) ($_POST['payment_id'] ?? ''));
                $location = $pid !== ''
                    ? $base . '/intouch/verification/' . rawurlencode($pid)
                    : $location;
            }
        }
        header('Location: ' . $location);
        exit;
    }

    call_user_func_array([$controller, $method], $router->getParams());

    // Tracking utilisateurs (pages vues) — ne pas bloquer en cas d'erreur
    if (!$router->isApi()) {
        try {
            \App\Services\TrackingService::logPageView($_SERVER['REQUEST_URI'] ?? '');
        } catch (Throwable $t) {
            // ignoré
        }
    }
} catch (Throwable $e) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    error_log(
        '[GLOBALO] ' . $e->getMessage()
        . ' @ ' . $e->getFile() . ':' . $e->getLine()
        . ' | URI=' . $uri
        . "\n" . $e->getTraceAsString()
    );
    if (DEBUG) {
        echo '<h1>Erreur</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n" . $e->getTraceAsString() . '</pre>';
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        require APP_PATH . '/Views/errors/500.php';
    }
}
