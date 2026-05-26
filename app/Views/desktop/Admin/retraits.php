<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$retraits = $retraits ?? [];
$statutFilter = $statutFilter ?? null;
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();

$nbEnAttente = count(array_filter($retraits, fn($r) => ($r['statut'] ?? '') === 'en_attente'));

$statutColors = [
    'en_attente' => 'warning',
    'traitee'    => 'success',
    'refusee'    => 'danger',
];
$statutLabels = [
    'en_attente' => 'En attente',
    'traitee'    => 'Traité',
    'refusee'    => 'Refusé',
];
?>
<div class="page-admin page-admin-retraits">

    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <h1>Demandes de retrait</h1>
                <p>Approuver ou rejeter les retraits des experts</p>
            </div>
            <?php if ($nbEnAttente > 0): ?>
            <span class="admin-badge admin-badge--warning"><?= $nbEnAttente ?> en attente</span>
            <?php endif; ?>
        </div>
        <div class="admin-page-hero__filters">
            <a href="<?= $baseUrl ?>/admin/retraits" class="admin-filter-pill <?= $statutFilter === null ? 'active' : '' ?>">Tous (<?= count($retraits) ?>)</a>
            <a href="<?= $baseUrl ?>/admin/retraits?statut=en_attente" class="admin-filter-pill <?= $statutFilter === 'en_attente' ? 'active' : '' ?>">En attente</a>
            <a href="<?= $baseUrl ?>/admin/retraits?statut=traitee" class="admin-filter-pill <?= $statutFilter === 'traitee' ? 'active' : '' ?>">Traités</a>
            <a href="<?= $baseUrl ?>/admin/retraits?statut=refusee" class="admin-filter-pill <?= $statutFilter === 'refusee' ? 'active' : '' ?>">Refusés</a>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="admin-alert admin-alert--error"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="admin-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <?= count($retraits) ?> demande<?= count($retraits) > 1 ? 's' : '' ?>
            </h2>
            <div class="admin-table-toolbar">
                <div class="admin-search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="retraits-search" placeholder="Expert, email, IBAN…" class="admin-search-input">
                </div>
                <div class="admin-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="retraits-table" data-export-name="retraits">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table" id="retraits-table">
                <thead>
                    <tr>
                        <th>Expert</th>
                        <th>Email</th>
                        <th>Montant</th>
                        <th>IBAN / Numéro Mobile Money</th>
                        <th>Date demande</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($retraits)): ?>
                    <tr><td colspan="7" class="admin-table-empty">Aucune demande de retrait.</td></tr>
                <?php else: foreach ($retraits as $r): ?>
                    <tr>
                        <td>
                            <strong><?= $e(trim(($r['expert_prenom'] ?? '') . ' ' . ($r['expert_nom'] ?? ''))) ?></strong>
                            <?php if (!empty($r['expert_titre'])): ?>
                                <br><small class="text-muted"><?= $e($r['expert_titre']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $e($r['expert_email'] ?? '') ?></td>
                        <td><strong><?= number_format((float)($r['montant'] ?? 0), 0, ',', ' ') ?> <?= \App\Core\Security::escape(devise()) ?></strong></td>
                        <td>
                            <?php $iban = $e($r['iban'] ?? ''); ?>
                            <?= $iban !== '' ? '<code style="font-size:0.8rem">' . $iban . '</code>' : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td><?= $e(isset($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '—') ?></td>
                        <td>
                            <?php $sc = $statutColors[$r['statut'] ?? ''] ?? 'default'; ?>
                            <span class="admin-badge admin-badge--<?= $sc ?>"><?= $statutLabels[$r['statut'] ?? ''] ?? $e($r['statut'] ?? '—') ?></span>
                        </td>
                        <td class="admin-table-col-action">
                            <div class="admin-action-group">
                                <!-- Voir le profil de l'expert -->
                                <?php if (!empty($r['expert_utilisateur_id'])): ?>
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$r['expert_utilisateur_id'] ?>" class="admin-action-btn admin-action-btn--neutral" title="Voir l'expert">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Expert
                                </a>
                                <?php endif; ?>

                                <?php if (($r['statut'] ?? '') === 'en_attente'): ?>
                                <!-- Approuver -->
                                <form method="post" action="<?= $baseUrl ?>/admin/approuver-retrait/<?= (int)$r['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--success" title="Approuver ce retrait" onclick="return confirm('Approuver ce retrait de <?= number_format((float)($r['montant']??0),0,',',' ') . ' ' . devise() ?> ?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Approuver
                                    </button>
                                </form>
                                <!-- Rejeter -->
                                <form method="post" action="<?= $baseUrl ?>/admin/rejeter-retrait/<?= (int)$r['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--danger" title="Rejeter ce retrait" onclick="return confirm('Rejeter ce retrait ?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Rejeter
                                    </button>
                                </form>
                                <?php elseif (($r['statut'] ?? '') === 'refusee'): ?>
                                <!-- Ré-approuver un retrait refusé -->
                                <form method="post" action="<?= $baseUrl ?>/admin/approuver-retrait/<?= (int)$r['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--info" title="Ré-approuver" onclick="return confirm('Ré-approuver ce retrait ?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        Ré-approuver
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var inp = document.getElementById('retraits-search');
    if (!inp) return;
    inp.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('#retraits-table tbody tr').forEach(function(tr) {
            if (tr.querySelector('td[colspan]')) return;
            tr.style.display = (!q || tr.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    });
})();
</script>
