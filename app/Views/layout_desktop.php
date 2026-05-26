<?php
// Base URL absolue pour tous les liens (évite menus cassés en prod / sous-dossier)
$baseUrl = rtrim(BASE_URL ?? '', '/');
if ($baseUrl === '') {
    $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$csrfField = \App\Core\Security::getCsrfField();
$user = $user ?? null;
$navActive = $navActive ?? '';
$seo = $seo ?? [];

$publicShellNav = ['accueil', 'apropos', 'contact', 'experts', 'professeurs', 'demandes_public'];
$isPublicShell = in_array($navActive, $publicShellNav, true);
$showHeaderSmartSearch = !empty($show_header_smart_search);
$loadPublicShellCss = $isPublicShell || $showHeaderSmartSearch;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= \App\Core\Security::escape($seo['description'] ?? __("footer.tagline")) ?>">
    <meta name="keywords" content="expert freelance Mali, consultant Bamako, expert Abidjan, freelance Sénégal, expert Dakar, consultant Côte d'Ivoire, freelance Bénin, expert Niger, plateforme freelance Afrique, Wave paiement expert, Orange Money consultant">
    <meta name="robots" content="<?= !empty($seo['robots']) ? \App\Core\Security::escape($seo['robots']) : 'index, follow' ?>">
    <meta name="geo.region" content="ML">
    <meta name="geo.placename" content="Bamako, Mali">
    <meta name="ICBM" content="12.6392, -8.0029">
    <?php if (defined('GSC_VERIFICATION') && GSC_VERIFICATION !== ''): ?>
    <meta name="google-site-verification" content="<?= \App\Core\Security::escape(GSC_VERIFICATION) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="<?= \App\Core\Security::generateCsrfToken() ?>">
    <?php if (!empty($seo['canonical'])): ?>
    <link rel="canonical" href="<?= \App\Core\Security::escape($seo['canonical']) ?>">
    <?php endif; ?>
    <?php if (!empty($seo['hreflang']) && is_array($seo['hreflang'])): ?>
        <?php foreach ($seo['hreflang'] as $hl): ?>
    <link rel="alternate" hreflang="<?= \App\Core\Security::escape($hl['lang']) ?>" href="<?= \App\Core\Security::escape($hl['url']) ?>">
        <?php endforeach; ?>
    <?php else: ?>
    <link rel="alternate" hreflang="fr" href="<?= \App\Core\Security::escape($seo['canonical'] ?? ($baseUrl . '/')) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= \App\Core\Security::escape($baseUrl . '/') ?>">
    <?php endif; ?>
    <title><?= \App\Core\Security::escape($seo['title'] ?? $pageTitle ?? 'GLOBALO') ?></title>
    <meta property="og:type"        content="<?= \App\Core\Security::escape($seo['og_type'] ?? 'website') ?>">
    <meta property="og:url"         content="<?= \App\Core\Security::escape($seo['og_url'] ?? $seo['canonical'] ?? '') ?>">
    <meta property="og:title"       content="<?= \App\Core\Security::escape($seo['og_title'] ?? $seo['title'] ?? '') ?>">
    <meta property="og:description" content="<?= \App\Core\Security::escape($seo['og_description'] ?? $seo['description'] ?? '') ?>">
    <meta property="og:image"       content="<?= \App\Core\Security::escape($seo['og_image'] ?? ($baseUrl . '/assets/images/og-default.png')) ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name"   content="<?= \App\Core\Security::escape($seo['og_site_name'] ?? 'GLOBALO') ?>">
    <meta property="og:locale"      content="<?= ($lang ?? 'fr') === 'en' ? 'en_US' : 'fr_FR' ?>">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= \App\Core\Security::escape($seo['twitter_title'] ?? $seo['title'] ?? '') ?>">
    <meta name="twitter:description" content="<?= \App\Core\Security::escape($seo['twitter_description'] ?? $seo['description'] ?? '') ?>">
    <meta name="twitter:image"       content="<?= \App\Core\Security::escape($seo['twitter_image'] ?? $seo['og_image'] ?? ($baseUrl . '/assets/images/og-default.png')) ?>">
    <?php if (!empty($seo['twitter_site'])): ?>
    <meta name="twitter:site" content="<?= \App\Core\Security::escape($seo['twitter_site']) ?>">
    <?php endif; ?>
    <?php if (!empty($seo['structured_data'])): ?>
    <?= $seo['structured_data'] ?>
    <?php endif; ?>
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.php">
    <link rel="icon" type="image/png" href="<?= logo_url() ?>">
    <link rel="apple-touch-icon" href="<?= logo_url() ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/desktop.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/desktop.css') ?>">
    <?php if (!empty($loadPublicShellCss)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/public-shell-desktop.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/public-shell-desktop.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/intouch-operators.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/intouch-operators.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"></noscript>
    <style>
    .nav-link-demandes-pub {
        display: inline-flex !important;
        align-items: center !important;
        gap: .4rem !important;
    }
    .nav-badge-pub {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 20px !important;
        height: 20px !important;
        padding: 0 6px !important;
        border-radius: 999px !important;
        background: #ef4444 !important;
        color: #fff !important;
        font-size: .75rem !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        vertical-align: middle !important;
        text-decoration: none !important;
    }
    </style>
</head>
<?php
$bodyClass = trim('layout-desktop'
    . ($isPublicShell ? ' page-public-shell' : '')
    . ($showHeaderSmartSearch ? ' has-home-sticky-search' : ''));
?>
<body class="<?= \App\Core\Security::escape($bodyClass) ?>" data-base-url="<?= \App\Core\Security::escape($baseUrl) ?>" data-user-id="<?= (!empty($user) && isset($user['id'])) ? (int) $user['id'] : '' ?>">
    <header class="header-desktop">
        <div class="header-inner<?= ($isPublicShell || $showHeaderSmartSearch) ? ' header-inner--public' : '' ?>">
            <div class="header-brand-search">
                <a href="<?= $baseUrl ?>/" class="logo logo-desktop-wrap" aria-label="Globalo - Accueil">
                    <img src="<?= $baseUrl ?>/assets/images/globalo-logo-affiche.png" alt="GLOBALO" class="logo-header-img"
                         onerror="this.src='<?= $baseUrl ?>/assets/images/logo.png';this.onerror=null;">
                </a>
                <?php if ($showHeaderSmartSearch): ?>
                    <?php require __DIR__ . '/partials/header_smart_search_desktop.php'; ?>
                <?php endif; ?>
            </div>
            <nav class="nav-main">
                <a href="<?= $baseUrl ?>/" class="<?= $navActive === 'accueil' ? 'active' : '' ?>"><?= __("nav.home") ?></a>
                <a href="<?= $baseUrl ?>/home/apropos" class="<?= $navActive === 'apropos' ? 'active' : '' ?>"><?= __("nav.about") ?></a>
                <a href="<?= $baseUrl ?>/demandes" class="nav-link-demandes-pub <?= $navActive === 'demandes_public' ? 'active' : '' ?>">
                    <?= __("nav.requests") ?>
                    <?php $_nbDemPub = (int) ($navBadgeDemandesPublic ?? 0); if ($_nbDemPub > 0): ?>
                    <span class="nav-badge-pub"><?= $_nbDemPub > 99 ? '99+' : $_nbDemPub ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= $baseUrl ?>/experts" class="<?= $navActive === 'experts' ? 'active' : '' ?>"><?= __("nav.experts") ?></a>
                <a href="<?= $baseUrl ?>/professeurs" class="<?= $navActive === 'professeurs' ? 'active' : '' ?>"><?= __("nav.professeurs") ?></a>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'client'): ?>
                        <a href="<?= $baseUrl ?>/client" class="btn btn-primary nav-espace-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Espace client
                        </a>
                    <?php elseif ($user['role'] === 'expert'): ?>
                        <a href="<?= $baseUrl ?>/expert" class="btn btn-primary nav-espace-btn" style="position:relative;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Espace expert
                            <?php $_nbDemExp = (int) ($navBadgeDemandesPublic ?? 0); if ($_nbDemExp > 0): ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:rgba(255,255,255,.25);color:#fff;font-size:.7rem;font-weight:800;margin-left:.2rem;"><?= $_nbDemExp > 99 ? '99+' : $_nbDemExp ?></span>
                            <?php endif; ?>
                        </a>
                    <?php elseif ($user['role'] === 'etudiant'): ?>
                        <a href="<?= $baseUrl ?>/etudiant" class="btn btn-primary nav-espace-btn" style="background:#2563eb;border-color:#2563eb;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            Espace étudiant
                        </a>
                    <?php elseif ($user['role'] === 'professeur'): ?>
                        <a href="<?= $baseUrl ?>/professeur" class="btn btn-primary nav-espace-btn" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-color:transparent;position:relative;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            Espace professeur
                            <?php $_nbExNav = (int) ($navBadgeExercices ?? 0); if ($_nbExNav > 0): ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:rgba(255,255,255,.25);color:#fff;font-size:.7rem;font-weight:800;margin-left:.2rem;"><?= $_nbExNav > 99 ? '99+' : $_nbExNav ?></span>
                            <?php endif; ?>
                        </a>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <a href="<?= $baseUrl ?>/admin" class="btn btn-primary nav-espace-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Administration
                        </a>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/auth/deconnexion" class="btn <?= $isPublicShell ? 'btn-outline nav-btn-outline-shell' : 'btn-outline' ?>"><?= __("nav.logout") ?></a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/auth/connexion" class="<?= $navActive === 'connexion' ? 'active' : '' ?> nav-auth-link"><?= __("nav.login") ?></a>
                    <a href="<?= $baseUrl ?>/auth/inscription" class="btn <?= $isPublicShell ? 'btn-public-signup btn-primary nav-cta-signup' : 'btn-primary' ?> <?= $navActive === 'inscription' ? 'active' : '' ?>"><?= __("nav.signup") ?></a>
                <?php endif; ?>
                <span class="nav-lang" aria-label="Langue">
                    <?php $cur = $lang ?? 'fr'; ?>
                    <a href="<?= $baseUrl ?>/auth/lang/fr" class="<?= $cur === 'fr' ? 'active' : '' ?>">FR</a>
                    <span class="nav-lang-sep">|</span>
                    <a href="<?= $baseUrl ?>/auth/lang/en" class="<?= $cur === 'en' ? 'active' : '' ?>">EN</a>
                </span>
            </nav>
        </div>
    </header>

    <main class="main-desktop" style="position:relative;z-index:1;">
        <?php
        /* Bannière email non-vérifié (public layout) */
        if (\App\Core\Auth::check() && !isset($_SESSION['_email_verifie'])) {
            $_uRowEvD = (new \App\Models\UtilisateurModel())->find((int) \App\Core\Auth::id());
            $_SESSION['_email_verifie']  = (is_array($_uRowEvD) && !empty($_uRowEvD['email_verifie'])) ? 1 : 0;
            $_SESSION['_ev_user_email']  = is_array($_uRowEvD) ? ($_uRowEvD['email'] ?? '') : '';
            unset($_uRowEvD);
        }
        $_evBannerD = \App\Core\Auth::check() && isset($_SESSION['_email_verifie']) && empty($_SESSION['_email_verifie']);
        ?>
        <?php if ($_evBannerD): ?>
        <div class="ev-pub-banner" id="ev-pub-banner" role="alert">
            <span class="ev-pub-banner__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </span>
            <span class="ev-pub-banner__msg">
                Vérifiez votre adresse email pour accéder à <strong>toutes les fonctionnalités</strong> de votre espace.
            </span>
            <form class="ev-pub-banner__form" id="ev-pub-banner-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_SESSION['_ev_user_email'] ?? '') ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" class="ev-pub-banner__btn" id="ev-pub-banner-resend">Renvoyer le lien</button>
            </form>
        </div>
        <style>
        .ev-pub-banner {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: .65rem 1rem;
            margin: 0 0 1.5rem;
            font-size: .875rem;
            color: #78350f;
            line-height: 1.45;
        }
        .ev-pub-banner__icon { flex-shrink: 0; color: #d97706; display: flex; }
        .ev-pub-banner__msg  { flex: 1 1 auto; min-width: 0; }
        .ev-pub-banner__form { flex-shrink: 0; }
        .ev-pub-banner__btn  {
            background: #f59e0b;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: .32rem .8rem;
            font-size: .8125rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
        }
        .ev-pub-banner__btn:hover { background: #d97706; }
        </style>
        <script>
        (function () {
            var form = document.getElementById('ev-pub-banner-form');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('ev-pub-banner-resend');
                btn.disabled = true; btn.textContent = 'Envoi…';
                fetch(form.action, { method:'POST', body: new FormData(form), credentials:'include', redirect:'manual' })
                    .then(function () {
                        btn.textContent = '✓ Email envoyé !';
                        btn.style.background = '#16a34a';
                        setTimeout(function () { btn.disabled=false; btn.textContent='Renvoyer le lien'; btn.style.background=''; }, 4000);
                    })
                    .catch(function () { btn.disabled=false; btn.textContent='Renvoyer le lien'; });
            });
        })();
        </script>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>

    <footer class="footer-desktop">
        <div class="footer-inner">
            <p>&copy; <?= date('Y') ?> GLOBALO — <?= __("footer.tagline") ?></p>
            <div class="footer-links">
                <a href="<?= $baseUrl ?>/"><?= __("nav.home") ?></a>
                <a href="<?= $baseUrl ?>/demandes"><?= __("nav.requests") ?></a>
                <a href="<?= $baseUrl ?>/experts"><?= __("nav.experts") ?></a>
                <a href="<?= $baseUrl ?>/home/apropos"><?= __("nav.about") ?></a>
                <a href="<?= $baseUrl ?>/home/contact"><?= __("nav.contact") ?></a>
                <a href="<?= $baseUrl ?>/home/confidentialite"><?= __("nav.privacy") ?></a>
                <a href="<?= $baseUrl ?>/home/donnees"><?= __("nav.data_policy") ?></a>
            </div>
        </div>
    </footer>

    <?php
    $gaId = defined('GA_MEASUREMENT_ID') ? GA_MEASUREMENT_ID : '';
    $fbPixelId = defined('FB_PIXEL_ID') ? FB_PIXEL_ID : '';
    $linkedInId = defined('LINKEDIN_PARTNER_ID') ? LINKEDIN_PARTNER_ID : '';
    ?>
    <script>
    window.GLOBALO_GROWTH = {
        gaId: <?= json_encode($gaId) ?>,
        fbPixelId: <?= json_encode($fbPixelId) ?>,
        linkedInId: <?= json_encode($linkedInId) ?>,
        baseUrl: <?= json_encode($baseUrl) ?>
    };
    </script>
    <?php include __DIR__ . '/partials/cookie_banner.php'; ?>

    <?php
    /* ── Popup + toast vérification email (layout public) ── */
    $_evUidD = \App\Core\Auth::id();
    $_showEvPopupD = false;
    $_evEmailD = '';
    if ($_evUidD && \App\Core\Auth::check()) {
        // Session déjà chargée par le bloc <main> ci-dessus
        $_showEvPopupD = isset($_SESSION['_email_verifie']) && empty($_SESSION['_email_verifie']);
        $_evEmailD = $_SESSION['_ev_user_email'] ?? '';
    }
    ?>
    <?php if ($_showEvPopupD): ?>
    <!-- Toast sur clic bouton "Espace XXX" dans la nav -->
    <div id="ev-nav-toast-pub" role="alert" aria-live="assertive"
         style="display:none;position:fixed;top:1.25rem;right:1.5rem;z-index:10001;max-width:340px;background:#1e293b;color:#fff;border-radius:12px;padding:.85rem 1.1rem;box-shadow:0 8px 32px rgba(0,0,0,.28);font-size:.875rem;line-height:1.5;font-family:inherit;">
        <div style="display:flex;align-items:flex-start;gap:.6rem;">
            <span style="flex-shrink:0;margin-top:.1rem;color:#fbbf24;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <span>Veuillez d'abord <strong>valider votre adresse email</strong> pour accéder à votre espace.</span>
        </div>
    </div>
    <!-- Popup modal vérification email -->
    <div id="ev-modal-pub" role="dialog" aria-modal="true" aria-labelledby="ev-modal-pub-title"
         style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;padding:1rem;">
        <div id="ev-modal-pub-backdrop" style="position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);"></div>
        <div style="position:relative;background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.22);max-width:440px;width:100%;padding:2.25rem 2rem 2rem;text-align:center;font-family:inherit;animation:evPubSlideIn .3s cubic-bezier(.34,1.56,.64,1);">
            <div style="width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,#fef9c3,#fef08a);margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 18px rgba(234,179,8,.2);">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </div>
            <h2 id="ev-modal-pub-title" style="margin:0 0 .6rem;font-size:1.25rem;font-weight:700;color:#0f172a;line-height:1.3;">
                Vérifiez votre adresse email
            </h2>
            <p style="margin:0 0 1.25rem;font-size:.9375rem;color:#475569;line-height:1.55;">
                Pour accéder à <strong>toutes les fonctionnalités</strong> de votre espace, confirmez votre adresse email en cliquant sur le lien que nous vous avons envoyé.
            </p>
            <?php if ($_evEmailD !== ''): ?>
            <div style="display:inline-flex;align-items:center;gap:.45rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem .9rem;font-size:.875rem;color:#334155;font-weight:500;margin-bottom:1.5rem;max-width:100%;word-break:break-all;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <?= \App\Core\Security::escape($_evEmailD) ?>
            </div>
            <?php endif; ?>
            <form id="ev-pub-resend-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-bottom:.85rem;">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_evEmailD) ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" id="ev-pub-resend-btn"
                        style="width:100%;background:#16a34a;color:#fff;border:none;border-radius:10px;padding:.75rem 1.25rem;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .18s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m3 3 1.664 9.526a2 2 0 0 0 2.104 1.65L21 12 6.768 9.824A2 2 0 0 1 5.108 7.76L3 3Z"/><path d="m14 12 3 7"/></svg>
                    Renvoyer le lien de vérification
                </button>
            </form>
            <button type="button" id="ev-modal-pub-close"
                    style="background:none;border:none;color:#64748b;font-size:.875rem;font-weight:500;cursor:pointer;padding:.4rem .75rem;border-radius:8px;transition:background .15s;">
                Je vérifierai plus tard
            </button>
        </div>
    </div>
    <style>
    @keyframes evPubSlideIn { from { opacity:0; transform:scale(.93) translateY(12px); } to { opacity:1; transform:scale(1) translateY(0); } }
    @keyframes evPubToastIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    #ev-pub-resend-btn:hover   { background: #15803d !important; }
    #ev-modal-pub-close:hover  { background: #f1f5f9 !important; color: #1e293b !important; }
    </style>
    <script>
    (function () {
        var STORAGE_KEY = 'ev_dismissed_<?= (int) $_evUidD ?>';
        var modal       = document.getElementById('ev-modal-pub');
        var toast       = document.getElementById('ev-nav-toast-pub');
        var toastTimer  = null;

        /* ── Popup modal (une fois par session) ── */
        if (modal && !sessionStorage.getItem(STORAGE_KEY)) {
            modal.style.display = 'flex';
        }
        function closeModal() {
            sessionStorage.setItem(STORAGE_KEY, '1');
            if (modal) modal.style.display = 'none';
        }
        var btnClose = document.getElementById('ev-modal-pub-close');
        var backdrop = document.getElementById('ev-modal-pub-backdrop');
        if (btnClose) btnClose.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        /* ── AJAX renvoi depuis le popup ── */
        var resendForm = document.getElementById('ev-pub-resend-form');
        if (resendForm) {
            resendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('ev-pub-resend-btn');
                btn.disabled = true;
                btn.innerHTML = 'Envoi en cours…';
                fetch(resendForm.action, { method:'POST', body: new FormData(resendForm), credentials:'include', redirect:'manual' })
                    .then(function () {
                        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Email envoyé ! Consultez votre boîte mail';
                        btn.style.background = '#0f766e';
                        setTimeout(closeModal, 3000);
                    })
                    .catch(function () { btn.disabled=false; btn.innerHTML='Renvoyer le lien de vérification'; });
            });
        }

        /* ── Toast sur clic du bouton "Espace XXX" dans le header ── */
        function showToast() {
            if (!toast) return;
            clearTimeout(toastTimer);
            toast.style.display = 'block';
            toast.style.animation = 'none';
            void toast.offsetWidth;
            toast.style.animation = 'evPubToastIn .25s ease';
            toastTimer = setTimeout(function () { toast.style.display = 'none'; }, 3500);
        }
        document.querySelectorAll('.nav-espace-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showToast();
            });
        });
    })();
    </script>
    <?php endif; ?>

    <?php
    $chatbotEnabled = '1';
    try {
        $chatbotEnabled = (new \App\Models\ParametreModel())->get('chatbot_enabled', '1');
    } catch (\Throwable $e) {
        $chatbotEnabled = '1';
    }
    ?>

    <?php if ($chatbotEnabled === '1'): ?>
    <?php
    $chatbotUserAuth = \App\Core\Auth::check() ? '1' : '0';
    $chatbotUserRole = \App\Core\Auth::role() ?? '';
    ?>
    <!-- Chatbot flottant -->
    <div
        id="chatbot-widget"
        class="chatbot-widget"
        data-base-url="<?= \App\Core\Security::escape($baseUrl) ?>"
        data-user-auth="<?= \App\Core\Security::escape($chatbotUserAuth) ?>"
        data-user-role="<?= \App\Core\Security::escape($chatbotUserRole) ?>"
        aria-hidden="false"
    >
        <button type="button" class="chatbot-toggle" id="chatbot-toggle" aria-label="Ouvrir l’assistant" title="Assistant GLOBALO">
            <span class="chatbot-toggle-icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="chatbot-badge" id="chatbot-badge" aria-hidden="true" style="display:none"></span>
        </button>
        <div id="chatbot-panel" class="chatbot-panel" hidden>
            <div class="chatbot-panel-header">
                <h2 class="chatbot-panel-title">Assistant GLOBALO</h2>
                <button type="button" class="chatbot-close" id="chatbot-close" aria-label="Fermer">×</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chatbot-welcome" id="chatbot-welcome">
                    <p>Bonjour, je suis l’assistant GLOBALO. Posez une question ou utilisez les boutons ci‑dessous.</p>
                </div>
            </div>
            <div class="chatbot-quick-actions" id="chatbot-quick-actions">
                <button type="button" class="chatbot-quick-btn" data-action="find_expert">Trouver un expert</button>
                <button type="button" class="chatbot-quick-btn" data-action="post_request">Publier une demande</button>
                <button type="button" class="chatbot-quick-btn" data-action="my_sessions">Mes sessions</button>
                <button type="button" class="chatbot-quick-btn" data-action="support">Support</button>
            </div>
            <div class="chatbot-input-wrap">
                <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Votre message..." autocomplete="off" maxlength="2000">
                <button type="button" class="chatbot-send" id="chatbot-send" aria-label="Envoyer">Envoyer</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($show_header_smart_search)): ?>
    <script src="<?= $baseUrl ?>/assets/js/smart-search-home.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/smart-search-home.js') ?>" defer></script>
    <script src="<?= $baseUrl ?>/assets/js/public-home-sticky-search.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/public-home-sticky-search.js') ?>" defer></script>
    <?php endif; ?>
    <script src="<?= $baseUrl ?>/assets/js/app.js" defer></script>
    <script src="<?= $baseUrl ?>/assets/js/cookie-consent.js" defer></script>
    <script src="<?= $baseUrl ?>/assets/js/growth.js" defer></script>
    <?php if ($chatbotEnabled === '1'): ?>
    <script src="<?= $baseUrl ?>/assets/js/chatbot.js" defer></script>
    <?php endif; ?>

    <!-- ── Service Worker + Push Notifications ── -->
    <script>
    (function(){
        var VAPID_PUBLIC_KEY = <?= json_encode(
            defined('VAPID_PUBLIC_KEY') ? (string) VAPID_PUBLIC_KEY : '',
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        ) ?: '""' ?>;
        var BASE_URL = '<?= $baseUrl ?>';
        var IS_AUTH  = <?= \App\Core\Auth::check() ? 'true' : 'false' ?>;

        if (!('serviceWorker' in navigator)) return;

        // Enregistrer le SW dans tous les cas (pour le cache offline)
        navigator.serviceWorker.register(BASE_URL + '/sw.js', { scope: BASE_URL + '/' })
        .then(function(reg) {
            if (!IS_AUTH || !('PushManager' in window) || !VAPID_PUBLIC_KEY) return;

            function urlB64ToUint8Array(b64) {
                var pad = '='.repeat((4 - b64.length % 4) % 4);
                var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
                var arr = new Uint8Array(raw.length);
                for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
                return arr;
            }

            if (Notification.permission === 'granted') {
                reg.pushManager.getSubscription().then(function(sub) {
                    if (!sub) return reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlB64ToUint8Array(VAPID_PUBLIC_KEY)
                    });
                    return sub;
                }).then(function(sub) {
                    if (!sub) return;
                    var json = sub.toJSON ? sub.toJSON() : JSON.parse(JSON.stringify(sub));
                    fetch(BASE_URL + '/api/push/subscribe', {
                        method: 'POST', credentials: 'include',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(json)
                    });
                }).catch(function(){});
            }
        }).catch(function(){});
    })();
    </script>
</body>
</html>
