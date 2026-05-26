<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$activities = $activities ?? [];
$limit = (int) ($limit ?? 200);
$userIdFilter = $user_id_filter ?? null;
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();
$totalEvents = count($activities);
$uniqueUsers = [];
$uniqueIps = [];
$activeDays = [];
$actionCounts = [];
$pageCounts = [];
$deviceCounts = [];
$browserCounts = [];
$countryCounts = [];
$dailyStats = [];

foreach ($activities as $a) {
    $uid = (int)($a['utilisateur_id'] ?? 0);
    if ($uid > 0) {
        $uniqueUsers[$uid] = true;
    }

    $ip = trim((string)($a['ip'] ?? ''));
    if ($ip !== '' && $ip !== '—') {
        $uniqueIps[$ip] = true;
    }

    $createdAt = (string)($a['created_at'] ?? '');
    $dateKey = '';
    if ($createdAt !== '') {
        $ts = strtotime($createdAt);
        if ($ts !== false) {
            $dateKey = date('Y-m-d', $ts);
            $activeDays[$dateKey] = true;
            if (!isset($dailyStats[$dateKey])) {
                $dailyStats[$dateKey] = [
                    'events' => 0,
                    'users' => [],
                    'pages' => 0,
                ];
            }
            $dailyStats[$dateKey]['events']++;
            if ($uid > 0) {
                $dailyStats[$dateKey]['users'][$uid] = true;
            }
            $dailyStats[$dateKey]['pages']++;
        }
    }

    $action = trim((string)($a['action'] ?? 'page_view'));
    $action = $action !== '' ? $action : 'page_view';
    $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;

    $page = trim((string)($a['page'] ?? ''));
    $page = $page !== '' ? $page : '—';
    $pageCounts[$page] = ($pageCounts[$page] ?? 0) + 1;

    $device = trim((string)($a['appareil'] ?? 'Inconnu'));
    $device = $device !== '' ? $device : 'Inconnu';
    $deviceCounts[$device] = ($deviceCounts[$device] ?? 0) + 1;

    $browser = trim((string)($a['navigateur'] ?? 'Inconnu'));
    $browser = $browser !== '' ? $browser : 'Inconnu';
    $browserCounts[$browser] = ($browserCounts[$browser] ?? 0) + 1;

    $country = trim((string)($a['pays'] ?? 'Inconnu'));
    $country = $country !== '' ? $country : 'Inconnu';
    $countryCounts[$country] = ($countryCounts[$country] ?? 0) + 1;
}

foreach ($dailyStats as $day => $data) {
    $dailyStats[$day]['users_count'] = count($data['users']);
}
krsort($dailyStats);
$dailyStats = array_slice($dailyStats, 0, 7, true);

arsort($actionCounts);
arsort($pageCounts);
arsort($deviceCounts);
arsort($browserCounts);
arsort($countryCounts);
$topPages = array_slice($pageCounts, 0, 10, true);
$topBrowsers = array_slice($browserCounts, 0, 10, true);
$topCountries = array_slice($countryCounts, 0, 10, true);
$totalActions = max(1, array_sum($actionCounts));
$totalDevices = max(1, array_sum($deviceCounts));
$totalBrowsers = max(1, array_sum($browserCounts));
$totalCountries = max(1, array_sum($countryCounts));
$updatedAt = date('H:i:s');
?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<div class="page-admin page-admin-tracking">
    <header class="admin-tracking-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-tracking-hero-content">
            <div class="admin-tracking-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l3-3 3 2 4-5"/></svg>
            </div>
            <div class="admin-tracking-hero-text">
                <h1>Tracking utilisateurs</h1>
                <p class="admin-tracking-hero-subtitle">Statistiques globales, tendances et comportement des visiteurs.</p>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
    <div class="admin-tracking-flash">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <p class="admin-tracking-flash__success"><?= $e($_SESSION['flash_success']) ?></p>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <p class="admin-tracking-flash__error"><?= $e($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); endif; ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-card admin-tracking-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M3 3v18h18"/><path d="M7 15l3-3 3 2 4-5"/></svg>
                Statistiques de tracking
            </h2>
            <div class="admin-tracking-toolbar">
                <form method="get" action="<?= $baseUrl ?>/admin/tracking" class="admin-tracking-filters">
                    <label>
                        <span>Fenêtre</span>
                        <select name="limit">
                            <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                            <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200</option>
                            <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500</option>
                        </select>
                    </label>
                    <label>
                        <span>Utilisateur (ID)</span>
                        <input type="number" name="user_id" value="<?= $userIdFilter !== null ? $e((string)$userIdFilter) : '' ?>" placeholder="Tous" min="1">
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                </form>
                <div class="admin-tracking-actions">
                    <span class="admin-tracking-last-update" id="tracking-last-update" title="Dernière mise à jour">
                        MAJ <?= $e($updatedAt) ?>
                    </span>
                    <button type="button" class="btn btn-outline btn-sm admin-tracking-btn-refresh" onclick="location.reload()" title="Rafraîchir">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        Rafraîchir
                    </button>
                    <a href="<?= $baseUrl ?>/admin/tracking" class="btn btn-outline btn-sm" title="Réinitialiser les filtres">Réinitialiser</a>
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-tracking-actions-table" data-export-name="tracking-actions" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
        </div>

        <?php if (empty($activities)): ?>
        <div class="admin-tracking-empty">
            <div class="admin-tracking-empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3v18h18"/><path d="M7 15l3-3 3 2 4-5"/></svg>
            </div>
            <p>Aucune activité sur la période choisie.</p>
        </div>
        <?php else: ?>
        <div class="admin-tracking-kpi-grid">
            <article class="admin-tracking-kpi-card">
                <div class="admin-tracking-kpi-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <h3>Total événements</h3>
                <strong><?= (int)$totalEvents ?></strong>
            </article>
            <article class="admin-tracking-kpi-card">
                <div class="admin-tracking-kpi-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <h3>Visiteurs uniques (IP)</h3>
                <strong><?= count($uniqueIps) ?></strong>
            </article>
            <article class="admin-tracking-kpi-card">
                <div class="admin-tracking-kpi-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
                </div>
                <h3>Jours actifs</h3>
                <strong><?= count($activeDays) ?></strong>
            </article>
            <article class="admin-tracking-kpi-card">
                <div class="admin-tracking-kpi-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                </div>
                <h3>Utilisateurs connectés</h3>
                <strong><?= count($uniqueUsers) ?></strong>
            </article>
        </div>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Répartition des actions</h2>
            </div>
            <div class="admin-tracking-table-wrap">
                <table class="table-desktop admin-table admin-tracking-table" id="admin-tracking-actions-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Nombre</th>
                        <th>Pourcentage</th>
                        <th>Graphique</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($actionCounts as $actionName => $actionValue):
                        $pct = ($actionValue / $totalActions) * 100;
                    ?>
                    <tr>
                        <td><?= $e((string)$actionName) ?></td>
                        <td><?= (int)$actionValue ?></td>
                        <td><?= number_format($pct, 1) ?>%</td>
                        <td>
                            <div style="background:#e2e8f0;height:10px;width:180px;border-radius:10px;overflow:hidden;">
                                <div style="background:#2563eb;height:100%;width:<?= (float)$pct ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Liste des activités (DataTables)</h2>
            </div>

            <!-- Barre d'actions groupées -->
            <form method="post" action="<?= $baseUrl ?>/admin/delete-tracking-bulk" id="tracking-bulk-form">
                <?= $csrfField ?>
                <div class="tracking-bulk-bar" id="tracking-bulk-bar">
                    <label class="tracking-bulk-info">
                        <span id="tracking-bulk-count">0</span> ligne(s) sélectionnée(s)
                    </label>
                    <div class="tracking-bulk-actions">
                        <button type="button" class="btn btn-sm btn-outline" id="tracking-select-all-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Tout sélectionner
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" id="tracking-deselect-btn" style="display:none;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Désélectionner tout
                        </button>
                        <button type="submit" class="btn btn-sm btn-danger" id="tracking-bulk-delete-btn" disabled
                                onclick="return confirm('Supprimer les ' + document.getElementById(\'tracking-bulk-count\').textContent + ' entrée(s) sélectionnée(s) ?')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Supprimer la sélection
                        </button>
                    </div>
                </div>

                <div class="admin-tracking-table-wrap">
                    <table class="table-desktop admin-table admin-tracking-table" id="admin-tracking-list-table">
                    <thead>
                        <tr>
                            <th class="tracking-col-check">
                                <input type="checkbox" id="tracking-check-all" class="tracking-checkbox" title="Tout cocher/décocher">
                            </th>
                            <th>Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Page</th>
                            <th>IP</th>
                            <th>Pays</th>
                            <th>Appareil</th>
                            <th>Navigateur</th>
                            <th class="admin-table-col-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                        <tr>
                            <td class="tracking-col-check">
                                <input type="checkbox" name="ids[]" value="<?= (int)($a['id'] ?? 0) ?>" class="tracking-checkbox tracking-row-check">
                            </td>
                            <td class="admin-tracking-date"><?= $e($a['created_at'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($a['utilisateur_id'])): ?>
                                    <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$a['utilisateur_id'] ?>"><?= $e(trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?: ($a['email'] ?? ('#' . (int)$a['utilisateur_id']))) ?></a>
                                <?php else: ?>
                                    <span class="admin-tracking-anon">Anonyme</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="admin-tracking-badge"><?= $e($a['action'] ?? 'page_view') ?></span></td>
                            <td class="admin-tracking-page" title="<?= $e($a['page'] ?? '') ?>"><?= $e($a['page'] ?? '—') ?></td>
                            <td><?= $e($a['ip'] ?? '—') ?></td>
                            <td><?= $e($a['pays'] ?? '—') ?></td>
                            <td><?= $e($a['appareil'] ?? '—') ?></td>
                            <td><?= $e($a['navigateur'] ?? '—') ?></td>
                            <td class="admin-tracking-cell-actions">
                                <form method="post" action="<?= $baseUrl ?>/admin/delete-tracking/<?= (int)($a['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Supprimer cette entrée de tracking ?');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-outline btn-sm admin-tracking-btn-delete admin-tracking-btn-icon" title="Supprimer" aria-label="Supprimer">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            </form>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Top pages</h2>
            </div>
            <div class="admin-tracking-table-wrap">
                <table class="table-desktop admin-table admin-tracking-table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Page</th>
                        <th>Nombre de vues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topPages as $page => $count): ?>
                    <tr>
                        <td><strong>#<?= $rank++ ?></strong></td>
                        <td class="admin-tracking-page" title="<?= $e((string)$page) ?>"><?= $e((string)$page) ?></td>
                        <td><strong><?= (int)$count ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Évolution sur 7 jours</h2>
            </div>
            <div class="admin-tracking-table-wrap">
                <table class="table-desktop admin-table admin-tracking-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Evénements</th>
                        <th>Utilisateurs uniques</th>
                        <th>Pages vues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyStats as $day => $data): ?>
                    <tr>
                        <td><strong><?= $e(date('d/m/Y', strtotime((string)$day))) ?></strong></td>
                        <td><?= (int)$data['events'] ?></td>
                        <td><?= (int)$data['users_count'] ?></td>
                        <td><?= (int)$data['pages'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Appareils et navigateurs</h2>
            </div>
            <div class="admin-tracking-table-wrap">
                <table class="table-desktop admin-table admin-tracking-table">
                <thead>
                    <tr>
                        <th>Appareil</th>
                        <th>Nombre</th>
                        <th>%</th>
                        <th>Navigateur</th>
                        <th>Nombre</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $deviceRows = array_keys($deviceCounts);
                    $browserRows = array_keys($topBrowsers);
                    $rows = max(count($deviceRows), count($browserRows));
                    for ($i = 0; $i < $rows; $i++):
                        $d = $deviceRows[$i] ?? '—';
                        $dCount = $d !== '—' ? (int)$deviceCounts[$d] : 0;
                        $dPct = $d !== '—' ? (($dCount / $totalDevices) * 100) : 0;
                        $b = $browserRows[$i] ?? '—';
                        $bCount = $b !== '—' ? (int)$topBrowsers[$b] : 0;
                        $bPct = $b !== '—' ? (($bCount / $totalBrowsers) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= $e((string)$d) ?></td>
                        <td><?= $d !== '—' ? $dCount : '—' ?></td>
                        <td><?= $d !== '—' ? number_format($dPct, 1) . '%' : '—' ?></td>
                        <td><?= $e((string)$b) ?></td>
                        <td><?= $b !== '—' ? $bCount : '—' ?></td>
                        <td><?= $b !== '—' ? number_format($bPct, 1) . '%' : '—' ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                </table>
            </div>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Carte des visiteurs par pays
                </h2>
            </div>
            <?php
            // Préparer les données pays pour la carte
            $mapCountryData = [];
            foreach ($topCountries as $cc => $cnt) {
                $c = strtoupper(trim((string)$cc));
                if ($c !== '' && $c !== 'INCONNU' && strlen($c) === 2) {
                    $mapCountryData[$c] = (int)$cnt;
                }
            }
            $countryNamesMap = [
                'SN'=>'Sénégal','CI'=>'Côte d\'Ivoire','ML'=>'Mali','BJ'=>'Bénin',
                'TG'=>'Togo','GN'=>'Guinée','CM'=>'Cameroun','CD'=>'RD Congo','MG'=>'Madagascar',
                'NE'=>'Niger','TD'=>'Tchad','GH'=>'Ghana','NG'=>'Nigeria','KE'=>'Kenya',
                'FR'=>'France','BE'=>'Belgique','CH'=>'Suisse','DE'=>'Allemagne','ES'=>'Espagne',
                'IT'=>'Italie','GB'=>'Royaume-Uni','CA'=>'Canada','US'=>'États-Unis',
                'MA'=>'Maroc','DZ'=>'Algérie','TN'=>'Tunisie','EG'=>'Égypte',
                'ZA'=>'Afrique du Sud','RW'=>'Rwanda','UG'=>'Ouganda','ET'=>'Éthiopie',
            ];
            ?>
            <div class="admin-tracking-map-wrap">
                <?php
                $svgCountryData = $mapCountryData;
                $svgMapId = 'tracking-world-map';
                $svgHeight = 360;
                include dirname(__DIR__, 2) . '/partials/world_map_svg.php';
                ?>
                <div class="admin-tracking-map-legend">
                    <p class="admin-tracking-map-legend__title">Top pays</p>
                    <?php $maxCnt = max(1, max(array_values($mapCountryData) ?: [1]));
                    foreach ($mapCountryData as $cc => $cnt):
                        $name = $countryNamesMap[$cc] ?? $cc;
                        $pct  = round($cnt / $totalCountries * 100, 1);
                        $bar  = round($cnt / $maxCnt * 100);
                    ?>
                    <div class="admin-tracking-map-row">
                        <span class="admin-tracking-map-code"><?= htmlspecialchars($cc) ?></span>
                        <div class="admin-tracking-map-info">
                            <span class="admin-tracking-map-name"><?= htmlspecialchars($name) ?></span>
                            <div class="admin-tracking-map-bar"><div style="width:<?= $bar ?>%"></div></div>
                        </div>
                        <span class="admin-tracking-map-pct"><?= $pct ?>%</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($mapCountryData)): ?>
                    <p style="font-size:12px;color:#94a3b8;margin:8px 0 0;">Aucune donnée géographique.<br>Activez Cloudflare pour enrichir le suivi.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="admin-table-card admin-tracking-section-card">
            <div class="admin-table-card-header">
                <h2>Top pays (tableau)</h2>
            </div>
            <div class="admin-tracking-table-wrap">
                <table class="table-desktop admin-table admin-tracking-table">
                <thead>
                    <tr>
                        <th>Pays</th>
                        <th>Nombre</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCountries as $country => $count): ?>
                    <?php $pct = ((int)$count / $totalCountries) * 100; ?>
                    <tr>
                        <td><?= $e((string)$country) ?></td>
                        <td><?= (int)$count ?></td>
                        <td><?= number_format($pct, 1) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>
<style>
/* ── Sélection multiple ─────────────────────────────── */
.tracking-col-check {
    width: 36px;
    text-align: center;
    padding: 0 8px !important;
}
.tracking-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #2563eb;
}
#admin-tracking-list-table tbody tr {
    cursor: pointer;
    transition: background .12s;
}
#admin-tracking-list-table tbody tr:has(.tracking-row-check:checked) {
    background: #eff6ff !important;
}
#admin-tracking-list-table tbody tr:hover {
    background: #f8fafc;
}

/* Barre d'actions groupées */
.tracking-bulk-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .65rem 1.1rem;
    background: #f0f7ff;
    border-bottom: 1px solid #bfdbfe;
    border-top: 1px solid #bfdbfe;
    flex-wrap: wrap;
}
.tracking-bulk-info {
    font-size: .82rem;
    font-weight: 600;
    color: #1e40af;
    display: flex;
    align-items: center;
    gap: .4rem;
    white-space: nowrap;
}
#tracking-bulk-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    border-radius: 99px;
    background: #2563eb;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    padding: 0 6px;
}
.tracking-bulk-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    align-items: center;
}
.btn.btn-danger {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}
.btn.btn-danger:hover:not(:disabled) {
    background: #b91c1c;
    border-color: #b91c1c;
}
.btn.btn-danger:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.btn.btn-danger svg { vertical-align: -2px; margin-right: 4px; }
.btn.btn-outline svg { vertical-align: -2px; margin-right: 4px; }

/* ── Carte visiteurs ────────────────────────────────── */
.admin-tracking-map-wrap {
    display: grid;
    grid-template-columns: 1fr 220px;
    min-height: 360px;
}
@media (max-width: 700px) { .admin-tracking-map-wrap { grid-template-columns: 1fr; } }
#tracking-world-map {
    height: 360px;
    background: #e8f0e8;
    border-radius: 0 0 0 8px;
    z-index: 1;
}
@media (max-width: 700px) { #tracking-world-map { border-radius: 0; height: 260px; } }
.admin-tracking-map-legend {
    padding: 14px;
    background: #fafbfc;
    border-left: 1px solid #f1f5f9;
    overflow-y: auto;
    max-height: 360px;
    border-radius: 0 0 8px 0;
}
.admin-tracking-map-legend__title {
    margin: 0 0 10px;
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.admin-tracking-map-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}
.admin-tracking-map-code {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    width: 22px;
    flex-shrink: 0;
    letter-spacing: .04em;
}
.admin-tracking-map-info { flex: 1; min-width: 0; }
.admin-tracking-map-name {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.admin-tracking-map-bar {
    height: 4px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
}
.admin-tracking-map-bar div {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
    border-radius: 99px;
}
.admin-tracking-map-pct {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    width: 36px;
    text-align: right;
    flex-shrink: 0;
}
</style>
<script>
(function() {
    var AUTO_REFRESH_MS = 30000;

    function hasEditingFocus() {
        var active = document.activeElement;
        if (!active) return false;
        var tag = (active.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select';
    }

    function refreshIfIdle() {
        if (document.hidden) return;
        if (hasEditingFocus()) return;
        window.location.reload();
    }

    window.setInterval(refreshIfIdle, AUTO_REFRESH_MS);

    // ── Sélection multiple ──────────────────────────────────────────────
    function initBulkSelect() {
        var checkAll   = document.getElementById('tracking-check-all');
        var countEl    = document.getElementById('tracking-bulk-count');
        var deleteBtn  = document.getElementById('tracking-bulk-delete-btn');
        var selectAllBtn = document.getElementById('tracking-select-all-btn');
        var deselectBtn  = document.getElementById('tracking-deselect-btn');

        function getVisibleChecks() {
            // DataTables n'affiche que les lignes de la page courante dans le DOM
            return Array.from(document.querySelectorAll('#admin-tracking-list-table tbody .tracking-row-check'));
        }

        function getAllChecks() {
            return Array.from(document.querySelectorAll('#admin-tracking-list-table .tracking-row-check'));
        }

        function updateCount() {
            var n = getAllChecks().filter(function(c) { return c.checked; }).length;
            countEl.textContent = n;
            deleteBtn.disabled = n === 0;
            if (selectAllBtn && deselectBtn) {
                var allVisible = getVisibleChecks();
                var allChecked = allVisible.length > 0 && allVisible.every(function(c) { return c.checked; });
                selectAllBtn.style.display = allChecked ? 'none' : '';
                deselectBtn.style.display  = allChecked ? '' : 'none';
            }
            // Mettre à jour la case "tout cocher"
            if (checkAll) {
                var vis = getVisibleChecks();
                checkAll.indeterminate = false;
                if (vis.length === 0) { checkAll.checked = false; }
                else if (vis.every(function(c){ return c.checked; })) { checkAll.checked = true; }
                else if (vis.some(function(c){ return c.checked; })) { checkAll.indeterminate = true; checkAll.checked = false; }
                else { checkAll.checked = false; }
            }
        }

        // Case "tout cocher" dans l'en-tête → seulement la page visible
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                getVisibleChecks().forEach(function(c) { c.checked = checkAll.checked; });
                updateCount();
            });
        }

        // Bouton "Tout sélectionner" → toutes les lignes (toutes pages DT)
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                getAllChecks().forEach(function(c) { c.checked = true; });
                if (checkAll) checkAll.checked = true;
                updateCount();
            });
        }

        if (deselectBtn) {
            deselectBtn.addEventListener('click', function() {
                getAllChecks().forEach(function(c) { c.checked = false; });
                if (checkAll) checkAll.checked = false;
                updateCount();
            });
        }

        // Clic sur une case individuelle
        document.getElementById('admin-tracking-list-table').addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('tracking-row-check')) {
                updateCount();
            }
        });

        // Clic sur une ligne entière pour cocher (hors liens et boutons)
        document.getElementById('admin-tracking-list-table').addEventListener('click', function(e) {
            var td = e.target.closest('td');
            if (!td || td.classList.contains('tracking-col-check') || td.classList.contains('admin-tracking-cell-actions')) return;
            var tr = td.closest('tr');
            if (!tr) return;
            var cb = tr.querySelector('.tracking-row-check');
            if (!cb) return;
            cb.checked = !cb.checked;
            updateCount();
        });

        // Recalculer après changement de page DataTables
        document.addEventListener('dt.page', updateCount);
        document.getElementById('admin-tracking-list-table').addEventListener('draw.dt', updateCount);

        updateCount();
    }

    // ── DataTables ──────────────────────────────────────────────────────
    function runDataTable() {
        var tableEl = document.getElementById('admin-tracking-list-table');
        if (!tableEl) return;
        if (tableEl.dataset.dtReady === '1') return;

        var dtConfig = {
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            language: {
                search: 'Rechercher :',
                lengthMenu: 'Afficher _MENU_ lignes',
                info: 'Lignes _START_ à _END_ sur _TOTAL_',
                infoEmpty: 'Aucune ligne',
                paginate: { first: 'Début', last: 'Fin', next: 'Suiv.', previous: 'Préc.' }
            },
            // Colonnes 0 (checkbox) et 9 (actions) non triables
            columnDefs: [
                { orderable: false, targets: [0, 9] },
                { className: 'tracking-col-check', targets: [0] }
            ]
        };

        var onDrawCb = function() {
            // Recalcul compteur + état checkAll après changement de page DT
            var checkAll = document.getElementById('tracking-check-all');
            if (checkAll) checkAll.checked = false;
            document.getElementById('tracking-bulk-count').textContent =
                Array.from(document.querySelectorAll('#admin-tracking-list-table .tracking-row-check'))
                     .filter(function(c){ return c.checked; }).length;
            var deleteBtn = document.getElementById('tracking-bulk-delete-btn');
            if (deleteBtn) deleteBtn.disabled = parseInt(document.getElementById('tracking-bulk-count').textContent) === 0;
        };

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            tableEl.dataset.dtReady = '1';
            window.jQuery(tableEl).DataTable(dtConfig).on('draw', onDrawCb);
            return;
        }

        if (typeof window.DataTable === 'function') {
            tableEl.dataset.dtReady = '1';
            new window.DataTable('#admin-tracking-list-table', dtConfig).on('draw', onDrawCb);
            return;
        }

        // Fallback: injecter le script DataTables si absent.
        var scriptId = 'tracking-datatables-cdn';
        if (!document.getElementById(scriptId)) {
            var s = document.createElement('script');
            s.id = scriptId;
            s.src = 'https://cdn.datatables.net/2.0.8/js/dataTables.min.js';
            s.onload = runDataTable;
            document.body.appendChild(s);
        }
    }

    function initTrackingTable() {
        runDataTable();
        initBulkSelect();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTrackingTable);
    } else {
        initTrackingTable();
    }
})();
</script>
