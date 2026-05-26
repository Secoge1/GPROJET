<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
if ($baseUrl === '') {
    $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$user = $user ?? null;
$adminSection = $adminSection ?? 'index';
$lang = $lang ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\Security::escape($pageTitle ?? 'Administration - GLOBALO') ?></title>
    <link rel="icon" type="image/png" href="<?= logo_url() ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/desktop.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/desktop.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/admin.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="layout-admin">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?= $baseUrl ?>/" class="admin-sidebar-logo" aria-label="Globalo Accueil">
                <svg class="admin-sidebar-logo-svg" viewBox="0 0 140 38" width="140" height="38" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <text x="0" y="26" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="22" font-weight="700" fill="rgba(255,255,255,0.95)" letter-spacing="0.03em">GLOBALO</text>
                </svg>
            </a>
            <p class="admin-sidebar-title">Administration GLOBALO</p>
            <button class="admin-sidebar-close" id="adminSidebarClose" aria-label="Fermer le menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <nav class="admin-sidebar-nav" aria-label="Menu administration">
            <a href="<?= $baseUrl ?>/admin" class="admin-sidebar-link <?= $adminSection === 'index' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                Tableau de bord
            </a>
            <a href="<?= $baseUrl ?>/admin/users" class="admin-sidebar-link <?= $adminSection === 'users' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                Utilisateurs
            </a>
            <a href="<?= $baseUrl ?>/admin/experts" class="admin-sidebar-link <?= $adminSection === 'experts' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                Validation experts
            </a>
            <a href="<?= $baseUrl ?>/admin/professeurs" class="admin-sidebar-link <?= in_array($adminSection, ['professeurs', 'editProfesseur', 'validerProfesseur', 'invaliderProfesseur', 'toggleDisponibiliteProfesseur'], true) ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                Professeurs
            </a>
            <a href="<?= $baseUrl ?>/admin/revenus" class="admin-sidebar-link <?= in_array($adminSection, ['revenus', 'reservation'], true) ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                Revenus
            </a>
            <a href="<?= $baseUrl ?>/admin/growth" class="admin-sidebar-link <?= $adminSection === 'growth' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
                Growth
            </a>
            <a href="<?= $baseUrl ?>/admin/tracking" class="admin-sidebar-link <?= $adminSection === 'tracking' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span>
                Tracking utilisateurs
            </a>
            <a href="<?= $baseUrl ?>/admin/parametres" class="admin-sidebar-link <?= $adminSection === 'parametres' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                Paramètres
            </a>
            <a href="<?= $baseUrl ?>/admin/chatbot" class="admin-sidebar-link <?= $adminSection === 'chatbot' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                Chatbot IA
            </a>
            <a href="<?= $baseUrl ?>/admin/assistant-emails" class="admin-sidebar-link <?= $adminSection === 'assistantEmails' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-6l-2 3-4-6-3 3H2"/><path d="M2 6h20v12H2z"/></svg></span>
                Emails IA auto
            </a>
            <a href="<?= $baseUrl ?>/admin/social" class="admin-sidebar-link <?= $adminSection === 'social' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></span>
                Réseaux Sociaux
            </a>
            <a href="<?= $baseUrl ?>/admin/retraits" class="admin-sidebar-link <?= $adminSection === 'retraits' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                Retraits
            </a>
            <a href="<?= $baseUrl ?>/admin/abonnements" class="admin-sidebar-link <?= $adminSection === 'abonnements' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
                Abonnements
            </a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions" class="admin-sidebar-link <?= $adminSection === 'waveTransactions' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                Paiements Mobile Money
            </a>
            <a href="<?= $baseUrl ?>/admin/demandes" class="admin-sidebar-link <?= $adminSection === 'demandes' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                Demandes
            </a>
            <a href="<?= $baseUrl ?>/admin/signalements" class="admin-sidebar-link <?= $adminSection === 'signalements' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></span>
                Signalements
            </a>
            <!-- Espace RH avec IA -->
            <a href="<?= $baseUrl ?>/rh" class="admin-sidebar-link <?= ($adminSection === 'rh' && ($navActive ?? '') !== 'rh_whatsapp') ? 'active' : '' ?>" style="margin-top:8px;border-top:1px solid rgba(255,255,255,.06);padding-top:12px;">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                Espace RH · IA
            </a>
            <!-- WhatsApp IA — sous-lien RH -->
            <a href="<?= $baseUrl ?>/rh/whatsapp" class="admin-sidebar-link admin-sidebar-link--sub <?= ($navActive ?? '') === 'rh_whatsapp' ? 'active' : '' ?>">
                <span class="admin-sidebar-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </span>
                WhatsApp IA
            </a>
        </nav>
        <a href="<?= $baseUrl ?>/?vue=site" class="admin-sidebar-site" title="Voir le site public">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            <span>Voir le site</span>
        </a>
        <div class="admin-sidebar-footer">
            <a href="<?= $baseUrl ?>/auth/deconnexion" class="admin-sidebar-link admin-sidebar-logout">
                <span class="admin-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                Déconnexion
            </a>
        </div>
    </aside>
    <!-- Overlay pour fermer la sidebar sur mobile -->
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay" aria-hidden="true"></div>

    <main class="admin-main">
        <!-- Topbar mobile avec bouton hamburger -->
        <div class="admin-mobile-topbar">
            <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="adminSidebar">
                <span class="admin-toggle-icon admin-toggle-icon--hamburger" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </span>
                <span class="admin-toggle-icon admin-toggle-icon--close" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </span>
            </button>
            <a href="<?= $baseUrl ?>/" class="admin-mobile-topbar__logo" aria-label="Accueil">
                <svg viewBox="0 0 140 38" width="90" height="24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <text x="0" y="26" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="22" font-weight="700" fill="#0f172a" letter-spacing="0.03em">GLOBALO</text>
                </svg>
            </a>
            <span class="admin-mobile-topbar__badge">Admin</span>
        </div>
        <?= $content ?? '' ?>
    </main>

    <!-- Bottom navigation mobile (admin) -->
    <nav class="admin-bottom-nav" aria-label="Navigation rapide admin">
        <a href="<?= $baseUrl ?>/admin" class="admin-bn-item <?= $adminSection === 'index' ? 'active' : '' ?>">
            <span class="admin-bn-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </span>
            <span class="admin-bn-label">Board</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/users" class="admin-bn-item <?= $adminSection === 'users' ? 'active' : '' ?>">
            <span class="admin-bn-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span class="admin-bn-label">Utilisateurs</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/experts" class="admin-bn-item <?= $adminSection === 'experts' ? 'active' : '' ?>">
            <span class="admin-bn-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
            <span class="admin-bn-label">Experts</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/demandes" class="admin-bn-item <?= $adminSection === 'demandes' ? 'active' : '' ?>">
            <span class="admin-bn-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </span>
            <span class="admin-bn-label">Demandes</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/revenus" class="admin-bn-item <?= in_array($adminSection, ['revenus','reservation'], true) ? 'active' : '' ?>">
            <span class="admin-bn-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
            <span class="admin-bn-label">Revenus</span>
        </a>
    </nav>
    <script src="<?= $baseUrl ?>/assets/js/admin-export.js"></script>
    <script>
    (function () {
        var sidebar      = document.getElementById('adminSidebar');
        var toggle       = document.getElementById('adminSidebarToggle');
        var overlay      = document.getElementById('adminSidebarOverlay');
        var closeBtn     = document.getElementById('adminSidebarClose');

        function openSidebar() {
            sidebar.classList.add('is-open');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Fermer le menu');
            // Overlay : d'abord display:block, puis opacity pour la transition
            overlay.style.display = 'block';
            requestAnimationFrame(function () {
                overlay.classList.add('is-visible');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Ouvrir le menu');
            overlay.classList.remove('is-visible');
            // Cacher l'overlay après la fin de la transition CSS
            overlay.addEventListener('transitionend', function hideOverlay() {
                if (!overlay.classList.contains('is-visible')) {
                    overlay.style.display = 'none';
                }
                overlay.removeEventListener('transitionend', hideOverlay);
            });
            document.body.style.overflow = '';
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Fermer avec Echap
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });

        // Fermer automatiquement quand on clique sur un lien du menu (mobile)
        var sidebarLinks = sidebar ? sidebar.querySelectorAll('.admin-sidebar-link') : [];
        sidebarLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 640) closeSidebar();
            });
        });
    })();
    </script>
</body>
</html>
