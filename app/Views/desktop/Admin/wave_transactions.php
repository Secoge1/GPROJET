<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$transactions   = $transactions ?? [];
$stats          = $stats ?? [];
$statusFilter   = $status_filter ?? null;
$providerFilter = $provider_filter ?? null;
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField      = \App\Core\Security::getCsrfField();

$buildTxQuery = static function (?string $status, ?string $provider): string {
    $q = [];
    if ($status   !== null && $status   !== '') { $q['status']   = $status; }
    if ($provider !== null && $provider !== '') { $q['provider'] = $provider; }
    return $q === [] ? '' : ('?' . http_build_query($q));
};

$statusConfig = [
    'pending' => ['label' => 'En attente', 'badge' => 'warning',  'icon' => '⏳'],
    'success' => ['label' => 'Validé',     'badge' => 'success',  'icon' => '✅'],
    'failed'  => ['label' => 'Refusé',     'badge' => 'danger',   'icon' => '❌'],
];
$nbPending      = (int) ($stats['pending']          ?? 0);
$nbSuccess      = (int) ($stats['success']          ?? 0);
$nbFailed       = (int) ($stats['failed']           ?? 0);
$totalCollecte  = number_format((float)($stats['total_collecte']  ?? 0), 0, ',', ' ');
$totalCommission= number_format((float)($stats['total_commission'] ?? 0), 0, ',', ' ');
$totalTx        = (int) ($stats['total'] ?? count($transactions));
$today          = date('Y-m-d');
?>
<div class="page-admin page-wave">

    <!-- ── En-tête héro ───────────────────────────────────────────── -->
    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div>
                <h1>Transactions InTouch</h1>
                <p>Valider, refuser ou supprimer les paiements Mobile Money</p>
            </div>
            <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;">
                <?php if ($nbPending > 0): ?>
                <span class="admin-badge admin-badge--warning wave-badge-pulse"><?= $nbPending ?> en attente</span>
                <?php endif; ?>
                <span class="admin-badge admin-badge--success"><?= $nbSuccess ?> validé<?= $nbSuccess > 1 ? 's' : '' ?></span>
                <span class="admin-badge admin-badge--danger"><?= $nbFailed ?> refusé<?= $nbFailed > 1 ? 's' : '' ?></span>
            </div>
        </div>
        <div class="admin-page-hero__filters">
            <span class="wave-filter-label">Statut :</span>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery(null, $providerFilter)) ?>"          class="admin-filter-pill <?= $statusFilter === null      ? 'active' : '' ?>">Tous</a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery('pending', $providerFilter)) ?>"     class="admin-filter-pill <?= $statusFilter === 'pending'  ? 'active' : '' ?>">⏳ En attente</a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery('success', $providerFilter)) ?>"     class="admin-filter-pill <?= $statusFilter === 'success'  ? 'active' : '' ?>">✅ Validés</a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery('failed', $providerFilter)) ?>"      class="admin-filter-pill <?= $statusFilter === 'failed'   ? 'active' : '' ?>">❌ Refusés</a>
            <span class="wave-filter-sep">|</span>
            <span class="wave-filter-label">Fournisseur :</span>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery($statusFilter, null)) ?>"            class="admin-filter-pill <?= $providerFilter === null      ? 'active' : '' ?>">Tous</a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery($statusFilter, 'intouch')) ?>"       class="admin-filter-pill <?= $providerFilter === 'intouch' ? 'active' : '' ?>">InTouch</a>
            <a href="<?= $baseUrl ?>/admin/wave-transactions<?= $e($buildTxQuery($statusFilter, 'wave')) ?>"          class="admin-filter-pill <?= $providerFilter === 'wave'    ? 'active' : '' ?>">Wave (historique)</a>
        </div>
    </header>

    <!-- ── Flash messages ─────────────────────────────────────────── -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="admin-alert admin-alert--danger"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- ── KPI Cards ──────────────────────────────────────────────── -->
    <div class="wave-kpi-grid">
        <div class="wave-kpi-card">
            <div class="wave-kpi-icon wave-kpi-icon--green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <div class="wave-kpi-body">
                <span class="wave-kpi-label">Collecté (validé)</span>
                <strong class="wave-kpi-value wave-kpi-value--green"><?= $totalCollecte ?> <small>XOF</small></strong>
            </div>
        </div>
        <div class="wave-kpi-card">
            <div class="wave-kpi-icon wave-kpi-icon--blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div class="wave-kpi-body">
                <span class="wave-kpi-label">Commissions plateforme</span>
                <strong class="wave-kpi-value wave-kpi-value--blue"><?= $totalCommission ?> <small>XOF</small></strong>
            </div>
        </div>
        <div class="wave-kpi-card">
            <div class="wave-kpi-icon wave-kpi-icon--slate">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div class="wave-kpi-body">
                <span class="wave-kpi-label">Total transactions</span>
                <strong class="wave-kpi-value"><?= $totalTx ?></strong>
            </div>
        </div>
        <div class="wave-kpi-card">
            <div class="wave-kpi-icon wave-kpi-icon--amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="wave-kpi-body">
                <span class="wave-kpi-label">En attente validation</span>
                <strong class="wave-kpi-value wave-kpi-value--amber"><?= $nbPending ?></strong>
            </div>
        </div>
    </div>

    <!-- ── Tableau principal ───────────────────────────────────────── -->
    <div class="admin-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <?= count($transactions) ?> transaction<?= count($transactions) > 1 ? 's' : '' ?>
                <?php if ($statusFilter || $providerFilter): ?>
                <span style="font-size:.75rem;font-weight:400;color:#6b7280;margin-left:.4rem;">(filtrées)</span>
                <?php endif; ?>
            </h2>
            <div class="admin-table-toolbar">
                <div class="admin-search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="wave-search" placeholder="Réf., utilisateur, téléphone, statut…" class="admin-search-input">
                </div>
                <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="wave-table" data-export-name="transactions_intouch">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:3px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Excel
                </button>
                <button type="button" class="btn btn-outline btn-sm admin-export-print">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:3px;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Imprimer
                </button>
            </div>
        </div>

        <!-- Barre de sélection multiple -->
        <form method="post" action="<?= $baseUrl ?>/admin/wave-delete-bulk" id="wave-bulk-form">
            <?= $csrfField ?>
            <div class="wave-bulk-bar" id="wave-bulk-bar">
                <label class="wave-bulk-info">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span id="wave-bulk-count">0</span> transaction(s) sélectionnée(s)
                </label>
                <div class="wave-bulk-actions">
                    <button type="button" class="btn btn-sm btn-outline" id="wave-select-all-btn" title="Tout sélectionner (toutes pages)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Tout sélectionner
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" id="wave-deselect-btn" style="display:none;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Désélectionner
                    </button>
                    <label class="wave-force-label" title="Permet aussi de supprimer les transactions validées">
                        <input type="checkbox" name="force_all" value="1" id="wave-force-check">
                        Inclure les validées
                    </label>
                    <button type="submit" class="btn btn-sm wave-btn-delete" id="wave-bulk-delete-btn" disabled
                            onclick="return confirmBulkDelete()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Supprimer la sélection
                    </button>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="table-desktop admin-table" id="wave-table">
                    <thead>
                        <tr>
                            <th class="wave-col-check">
                                <input type="checkbox" id="wave-check-all" class="wave-checkbox" title="Tout cocher/décocher">
                            </th>
                            <th>#</th>
                            <th>Référence</th>
                            <th>Utilisateur</th>
                            <th>Téléphone</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Commission</th>
                            <th>Total</th>
                            <th>Code opérateur</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="admin-table-col-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="13" class="admin-table-empty">Aucune transaction trouvée.</td></tr>
                    <?php else: foreach ($transactions as $tx):
                        $sc        = $statusConfig[$tx['status']] ?? ['label' => $e($tx['status'] ?? ''), 'badge' => 'default', 'icon' => ''];
                        $isPending = ($tx['status'] ?? '') === 'pending';
                        $isSuccess = ($tx['status'] ?? '') === 'success';
                        $hasCode   = !empty($tx['transaction_code']);
                        $isToday   = isset($tx['created_at']) && strpos($tx['created_at'], $today) === 0;
                        $rowClass  = $isPending && $hasCode ? 'wave-row-action' : ($isToday && !$isSuccess ? 'wave-row-today' : '');
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="wave-col-check">
                                <input type="checkbox" name="ids[]" value="<?= (int)$tx['id'] ?>"
                                       class="wave-checkbox wave-row-check"
                                       <?= $isSuccess ? 'title="Transaction validée — cochée à vos risques"' : '' ?>>
                            </td>
                            <td style="color:#9ca3af;font-size:.8rem;"><?= (int)$tx['id'] ?></td>
                            <td>
                                <code class="wave-ref"><?= $e($tx['payment_id']) ?></code>
                            </td>
                            <td>
                                <div class="wave-user-name"><?= $e(trim(($tx['prenom'] ?? '') . ' ' . ($tx['nom'] ?? ''))) ?></div>
                                <div class="wave-user-email"><?= $e($tx['email'] ?? '') ?></div>
                            </td>
                            <td class="wave-phone"><?= $e($tx['phone'] ?? '—') ?></td>
                            <td>
                                <?php $txType = (string)($tx['type'] ?? '');
                                if ($txType === 'depot_portefeuille'): ?>
                                    <span class="admin-badge admin-badge--info">💰 Dépôt</span>
                                <?php else:
                                    $aboType = $tx['abonnement_type'] ?? '';
                                    $aboBadge = $aboType === 'expert' ? 'info' : ($aboType === 'professeur' ? 'warning' : 'default');
                                ?>
                                    <span class="admin-badge admin-badge--<?= $aboBadge ?>">Abo. <?= ucfirst($e($aboType ?: '—')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="wave-amount"><?= number_format((float)($tx['amount'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="wave-fee"><?= number_format((float)($tx['platform_fee'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="wave-total"><?= number_format((float)($tx['total_amount'] ?? 0), 0, ',', ' ') ?> <span class="wave-currency">XOF</span></td>
                            <td>
                                <?php if ($hasCode): ?>
                                    <code class="wave-code-ok"><?= $e($tx['transaction_code']) ?></code>
                                <?php else: ?>
                                    <span class="wave-no-code">Non soumis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge--<?= $sc['badge'] ?>">
                                    <?= $sc['icon'] ?> <?= is_string($sc['label']) ? $e($sc['label']) : $sc['label'] ?>
                                </span>
                            </td>
                            <td class="wave-date">
                                <?= $e(isset($tx['created_at']) ? date('d/m/Y', strtotime($tx['created_at'])) : '') ?>
                                <br><small><?= $e(isset($tx['created_at']) ? date('H:i', strtotime($tx['created_at'])) : '') ?></small>
                            </td>
                            <td class="admin-table-col-action">
                                <div class="admin-action-group">
                                    <?php if ($isPending && $hasCode): ?>
                                    <!-- VALIDER -->
                                    <?php $confirmValider = ($tx['type'] ?? '') === 'depot_portefeuille'
                                        ? 'Créditer le portefeuille du client pour ce dépôt ?'
                                        : 'Valider cette transaction et activer l\'abonnement ?'; ?>
                                    <form method="post" action="<?= $baseUrl ?>/admin/wave-valider/<?= $e($tx['payment_id']) ?>"
                                          onsubmit="return confirm(<?= htmlspecialchars(json_encode($confirmValider), ENT_QUOTES, 'UTF-8') ?>)">
                                        <?= $csrfField ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--success" title="Valider">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                                            Valider
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="admin-action-btn admin-action-btn--danger"
                                            onclick="openRefuseModal('<?= $e($tx['payment_id']) ?>', '<?= $e(trim(($tx['prenom'] ?? '') . ' ' . ($tx['nom'] ?? ''))) ?>')"
                                            title="Refuser">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Refuser
                                    </button>
                                    <?php elseif ($isPending && !$hasCode): ?>
                                    <span class="wave-waiting-code">⏳ Code attendu</span>
                                    <button type="button"
                                            class="admin-action-btn admin-action-btn--danger"
                                            onclick="openRefuseModal('<?= $e($tx['payment_id']) ?>', '<?= $e(trim(($tx['prenom'] ?? '') . ' ' . ($tx['nom'] ?? ''))) ?>')"
                                            title="Annuler">
                                        Annuler
                                    </button>
                                    <?php else: ?>
                                    <span class="wave-done-label">—</span>
                                    <?php endif; ?>

                                    <!-- Lien profil -->
                                    <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$tx['user_id'] ?>"
                                       class="admin-action-btn admin-action-btn--neutral" title="Profil utilisateur">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        Profil
                                    </a>

                                    <!-- Supprimer (unitaire) -->
                                    <form method="post" action="<?= $baseUrl ?>/admin/wave-delete/<?= (int)$tx['id'] ?>"
                                          onsubmit="return confirm('Supprimer cette transaction ?<?= $isSuccess ? ' (attention : transaction validée !)' : '' ?>')">
                                        <?= $csrfField ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger" title="Supprimer">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </form><!-- /wave-bulk-form -->
    </div>
</div>

<!-- ── Modal refus ──────────────────────────────────────────────── -->
<div id="refuse-modal" class="wave-modal-overlay" style="display:none;">
    <div class="wave-modal">
        <div class="wave-modal-header">
            <div class="wave-modal-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div>
                <h3>Refuser la transaction</h3>
                <p id="refuse-user"></p>
            </div>
        </div>
        <form method="post" id="refuse-form">
            <?= $csrfField ?>
            <label class="wave-modal-label" for="refuse-notes">Motif du refus <span style="color:#9ca3af;font-weight:400;">(visible par l'utilisateur)</span></label>
            <textarea id="refuse-notes" name="notes" rows="3"
                      placeholder="Ex : code invalide, montant incorrect, délai dépassé…"
                      class="wave-modal-textarea"></textarea>
            <div class="wave-modal-footer">
                <button type="submit" class="btn wave-btn-refuse-confirm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Confirmer le refus
                </button>
                <button type="button" class="btn wave-btn-cancel" onclick="document.getElementById('refuse-modal').style.display='none'">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ── KPI ──────────────────────────────────────────────────────── */
.wave-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.wave-kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    transition: box-shadow .15s;
}
.wave-kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.wave-kpi-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.wave-kpi-icon--green  { background: #dcfce7; color: #16a34a; }
.wave-kpi-icon--blue   { background: #dbeafe; color: #2563eb; }
.wave-kpi-icon--slate  { background: #f1f5f9; color: #475569; }
.wave-kpi-icon--amber  { background: #fef3c7; color: #d97706; }
.wave-kpi-icon svg     { display: block; }
.wave-kpi-body { min-width: 0; }
.wave-kpi-label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .2rem;
}
.wave-kpi-value {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    display: block;
    line-height: 1.1;
}
.wave-kpi-value small { font-size: .75rem; font-weight: 500; color: #9ca3af; }
.wave-kpi-value--green { color: #16a34a; }
.wave-kpi-value--blue  { color: #2563eb; }
.wave-kpi-value--amber { color: #d97706; }

/* ── Filtres hero ─────────────────────────────────────────────── */
.wave-filter-label {
    font-size: .8rem;
    color: rgba(255,255,255,.6);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.wave-filter-sep {
    color: rgba(255,255,255,.25);
    margin: 0 .25rem;
}

/* ── Badge pulse ──────────────────────────────────────────────── */
@keyframes wavePulse { 0%,100% { opacity:1; } 50% { opacity:.6; } }
.wave-badge-pulse { animation: wavePulse 2s ease-in-out infinite; }

/* ── Sélection multiple ───────────────────────────────────────── */
.wave-bulk-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
    padding: .65rem 1.1rem;
    background: #f0f7ff;
    border-top: 1px solid #bfdbfe;
    border-bottom: 1px solid #bfdbfe;
}
.wave-bulk-info {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .82rem;
    font-weight: 600;
    color: #1e40af;
    white-space: nowrap;
}
#wave-bulk-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    background: #2563eb;
    color: #fff;
    border-radius: 99px;
    font-size: .73rem;
    font-weight: 800;
    padding: 0 5px;
}
.wave-bulk-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}
.wave-force-label {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .78rem;
    color: #6b7280;
    cursor: pointer;
    padding: .25rem .5rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    white-space: nowrap;
}
.wave-force-label input { accent-color: #dc2626; }
.wave-btn-delete {
    background: #dc2626;
    color: #fff;
    border: 1px solid #dc2626;
    border-radius: 6px;
    padding: .3rem .75rem;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .35rem;
    transition: background .15s;
}
.wave-btn-delete:hover:not(:disabled) { background: #b91c1c; border-color: #b91c1c; }
.wave-btn-delete:disabled { opacity: .45; cursor: not-allowed; }
.wave-col-check { width: 36px; text-align: center; padding: 0 8px !important; }
.wave-checkbox {
    width: 15px; height: 15px;
    cursor: pointer;
    accent-color: #2563eb;
}
#wave-table tbody tr { transition: background .1s; cursor: pointer; }
#wave-table tbody tr.wave-row-action  { background: #fffbeb; }
#wave-table tbody tr.wave-row-today   { background: #f0fdf4; }
#wave-table tbody tr:has(.wave-row-check:checked) { background: #eff6ff !important; }
#wave-table tbody tr:hover { background: #f8fafc; }

/* ── Cellules tableau ─────────────────────────────────────────── */
.wave-ref {
    font-size: .75rem;
    background: #f1f5f9;
    color: #475569;
    padding: .15rem .45rem;
    border-radius: 4px;
    font-family: monospace;
    max-width: 130px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}
.wave-user-name { font-weight: 600; font-size: .875rem; color: #0f172a; }
.wave-user-email { font-size: .72rem; color: #9ca3af; }
.wave-phone { font-family: monospace; font-size: .875rem; }
.wave-amount, .wave-fee { font-size: .875rem; color: #374151; }
.wave-total { font-weight: 700; font-size: .9rem; white-space: nowrap; }
.wave-currency { font-size: .72rem; color: #9ca3af; font-weight: 400; }
.wave-code-ok {
    font-size: .78rem;
    background: #f0fdf4;
    color: #16a34a;
    padding: .2rem .45rem;
    border-radius: 4px;
    font-family: monospace;
}
.wave-no-code { font-size: .78rem; color: #9ca3af; }
.wave-date { font-size: .78rem; color: #6b7280; white-space: nowrap; line-height: 1.4; }
.wave-date small { color: #9ca3af; }
.wave-waiting-code { font-size: .78rem; color: #d97706; font-weight: 600; }
.wave-done-label { font-size: .78rem; color: #9ca3af; }

/* ── Modal refus ──────────────────────────────────────────────── */
.wave-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}
.wave-modal {
    background: #fff;
    border-radius: 16px;
    padding: 1.75rem;
    width: 90%;
    max-width: 460px;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
}
.wave-modal-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.wave-modal-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: #fef2f2;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.wave-modal-header h3 { margin: 0 0 .2rem; font-size: 1.05rem; color: #0f172a; }
.wave-modal-header p  { margin: 0; font-size: .82rem; color: #6b7280; }
.wave-modal-label {
    display: block;
    font-size: .85rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .45rem;
}
.wave-modal-textarea {
    width: 100%;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: .65rem .875rem;
    font-size: .875rem;
    box-sizing: border-box;
    resize: vertical;
    font-family: inherit;
    transition: border-color .15s;
}
.wave-modal-textarea:focus { outline: none; border-color: #2563eb; }
.wave-modal-footer { display: flex; gap: .75rem; margin-top: 1.1rem; }
.wave-btn-refuse-confirm {
    flex: 1;
    background: #dc2626;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: .75rem;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .4rem;
    font-size: .875rem;
    transition: background .15s;
}
.wave-btn-refuse-confirm:hover { background: #b91c1c; }
.wave-btn-cancel {
    flex: 1;
    background: #f1f5f9;
    color: #374151;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: .75rem;
    font-weight: 600;
    cursor: pointer;
    font-size: .875rem;
    transition: background .15s;
}
.wave-btn-cancel:hover { background: #e2e8f0; }
</style>

<script>
(function () {
    /* ── Recherche live ───────────────────────────────────────── */
    document.getElementById('wave-search')?.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#wave-table tbody tr').forEach(function (tr) {
            var isEmpty = tr.querySelector('.admin-table-empty');
            if (isEmpty) return;
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    /* ── Modal refus ──────────────────────────────────────────── */
    window.openRefuseModal = function (paymentId, userName) {
        var baseUrl = '<?= $baseUrl ?>';
        document.getElementById('refuse-form').action = baseUrl + '/admin/wave-refuser/' + paymentId;
        document.getElementById('refuse-user').textContent = paymentId + (userName ? ' — ' + userName : '');
        document.getElementById('refuse-notes').value = '';
        var modal = document.getElementById('refuse-modal');
        modal.style.display = 'flex';
    };
    document.getElementById('refuse-modal')?.addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var m = document.getElementById('refuse-modal');
            if (m) m.style.display = 'none';
        }
    });

    /* ── Sélection multiple ───────────────────────────────────── */
    var checkAll    = document.getElementById('wave-check-all');
    var countEl     = document.getElementById('wave-bulk-count');
    var deleteBtn   = document.getElementById('wave-bulk-delete-btn');
    var selectAllB  = document.getElementById('wave-select-all-btn');
    var deselectB   = document.getElementById('wave-deselect-btn');

    function getAllChecks()     { return Array.from(document.querySelectorAll('#wave-table .wave-row-check')); }
    function getVisibleChecks(){ return Array.from(document.querySelectorAll('#wave-table tbody tr:not([style*="none"]) .wave-row-check')); }

    function updateBulk() {
        var all  = getAllChecks();
        var n    = all.filter(function(c){ return c.checked; }).length;
        countEl.textContent = n;
        deleteBtn.disabled  = n === 0;

        var vis     = getVisibleChecks();
        var allChk  = vis.length > 0 && vis.every(function(c){ return c.checked; });
        var someChk = vis.some(function(c){ return c.checked; });

        if (checkAll) {
            checkAll.indeterminate = false;
            if (!vis.length)      { checkAll.checked = false; }
            else if (allChk)      { checkAll.checked = true; }
            else if (someChk)     { checkAll.indeterminate = true; checkAll.checked = false; }
            else                  { checkAll.checked = false; }
        }
        if (selectAllB && deselectB) {
            var allGlobal = all.every(function(c){ return c.checked; });
            selectAllB.style.display = allGlobal ? 'none' : '';
            deselectB.style.display  = allGlobal ? ''     : 'none';
        }
    }

    // Case en-tête → page visible uniquement
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            getVisibleChecks().forEach(function(c){ c.checked = checkAll.checked; });
            updateBulk();
        });
    }

    // Tout sélectionner (toutes lignes du tableau)
    if (selectAllB) {
        selectAllB.addEventListener('click', function () {
            getAllChecks().forEach(function(c){ c.checked = true; });
            if (checkAll) checkAll.checked = true;
            updateBulk();
        });
    }

    if (deselectB) {
        deselectB.addEventListener('click', function () {
            getAllChecks().forEach(function(c){ c.checked = false; });
            if (checkAll) checkAll.checked = false;
            updateBulk();
        });
    }

    // Clic sur case individuelle
    document.getElementById('wave-table').addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('wave-row-check')) updateBulk();
    });

    // Clic sur ligne entière pour cocher (hors liens/boutons/cases)
    document.getElementById('wave-table').addEventListener('click', function (e) {
        var td = e.target.closest('td');
        if (!td || td.classList.contains('wave-col-check') || td.classList.contains('admin-table-col-action')) return;
        var cb = td.closest('tr')?.querySelector('.wave-row-check');
        if (!cb) return;
        cb.checked = !cb.checked;
        updateBulk();
    });

    updateBulk();

    /* ── Confirmation suppression bulk ───────────────────────── */
    window.confirmBulkDelete = function () {
        var n      = getAllChecks().filter(function(c){ return c.checked; }).length;
        var force  = document.getElementById('wave-force-check')?.checked;
        var msg    = 'Supprimer ' + n + ' transaction(s) sélectionnée(s) ?';
        if (force) msg += '\n⚠ Les transactions validées seront aussi supprimées !';
        else       msg += '\n(les transactions validées ✅ seront ignorées par sécurité)';
        return confirm(msg);
    };
})();
</script>
