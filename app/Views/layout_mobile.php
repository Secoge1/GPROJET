<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$user = $user ?? null;
$lang = $lang ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php
    $esc     = fn($s) => \App\Core\Security::escape($s ?? '');
    $seoM    = $seo ?? [];
    $seoDesc = $seoM['description'] ?? ('GLOBALO — ' . __('footer.tagline'));
    $seoTitle = $esc($seoM['title'] ?? $pageTitle ?? 'GLOBALO');
    $seoCanon = $esc($seoM['canonical'] ?? ($baseUrl . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)));
    $seoImg   = $esc($seoM['og_image'] ?? ($baseUrl . '/assets/images/og-default.png'));
    $seoLocale = ($lang === 'en') ? 'en_US' : 'fr_FR';
    $seoAlt    = ($lang === 'en') ? 'fr' : 'en';
    ?>
    <meta name="description" content="<?= $esc($seoDesc) ?>">
    <meta name="keywords" content="expert freelance Mali, consultant Bamako, expert Abidjan, freelance Sénégal, expert Dakar, consultant Côte d'Ivoire, freelance Bénin, expert Niger, plateforme freelance Afrique, Wave paiement expert, Orange Money consultant">
    <meta name="robots" content="<?= !empty($seoM['robots']) ? $esc($seoM['robots']) : 'index, follow' ?>">
    <meta name="geo.region" content="ML">
    <meta name="geo.placename" content="Bamako, Mali">
    <?php if (defined('GSC_VERIFICATION') && GSC_VERIFICATION !== ''): ?>
    <meta name="google-site-verification" content="<?= $esc(GSC_VERIFICATION) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="<?= \App\Core\Security::generateCsrfToken() ?>">
    <title><?= $seoTitle ?></title>
    <?php if ($seoCanon): ?>
    <link rel="canonical" href="<?= $seoCanon ?>">
    <?php endif; ?>
    <?php if (!empty($seoM['hreflang']) && is_array($seoM['hreflang'])): ?>
        <?php foreach ($seoM['hreflang'] as $hl): ?>
    <link rel="alternate" hreflang="<?= $esc($hl['lang']) ?>" href="<?= $esc($hl['url']) ?>">
        <?php endforeach; ?>
    <?php else: ?>
    <link rel="alternate" hreflang="fr" href="<?= $seoCanon ?>">
    <link rel="alternate" hreflang="x-default" href="<?= $esc($baseUrl . '/') ?>">
    <?php endif; ?>
    <!-- Open Graph -->
    <meta property="og:type"        content="<?= $esc($seoM['og_type'] ?? 'website') ?>">
    <meta property="og:url"         content="<?= $esc($seoM['og_url'] ?? $seoCanon) ?>">
    <meta property="og:title"       content="<?= $esc($seoM['og_title'] ?? $seoM['title'] ?? 'GLOBALO') ?>">
    <meta property="og:description" content="<?= $esc($seoM['og_description'] ?? $seoDesc) ?>">
    <meta property="og:image"       content="<?= $seoImg ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name"   content="GLOBALO">
    <meta property="og:locale"      content="<?= $seoLocale ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $esc($seoM['twitter_title'] ?? $seoM['title'] ?? 'GLOBALO') ?>">
    <meta name="twitter:description" content="<?= $esc($seoM['twitter_description'] ?? $seoDesc) ?>">
    <meta name="twitter:image"       content="<?= $seoImg ?>">
    <?php if (!empty($seoM['twitter_site'])): ?>
    <meta name="twitter:site" content="<?= $esc($seoM['twitter_site']) ?>">
    <?php endif; ?>
    <?php if (!empty($seoM['structured_data'])): ?>
    <?= $seoM['structured_data'] ?>
    <?php endif; ?>
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.php">
    <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>/assets/icons/icon.svg">
    <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/icon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $baseUrl ?>/assets/icons/icon.svg">
    <meta name="apple-mobile-web-app-title" content="GLOBALO">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/mobile.css') ?>">
    <?php if (!empty($user) && in_array($user['role'] ?? '', ['etudiant', 'professeur'], true)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/etudiant.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/etudiant.css') ?: time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/intouch-operators.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/intouch-operators.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"></noscript>
</head>
<body class="layout-mobile" data-base-url="<?= \App\Core\Security::escape($baseUrl) ?>" data-user-id="<?= (!empty($user) && isset($user['id'])) ? (int) $user['id'] : '' ?>">
<?php
$nbNavMsg = (int) ($navBadgeMessages ?? 0);
$nbNavRes = (int) ($navBadgeReservations ?? 0);
?>
    <header class="header-mobile">
        <div class="header-mobile-inner">
            <div class="hdr-brand-cluster<?= !empty($showMobileHeaderSearch) ? ' hdr-brand-cluster--with-search' : '' ?>">
                <a href="<?= ($user && ($user['role'] ?? '') === 'professeur') ? $baseUrl . '/professeur' : (($user && ($user['role'] ?? '') === 'etudiant') ? $baseUrl . '/etudiant' : $baseUrl . '/app') ?>"
                   class="hdr-logo" aria-label="Globalo — Accueil">
                    <img src="<?= $baseUrl ?>/assets/images/globalo-logo-affiche.png" alt="Globalo" class="hdr-logo-img"
                         onerror="this.src='<?= $baseUrl ?>/assets/images/logo.png';this.onerror=null;">
                </a>

                <?php if (!empty($showMobileHeaderSearch)): ?>
                <?php
                    $hdrPhEtudiant = ['Matière, domaine, cours…', 'Ex. statistiques, dissertation', 'Recherche par compétence'];
                    $hdrPhDefault  = ['Que cherchez-vous ? Ex. Excel, droit', 'Compétence ou métier…', 'Ex. traduction, compta…'];
                    $roleHdr = is_array($user) ? (string) ($user['role'] ?? '') : '';
                    $hdrPhArr      = ($roleHdr === 'etudiant') ? $hdrPhEtudiant : $hdrPhDefault;
                    $hdrSearchPlaceholders = htmlspecialchars(json_encode($hdrPhArr, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $hdrInputPh = ($roleHdr === 'etudiant') ? 'Matière, domaine…' : 'Expert, compétence…';
                ?>
                <div class="hdr-inline-search">
                    <div id="hdr-smart-search-app" class="hero-smart-search hero-smart-search--hdr-inline" data-smart-search data-smart-search-api="<?= $baseUrl ?>/api/search/suggest" data-smart-search-app="<?= htmlspecialchars((string) ($mobileSmartSearchAppAttr ?? '1'), ENT_QUOTES, 'UTF-8') ?>" data-smart-search-placeholders="<?= $hdrSearchPlaceholders ?>">
                        <form class="hero-smart-search__form js-smart-search-form" method="get" action="<?= \App\Core\Security::escape($mobileExpertsSearchUrl ?? ($baseUrl . '/app/experts')) ?>" role="search" autocomplete="off">
                            <div class="hero-smart-search__field-wrap">
                                <label for="hdr-smart-search-q" class="visually-hidden">Rechercher un expert ou une compétence</label>
                                <input type="search" id="hdr-smart-search-q" name="q" class="hero-smart-search__input js-smart-search-input" value="<?= $headerSearchQ ?? '' ?>" placeholder="<?= \App\Core\Security::escape($hdrInputPh) ?>" autocomplete="off" aria-autocomplete="list" aria-expanded="false">
                                <button type="submit" class="hero-smart-search__submit" aria-label="Lancer la recherche">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                </button>
                            </div>
                            <?php if (!empty($headerSearchCompetence)): ?>
                            <input type="hidden" name="competence" value="<?= (int) $headerSearchCompetence ?>">
                            <?php endif; ?>
                            <div class="hero-smart-search__dropdown smart-search-dropdown js-smart-search-results" hidden role="listbox" aria-label="Suggestions"></div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Droite : langue + avatar -->
            <div class="hdr-right">
                <div class="hdr-lang" aria-label="Langue">
                    <a href="<?= $baseUrl ?>/auth/lang/fr"
                       class="hdr-lang-btn<?= $lang === 'fr' ? ' hdr-lang-btn--active' : '' ?>"
                       aria-current="<?= $lang === 'fr' ? 'true' : 'false' ?>">FR</a>
                    <span class="hdr-lang-sep" aria-hidden="true"></span>
                    <a href="<?= $baseUrl ?>/auth/lang/en"
                       class="hdr-lang-btn<?= $lang === 'en' ? ' hdr-lang-btn--active' : '' ?>"
                       aria-current="<?= $lang === 'en' ? 'true' : 'false' ?>">EN</a>
                </div>

                <?php if ($user): ?>
                <?php
                    $profilHref   = ($user['role'] ?? '') === 'professeur' ? $baseUrl . '/professeur/compte' : (($user['role'] ?? '') === 'etudiant' ? $baseUrl . '/etudiant/compte' : (($user['role'] ?? '') === 'expert' ? $baseUrl . '/expert/compte' : (($user['role'] ?? '') === 'client' ? $baseUrl . '/client/compte' : $baseUrl . '/app/profil')));
                    $avatarLetter = strtoupper(mb_substr($user['prenom'] ?: ($user['nom'] ?? ''), 0, 1)) ?: 'U';
                    $avatarColors = ['#2563eb','#16a34a','#7c3aed','#0d9488','#d97706','#dc2626'];
                    $avatarBg     = $avatarColors[abs(crc32($user['prenom'] ?? '')) % count($avatarColors)];
                    $uidHdr       = (int) ($user['id'] ?? 0);
                    $hasPhoto     = $uidHdr > 0 && !empty($user['avatar']);
                    $avatarSrc    = $hasPhoto ? $baseUrl . '/fichier/user-avatar/' . $uidHdr : '';
                ?>
                <a href="<?= $profilHref ?>"
                   class="hdr-avatar<?= $hasPhoto ? ' hdr-avatar--photo' : '' ?>"
                   style="background:<?= $avatarBg ?>"
                   aria-label="Mon profil">
                    <?php if ($hasPhoto): ?>
                    <img src="<?= \App\Core\Security::escape($avatarSrc) ?>" alt="" class="hdr-avatar__img" width="36" height="36" decoding="async" loading="lazy"
                         onerror="this.classList.add('hdr-avatar__img--broken');">
                    <?php endif; ?>
                    <span class="hdr-avatar__letter"<?= $hasPhoto ? ' aria-hidden="true"' : '' ?>><?= \App\Core\Security::escape($avatarLetter) ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-mobile">
        <?php
        /* Bannière email non-vérifié — DB query si cache session absent */
        if (\App\Core\Auth::check() && !isset($_SESSION['_email_verifie'])) {
            $_uRowEvM = (new \App\Models\UtilisateurModel())->find((int) \App\Core\Auth::id());
            $_SESSION['_email_verifie']  = (is_array($_uRowEvM) && !empty($_uRowEvM['email_verifie'])) ? 1 : 0;
            $_SESSION['_ev_user_email']  = is_array($_uRowEvM) ? ($_uRowEvM['email'] ?? '') : '';
            unset($_uRowEvM);
        }
        $_evBannerM = \App\Core\Auth::check() && isset($_SESSION['_email_verifie']) && empty($_SESSION['_email_verifie']);
        ?>
        <?php if ($_evBannerM): ?>
        <div class="ev-top-banner-m" id="ev-top-banner" role="alert">
            <div class="ev-top-banner-m__row">
                <span class="ev-top-banner-m__icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </span>
                <span class="ev-top-banner-m__msg">
                    Validez votre email pour accéder à <strong>toutes les fonctionnalités</strong>.
                </span>
            </div>
            <form class="ev-top-banner-m__form" id="ev-banner-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_SESSION['_ev_user_email'] ?? '') ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" class="ev-top-banner-m__btn" id="ev-banner-resend">
                    Renvoyer le lien de vérification
                </button>
            </form>
        </div>
        <style>
        .ev-top-banner-m {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: .75rem .9rem;
            margin: 0 0 1rem;
            font-size: .8125rem;
            color: #78350f;
            line-height: 1.45;
        }
        .ev-top-banner-m__row  { display: flex; align-items: flex-start; gap: .5rem; margin-bottom: .6rem; }
        .ev-top-banner-m__icon { flex-shrink: 0; color: #d97706; display: flex; margin-top: .1rem; }
        .ev-top-banner-m__msg  { flex: 1; min-width: 0; }
        .ev-top-banner-m__form { display: block; }
        .ev-top-banner-m__btn  {
            width: 100%;
            background: #f59e0b;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: .55rem 1rem;
            font-size: .8125rem;
            font-weight: 600;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        .ev-top-banner-m__btn:active { opacity: .88; }
        </style>
        <script>
        (function () {
            var form = document.getElementById('ev-banner-form');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('ev-banner-resend');
                btn.disabled = true;
                btn.textContent = 'Envoi…';
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'include',
                    redirect: 'manual'
                }).then(function () {
                    btn.textContent = '✓ Email envoyé ! Vérifiez votre boîte mail.';
                    btn.style.background = '#16a34a';
                    setTimeout(function () {
                        btn.disabled = false;
                        btn.textContent = 'Renvoyer le lien de vérification';
                        btn.style.background = '';
                    }, 4000);
                }).catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Renvoyer le lien de vérification';
                });
            });
        })();
        </script>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>

    <nav class="bottom-nav" aria-label="Navigation principale">
        <?php
        $_navRole = (is_array($user) && !empty($user['role'])) ? (string) $user['role'] : '';
        if ($_navRole === '' && \App\Core\Auth::check()) {
            $_navRole = (string) (\App\Core\Auth::role() ?? '');
        }
        $isEtudiant   = $_navRole === 'etudiant';
        $isProfesseur = $_navRole === 'professeur';

        /* ── Bibliothèque d'icônes SVG inline ── */
        $navSvg = [
            'home'      => '<svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'experts'   => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'demandes'  => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'missions'  => '<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>',
            'reservations' => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'messages'  => '<svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            'profil'    => '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'login'     => '<svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
            'etudiant'  => '<svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'exercices' => '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
            'matieres'  => '<svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'professeurs' => '<svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
            'portefeuille' => '<svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
            'retrait'   => '<svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        ];

        if ($isProfesseur):
            $profBp = (!empty($base_path) && in_array($base_path, ['/professeur', '/app'], true))
                ? $base_path
                : '/professeur';
            $reqPathProf = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '');
            if ($profBp === '/professeur' && strpos($reqPathProf, '/app/') !== false) {
                $profBp = '/app';
            }
            $profNav = [
                ['id'=>'etudiant',    'href'=>$profBp === '/app' ? $baseUrl.'/app/professeur' : $baseUrl.'/professeur', 'active'=>in_array(($navActive??''), ['etudiant','professeur'], true), 'label'=>'Accueil'],
                ['id'=>'exercices',   'href'=>$profBp === '/app' ? $baseUrl.'/app/exercices-disponibles' : $baseUrl.'/professeur/exercices-disponibles', 'active'=>($navActive??'')==='exercices', 'label'=>'À corriger'],
                ['id'=>'portefeuille','href'=>$baseUrl.'/professeur/portefeuille', 'active'=>($navActive??'')==='portefeuille', 'label'=>'Portefeuille'],
                ['id'=>'retrait',     'href'=>$baseUrl.'/professeur/retrait-choix', 'active'=>in_array(($navActive??''), ['retrait','retraitChoix'], true), 'label'=>'Retrait'],
                ['id'=>'messages',    'href'=>$baseUrl.'/messages', 'active'=>($navActive??'')==='messages', 'label'=>'Messages'],
            ];
            foreach ($profNav as $item):
        ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $item['active'] ? 'active' : '' ?>" aria-label="<?= $item['label'] ?>">
            <span class="nav-icon-wrap">
                <span class="nav-icon"><?= $navSvg[$item['id']] ?? $navSvg['home'] ?></span>
                <?php if ($item['id'] === 'messages'): ?>
                <span class="nav-badge" data-nav-badge="messages"<?= $nbNavMsg < 1 ? ' hidden' : '' ?>><?= $nbNavMsg > 99 ? '99+' : (string) max(0, $nbNavMsg) ?></span>
                <?php endif; ?>
                <?php if ($item['id'] === 'reservations'): ?>
                <span class="nav-badge" data-nav-badge="reservations"<?= $nbNavRes < 1 ? ' hidden' : '' ?>><?= $nbNavRes > 99 ? '99+' : (string) max(0, $nbNavRes) ?></span>
                <?php endif; ?>
                <?php if ($item['id'] === 'exercices'): ?>
                <?php $_nbExMob = (int) ($navBadgeExercices ?? 0); if ($_nbExMob > 0): ?>
                <span class="nav-badge nav-badge--orange"><?= $_nbExMob > 99 ? '99+' : $_nbExMob ?></span>
                <?php endif; ?>
                <?php endif; ?>
            </span>
            <span class="nav-label"><?= $item['label'] ?></span>
        </a>
        <?php
            endforeach;
        elseif ($isEtudiant):
            $etdNav = [
                ['id'=>'etudiant',    'href'=>$baseUrl.'/etudiant',           'active'=>($navActive??'')==='etudiant',                                    'label'=>'Accueil'],
                ['id'=>'exercices',   'href'=>$baseUrl.'/etudiant/exercices',  'active'=>($navActive??'')==='exercices',                                   'label'=>'Exercices'],
                ['id'=>'matieres',    'href'=>$baseUrl.'/etudiant/matieres',   'active'=>($navActive??'')==='matieres',                                    'label'=>'Matières'],
                ['id'=>'professeurs', 'href'=>$baseUrl.'/app/professeurs',    'active'=>($navActive??'')==='professeurs',                                 'label'=>'Profs'],
            ];
            foreach ($etdNav as $item):
        ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $item['active'] ? 'active' : '' ?>" aria-label="<?= $item['label'] ?>">
            <span class="nav-icon-wrap">
                <span class="nav-icon"><?= $navSvg[$item['id']] ?? $navSvg['home'] ?></span>
                <?php if ($item['id'] === 'messages'): ?>
                <span class="nav-badge" data-nav-badge="messages"<?= $nbNavMsg < 1 ? ' hidden' : '' ?>><?= $nbNavMsg > 99 ? '99+' : (string) max(0, $nbNavMsg) ?></span>
                <?php endif; ?>
                <?php if ($item['id'] === 'reservations'): ?>
                <span class="nav-badge" data-nav-badge="reservations"<?= $nbNavRes < 1 ? ' hidden' : '' ?>><?= $nbNavRes > 99 ? '99+' : (string) max(0, $nbNavRes) ?></span>
                <?php endif; ?>
            </span>
            <span class="nav-label"><?= $item['label'] ?></span>
        </a>
        <?php
            endforeach;
        else:
            $isClient = $user && ($user['role']??'') === 'client';
            $isExpert = $user && ($user['role'] ?? '') === 'expert';
            $clientBp = (!empty($client_base_path) && in_array($client_base_path, ['/client', '/app'], true))
                ? $client_base_path
                : '/client';
            $reqPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '');
            if ($clientBp === '/client' && strpos($reqPath, '/app/') !== false) {
                $clientBp = '/app';
            }
            if ($isClient) {
                $clientNavDemandes = $baseUrl . ($clientBp === '/app' ? '/app/demandes' : '/client/demandes');
                $navItems = [
                    ['id'=>'home', 'href'=>$baseUrl.'/client', 'active'=>in_array($navActive??'',['accueil','client'],true), 'label'=>__("nav.home")],
                    ['id'=>'experts', 'href'=>$baseUrl.'/experts', 'active'=>($navActive??'')==='experts', 'label'=>'Experts'],
                    ['id'=>'demandes', 'href'=>$clientNavDemandes, 'active'=>in_array($navActive??'', ['demandes','demandes_public'], true), 'label'=>__("nav.requests")],
                    ['id'=>'messages', 'href'=>$baseUrl.'/messages', 'active'=>($navActive??'')==='messages', 'label'=>__("nav.messages")],
                ];
            } else {
                $navItems = [
                    ['id'=>'home', 'href'=>$isExpert ? $baseUrl.'/expert' : $baseUrl.'/app', 'active'=>in_array($navActive??'',['accueil','client','expert'],true), 'label'=>__("nav.home")],
                ];
                if (!$user) {
                    $navItems[] = ['id'=>'experts', 'href'=>$baseUrl.'/app/experts', 'active'=>($navActive??'')==='experts', 'label'=>'Experts'];
                }
                if ($isExpert) {
                    $navItems[] = ['id'=>'missions',     'href'=>$baseUrl.'/app/expert-missions',     'active'=>($navActive??'')==='missions',     'label'=>__("nav.missions")];
                    $navItems[] = ['id'=>'reservations', 'href'=>$baseUrl.'/app/expert-reservations', 'active'=>($navActive??'')==='reservations', 'label'=>__("nav.reservations")];
                    $navItems[] = ['id'=>'demandes',     'href'=>$baseUrl.'/app/expert-demandes',     'active'=>($navActive??'')==='demandes',     'label'=>__("nav.requests")];
                } else {
                    $demandesHref = $user ? $baseUrl.'/app/demandes' : $baseUrl.'/demandes';
                    $navItems[] = ['id'=>'demandes', 'href'=>$demandesHref, 'active'=>in_array($navActive??'', ['demandes','demandes_public'], true), 'label'=>__("nav.requests")];
                }
                $navItems[] = ['id'=>'messages', 'href'=>$baseUrl.'/messages', 'active'=>($navActive??'')==='messages', 'label'=>__("nav.messages")];
            }
            if (!$user) {
                $navItems[] = ['id'=>'login', 'href'=>$baseUrl.'/auth/connexion', 'active'=>false, 'label'=>__("nav.login")];
            }
            $nbNavDemM = (int) ($navBadgeDemandes ?? 0);
            foreach ($navItems as $item):
        ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $item['active'] ? 'active' : '' ?>" aria-label="<?= $item['label'] ?>">
            <span class="nav-icon-wrap">
                <span class="nav-icon"><?= $navSvg[$item['id']] ?? $navSvg['home'] ?></span>
                <?php if ($item['id'] === 'messages'): ?>
                <span class="nav-badge" data-nav-badge="messages"<?= $nbNavMsg < 1 ? ' hidden' : '' ?>><?= $nbNavMsg > 99 ? '99+' : (string) max(0, $nbNavMsg) ?></span>
                <?php endif; ?>
                <?php if ($item['id'] === 'reservations'): ?>
                <span class="nav-badge" data-nav-badge="reservations"<?= $nbNavRes < 1 ? ' hidden' : '' ?>><?= $nbNavRes > 99 ? '99+' : (string) max(0, $nbNavRes) ?></span>
                <?php endif; ?>
                <?php if ($item['id'] === 'demandes'): ?>
                    <?php if ($isClient && $nbNavDemM > 0): ?>
                    <span class="nav-badge nav-badge--orange" aria-label="<?= $nbNavDemM ?> demande<?= $nbNavDemM > 1 ? 's' : '' ?> en cours"><?= $nbNavDemM > 99 ? '99+' : $nbNavDemM ?></span>
                    <?php elseif (!$isClient): ?>
                    <?php $_nbDemPubM = (int) ($navBadgeDemandesPublic ?? 0); if ($_nbDemPubM > 0): ?>
                    <span class="nav-badge nav-badge--orange" aria-label="<?= $_nbDemPubM ?> demande<?= $_nbDemPubM > 1 ? 's' : '' ?> ouverte<?= $_nbDemPubM > 1 ? 's' : '' ?>"><?= $_nbDemPubM > 99 ? '99+' : $_nbDemPubM ?></span>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </span>
            <span class="nav-label"><?= $item['label'] ?></span>
        </a>
        <?php
            endforeach;
        endif;
        ?>
    </nav>

    <?php if (\App\Core\Auth::check() && defined('VAPID_PUBLIC_KEY') && VAPID_PUBLIC_KEY !== ''): ?>
    <!-- Bannière activation notifications push (au-dessus de la nav basse) -->
    <div id="push-notif-banner" class="push-notif-banner-mobile" style="display:none" role="alert" aria-live="polite">
        <span class="push-notif-banner-mobile__icon" aria-hidden="true">🔔</span>
        <span class="push-notif-banner-mobile__text">Activer les notifications pour ne rien manquer (messages, réservations…).</span>
        <div class="push-notif-banner-mobile__actions">
            <button type="button" id="push-enable-btn" class="push-notif-banner-mobile__btn-primary">Activer</button>
            <button type="button" id="push-banner-close" class="push-notif-banner-mobile__btn-close" aria-label="Fermer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>
    <style>
    .push-notif-banner-mobile {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        bottom: calc(var(--nav-height, 70px) + var(--safe-bottom, env(safe-area-inset-bottom, 0px)) + 12px);
        max-width: 480px;
        width: calc(100% - 1rem);
        background: #1e293b;
        color: #fff;
        border-radius: 14px;
        padding: 0.65rem 0.85rem;
        box-shadow: 0 10px 36px rgba(0,0,0,0.28);
        display: none;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 0.5rem;
        font-family: var(--font, inherit);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.45;
        -webkit-font-smoothing: antialiased;
    }
    .push-notif-banner-mobile__icon { flex-shrink: 0; font-size: 1.25rem; line-height: 1; }
    .push-notif-banner-mobile__text { flex: 1 1 auto; min-width: 0; }
    .push-notif-banner-mobile__actions {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 0.35rem;
    }
    .push-notif-banner-mobile__btn-primary {
        background: #16a34a;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.45rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
    .push-notif-banner-mobile__btn-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.35rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
    .push-notif-banner-mobile__btn-close:active {
        background: rgba(255,255,255,0.08);
        color: #e2e8f0;
    }
    .push-notif-banner-mobile__btn-primary:active {
        opacity: 0.92;
    }
    .push-notif-banner-mobile--animate {
        animation: globalo-slide-up-push-mobile .35s ease;
    }
    @keyframes globalo-slide-up-push-mobile {
        from { opacity: 0; transform: translateX(-50%) translateY(14px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
    </style>
    <?php endif; ?>

    <?php if (!empty($showMobileHeaderSearch)): ?>
    <script src="<?= $baseUrl ?>/assets/js/smart-search-home.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/smart-search-home.js') ?>" defer></script>
    <?php endif; ?>
    <script src="<?= $baseUrl ?>/assets/js/app.js" defer></script>
    <script src="<?= $baseUrl ?>/assets/js/mobile-nav-loading.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/mobile-nav-loading.js') ?>" defer></script>
    <?php if (\App\Core\Auth::check() && defined('VAPID_PUBLIC_KEY') && VAPID_PUBLIC_KEY !== ''): ?>
    <script>
    (function(){
        var VAPID_PUBLIC_KEY = <?= json_encode((string) VAPID_PUBLIC_KEY) ?>;
        var BASE_URL = <?= json_encode((string) $baseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        function urlB64ToUint8Array(b64) {
            var pad = '='.repeat((4 - b64.length % 4) % 4);
            var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
            var arr = new Uint8Array(raw.length);
            for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
            return arr;
        }

        function sendSubToServer(sub) {
            var json = sub.toJSON ? sub.toJSON() : JSON.parse(JSON.stringify(sub));
            return fetch(BASE_URL + '/api/push/subscribe', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(json)
            });
        }

        function subscribePush(reg) {
            reg.pushManager.getSubscription().then(function(sub) {
                if (sub) { sendSubToServer(sub); return; }
                reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlB64ToUint8Array(VAPID_PUBLIC_KEY)
                }).then(function(newSub) {
                    sendSubToServer(newSub);
                    showPushBanner(false);
                }).catch(function() { showPushBanner(false); });
            }).catch(function() { showPushBanner(false); });
        }

        function showPushBanner(show) {
            var banner = document.getElementById('push-notif-banner');
            if (!banner) return;
            if (show) {
                banner.style.display = 'flex';
                banner.classList.add('push-notif-banner-mobile--animate');
            } else {
                banner.style.display = 'none';
                banner.classList.remove('push-notif-banner-mobile--animate');
            }
        }

        navigator.serviceWorker.register(BASE_URL + '/sw.js', { scope: BASE_URL + '/' })
            .then(function(reg) {
                if (typeof Notification === 'undefined') return;
                if (Notification.permission === 'granted') {
                    subscribePush(reg);
                } else if (Notification.permission === 'default') {
                    setTimeout(function() { showPushBanner(true); }, 3000);
                    var btn = document.getElementById('push-enable-btn');
                    if (btn) {
                        btn.addEventListener('click', function() {
                            Notification.requestPermission().then(function(perm) {
                                if (perm === 'granted') { subscribePush(reg); }
                                showPushBanner(false);
                            });
                        });
                    }
                    var closeBtn = document.getElementById('push-banner-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() { showPushBanner(false); });
                    }
                }
            })
            .catch(function() {});
    })();
    </script>
    <?php endif; ?>
    <?php if (!empty($dispoPrompt)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/prestataire_disponibilite.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/prestataire_disponibilite.css') ?: time() ?>">
    <?php include __DIR__ . '/partials/prestataire_disponibilite_modal.php'; ?>
    <script src="<?= $baseUrl ?>/assets/js/prestataire_disponibilite.js?v=<?= @filemtime(PUBLIC_PATH . '/assets/js/prestataire_disponibilite.js') ?: time() ?>" defer></script>
    <?php endif; ?>

    <?php
    /* ── Popup vérification email (mobile) ── */
    $_evUidM = \App\Core\Auth::id();
    $_showEvPopupM = false;
    $_evEmailM = '';
    if ($_evUidM && \App\Core\Auth::check()) {
        if (!isset($_SESSION['_email_verifie'])) {
            $uRowM = (new \App\Models\UtilisateurModel())->find((int) $_evUidM);
            $_SESSION['_email_verifie']  = (is_array($uRowM) && !empty($uRowM['email_verifie'])) ? 1 : 0;
            $_SESSION['_ev_user_email']  = is_array($uRowM) ? ($uRowM['email'] ?? '') : '';
        }
        $_showEvPopupM = empty($_SESSION['_email_verifie']);
        $_evEmailM = $_SESSION['_ev_user_email'] ?? '';
    }
    ?>
    <?php if ($_showEvPopupM): ?>
    <!-- Toast nav-click (mobile) -->
    <div id="ev-nav-toast" role="alert" aria-live="assertive"
         style="display:none;position:fixed;top:calc(var(--header-height,60px) + .5rem);left:50%;transform:translateX(-50%);z-index:10001;width:calc(100% - 2rem);max-width:400px;background:#1e293b;color:#fff;border-radius:12px;padding:.8rem 1rem;box-shadow:0 8px 28px rgba(0,0,0,.28);font-size:.8125rem;line-height:1.5;font-family:inherit;animation:evToastIn .25s ease;">
        <div style="display:flex;align-items:flex-start;gap:.55rem;">
            <span style="flex-shrink:0;margin-top:.1rem;color:#fbbf24;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <span>Veuillez d'abord <strong>valider votre email</strong> pour accéder à cette section.</span>
        </div>
    </div>
    <style>
    @keyframes evToastIn { from { opacity:0; transform:translateX(-50%) translateY(-10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
    </style>
    <script>
    (function () {
        var toastTimerM = null;
        function showNavToastM() {
            var toast = document.getElementById('ev-nav-toast');
            if (!toast) return;
            clearTimeout(toastTimerM);
            toast.style.display = 'block';
            toast.style.animation = 'none';
            void toast.offsetWidth;
            toast.style.animation = 'evToastIn .25s ease';
            toastTimerM = setTimeout(function () { toast.style.display = 'none'; }, 3500);
        }
        // Intercepter les clics sur la nav basse mobile
        document.querySelectorAll('.nav-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                showNavToastM();
            });
        });
    })();
    </script>
    <!-- Popup vérification email (mobile) -->
    <div id="ev-modal" role="dialog" aria-modal="true" aria-labelledby="ev-modal-title"
         style="display:none;position:fixed;inset:0;z-index:10000;align-items:flex-end;justify-content:center;padding:0;">
        <div id="ev-modal-backdrop" style="position:absolute;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);"></div>
        <div style="position:relative;background:#fff;border-radius:22px 22px 0 0;box-shadow:0 -8px 40px rgba(0,0,0,.18);width:100%;max-width:560px;padding:1.75rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom, 0px));text-align:center;font-family:inherit;animation:evSlideUp .32s cubic-bezier(.34,1.2,.64,1);">
            <!-- Poignée -->
            <div style="width:40px;height:4px;border-radius:4px;background:#e2e8f0;margin:0 auto 1.25rem;"></div>
            <!-- Icône enveloppe -->
            <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#fef9c3,#fef08a);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </div>
            <h2 id="ev-modal-title" style="margin:0 0 .5rem;font-size:1.125rem;font-weight:700;color:#0f172a;line-height:1.3;">
                Vérifiez votre adresse email
            </h2>
            <p style="margin:0 0 1rem;font-size:.875rem;color:#475569;line-height:1.55;">
                Confirmez votre email pour accéder à <strong>toutes les fonctionnalités</strong> de votre espace.
            </p>
            <?php if ($_evEmailM !== ''): ?>
            <div style="display:inline-flex;align-items:center;gap:.4rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:.45rem .8rem;font-size:.8125rem;color:#334155;font-weight:500;margin-bottom:1.25rem;max-width:100%;word-break:break-all;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <?= \App\Core\Security::escape($_evEmailM) ?>
            </div>
            <?php endif; ?>
            <form id="ev-resend-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-bottom:.7rem;">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_evEmailM) ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" id="ev-resend-btn"
                        style="width:100%;background:#16a34a;color:#fff;border:none;border-radius:12px;padding:.8rem 1rem;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;-webkit-tap-highlight-color:transparent;touch-action:manipulation;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m3 3 1.664 9.526a2 2 0 0 0 2.104 1.65L21 12 6.768 9.824A2 2 0 0 1 5.108 7.76L3 3Z"/><path d="m14 12 3 7"/></svg>
                    Renvoyer le lien de vérification
                </button>
            </form>
            <button type="button" id="ev-modal-close"
                    style="background:none;border:none;color:#64748b;font-size:.875rem;font-weight:500;cursor:pointer;padding:.5rem 1rem;-webkit-tap-highlight-color:transparent;touch-action:manipulation;">
                Je vérifierai plus tard
            </button>
        </div>
    </div>
    <style>
    @keyframes evSlideUp {
        from { opacity: 0; transform: translateY(60px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    </style>
    <script>
    (function () {
        var modal = document.getElementById('ev-modal');
        if (!modal) return;
        var STORAGE_KEY = 'ev_dismissed_<?= (int) $_evUidM ?>';
        if (!sessionStorage.getItem(STORAGE_KEY)) {
            modal.style.display = 'flex';
        }
        function closeModal() {
            sessionStorage.setItem(STORAGE_KEY, '1');
            modal.style.display = 'none';
        }
        document.getElementById('ev-modal-close').addEventListener('click', closeModal);
        document.getElementById('ev-modal-backdrop').addEventListener('click', closeModal);
        document.getElementById('ev-resend-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('ev-resend-btn');
            btn.disabled = true;
            btn.innerHTML = 'Envoi en cours…';
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                credentials: 'include',
                redirect: 'manual'
            }).then(function () {
                btn.innerHTML = '✓ Email envoyé ! Consultez votre boîte mail';
                btn.style.background = '#0f766e';
                setTimeout(closeModal, 3000);
            }).catch(function () {
                btn.disabled = false;
                btn.innerHTML = 'Renvoyer le lien de vérification';
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
