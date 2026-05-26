<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$recentUsers = $recentUsers ?? [];
$recentReservations = $recentReservations ?? [];
$stats = $stats ?? [];
$payment_stats = $payment_stats ?? ['paiements' => [], 'transactions' => [], 'retraits_experts' => 0, 'retraits_professeurs' => 0];
$pp = $payment_stats['paiements'] ?? [];
$tx = $payment_stats['transactions'] ?? [];
$vs = $visitor_stats ?? [];
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };

// Sparkline pour les 7 derniers jours
$last7 = $vs['last7days'] ?? [];
$sparkValues = [];
for ($d = 6; $d >= 0; $d--) {
    $day = date('Y-m-d', strtotime("-{$d} days"));
    $found = 0;
    foreach ($last7 as $row) {
        if (($row['day'] ?? '') === $day) { $found = (int)$row['views']; break; }
    }
    $sparkValues[] = $found;
}
$sparkMax = max(1, max($sparkValues));

// Countries data for map
$countriesRaw = $vs['countries'] ?? [];
$countryMap = [];
foreach ($countriesRaw as $row) {
    if (!empty($row['pays'])) {
        $countryMap[strtoupper($row['pays'])] = (int)$row['visits'];
    }
}
$topCountries = array_slice($countriesRaw, 0, 8);

// Device stats
$deviceStats  = $vs['devices']['devices'] ?? [];
$browserStats = $vs['devices']['browsers'] ?? [];
$totalDevices = max(1, array_sum($deviceStats));
$totalBrowsers = max(1, array_sum($browserStats));
$migrationManquante = $migration_professeur_needed ?? [];

// Alertes urgentes
$urgentRetraitsExperts = (int)($payment_stats['retraits_experts'] ?? 0);
$urgentRetraitsProfs   = (int)($payment_stats['retraits_professeurs'] ?? 0);
$urgentEscrow          = (int)($pp['session_escrow_bloque'] ?? 0);
$urgentMM              = (int)($tx['pending_a_valider'] ?? 0);
$hasUrgent             = ($urgentRetraitsExperts + $urgentRetraitsProfs + $urgentEscrow + $urgentMM + count($migrationManquante)) > 0;
?>
<div class="page-admin page-admin-index pad2">

    <!-- ══════════════════════════════════════════
         HERO
    ══════════════════════════════════════════ -->
    <header class="adm-hero">
        <div class="adm-hero__left">
            <div class="adm-hero__live">
                <span class="adm-hero__dot"></span>
                Live
            </div>
            <h1 class="adm-hero__title">Tableau de bord</h1>
            <p class="adm-hero__sub">Vue d'ensemble — <span id="dash-datetime"></span></p>
        </div>
        <div class="adm-hero__actions">
            <div class="adm-refresh" id="dash-refresh-box">
                <button class="adm-refresh__btn" id="dash-refresh-toggle" aria-label="Pause/Reprendre">
                    <svg id="dash-rf-pause" width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                    <svg id="dash-rf-play"  width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style="display:none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </button>
                <span class="adm-refresh__label">Actualisation dans <strong id="dash-rf-count">30</strong>s</span>
                <div class="adm-refresh__bar"><div id="dash-rf-bar"></div></div>
            </div>
            <a href="<?= $baseUrl ?>/admin/users" class="adm-btn adm-btn--primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Utilisateurs
            </a>
            <a href="<?= $baseUrl ?>/admin/experts" class="adm-btn adm-btn--outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Experts
            </a>
            <a href="<?= $baseUrl ?>/rh" class="adm-btn adm-btn--outline" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(5,150,105,.1));border-color:rgba(16,185,129,.4);color:#34d399;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Espace RH · IA
            </a>
            <a href="<?= $baseUrl ?>/admin/parametres" class="adm-btn adm-btn--ghost">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Paramètres
            </a>
        </div>
    </header>

    <!-- ══════════════════════════════════════════
         ALERTES URGENTES
    ══════════════════════════════════════════ -->
    <?php if ($hasUrgent): ?>
    <div class="adm-alerts">
        <?php if (!empty($migrationManquante)): ?>
        <div class="adm-alert adm-alert--danger">
            <div class="adm-alert__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="adm-alert__body">
                <strong>Migration base de données requise — Rôle Professeur / Étudiant</strong>
                <p>Tables manquantes : <?= implode(', ', array_map('htmlspecialchars', $migrationManquante)) ?>. Exécutez <code>database/migration_professeur_complet.sql</code> via phpMyAdmin.</p>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($urgentRetraitsExperts > 0): ?>
        <a href="<?= $baseUrl ?>/admin/retraits" class="adm-alert adm-alert--warn adm-alert--link">
            <div class="adm-alert__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div class="adm-alert__body"><strong><?= $urgentRetraitsExperts ?> retrait(s) expert en attente</strong><p>Cliquez pour traiter les demandes de retrait des experts.</p></div>
            <svg class="adm-alert__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
        <?php if ($urgentRetraitsProfs > 0): ?>
        <a href="<?= $baseUrl ?>/admin/retraits" class="adm-alert adm-alert--warn adm-alert--link">
            <div class="adm-alert__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
            <div class="adm-alert__body"><strong><?= $urgentRetraitsProfs ?> retrait(s) professeur en attente</strong><p>Cliquez pour traiter les demandes de retrait des professeurs.</p></div>
            <svg class="adm-alert__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
        <?php if ($urgentEscrow > 0): ?>
        <a href="<?= $baseUrl ?>/admin/revenus" class="adm-alert adm-alert--info adm-alert--link">
            <div class="adm-alert__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
            <div class="adm-alert__body"><strong><?= $urgentEscrow ?> paiement(s) escrow bloqué(s)</strong><p>Des sessions ont un escrow en attente de libération.</p></div>
            <svg class="adm-alert__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
        <?php if ($urgentMM > 0): ?>
        <a href="<?= $baseUrl ?>/admin/wave-transactions" class="adm-alert adm-alert--info adm-alert--link">
            <div class="adm-alert__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></div>
            <div class="adm-alert__body"><strong><?= $urgentMM ?> transaction(s) Mobile Money à valider</strong><p>Cliquez pour valider les transactions Wave/Orange Money en attente.</p></div>
            <svg class="adm-alert__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════ -->
    <div class="adm-kpis">
        <a href="<?= $baseUrl ?>/admin/users" class="adm-kpi adm-kpi--blue">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((int)($stats['total_utilisateurs'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Utilisateurs inscrits</span>
                <div class="adm-kpi__pills">
                    <span class="adm-pill adm-pill--blue"><?= (int)($stats['total_clients'] ?? 0) ?> clients</span>
                    <span class="adm-pill adm-pill--green"><?= (int)($stats['total_experts'] ?? 0) ?> experts</span>
                </div>
            </div>
        </a>
        <a href="<?= $baseUrl ?>/admin/experts" class="adm-kpi adm-kpi--green">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((int)($stats['total_experts_valides'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Experts validés</span>
                <span class="adm-kpi__sub"><?= (int)($stats['total_experts'] ?? 0) ?> inscrit(s) au total</span>
            </div>
        </a>
        <a href="<?= $baseUrl ?>/admin/professeurs" class="adm-kpi adm-kpi--purple">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((int)($stats['total_professeurs'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Professeurs</span>
                <span class="adm-kpi__sub"><?= (int)($stats['total_etudiants'] ?? 0) ?> étudiant(s)</span>
            </div>
        </a>
        <a href="<?= $baseUrl ?>/admin/revenus" class="adm-kpi adm-kpi--orange">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((int)($stats['total_reservations'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Réservations</span>
                <span class="adm-kpi__sub"><?= (int)($stats['total_demandes'] ?? 0) ?> demande(s)</span>
            </div>
        </a>
        <div class="adm-kpi adm-kpi--sky">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((int)($vs['today_views'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Vues aujourd'hui</span>
                <span class="adm-kpi__sub"><?= (int)($vs['today_unique'] ?? 0) ?> visiteur(s) unique(s)</span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/admin/revenus" class="adm-kpi adm-kpi--yellow">
            <div class="adm-kpi__accent"></div>
            <div class="adm-kpi__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="adm-kpi__body">
                <span class="adm-kpi__val"><?= number_format((float)($stats['total_commissions'] ?? 0), 0, ',', ' ') ?></span>
                <span class="adm-kpi__label">Commissions totales</span>
                <span class="adm-kpi__sub">XOF</span>
            </div>
        </a>
    </div>

    <!-- ══════════════════════════════════════════
         ACCÈS RAPIDE — GESTION
    ══════════════════════════════════════════ -->
    <div class="adm-section">
        <div class="adm-section__head">
            <h2 class="adm-section__title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Accès rapide — Gestion
            </h2>
        </div>
        <div class="adm-shortcuts">
            <a href="<?= $baseUrl ?>/admin/users" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#2563eb;--scbg:#eff6ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="adm-shortcut__label">Utilisateurs</span>
                <span class="adm-shortcut__count"><?= (int)($stats['total_utilisateurs'] ?? 0) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/experts" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#16a34a;--scbg:#f0fdf4">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <span class="adm-shortcut__label">Experts</span>
                <span class="adm-shortcut__count"><?= (int)($stats['total_experts'] ?? 0) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/professeurs" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#7c3aed;--scbg:#f5f3ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <span class="adm-shortcut__label">Professeurs</span>
                <span class="adm-shortcut__count"><?= (int)($stats['total_professeurs'] ?? 0) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/demandes" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#ea580c;--scbg:#fff7ed">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <span class="adm-shortcut__label">Demandes</span>
                <span class="adm-shortcut__count"><?= (int)($stats['total_demandes'] ?? 0) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/revenus" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#0891b2;--scbg:#ecfeff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <span class="adm-shortcut__label">Réservations</span>
                <span class="adm-shortcut__count"><?= (int)($stats['total_reservations'] ?? 0) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/retraits" class="adm-shortcut <?= ($urgentRetraitsExperts + $urgentRetraitsProfs) > 0 ? 'adm-shortcut--urgent' : '' ?>">
                <div class="adm-shortcut__icon" style="--sc:#dc2626;--scbg:#fef2f2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <span class="adm-shortcut__label">Retraits</span>
                <?php $pendingRetraits = $urgentRetraitsExperts + $urgentRetraitsProfs; ?>
                <span class="adm-shortcut__count <?= $pendingRetraits > 0 ? 'adm-shortcut__count--red' : '' ?>"><?= $pendingRetraits ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions" class="adm-shortcut <?= $urgentMM > 0 ? 'adm-shortcut--urgent' : '' ?>">
                <div class="adm-shortcut__icon" style="--sc:#2563eb;--scbg:#eff6ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <span class="adm-shortcut__label">Mobile Money</span>
                <span class="adm-shortcut__count <?= $urgentMM > 0 ? 'adm-shortcut__count--red' : '' ?>"><?= $urgentMM ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/abonnements" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#db2777;--scbg:#fdf2f8">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                </div>
                <span class="adm-shortcut__label">Abonnements</span>
                <span class="adm-shortcut__count">—</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/signalements" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#b45309;--scbg:#fffbeb">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                </div>
                <span class="adm-shortcut__label">Signalements</span>
                <span class="adm-shortcut__count">—</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/tracking" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#0891b2;--scbg:#ecfeff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <span class="adm-shortcut__label">Tracking</span>
                <span class="adm-shortcut__count"><?= number_format((int)($vs['today_views'] ?? 0)) ?></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/growth" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#16a34a;--scbg:#f0fdf4">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <span class="adm-shortcut__label">Growth</span>
                <span class="adm-shortcut__count">→</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/chatbot" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#2563eb;--scbg:#eff6ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <span class="adm-shortcut__label">Chatbot IA</span>
                <span class="adm-shortcut__count">→</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/social" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#1d4ed8;--scbg:#eff6ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                </div>
                <span class="adm-shortcut__label">Réseaux sociaux</span>
                <span class="adm-shortcut__count">→</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/relances-rapport" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#0ea5e9;--scbg:#f0f9ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="adm-shortcut__label">Relances ARIA &amp; PROFIA</span>
                <span class="adm-shortcut__count">→</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/assistant-emails" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#7c3aed;--scbg:#f5f3ff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <span class="adm-shortcut__label">Emails IA</span>
                <span class="adm-shortcut__count">→</span>
            </a>
            <a href="<?= $baseUrl ?>/admin/parametres" class="adm-shortcut">
                <div class="adm-shortcut__icon" style="--sc:#475569;--scbg:#f1f5f9">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </div>
                <span class="adm-shortcut__label">Paramètres</span>
                <span class="adm-shortcut__count">→</span>
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         TRAFIC + PAIEMENTS
    ══════════════════════════════════════════ -->
    <div class="adm-row adm-row--3-2">

        <!-- Sparkline -->
        <div class="adm-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Trafic — 7 derniers jours
                </h2>
                <a href="<?= $baseUrl ?>/admin/tracking" class="adm-card__action">Voir tout</a>
            </div>
            <div class="adm-spark-wrap">
                <svg class="adm-spark-svg" viewBox="0 0 340 90" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="spGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity=".18"/>
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <?php
                    $pts = [];
                    $n = count($sparkValues);
                    $w = 340; $h = 90; $pad = 8;
                    for ($i = 0; $i < $n; $i++) {
                        $x = $pad + ($i / max(1, $n - 1)) * ($w - 2 * $pad);
                        $y = $h - $pad - (($sparkValues[$i] / $sparkMax) * ($h - 2 * $pad));
                        $pts[] = "{$x},{$y}";
                    }
                    $polyline = implode(' ', $pts);
                    $firstPt = $pts[0] ?? "0,{$h}";
                    $lastPt  = $pts[count($pts) - 1] ?? "{$w},{$h}";
                    [$lx, $ly] = explode(',', $lastPt);
                    [$fx, $fy] = explode(',', $firstPt);
                    $areaPath = "M {$fx},{$h} L {$polyline} L {$lx},{$h} Z";
                    ?>
                    <path d="<?= $areaPath ?>" fill="url(#spGrad)"/>
                    <polyline points="<?= $polyline ?>" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <?php foreach ($pts as $pt):
                        [$px, $py] = explode(',', $pt); ?>
                    <circle cx="<?= $px ?>" cy="<?= $py ?>" r="3.5" fill="#fff" stroke="#2563eb" stroke-width="2"/>
                    <?php endforeach; ?>
                </svg>
                <div class="adm-spark-labels">
                    <?php for ($d = 6; $d >= 0; $d--): ?>
                    <span><?= date('d/m', strtotime("-{$d} days")) ?></span>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="adm-spark-footer">
                <div class="adm-spark-stat"><span><?= array_sum($sparkValues) ?></span><small>vues / 7j</small></div>
                <div class="adm-spark-stat"><span><?= max($sparkValues) ?></span><small>pic</small></div>
                <div class="adm-spark-stat"><span><?= (int)($vs['today_views'] ?? 0) ?></span><small>aujourd'hui</small></div>
                <div class="adm-spark-stat"><span><?= (int)($vs['today_unique'] ?? 0) ?></span><small>uniques</small></div>
            </div>
        </div>

        <!-- Paiements -->
        <div class="adm-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Paiements & flux
                </h2>
                <div style="display:flex;gap:6px;">
                    <a href="<?= $baseUrl ?>/admin/revenus" class="adm-card__action">Revenus</a>
                    <a href="<?= $baseUrl ?>/admin/wave-transactions" class="adm-card__action">Mobile Money</a>
                </div>
            </div>
            <div class="adm-pay-grid">
                <div class="adm-pay-item <?= $urgentEscrow > 0 ? 'adm-pay-item--warn' : '' ?>">
                    <span class="adm-pay-item__val"><?= $urgentEscrow ?></span>
                    <span class="adm-pay-item__label">Escrow bloqué</span>
                </div>
                <div class="adm-pay-item">
                    <span class="adm-pay-item__val"><?= (int)($pp['session_effectue'] ?? 0) ?></span>
                    <span class="adm-pay-item__label">Sessions payées</span>
                </div>
                <div class="adm-pay-item <?= (int)($pp['session_en_attente'] ?? 0) > 0 ? 'adm-pay-item--accent' : '' ?>">
                    <span class="adm-pay-item__val"><?= (int)($pp['session_en_attente'] ?? 0) ?></span>
                    <span class="adm-pay-item__label">En attente</span>
                </div>
                <div class="adm-pay-item">
                    <span class="adm-pay-item__val"><?= (int)($pp['depot_effectue'] ?? 0) ?></span>
                    <span class="adm-pay-item__label">Dépôts effectués</span>
                </div>
                <?php if (!empty($tx)): ?>
                <div class="adm-pay-item">
                    <span class="adm-pay-item__val"><?= number_format((float)($tx['total_collecte'] ?? 0), 0, ',', ' ') ?></span>
                    <span class="adm-pay-item__label">Collecté MM</span>
                </div>
                <div class="adm-pay-item <?= $urgentMM > 0 ? 'adm-pay-item--warn' : '' ?>">
                    <span class="adm-pay-item__val"><?= $urgentMM ?></span>
                    <span class="adm-pay-item__label">À valider MM</span>
                </div>
                <?php endif; ?>
                <div class="adm-pay-item <?= $urgentRetraitsExperts > 0 ? 'adm-pay-item--warn' : '' ?>">
                    <span class="adm-pay-item__val"><?= $urgentRetraitsExperts ?></span>
                    <span class="adm-pay-item__label">Retraits experts</span>
                </div>
                <div class="adm-pay-item <?= $urgentRetraitsProfs > 0 ? 'adm-pay-item--warn' : '' ?>">
                    <span class="adm-pay-item__val"><?= $urgentRetraitsProfs ?></span>
                    <span class="adm-pay-item__label">Retraits profs</span>
                </div>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════
         TABLES : UTILISATEURS + RÉSERVATIONS
    ══════════════════════════════════════════ -->
    <div class="adm-row adm-row--equal">
        <!-- Utilisateurs récents -->
        <div class="adm-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Utilisateurs récents
                </h2>
                <a href="<?= $baseUrl ?>/admin/users" class="adm-card__action">Voir tout</a>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Email</th><th>Nom</th><th>Rôle</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentUsers, 0, 6) as $u): ?>
                        <tr>
                            <td><a href="mailto:<?= $e($u['email'] ?? '') ?>" class="adm-table-email"><?= $e($u['email'] ?? '') ?></a></td>
                            <td class="adm-table-name"><?= $e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></td>
                            <td><span class="adm-role adm-role--<?= $e($u['role'] ?? 'client') ?>"><?= $e($u['role'] ?? '') ?></span></td>
                            <td class="adm-table-muted"><?= $e(date('d/m/Y', strtotime($u['created_at'] ?? 'now'))) ?></td>
                            <td><a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)($u['id'] ?? 0) ?>" class="adm-table-btn">Éditer</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentUsers)): ?>
                        <tr><td colspan="5" class="adm-table-empty">Aucun utilisateur récent</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Réservations récentes -->
        <div class="adm-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Dernières réservations
                </h2>
                <a href="<?= $baseUrl ?>/admin/revenus" class="adm-card__action">Voir tout</a>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Demande</th><th>Expert</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentReservations, 0, 6) as $r): ?>
                        <tr>
                            <td>
                                <strong class="adm-table-main"><?= $e($r['demande_titre'] ?? '') ?></strong>
                                <span class="adm-table-muted"><?= $e(trim(($r['client_prenom'] ?? '') . ' ' . ($r['client_nom'] ?? ''))) ?></span>
                            </td>
                            <td class="adm-table-muted"><?= $e($r['expert_titre'] ?? '') ?></td>
                            <td class="adm-table-mono"><?= number_format((float)($r['montant_total'] ?? 0), 0, ',', ' ') ?></td>
                            <td><span class="adm-status adm-status--<?= $e(strtolower(str_replace([' ', '-'], '_', $r['statut'] ?? ''))) ?>"><?= $e($r['statut'] ?? '') ?></span></td>
                            <td class="adm-table-muted"><?= $e(date('d/m/Y', strtotime($r['created_at'] ?? 'now'))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentReservations)): ?>
                        <tr><td colspan="5" class="adm-table-empty">Aucune réservation récente</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         APPAREILS + CARTE DU MONDE
    ══════════════════════════════════════════ -->
    <div class="adm-row adm-row--2-3">

        <!-- Appareils & navigateurs -->
        <div class="adm-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    Appareils & navigateurs
                </h2>
                <span class="adm-badge-info">30 jours</span>
            </div>
            <div class="adm-devices">
                <?php if (!empty($deviceStats)): ?>
                <p class="adm-devices__label">Appareils</p>
                <?php foreach (array_slice($deviceStats, 0, 3, true) as $dev => $cnt):
                    $pct = round($cnt / $totalDevices * 100);
                    $icon = $dev === 'Mobile' ? '📱' : ($dev === 'Tablette' ? '📟' : '🖥️');
                ?>
                <div class="adm-bar-row">
                    <span class="adm-bar-row__name"><?= $icon ?> <?= $e($dev) ?></span>
                    <div class="adm-bar-row__track"><div class="adm-bar-row__fill adm-bar-row__fill--blue" style="width:<?= $pct ?>%"></div></div>
                    <span class="adm-bar-row__pct"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($browserStats)): ?>
                <p class="adm-devices__label" style="margin-top:.75rem">Navigateurs</p>
                <?php $bColors = ['Chrome'=>'adm-bar-row__fill--orange','Firefox'=>'adm-bar-row__fill--orange2','Safari'=>'adm-bar-row__fill--green','Edge'=>'adm-bar-row__fill--blue','Opera'=>'adm-bar-row__fill--red'];
                foreach (array_slice($browserStats, 0, 4, true) as $br => $cnt):
                    $pct = round($cnt / $totalBrowsers * 100);
                    $col = $bColors[$br] ?? 'adm-bar-row__fill--gray';
                ?>
                <div class="adm-bar-row">
                    <span class="adm-bar-row__name"><?= $e($br) ?></span>
                    <div class="adm-bar-row__track"><div class="adm-bar-row__fill <?= $col ?>" style="width:<?= $pct ?>%"></div></div>
                    <span class="adm-bar-row__pct"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($deviceStats) && empty($browserStats)): ?>
                <p class="adm-empty-note">Aucune donnée disponible.<br><small>Activez le tracking utilisateur.</small></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Carte du monde -->
        <div class="adm-card adm-map-card">
            <div class="adm-card__head">
                <h2 class="adm-card__title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Visiteurs dans le monde
                </h2>
                <span class="adm-badge-info">30 jours</span>
                <a href="<?= $baseUrl ?>/admin/tracking" class="adm-card__action">Tracking complet</a>
            </div>
            <div class="adm-map-body">
                <?php
                $svgCountryData = $countryMap;
                $svgMapId = 'dash-world-map';
                $svgHeight = 300;
                include dirname(__DIR__, 2) . '/partials/world_map_svg.php';
                ?>
                <div class="adm-map-countries">
                    <?php if (!empty($topCountries)):
                        $dashCountryNames = ['SN'=>'Sénégal','CI'=>'Côte d\'Ivoire','ML'=>'Mali','BJ'=>'Bénin','TG'=>'Togo','GN'=>'Guinée','CM'=>'Cameroun','CD'=>'RD Congo','MG'=>'Madagascar','NE'=>'Niger','TD'=>'Tchad','GH'=>'Ghana','NG'=>'Nigeria','KE'=>'Kenya','ZA'=>'Afrique du Sud','RW'=>'Rwanda','UG'=>'Ouganda','FR'=>'France','BE'=>'Belgique','CH'=>'Suisse','CA'=>'Canada','US'=>'États-Unis','GB'=>'Royaume-Uni','DE'=>'Allemagne','ES'=>'Espagne','IT'=>'Italie','MA'=>'Maroc','DZ'=>'Algérie','TN'=>'Tunisie'];
                        $maxVisits = max(1, (int)($topCountries[0]['visits'] ?? 1));
                        foreach ($topCountries as $row):
                            $code  = strtoupper($row['pays'] ?? '');
                            $name  = $dashCountryNames[$code] ?? $code;
                            $count = (int)$row['visits'];
                            $pct   = round($count / $maxVisits * 100);
                    ?>
                    <div class="adm-map-country">
                        <span class="adm-map-country__code"><?= $e($code) ?></span>
                        <div class="adm-map-country__info">
                            <span class="adm-map-country__name"><?= $e($name) ?></span>
                            <div class="adm-bar-row__track" style="height:4px;margin:0"><div class="adm-bar-row__fill adm-bar-row__fill--blue" style="width:<?= $pct ?>%"></div></div>
                        </div>
                        <span class="adm-map-country__count"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="adm-empty-note" style="font-size:.8rem">Aucune visite enregistrée avec géolocalisation.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
(function () {
    /* ── Date/heure live ── */
    function updateDT() {
        var el = document.getElementById('dash-datetime');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleDateString('fr-FR', {weekday:'long',day:'numeric',month:'long',year:'numeric'})
            + ' — ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    updateDT();
    setInterval(updateDT, 1000);

    /* ── Auto-refresh ── */
    var INTERVAL  = 60;
    var remaining = INTERVAL;
    var paused    = false;
    var ticker    = null;
    var countEl   = document.getElementById('dash-rf-count');
    var barEl     = document.getElementById('dash-rf-bar');
    var pauseIcon = document.getElementById('dash-rf-pause');
    var playIcon  = document.getElementById('dash-rf-play');
    var toggleBtn = document.getElementById('dash-refresh-toggle');

    function updateBar() {
        if (!barEl) return;
        barEl.style.width = (((INTERVAL - remaining) / INTERVAL) * 100) + '%';
    }
    function tick() {
        if (paused) return;
        remaining--;
        if (countEl) countEl.textContent = remaining;
        updateBar();
        if (remaining <= 0) {
            document.body.style.transition = 'opacity .3s';
            document.body.style.opacity = '0';
            setTimeout(function () { location.reload(); }, 320);
        }
    }
    function startTicker() {
        if (ticker) clearInterval(ticker);
        ticker = setInterval(tick, 1000);
    }
    function togglePause() {
        paused = !paused;
        if (pauseIcon) pauseIcon.style.display = paused ? 'none' : '';
        if (playIcon)  playIcon.style.display  = paused ? ''     : 'none';
        if (paused) {
            clearInterval(ticker); ticker = null;
            if (countEl) countEl.textContent = '—';
            if (barEl)   barEl.style.width   = '0%';
        } else {
            remaining = INTERVAL;
            if (countEl) countEl.textContent = remaining;
            startTicker();
        }
    }
    if (toggleBtn) toggleBtn.addEventListener('click', togglePause);
    updateBar();
    startTicker();

    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity .35s';
    requestAnimationFrame(function () {
        requestAnimationFrame(function () { document.body.style.opacity = '1'; });
    });
})();
</script>
