<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
if ($baseUrl === '') {
    $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$user = $user ?? null;
$navActive = $navActive ?? '';
$_role = (is_array($user) && !empty($user['role'])) ? (string) $user['role'] : '';
if ($_role === '' && \App\Core\Auth::check()) {
    $_role = (string) (\App\Core\Auth::role() ?? '');
}
$dashboardRole = $_role === 'expert' ? 'expert' : ($_role === 'professeur' ? 'professeur' : ($_role === 'etudiant' ? 'etudiant' : 'client'));
$csrfField = \App\Core\Security::getCsrfField();
$lang = $lang ?? 'fr';
$seo = $seo ?? [];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= \App\Core\Security::escape($seo['description'] ?? 'Espace GLOBALO') ?>">
    <meta name="theme-color" content="#16a34a">
    <meta name="csrf-token" content="<?= \App\Core\Security::generateCsrfToken() ?>">
    <title><?= \App\Core\Security::escape($pageTitle ?? 'GLOBALO') ?></title>
    <link rel="icon" type="image/png" href="<?= logo_url() ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/desktop.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/desktop.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/intouch-operators.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/intouch-operators.css') ?>">
    <?php if (in_array($dashboardRole, ['etudiant', 'professeur'], true)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/etudiant.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/etudiant.css') ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="layout-dashboard layout-dashboard--<?= $dashboardRole ?>" data-base-url="<?= \App\Core\Security::escape($baseUrl) ?>" data-user-id="<?= (!empty($user) && isset($user['id'])) ? (int) $user['id'] : '' ?>">
    <style>
    .nav-badge-demandes {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #f97316;
        color: #fff;
        font-size: .6875rem;
        font-weight: 700;
        line-height: 1;
        margin-left: auto;
        flex-shrink: 0;
    }
    </style>
    <aside class="dashboard-sidebar">
        <div class="dashboard-sidebar-header">
            <a href="<?= $baseUrl ?>/" class="dashboard-sidebar-logo" aria-label="Globalo Accueil">
                <div class="dashboard-sidebar-logo-wrap">
                    <img src="<?= logo_url() ?>" alt="Globalo" height="34"
                         onerror="this.closest('.dashboard-sidebar-logo-wrap').style.display='none';var fb=document.getElementById('sidebar-logo-fb');if(fb)fb.style.display='inline-flex';">
                </div>
                <span id="sidebar-logo-fb" class="dashboard-sidebar-logo-fallback" style="display:none;">
                    <svg width="22" height="22" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="8" fill="rgba(255,255,255,0.15)"/>
                        <text x="16" y="22" font-family="system-ui,sans-serif" font-size="17" font-weight="800" fill="white" text-anchor="middle">G</text>
                    </svg>
                    GLOBALO
                </span>
            </a>
            <p class="dashboard-sidebar-title"><?= $dashboardRole === 'expert' ? 'Espace Expert' : ($dashboardRole === 'professeur' ? 'Espace Professeur' : ($dashboardRole === 'etudiant' ? 'Espace Étudiant' : 'Espace Client')) ?></p>
        </div>
        <nav class="dashboard-sidebar-nav" aria-label="Menu">
            <?php if ($dashboardRole === 'professeur'): ?>
                <a href="<?= $baseUrl ?>/professeur" class="dashboard-sidebar-link <?= in_array($navActive, ['etudiant', 'professeur'], true) ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                    Tableau de bord
                </a>
                <a href="<?= $baseUrl ?>/professeur/exercices-disponibles" class="dashboard-sidebar-link <?= $navActive === 'exercices' ? 'active' : '' ?>" style="position:relative;">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                    Exercices à corriger
                    <?php $_nbEx = (int) ($navBadgeExercices ?? 0); if ($_nbEx > 0): ?>
                    <span class="nav-badge-demandes" aria-label="<?= $_nbEx ?> exercice<?= $_nbEx > 1 ? 's' : '' ?> en attente"><?= $_nbEx > 99 ? '99+' : $_nbEx ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= $baseUrl ?>/professeur/portefeuille" class="dashboard-sidebar-link <?= $navActive === 'portefeuille' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                    Mon portefeuille
                </a>
                <a href="<?= $baseUrl ?>/professeur/retrait-choix" class="dashboard-sidebar-link <?= in_array($navActive, ['retrait', 'retraitChoix'], true) ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    Retrait Mobile Money
                </a>
                <a href="<?= $baseUrl ?>/messages" class="dashboard-sidebar-link <?= $navActive === 'messages' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    Messagerie
                </a>
                <a href="<?= $baseUrl ?>/professeur/profil" class="dashboard-sidebar-link <?= $navActive === 'profil' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                    Mon profil professeur
                </a>
                <a href="<?= $baseUrl ?>/abonnement" class="dashboard-sidebar-link <?= $navActive === 'abonnement' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg></span>
                    Mon abonnement
                </a>
                <a href="<?= $baseUrl ?>/professeur/compte" class="dashboard-sidebar-link <?= $navActive === 'compte' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    Mon compte
                </a>
            <?php elseif ($dashboardRole === 'etudiant'): ?>
                <a href="<?= $baseUrl ?>/etudiant" class="dashboard-sidebar-link <?= $navActive === 'etudiant' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                    Tableau de bord
                </a>
                <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="dashboard-sidebar-link <?= $navActive === 'exercice_nouveau' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
                    Soumettre un exercice
                </a>
                <a href="<?= $baseUrl ?>/etudiant/exercices" class="dashboard-sidebar-link <?= $navActive === 'exercices' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                    Mes exercices
                </a>
                <a href="<?= $baseUrl ?>/etudiant/matieres" class="dashboard-sidebar-link <?= $navActive === 'matieres' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
                    Matières
                </a>
                <a href="<?= $baseUrl ?>/experts" class="dashboard-sidebar-link <?= $navActive === 'experts' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    Trouver un tuteur
                </a>
                <a href="<?= $baseUrl ?>/professeurs" class="dashboard-sidebar-link <?= $navActive === 'professeurs' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                    <?= __("nav.professeurs") ?>
                </a>
                <a href="<?= $baseUrl ?>/messages" class="dashboard-sidebar-link <?= $navActive === 'messages' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    Messagerie
                </a>
                <a href="<?= $baseUrl ?>/etudiant/portefeuille" class="dashboard-sidebar-link <?= $navActive === 'portefeuille' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                    Mon portefeuille
                </a>
                <a href="<?= $baseUrl ?>/etudiant/profil" class="dashboard-sidebar-link <?= $navActive === 'profil' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                    Mon profil étudiant
                </a>
                <a href="<?= $baseUrl ?>/abonnement" class="dashboard-sidebar-link <?= $navActive === 'abonnement' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg></span>
                    Mon abonnement
                </a>
                <a href="<?= $baseUrl ?>/etudiant/compte" class="dashboard-sidebar-link <?= $navActive === 'compte' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    Mon compte
                </a>
            <?php elseif ($dashboardRole === 'client'): ?>
                <a href="<?= $baseUrl ?>/client" class="dashboard-sidebar-link <?= $navActive === 'client' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                    <?= __("nav.dashboard") ?>
                </a>
                <a href="<?= $baseUrl ?>/client/urgence" class="dashboard-sidebar-link <?= $navActive === 'urgence' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                    Besoin d'aide
                </a>
                <a href="<?= $baseUrl ?>/client/demandes" class="dashboard-sidebar-link <?= $navActive === 'demandes' ? 'active' : '' ?>" style="position:relative;">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span>
                    <?= __("nav.requests") ?>
                    <?php $nbNavDem = (int) ($navBadgeDemandes ?? 0); if ($nbNavDem > 0): ?>
                    <span class="nav-badge-demandes" aria-label="<?= $nbNavDem ?> demande<?= $nbNavDem > 1 ? 's' : '' ?> en cours"><?= $nbNavDem > 99 ? '99+' : $nbNavDem ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= $baseUrl ?>/client/reservations" class="dashboard-sidebar-link <?= $navActive === 'reservations' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <?= __("nav.reservations") ?>
                </a>
                <a href="<?= $baseUrl ?>/abonnement" class="dashboard-sidebar-link <?= $navActive === 'abonnement' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg></span>
                    Mon abonnement
                </a>
                <a href="<?= $baseUrl ?>/experts" class="dashboard-sidebar-link <?= $navActive === 'experts' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <?= __("nav.experts") ?>
                </a>
                <a href="<?= $baseUrl ?>/messages" class="dashboard-sidebar-link <?= $navActive === 'messages' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    <?= __("nav.messages") ?>
                </a>
                <a href="<?= $baseUrl ?>/client/portefeuille" class="dashboard-sidebar-link <?= $navActive === 'portefeuille' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                    Mon portefeuille
                </a>
                <a href="<?= $baseUrl ?>/client/compte" class="dashboard-sidebar-link <?= $navActive === 'compte' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    Mon compte
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/expert" class="dashboard-sidebar-link <?= $navActive === 'expert' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                    <?= __("nav.dashboard") ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/urgences" class="dashboard-sidebar-link <?= $navActive === 'urgences' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                    Missions urgentes
                </a>
                <a href="<?= $baseUrl ?>/expert/missions" class="dashboard-sidebar-link <?= $navActive === 'missions' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                    <?= __("nav.missions") ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/reservations" class="dashboard-sidebar-link <?= $navActive === 'reservations' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <?= __("nav.reservations") ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/revenus" class="dashboard-sidebar-link <?= $navActive === 'revenus' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <?= __("nav.revenue") ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/profil" class="dashboard-sidebar-link <?= $navActive === 'profil' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    Mon profil
                </a>
                <a href="<?= $baseUrl ?>/abonnement" class="dashboard-sidebar-link <?= $navActive === 'abonnement' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg></span>
                    Mon abonnement
                </a>
                <a href="<?= $baseUrl ?>/messages" class="dashboard-sidebar-link <?= $navActive === 'messages' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    <?= __("nav.messages") ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/demandes" class="dashboard-sidebar-link <?= $navActive === 'demandes' ? 'active' : '' ?>" style="position:relative;">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg></span>
                    Demandes clients
                    <?php $_nbDemExp = (int) ($navBadgeDemandesPublic ?? 0); if ($_nbDemExp > 0): ?>
                    <span class="nav-badge-demandes" aria-label="<?= $_nbDemExp ?> demande<?= $_nbDemExp > 1 ? 's' : '' ?> ouverte<?= $_nbDemExp > 1 ? 's' : '' ?>"><?= $_nbDemExp > 99 ? '99+' : $_nbDemExp ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= $baseUrl ?>/expert/retrait-choix" class="dashboard-sidebar-link <?= in_array($navActive, ['retrait', 'retraitChoix'], true) ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    Retrait
                </a>
                <a href="<?= $baseUrl ?>/expert/compte" class="dashboard-sidebar-link <?= $navActive === 'compte' ? 'active' : '' ?>">
                    <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    Mon compte
                </a>
            <?php endif; ?>
        </nav>
        <div class="dashboard-sidebar-footer">
            <?php if (\App\Core\Auth::check() && \App\Core\Auth::role() === 'admin'): ?>
            <a href="<?= $baseUrl ?>/rh" class="dashboard-sidebar-link <?= (strpos($navActive, 'rh') === 0) ? 'active' : '' ?>" style="background:rgba(16,185,129,.08);border-left:2px solid #10b981;margin:0 8px 4px;border-radius:8px;">
                <span class="dashboard-sidebar-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                <span style="color:#34d399;font-weight:600;">Espace RH · IA</span>
            </a>
            <?php endif; ?>
            <!-- Voir le site public -->
            <a href="<?= $baseUrl ?>/?vue=site" class="dashboard-sidebar-site" title="Retour au site public">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span>Visiter le site</span>
            </a>
            <!-- Langue -->
            <span class="nav-lang" aria-label="Langue" style="display:flex;align-items:center;gap:0.5rem;padding:0.45rem 1rem;font-size:0.8125rem;color:var(--dashboard-sidebar-text-muted);">
                <?php $cur = $lang ?? 'fr'; ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <a href="<?= $baseUrl ?>/auth/lang/fr" class="<?= $cur === 'fr' ? 'active' : '' ?>" style="color:inherit;text-decoration:none;font-weight:<?= $cur === 'fr' ? '700' : '400' ?>;">FR</a>
                <span style="opacity:.4;">|</span>
                <a href="<?= $baseUrl ?>/auth/lang/en" class="<?= $cur === 'en' ? 'active' : '' ?>" style="color:inherit;text-decoration:none;font-weight:<?= $cur === 'en' ? '700' : '400' ?>;">EN</a>
            </span>
            <!-- Déconnexion -->
            <a href="<?= $baseUrl ?>/auth/deconnexion" class="dashboard-sidebar-link dashboard-sidebar-logout">
                <span class="dashboard-sidebar-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </span>
                <?= __("nav.logout") ?>
            </a>
        </div>
    </aside>
    <main class="dashboard-main">
        <?php
        /* Bannière email non-vérifié — DB query si cache session absent */
        if (\App\Core\Auth::check() && !isset($_SESSION['_email_verifie'])) {
            $_uRowEv = (new \App\Models\UtilisateurModel())->find((int) \App\Core\Auth::id());
            $_SESSION['_email_verifie']  = (is_array($_uRowEv) && !empty($_uRowEv['email_verifie'])) ? 1 : 0;
            $_SESSION['_ev_user_email']  = is_array($_uRowEv) ? ($_uRowEv['email'] ?? '') : '';
            unset($_uRowEv);
        }
        $_evBanner = \App\Core\Auth::check() && isset($_SESSION['_email_verifie']) && empty($_SESSION['_email_verifie']);
        ?>
        <?php if ($_evBanner): ?>
        <div class="ev-top-banner" id="ev-top-banner" role="alert">
            <span class="ev-top-banner__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </span>
            <span class="ev-top-banner__msg">
                Validez votre adresse email pour accéder à <strong>toutes les fonctionnalités</strong> de votre espace.
            </span>
            <form class="ev-top-banner__form" id="ev-banner-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_SESSION['_ev_user_email'] ?? '') ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" class="ev-top-banner__btn" id="ev-banner-resend">Renvoyer le lien</button>
            </form>
        </div>
        <style>
        .ev-top-banner {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: .7rem 1rem;
            margin: 0 0 1.25rem;
            font-size: .875rem;
            color: #78350f;
            line-height: 1.45;
        }
        .ev-top-banner__icon { flex-shrink: 0; color: #d97706; display: flex; }
        .ev-top-banner__msg  { flex: 1 1 auto; min-width: 0; }
        .ev-top-banner__form { flex-shrink: 0; }
        .ev-top-banner__btn  {
            background: #f59e0b;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: .35rem .85rem;
            font-size: .8125rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
        }
        .ev-top-banner__btn:hover { background: #d97706; }
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
                    btn.textContent = '✓ Email envoyé !';
                    btn.style.background = '#16a34a';
                    setTimeout(function () {
                        btn.disabled = false;
                        btn.textContent = 'Renvoyer le lien';
                        btn.style.background = '';
                    }, 4000);
                }).catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Renvoyer le lien';
                });
            });
        })();
        </script>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>
    <?php include __DIR__ . '/partials/cookie_banner.php'; ?>

    <?php
    /* ── Popup vérification email ── */
    $_evUid = \App\Core\Auth::id();
    $_showEmailVerifyPopup = false;
    $_evUserEmail = '';
    if ($_evUid && \App\Core\Auth::check()) {
        if (!isset($_SESSION['_email_verifie'])) {
            $uRow = (new \App\Models\UtilisateurModel())->find((int) $_evUid);
            $_SESSION['_email_verifie']    = (is_array($uRow) && !empty($uRow['email_verifie'])) ? 1 : 0;
            $_SESSION['_ev_user_email']    = is_array($uRow) ? ($uRow['email'] ?? '') : '';
        }
        $_showEmailVerifyPopup = empty($_SESSION['_email_verifie']);
        $_evUserEmail = $_SESSION['_ev_user_email'] ?? '';
    }
    ?>
    <?php if ($_showEmailVerifyPopup): ?>
    <!-- Toast nav-click + Popup vérification email -->
    <!-- Toast affiché sur clic de n'importe quel lien sidebar -->
    <div id="ev-nav-toast" role="alert" aria-live="assertive"
         style="display:none;position:fixed;top:1.25rem;right:1.5rem;z-index:10001;max-width:340px;background:#1e293b;color:#fff;border-radius:12px;padding:.85rem 1.1rem;box-shadow:0 8px 32px rgba(0,0,0,.28);font-size:.875rem;line-height:1.5;font-family:inherit;animation:evToastIn .25s ease;">
        <div style="display:flex;align-items:flex-start;gap:.6rem;">
            <span style="flex-shrink:0;margin-top:.1rem;color:#fbbf24;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <span>Veuillez d'abord <strong>valider votre adresse email</strong> pour accéder à cette section.</span>
        </div>
    </div>
    <style>
    @keyframes evToastIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    </style>
    <script>
    (function () {
        var toastTimer = null;
        function showNavToast() {
            var toast = document.getElementById('ev-nav-toast');
            if (!toast) return;
            clearTimeout(toastTimer);
            toast.style.display = 'block';
            toast.style.animation = 'none';
            void toast.offsetWidth; // reflow
            toast.style.animation = 'evToastIn .25s ease';
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 3500);
        }
        // Intercepter les clics sur les liens du sidebar
        document.querySelectorAll('.dashboard-sidebar-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                // Ne pas bloquer les liens logout/lang
                if (link.closest('.dashboard-sidebar-footer')) return;
                e.preventDefault();
                showNavToast();
            });
        });
    })();
    </script>
    <div id="ev-modal" role="dialog" aria-modal="true" aria-labelledby="ev-modal-title"
         style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;padding:1rem;">
        <div id="ev-modal-backdrop" style="position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);"></div>
        <div style="position:relative;background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.22);max-width:440px;width:100%;padding:2.25rem 2rem 2rem;text-align:center;font-family:inherit;animation:evSlideIn .3s cubic-bezier(.34,1.56,.64,1);">
            <!-- Icône enveloppe -->
            <div style="width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,#fef9c3,#fef08a);margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 18px rgba(234,179,8,.2);">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </div>
            <!-- Titre -->
            <h2 id="ev-modal-title" style="margin:0 0 .6rem;font-size:1.25rem;font-weight:700;color:#0f172a;line-height:1.3;">
                Vérifiez votre adresse email
            </h2>
            <!-- Description -->
            <p style="margin:0 0 1.25rem;font-size:.9375rem;color:#475569;line-height:1.55;">
                Pour accéder à <strong>toutes les fonctionnalités</strong> de votre espace, confirmez votre adresse email en cliquant sur le lien que nous vous avons envoyé.
            </p>
            <!-- Email de l'utilisateur -->
            <?php if ($_evUserEmail !== ''): ?>
            <div style="display:inline-flex;align-items:center;gap:.45rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem .9rem;font-size:.875rem;color:#334155;font-weight:500;margin-bottom:1.5rem;max-width:100%;word-break:break-all;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <?= \App\Core\Security::escape($_evUserEmail) ?>
            </div>
            <?php endif; ?>
            <!-- Bouton renvoyer -->
            <form id="ev-resend-form" method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-bottom:.85rem;">
                <input type="hidden" name="email" value="<?= \App\Core\Security::escape($_evUserEmail) ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <button type="submit" id="ev-resend-btn"
                        style="width:100%;background:#16a34a;color:#fff;border:none;border-radius:10px;padding:.75rem 1.25rem;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .18s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m3 3 1.664 9.526a2 2 0 0 0 2.104 1.65L21 12 6.768 9.824A2 2 0 0 1 5.108 7.76L3 3Z"/><path d="m14 12 3 7"/></svg>
                    Renvoyer le lien de vérification
                </button>
            </form>
            <!-- Fermer -->
            <button type="button" id="ev-modal-close"
                    style="background:none;border:none;color:#64748b;font-size:.875rem;font-weight:500;cursor:pointer;padding:.4rem .75rem;border-radius:8px;transition:background .15s;">
                Je vérifierai plus tard
            </button>
        </div>
    </div>
    <style>
    @keyframes evSlideIn {
        from { opacity: 0; transform: scale(.93) translateY(12px); }
        to   { opacity: 1; transform: scale(1)  translateY(0); }
    }
    #ev-resend-btn:hover  { background: #15803d !important; }
    #ev-modal-close:hover { background: #f1f5f9 !important; color: #1e293b !important; }
    </style>
    <script>
    (function () {
        var modal = document.getElementById('ev-modal');
        if (!modal) return;
        var STORAGE_KEY = 'ev_dismissed_<?= (int) $_evUid ?>';
        // Afficher si pas encore fermé cette session
        if (!sessionStorage.getItem(STORAGE_KEY)) {
            modal.style.display = 'flex';
        }
        function closeModal() {
            sessionStorage.setItem(STORAGE_KEY, '1');
            modal.style.display = 'none';
        }
        document.getElementById('ev-modal-close').addEventListener('click', closeModal);
        document.getElementById('ev-modal-backdrop').addEventListener('click', closeModal);
        // Envoi AJAX du formulaire de renvoi
        document.getElementById('ev-resend-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('ev-resend-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Envoi en cours…';
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                credentials: 'include',
                redirect: 'manual'
            }).then(function () {
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Email envoyé ! Consultez votre boîte mail';
                btn.style.background = '#0f766e';
                setTimeout(closeModal, 3000);
            }).catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m3 3 1.664 9.526a2 2 0 0 0 2.104 1.65L21 12 6.768 9.824A2 2 0 0 1 5.108 7.76L3 3Z"/><path d="m14 12 3 7"/></svg> Renvoyer le lien de vérification';
            });
        });
    })();
    </script>
    <?php endif; ?>

    <?php if (!empty($dispoPrompt)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/prestataire_disponibilite.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/prestataire_disponibilite.css') ?: time() ?>">
    <?php include __DIR__ . '/partials/prestataire_disponibilite_modal.php'; ?>
    <script src="<?= $baseUrl ?>/assets/js/prestataire_disponibilite.js?v=<?= @filemtime(PUBLIC_PATH . '/assets/js/prestataire_disponibilite.js') ?: time() ?>"></script>
    <?php endif; ?>
    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/cookie-consent.js"></script>

    <!-- ── Push Notifications Web (VAPID) ── -->
    <script>
    (function(){
        var VAPID_PUBLIC_KEY = <?= json_encode(
            defined('VAPID_PUBLIC_KEY') ? (string) VAPID_PUBLIC_KEY : '',
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        ) ?: '""' ?>;
        var BASE_URL = '<?= $baseUrl ?>';

        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !VAPID_PUBLIC_KEY) return;

        function urlB64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var arr = new Uint8Array(rawData.length);
            for (var i = 0; i < rawData.length; ++i) arr[i] = rawData.charCodeAt(i);
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
            });
        }

        function showPushBanner(show) {
            var banner = document.getElementById('push-notif-banner');
            if (banner) banner.style.display = show ? 'flex' : 'none';
        }

        navigator.serviceWorker.register(BASE_URL + '/sw.js', { scope: BASE_URL + '/' })
        .then(function(reg) {
            if (Notification.permission === 'granted') {
                subscribePush(reg);
            } else if (Notification.permission === 'default') {
                // Afficher le bouton d'activation après 3s
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
        }).catch(function() {});
    })();
    </script>

    <!-- Bannière d'activation des notifications push -->
    <div id="push-notif-banner" style="display:none;position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);z-index:9999;background:#1e293b;color:#fff;border-radius:14px;padding:0.9rem 1.25rem;box-shadow:0 8px 32px rgba(0,0,0,0.25);align-items:center;gap:0.85rem;max-width:420px;width:calc(100% - 2rem);font-family:inherit;animation:slideUp .35s ease;" role="alert" aria-live="polite">
        <span style="font-size:1.4rem;flex-shrink:0">🔔</span>
        <span style="flex:1;font-size:0.875rem;font-weight:500;line-height:1.4">Activer les notifications pour ne rien manquer</span>
        <button id="push-enable-btn" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:0.45rem 1rem;font-size:0.8125rem;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0">Activer</button>
        <button id="push-banner-close" style="background:transparent;border:none;color:#94a3b8;cursor:pointer;padding:0.2rem;flex-shrink:0;display:flex" aria-label="Fermer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <style>
    @keyframes slideUp { from { opacity:0; transform:translateX(-50%) translateY(20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
    </style>
</body>
</html>
