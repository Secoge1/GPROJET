<?php
/**
 * GLOBALO - Configuration principale
 * Plateforme d'assistance professionnelle à la demande
 */

declare(strict_types=1);

// Démarrer la session de manière sécurisée si pas encore démarrée
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    // Obligatoire en prod : index.php est sous /public/ mais les URLs sont à la racine (/client, /professeur, /auth/…).
    // Sans cela le cookie Path=/public et la session est perdue après login → accueil public pour tous les rôles.
    ini_set('session.cookie_path', '/');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Chemins absolus (définis par public/index.php si on passe par lui, sinon ici)
if (!defined('ROOT_PATH')) {
    $rootPath = dirname(__DIR__);
    define('ROOT_PATH', $rootPath);
    define('APP_PATH', $rootPath . DIRECTORY_SEPARATOR . 'app');
}
define('PUBLIC_PATH', ROOT_PATH . '/public');
/**
 * Fichiers uploadés : dossier globalo/uploads (UPLOAD_PATH).
 * En prod : exposer ce dossier sous l’URL /uploads/… — lien symbolique public/uploads → ../uploads
 * (tools/link-uploads-linux.sh) ou alias Nginx ; Windows : tools/link-uploads-windows.bat
 */
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/cache');

// Hôte local : ignorer SetEnv BASE_URL du .htaccess prod (évite redirect post-login vers globalo.secogesarl.com).
$httpHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$isLocalHost = $httpHost === 'localhost'
    || $httpHost === '127.0.0.1'
    || strpos($httpHost, '127.') === 0
    || (strlen($httpHost) >= 6 && substr($httpHost, -6) === '.local')
    || strpos($httpHost, 'localhost:') !== false;

// URL de base (à adapter selon l'environnement)
// Production globalo.secogesarl.com : détection automatique selon l'URL d'accès (avec ou sans /public).
// Pour forcer une URL : définir BASE_URL en variable d'environnement sur le serveur.
$envBaseUrl = getenv('BASE_URL');
if (!$isLocalHost && $envBaseUrl !== false && $envBaseUrl !== '') {
    define('BASE_URL', rtrim((string) $envBaseUrl, '/'));
} elseif (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'globalo.secogesarl.com') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $usePublic = ($path !== false && $path !== '' && strpos($path, '/public') === 0);
    define('BASE_URL', $usePublic ? 'https://globalo.secogesarl.com/public' : 'https://globalo.secogesarl.com');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = ($script === '/' || $script === '\\' || $script === '') ? '' : rtrim(str_replace('\\', '/', $script), '/');
    define('BASE_URL', $protocol . '://' . $host . $baseUrl);
}

// Environnement : development | production
// En ligne : définir GLOBALO_ENV=production (SetEnv Apache, variables d’hébergeur, etc.)
// pour désactiver DEBUG et éviter d’afficher les détails techniques aux utilisateurs.
define('ENV', $isLocalHost ? 'development' : (getenv('GLOBALO_ENV') ?: 'production'));
define('DEBUG', ENV === 'development');

// Base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'cp2640311p29_globalo');
define('DB_USER', getenv('DB_USER') ?: 'cp2640311p29_globalo');
define('DB_PASS', getenv('DB_PASS') ?: 'cp2640311p29_globalo');
define('DB_CHARSET', 'utf8mb4');

// Sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24); // 24h
define('PASSWORD_MIN_LENGTH', 8);

// Optionnel : secret pour scripts de diagnostic techniques (si vous en ajoutez un)
define('DIAGNOSTIC_SECRET', getenv('DIAGNOSTIC_SECRET') !== false ? trim((string) getenv('DIAGNOSTIC_SECRET')) : '');

// Fichiers
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 Mo
define('ALLOWED_UPLOAD_EXT', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip']);

// Langue
define('DEFAULT_LANG', 'fr');

// Commission plateforme (pourcent) — doit correspondre à la valeur par défaut en BDD (migration_monetisation.sql)
define('COMMISSION_DEFAULT', 20);

// Chatbot IA (OpenAI)
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('CHATBOT_MAX_HISTORY', (int) (getenv('CHATBOT_MAX_HISTORY') ?: 20));

// Moyens de paiement (affichage / paramètres)
define('PAIEMENT_MOYEN_DEFAUT', 'intouch');
define('PAIEMENT_MOYEN_LIBELLE', 'InTouch / TouchPay');

// InTouch Group / TouchPay - SECOGE SARL ML
// Priorite : 1) getenv() (Apache SetEnv)  2) $_SERVER (mod_php/WAMP)  3) fallback code
function _intouch_env(string $key, string $fallback = '' ): string {
    $v = getenv($key);
    if ($v !== false && trim($v) !== '') return trim($v);
    if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) return trim($_SERVER[$key]);
    if (!empty($_ENV[$key])    && is_string($_ENV[$key]))    return trim($_ENV[$key]);
    return $fallback;
}

define('INTOUCH_API_USERNAME',    _intouch_env('INTOUCH_API_USERNAME',   '23226cf1c7cd96dbf02f3391de1a3b568ff7b4c58d28766f36f8612b9e68b5f2'));
define('INTOUCH_API_PASSWORD',    _intouch_env('INTOUCH_API_PASSWORD',   '1203988b6809958c66e877d4b78b444604459d0edd6093d962a833eac592d24a'));
define('INTOUCH_LOGIN_AGENT',     _intouch_env('INTOUCH_LOGIN_AGENT',    '78063874'));
define('INTOUCH_PASSWORD_AGENT',  _intouch_env('INTOUCH_PASSWORD_AGENT', 'AaS3Ncav'));
define('INTOUCH_ID',              _intouch_env('INTOUCH_ID',             'SECOG8069'));
define('INTOUCH_MERCHANT_URL',    _intouch_env('INTOUCH_MERCHANT_URL',   'https://apidist.gutouch.net/apidist/sec/touchpayapi/[INTOUCH_ID]/transaction?loginAgent=[LOGIN_AGENT]&passwordAgent=[PASSWORD_AGENT]'));
define('INTOUCH_SERVICE_ORANGE',  _intouch_env('INTOUCH_SERVICE_ORANGE', 'ML_PAIEMENTMARCHAND_OM_TP'));
define('INTOUCH_SERVICE_MOOV',    _intouch_env('INTOUCH_SERVICE_MOOV',   'ML_PAIEMENTMARCHAND_MOOV_TP'));
define('INTOUCH_SERVICE_WAVE',    _intouch_env('INTOUCH_SERVICE_WAVE',   'ML_PAIEMENTWAVE_TP'));
define('INTOUCH_CALLBACK_SECRET', _intouch_env('INTOUCH_CALLBACK_SECRET', ''));

// PayTech — Passerelle de paiement (https://paytech.sn)
// Clés fournies par PayTech ; en prod : PAYTECH_* via SetEnv ou panneau hébergeur (pas de secrets dans le dépôt).
// Vérifier après déploiement : BASE_URL en HTTPS ; IPN https://…/paytech/callback joignable par PayTech.
define('PAYTECH_API_KEY',    _intouch_env('PAYTECH_API_KEY',    ''));
define('PAYTECH_API_SECRET', _intouch_env('PAYTECH_API_SECRET', ''));
define('PAYTECH_ENV',        _intouch_env('PAYTECH_ENV',        'prod')); // test | prod

// TouchPay - page de paiement (script JS) - doc PAYMENT/22
define('TOUCHPAY_SCRIPT_URL',       _intouch_env('TOUCHPAY_SCRIPT_URL',      'https://touchpay.gutouch.net/touchpayv2/script/touchpaynr/prod_touchpay-0.0.1.js'));
define('TOUCHPAY_SECURE_CODE',      _intouch_env('TOUCHPAY_SECURE_CODE',     'ZkkHgWfaZPat2CsKw8VnfFPf7dvZESCoQcl71fdfGR00xiPeR6'));
define('TOUCHPAY_ABONNEMENT_MODE',  _intouch_env('TOUCHPAY_ABONNEMENT_MODE', 'widget')); // widget = Checkout Page script2
// Référence grille frais agrégateur Mali (TouchPay, paiement marchand) : 2,5 % / transaction — appliqué côté utilisateur via paramètre admin « Frais Mobile Money sur l’abonnement » (clé wave_commission_pct).

// Mode de monétisation :
//   commission   → Inscription gratuite pour tous. La plateforme prend un % sur chaque paiement de prestation.
//   abonnement   → Clients et experts paient un abonnement mensuel. Commission = 0% sur les prestations.
define('MONETISATION_MODE_DEFAULT', 'abonnement');
// Fournisseur abonnement : intouch (TouchPay) | gratuit
define('ABONNEMENT_PROVIDER_DEFAULT', 'intouch');

// Tarifs abonnement mensuels (FCFA) — valeurs par défaut (modifiables en admin)
define('ABONNEMENT_PRIX_CLIENT_XOF',     2500);
define('ABONNEMENT_PRIX_EXPERT_XOF',     3000);
define('ABONNEMENT_PRIX_PROFESSEUR_XOF', 3000);
define('ABONNEMENT_PRIX_ETUDIANT_XOF',   2000);
define('ABONNEMENT_DUREE_JOURS', 30); // durée d'un abonnement payant (mensuel)

// Google OAuth 2.0
// Callback enregistré : https://globalo.secogesarl.com/auth/google-callback
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: '614661514666-p99j2jeog9a7p3q0ik0ddcm2802sse6m.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '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');
// GOOGLE_REDIRECT_URI est calculé dynamiquement dans GoogleOAuthService

// ── Push Notifications (Web Push / VAPID) ───────────────────────────────────
// Générez de nouvelles clés en exécutant : php generate_vapid_keys.php
// La clé publique est partagée avec le navigateur (PushManager.subscribe).
// La clé privée signe le JWT VAPID côté serveur (ne jamais exposer).
define('VAPID_PUBLIC_KEY',  getenv('VAPID_PUBLIC_KEY')  ?: 'BMRdacvH4AUgVbFObJIc21UMxp2n55an6MZmjGbhwY4wFZ0ZLaCk41PQGA4EFjc8AFHxjB5qA_6BwWUEC74bQ-4');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: 'V0P7yYLPo0LqZG5H8uOOvwz08qCg5HexVdu7a52uCTo');
define('VAPID_SUBJECT',     getenv('VAPID_SUBJECT')     ?: 'mailto:admin@globalo.secogesarl.com');

// ── Mode Maintenance ──────────────────────────────────────────────────────────
// Pour activer le mode maintenance : créer le fichier ROOT_PATH/.maintenance
// Optionnel : écrire un message JSON dedans {"message":"...","eta":"2026-01-01 12:00"}

// Growth: SEO & Tracking (optionnel, peut être surchargé par paramètres en BDD)
define('SEO_SITE_NAME', getenv('SEO_SITE_NAME') ?: 'GLOBALO');
define('SEO_TWITTER', getenv('SEO_TWITTER') ?: '');
define('GA_MEASUREMENT_ID', getenv('GA_MEASUREMENT_ID') ?: '');
define('FB_PIXEL_ID', getenv('FB_PIXEL_ID') ?: '');
define('LINKEDIN_PARTNER_ID', getenv('LINKEDIN_PARTNER_ID') ?: '');
// Google Search Console : token de vérification propriété (HTML meta tag)
// Obtenir sur https://search.google.com/search-console → Ajouter une propriété → Balise HTML
define('GSC_VERIFICATION', getenv('GSC_VERIFICATION') ?: '');
