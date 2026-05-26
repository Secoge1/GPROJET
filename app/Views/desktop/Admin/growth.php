<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$referral       = $referral ?? [];
$growthStats    = $growth_stats ?? [];
$topExpertViews = $top_expert_views ?? [];
$topJobViews    = $top_job_views ?? [];
$topBlogViews   = $top_blog_views ?? [];
$e = fn($s) => \App\Core\Security::escape($s ?? '');

$tauxReservation = $taux_reservation ?? 0.0;
$tauxPaiement    = $taux_paiement    ?? 0.0;
$tauxGlobal      = $taux_global      ?? 0.0;

$totalViews = ($growthStats['expert'] ?? 0) + ($growthStats['job'] ?? 0) + ($growthStats['blog'] ?? 0);

// Nom affiché d'un expert
function growthExpertLabel(array $v): string {
    $prenom = trim(($v['expert_prenom'] ?? '') . ' ' . ($v['expert_nom'] ?? ''));
    $titre  = $v['expert_titre'] ?? '';
    if ($prenom) return htmlspecialchars($prenom, ENT_QUOTES) . ($titre ? ' <span class="growth-sub">' . htmlspecialchars($titre, ENT_QUOTES) . '</span>' : '');
    if ($titre)  return htmlspecialchars($titre, ENT_QUOTES);
    return 'Expert #' . (int)($v['expert_id'] ?? 0);
}
?>
<div class="page-admin page-admin-growth">

    <!-- En-tête -->
    <header class="admin-growth-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-growth-hero-content">
            <div class="admin-growth-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <h1>Growth</h1>
                <p class="admin-growth-hero-subtitle">Conversions, parrainage, visites SEO et tracking</p>
            </div>
        </div>
    </header>

    <!-- ══════════════════ CONVERSIONS ══════════════════ -->
    <div class="admin-growth-grid">
        <section class="admin-growth-block admin-growth-block--conversions">
            <div class="admin-growth-block-header">
                <span class="admin-growth-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <h2>Conversions plateforme</h2>
            </div>
            <div class="admin-growth-metrics">
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--blue" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($total_utilisateurs ?? 0) ?></span>
                        <span class="admin-metric-label">Inscriptions</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--orange" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($total_reservations ?? 0) ?></span>
                        <span class="admin-metric-label">Réservations</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--green" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($conversions_paiements ?? 0) ?></span>
                        <span class="admin-metric-label">Paiements effectués</span>
                    </div>
                </article>
            </div>

            <!-- Taux de conversion -->
            <div class="admin-growth-rates">
                <div class="admin-growth-rate-row">
                    <span class="admin-growth-rate-label">Inscrits → Réservation</span>
                    <div class="admin-growth-rate-bar-wrap">
                        <div class="admin-growth-rate-bar" style="width:<?= min(100, $tauxReservation) ?>%"></div>
                    </div>
                    <span class="admin-growth-rate-value"><?= number_format($tauxReservation, 1) ?> %</span>
                </div>
                <div class="admin-growth-rate-row">
                    <span class="admin-growth-rate-label">Réservation → Paiement</span>
                    <div class="admin-growth-rate-bar-wrap">
                        <div class="admin-growth-rate-bar admin-growth-rate-bar--green" style="width:<?= min(100, $tauxPaiement) ?>%"></div>
                    </div>
                    <span class="admin-growth-rate-value"><?= number_format($tauxPaiement, 1) ?> %</span>
                </div>
                <div class="admin-growth-rate-row">
                    <span class="admin-growth-rate-label">Taux global (inscription → paiement)</span>
                    <div class="admin-growth-rate-bar-wrap">
                        <div class="admin-growth-rate-bar admin-growth-rate-bar--purple" style="width:<?= min(100, $tauxGlobal) ?>%"></div>
                    </div>
                    <span class="admin-growth-rate-value admin-growth-rate-value--highlight"><?= number_format($tauxGlobal, 1) ?> %</span>
                </div>
            </div>
        </section>

        <!-- ══════════════════ PARRAINAGE ══════════════════ -->
        <section class="admin-growth-block admin-growth-block--parrainage">
            <div class="admin-growth-block-header">
                <span class="admin-growth-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </span>
                <h2>Parrainage</h2>
            </div>
            <div class="admin-growth-metrics">
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--blue" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($referral['total_parrainages'] ?? 0) ?></span>
                        <span class="admin-metric-label">Liens partagés</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--green" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($referral['total_inscrits'] ?? 0) ?></span>
                        <span class="admin-metric-label">Inscrits via parrainage</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--orange" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($referral['total_recompenses'] ?? 0) ?></span>
                        <span class="admin-metric-label">Récompenses attribuées</span>
                    </div>
                </article>
            </div>
            <?php if ((int)($referral['total_parrainages'] ?? 0) > 0): ?>
            <div class="admin-growth-rates" style="margin-top:1rem">
                <div class="admin-growth-rate-row">
                    <span class="admin-growth-rate-label">Taux conversion parrainage</span>
                    <div class="admin-growth-rate-bar-wrap">
                        <?php $tauxParr = (int)($referral['total_parrainages'] ?? 0) > 0
                            ? round((int)($referral['total_inscrits'] ?? 0) / (int)($referral['total_parrainages'] ?? 1) * 100, 1) : 0; ?>
                        <div class="admin-growth-rate-bar" style="width:<?= min(100, $tauxParr) ?>%"></div>
                    </div>
                    <span class="admin-growth-rate-value"><?= number_format($tauxParr, 1) ?> %</span>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- ══════════════════ SEO VUES ══════════════════ -->
        <section class="admin-growth-block admin-growth-block--seo">
            <div class="admin-growth-block-header">
                <span class="admin-growth-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <h2>Visites pages growth (SEO)</h2>
                <?php if ($totalViews > 0): ?>
                <span class="admin-growth-badge admin-growth-badge--ok"><?= number_format($totalViews) ?> vues totales</span>
                <?php endif; ?>
            </div>
            <div class="admin-growth-metrics">
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--blue" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($growthStats['expert'] ?? 0) ?></span>
                        <span class="admin-metric-label">Pages expert</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--orange" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($growthStats['job'] ?? 0) ?></span>
                        <span class="admin-metric-label">Pages mission</span>
                    </div>
                </article>
                <article class="admin-metric-card">
                    <span class="admin-metric-icon admin-metric-icon--green" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    </span>
                    <div class="admin-metric-content">
                        <span class="admin-metric-value"><?= (int)($growthStats['blog'] ?? 0) ?></span>
                        <span class="admin-metric-label">Pages blog</span>
                    </div>
                </article>
            </div>
        </section>
    </div>

    <!-- ══════════════════ TABLES TOP VUES ══════════════════ -->
    <div class="admin-growth-tables">

        <!-- Top Experts -->
        <div class="admin-table-card admin-growth-table-card">
            <div class="admin-table-card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Top profils experts (vues)
                </h2>
                <div class="admin-growth-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-growth-experts-table" data-export-name="growth-experts" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="table-desktop admin-table admin-growth-table" id="admin-growth-experts-table">
                    <thead>
                        <tr>
                            <th class="admin-growth-table-rank">#</th>
                            <th>Profil</th>
                            <th class="admin-growth-table-views">Vues</th>
                            <th class="admin-growth-table-views" style="color:#64748b">Uniques</th>
                            <th class="admin-table-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topExpertViews as $i => $v):
                            $rank  = $i + 1;
                            $slug  = $v['slug'] ?? ('expert-' . ($v['expert_id'] ?? 0));
                            $label = growthExpertLabel($v);
                        ?>
                        <tr>
                            <td class="admin-growth-table-rank">
                                <span class="admin-growth-rank admin-growth-rank--<?= $rank <= 3 ? $rank : 'n' ?>"><?= $rank ?></span>
                            </td>
                            <td><?= $label ?></td>
                            <td class="admin-growth-table-views"><strong><?= (int)($v['views'] ?? 0) ?></strong></td>
                            <td class="admin-growth-table-views" style="color:#94a3b8"><?= (int)($v['unique_sessions'] ?? 0) ?></td>
                            <td class="admin-table-col-action">
                                <a href="<?= $baseUrl ?>/expert/<?= $e($slug) ?>" class="btn btn-icon btn-outline btn-sm" title="Voir le profil" target="_blank" rel="noopener">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topExpertViews)): ?>
                        <tr><td colspan="5" class="admin-table-empty">Aucune vue enregistrée. Les visites des pages expert sont tracées automatiquement.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Missions -->
        <div class="admin-table-card admin-growth-table-card">
            <div class="admin-table-card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    Top missions (vues)
                </h2>
                <div class="admin-growth-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-growth-jobs-table" data-export-name="growth-missions" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="table-desktop admin-table admin-growth-table" id="admin-growth-jobs-table">
                    <thead>
                        <tr>
                            <th class="admin-growth-table-rank">#</th>
                            <th>Mission</th>
                            <th class="admin-growth-table-views">Vues</th>
                            <th class="admin-growth-table-views" style="color:#64748b">Uniques</th>
                            <th class="admin-table-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topJobViews as $i => $v):
                            $rank  = $i + 1;
                            $slug  = $v['slug'] ?? ('job-' . ($v['job_id'] ?? 0));
                            $titre = $v['job_titre'] ?? ('Mission #' . (int)($v['job_id'] ?? 0));
                        ?>
                        <tr>
                            <td class="admin-growth-table-rank">
                                <span class="admin-growth-rank admin-growth-rank--<?= $rank <= 3 ? $rank : 'n' ?>"><?= $rank ?></span>
                            </td>
                            <td><?= $e($titre) ?></td>
                            <td class="admin-growth-table-views"><strong><?= (int)($v['views'] ?? 0) ?></strong></td>
                            <td class="admin-growth-table-views" style="color:#94a3b8"><?= (int)($v['unique_sessions'] ?? 0) ?></td>
                            <td class="admin-table-col-action">
                                <a href="<?= $baseUrl ?>/jobs/<?= $e($slug) ?>" class="btn btn-icon btn-outline btn-sm" title="Voir la mission" target="_blank" rel="noopener">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topJobViews)): ?>
                        <tr><td colspan="5" class="admin-table-empty">Aucune vue enregistrée. Les visites des pages mission sont tracées automatiquement.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Blog -->
        <?php if (!empty($topBlogViews) || (int)($growthStats['blog'] ?? 0) > 0): ?>
        <div class="admin-table-card admin-growth-table-card">
            <div class="admin-table-card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Top articles blog (vues)
                </h2>
                <div class="admin-growth-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-growth-blog-table" data-export-name="growth-blog" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="table-desktop admin-table admin-growth-table" id="admin-growth-blog-table">
                    <thead>
                        <tr>
                            <th class="admin-growth-table-rank">#</th>
                            <th>Article</th>
                            <th class="admin-growth-table-views">Vues</th>
                            <th class="admin-growth-table-views" style="color:#64748b">Uniques</th>
                            <th class="admin-table-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topBlogViews as $i => $v):
                            $rank  = $i + 1;
                            $slug  = $v['slug'] ?? null;
                            $titre = $v['blog_titre'] ?? ('Article #' . (int)($v['blog_id'] ?? 0));
                        ?>
                        <tr>
                            <td class="admin-growth-table-rank">
                                <span class="admin-growth-rank admin-growth-rank--<?= $rank <= 3 ? $rank : 'n' ?>"><?= $rank ?></span>
                            </td>
                            <td><?= $e($titre) ?></td>
                            <td class="admin-growth-table-views"><strong><?= (int)($v['views'] ?? 0) ?></strong></td>
                            <td class="admin-growth-table-views" style="color:#94a3b8"><?= (int)($v['unique_sessions'] ?? 0) ?></td>
                            <td class="admin-table-col-action">
                                <?php if ($slug): ?>
                                <a href="<?= $baseUrl ?>/blog/<?= $e($slug) ?>" class="btn btn-icon btn-outline btn-sm" title="Voir l'article" target="_blank" rel="noopener">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topBlogViews)): ?>
                        <tr><td colspan="5" class="admin-table-empty">Aucune vue blog enregistrée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══════════════════ TRACKING ══════════════════ -->
    <section class="admin-growth-block admin-growth-block--tracking">
        <div class="admin-growth-block-header">
            <span class="admin-growth-block-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </span>
            <h2>Tracking & SEO</h2>
        </div>
        <div class="admin-table-card">
            <div class="admin-growth-tracking">
                <!-- Google Analytics -->
                <div class="admin-growth-tracking-row">
                    <span class="admin-growth-tracking-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-growth-tracking-icon"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Google Analytics 4
                    </span>
                    <span class="admin-growth-tracking-right">
                        <?php $gaOk = !empty($ga_id); ?>
                        <span class="admin-growth-badge admin-growth-badge--<?= $gaOk ? 'ok' : 'off' ?>"><?= $gaOk ? 'Configuré' : 'Non configuré' ?></span>
                        <span class="admin-growth-tracking-value"><?= $gaOk ? $e($ga_id) : 'GA_MEASUREMENT_ID manquant' ?></span>
                    </span>
                </div>
                <!-- Facebook Pixel -->
                <div class="admin-growth-tracking-row">
                    <span class="admin-growth-tracking-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-growth-tracking-icon"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        Facebook Pixel
                    </span>
                    <span class="admin-growth-tracking-right">
                        <?php $fbOk = !empty($fb_pixel_id); ?>
                        <span class="admin-growth-badge admin-growth-badge--<?= $fbOk ? 'ok' : 'off' ?>"><?= $fbOk ? 'Configuré' : 'Non configuré' ?></span>
                        <span class="admin-growth-tracking-value"><?= $fbOk ? $e($fb_pixel_id) : 'FB_PIXEL_ID manquant' ?></span>
                    </span>
                </div>
                <!-- LinkedIn Insight Tag -->
                <div class="admin-growth-tracking-row">
                    <span class="admin-growth-tracking-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-growth-tracking-icon"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                        LinkedIn Insight Tag
                    </span>
                    <span class="admin-growth-tracking-right">
                        <?php $liOk = !empty($linkedin_id); ?>
                        <span class="admin-growth-badge admin-growth-badge--<?= $liOk ? 'ok' : 'off' ?>"><?= $liOk ? 'Configuré' : 'Non configuré' ?></span>
                        <span class="admin-growth-tracking-value"><?= $liOk ? $e($linkedin_id) : 'LINKEDIN_PARTNER_ID manquant' ?></span>
                    </span>
                </div>
                <!-- Fichiers SEO -->
                <div class="admin-growth-tracking-row admin-growth-tracking-links">
                    <span class="admin-growth-tracking-label">Fichiers SEO</span>
                    <span class="admin-growth-tracking-right">
                        <a href="<?= $baseUrl ?>/sitemap.xml" target="_blank" rel="noopener" class="btn btn-outline btn-sm">sitemap.xml</a>
                        <a href="<?= $baseUrl ?>/robots.txt" target="_blank" rel="noopener" class="btn btn-outline btn-sm">robots.txt</a>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Note -->
    <aside class="admin-growth-note">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <p>Les vues "Uniques" sont basées sur l'identifiant de session PHP. Pour le trafic détaillé et le classement SEO, connectez Google Analytics et Google Search Console. Les conversions sont issues de la base de données en temps réel.</p>
    </aside>

</div>

<style>
/* Barres de taux de conversion */
.admin-growth-rates { margin-top: 1.25rem; display: flex; flex-direction: column; gap: .6rem; }
.admin-growth-rate-row { display: flex; align-items: center; gap: .75rem; font-size: .8rem; }
.admin-growth-rate-label { flex: 0 0 220px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-growth-rate-bar-wrap { flex: 1; height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.admin-growth-rate-bar { height: 100%; background: #3b82f6; border-radius: 999px; transition: width .5s ease; min-width: 2px; }
.admin-growth-rate-bar--green  { background: #16a34a; }
.admin-growth-rate-bar--purple { background: #7c3aed; }
.admin-growth-rate-value { flex: 0 0 52px; text-align: right; font-weight: 700; color: #374151; font-size: .825rem; }
.admin-growth-rate-value--highlight { color: #7c3aed; }
/* Label sous-titre dans les cellules expert */
.growth-sub { font-size: .75rem; color: #94a3b8; display: block; margin-top: .1rem; }
</style>
