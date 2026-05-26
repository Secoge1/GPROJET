<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$stats = $stats ?? [];
$soldePlateforme = $solde_plateforme ?? null;
$periode = $periode ?? 'mois';
$expertsActifs = $stats['experts_actifs'] ?? [];
$devise = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$periodeLabel = $periode === 'jour' ? 'Aujourd\'hui' : ($periode === 'semaine' ? '7 derniers jours' : '30 derniers jours');
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
?>
<div class="page-admin page-admin-revenus">
    <header class="admin-revenus-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-revenus-hero-content">
            <div class="admin-revenus-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="admin-revenus-hero-text">
                <h1>Revenus</h1>
                <p class="admin-revenus-hero-subtitle">Commissions et volume des transactions sur la plateforme</p>
            </div>
            <div class="admin-revenus-period">
                <span class="admin-revenus-period-label">Période</span>
                <div class="admin-revenus-pills">
                    <a href="<?= $baseUrl ?>/admin/revenus?periode=jour" class="admin-revenus-pill <?= $periode === 'jour' ? 'active' : '' ?>">Aujourd'hui</a>
                    <a href="<?= $baseUrl ?>/admin/revenus?periode=semaine" class="admin-revenus-pill <?= $periode === 'semaine' ? 'active' : '' ?>">7 jours</a>
                    <a href="<?= $baseUrl ?>/admin/revenus?periode=mois" class="admin-revenus-pill <?= $periode === 'mois' ? 'active' : '' ?>">30 jours</a>
                </div>
            </div>
            <div class="admin-revenus-hero-actions">
                <button type="button" class="btn btn-outline btn-sm admin-revenus-btn-refresh" onclick="location.reload()" title="Rafraîchir les données">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Rafraîchir
                </button>
                <a href="<?= $baseUrl ?>/admin/parametres" class="btn btn-outline btn-sm" title="Commissions, devise, fournisseur d'abonnement">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Paramètres
                </a>
                <a href="<?= $baseUrl ?>/admin/wave-transactions" class="btn btn-outline btn-sm" title="Toutes les transactions Mobile Money">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Toutes les transactions MM
                </a>
                <a href="<?= $baseUrl ?>/admin/wave-transactions?status=pending" class="btn btn-outline btn-sm" title="Dépôts et abonnements en attente de validation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    MM en attente
                </a>
                <a href="<?= $baseUrl ?>/admin/retraits?statut=en_attente" class="btn btn-outline btn-sm" title="Demandes de retrait experts à traiter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Retraits à traiter
                </a>
                <a href="<?= $baseUrl ?>/admin/abonnements" class="btn btn-outline btn-sm" title="Abonnements utilisateurs">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Abonnements
                </a>
            </div>
        </div>
    </header>

    <div class="admin-revenus-grid">
        <section class="admin-revenus-block admin-revenus-block--commissions">
            <div class="admin-revenus-block-header">
                <span class="admin-revenus-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
                <h2>Commissions</h2>
            </div>
            <div class="admin-revenus-block-value">
                <span class="admin-revenus-number"><?= number_format((float)($stats['commissions'] ?? 0), 0, ',', ' ') ?></span>
                <span class="admin-revenus-devise"><?= $e($devise) ?></span>
            </div>
            <p class="admin-revenus-block-desc">Commission plateforme sur la période</p>
        </section>

        <section class="admin-revenus-block admin-revenus-block--volume">
            <div class="admin-revenus-block-header">
                <span class="admin-revenus-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                <h2>Volume des transactions</h2>
            </div>
            <div class="admin-revenus-block-value">
                <span class="admin-revenus-number"><?= number_format((float)($stats['volume'] ?? 0), 0, ',', ' ') ?></span>
                <span class="admin-revenus-devise"><?= $e($devise) ?></span>
            </div>
            <p class="admin-revenus-block-desc">Montant total des transactions</p>
        </section>

        <section class="admin-revenus-block admin-revenus-block--transactions">
            <div class="admin-revenus-block-header">
                <span class="admin-revenus-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </span>
                <h2>Transactions</h2>
            </div>
            <div class="admin-revenus-block-value">
                <span class="admin-revenus-number"><?= (int)($stats['nb_transactions'] ?? 0) ?></span>
            </div>
            <p class="admin-revenus-block-desc">Nombre de transactions sur la période</p>
        </section>

        <?php if ($soldePlateforme !== null): ?>
        <section class="admin-revenus-block admin-revenus-block--solde admin-revenus-block--highlight">
            <div class="admin-revenus-block-header">
                <span class="admin-revenus-block-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </span>
                <h2>Solde plateforme</h2>
            </div>
            <div class="admin-revenus-block-value">
                <span class="admin-revenus-number"><?= number_format((float) $soldePlateforme, 0, ',', ' ') ?></span>
                <span class="admin-revenus-devise"><?= $e($devise) ?></span>
            </div>
            <p class="admin-revenus-block-desc">Commissions et montants en attente (escrow)</p>
        </section>
        <?php endif; ?>
    </div>

    <section class="admin-revenus-quick-actions" aria-label="Actions dédiées revenus et paiements">
        <div class="admin-revenus-quick-actions-head">
            <h2 class="admin-revenus-quick-actions-title">Actions dédiées</h2>
            <p class="admin-revenus-quick-actions-lead">Raccourcis vers les écrans de gestion financière et des paiements</p>
        </div>
        <div class="admin-revenus-quick-actions-grid">
            <a href="<?= $baseUrl ?>/admin/parametres" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Paramètres monétisation</strong><small>Commissions, devise, abonnements</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Transactions Mobile Money</strong><small>Tous statuts</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions?status=pending" class="admin-revenus-action-tile admin-revenus-action-tile--accent">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>MM — En attente</strong><small>À valider ou refuser</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions?status=success" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>MM — Validées</strong><small>Historique des succès</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/retraits?statut=en_attente" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Retraits experts</strong><small>Demandes en attente</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/abonnements" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Abonnements</strong><small>Vue globale</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/demandes" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Demandes assistance</strong><small>Demandes clients</small></span>
            </a>
            <a href="<?= $baseUrl ?>/admin/growth" class="admin-revenus-action-tile">
                <span class="admin-revenus-action-tile-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
                <span class="admin-revenus-action-tile-text"><strong>Growth</strong><small>Conversions &amp; trafic</small></span>
            </a>
        </div>
    </section>

    <div class="admin-revenus-table-card admin-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Experts les plus actifs
                <span class="admin-revenus-table-period">— <?= $e($periodeLabel) ?></span>
            </h2>
            <div class="admin-revenus-table-actions">
                <a href="<?= $baseUrl ?>/admin/wave-transactions?status=pending" class="btn btn-primary btn-sm" title="Valider les paiements MM">Valider MM</a>
                <a href="<?= $baseUrl ?>/admin/retraits?statut=en_attente" class="btn btn-outline btn-sm" title="Retraits experts">Retraits</a>
                <button type="button" class="btn btn-outline btn-sm admin-revenus-btn-refresh" onclick="location.reload()" title="Rafraîchir">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Rafraîchir
                </button>
                <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-revenus-experts-table" data-export-name="revenus-experts" title="Export Excel">Excel</button>
                <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table admin-revenus-table" id="admin-revenus-experts-table">
                <thead>
                    <tr>
                        <th class="admin-revenus-table-rank">#</th>
                        <th>Expert</th>
                        <th>Titre</th>
                        <th class="admin-revenus-table-missions">Missions</th>
                        <th class="admin-revenus-table-commissions">Commissions</th>
                        <th class="admin-revenus-expert-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expertsActifs as $i => $expert): $rank = $i + 1;
                        $eid = (int)($expert['expert_id'] ?? 0);
                        $uid = (int)($expert['expert_user_id'] ?? 0);
                        $slug = trim((string)($expert['expert_slug'] ?? ''));
                        $profilPublic = $slug !== ''
                            ? $baseUrl . '/expert/' . rawurlencode($slug)
                            : $baseUrl . '/experts/show/' . $eid;
                    ?>
                    <tr>
                        <td class="admin-revenus-table-rank">
                            <span class="admin-revenus-rank admin-revenus-rank--<?= $rank <= 3 ? $rank : 'n' ?>"><?= $rank ?></span>
                        </td>
                        <td><strong><?= $e(trim(($expert['prenom'] ?? '') . ' ' . ($expert['nom'] ?? ''))) ?></strong></td>
                        <td><?= $e($expert['titre'] ?? '—') ?></td>
                        <td class="admin-revenus-table-missions"><?= (int)($expert['nb_missions'] ?? 0) ?></td>
                        <td class="admin-revenus-table-commissions"><strong><?= number_format((float)($expert['commissions_generees'] ?? 0), 0, ',', ' ') ?></strong> <?= $e($devise) ?></td>
                        <td class="admin-revenus-expert-actions">
                            <div class="admin-revenus-row-actions">
                                <a href="<?= $e($profilPublic) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener" title="Voir le profil public">Profil</a>
                                <?php if ($uid > 0): ?>
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= $uid ?>" class="btn btn-outline btn-sm" title="Compte utilisateur">Compte</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expertsActifs)): ?>
                    <tr>
                        <td colspan="6" class="admin-table-empty">Aucune donnée sur la période sélectionnée.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-table-footer">
            <span class="admin-table-count"><?= count($expertsActifs) ?> expert(s)</span>
        </div>
    </div>
</div>
