<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$demandes = $demandes ?? [];
$statutFilter = $statutFilter ?? null;
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();

$statutColors = [
    'ouverte'   => 'success',
    'en_cours'  => 'info',
    'terminee'  => 'default',
    'annulee'   => 'danger',
];
$statutLabels = [
    'ouverte'   => 'Ouverte',
    'en_cours'  => 'En cours',
    'terminee'  => 'Terminée',
    'annulee'   => 'Annulée',
];

$nbOuvertes = count(array_filter($demandes, fn($d) => ($d['statut'] ?? '') === 'ouverte'));
$nbEnCours  = count(array_filter($demandes, fn($d) => ($d['statut'] ?? '') === 'en_cours'));
?>
<div class="page-admin page-admin-demandes">

    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
                <h1>Demandes d'assistance</h1>
                <p>Superviser toutes les demandes clients de la plateforme</p>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <span class="admin-badge admin-badge--success"><?= $nbOuvertes ?> ouverte<?= $nbOuvertes > 1 ? 's' : '' ?></span>
                <span class="admin-badge admin-badge--info"><?= $nbEnCours ?> en cours</span>
                <a href="<?= $baseUrl ?>/demandes" target="_blank"
                   style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.78rem;font-weight:600;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:4px 10px;text-decoration:none;"
                   title="Voir la page publique des demandes (ce que voient les visiteurs)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Voir page publique
                </a>
            </div>
        </div>
        <div class="admin-page-hero__filters">
            <a href="<?= $baseUrl ?>/admin/demandes" class="admin-filter-pill <?= $statutFilter === null ? 'active' : '' ?>">Toutes (<?= count($demandes) ?>)</a>
            <a href="<?= $baseUrl ?>/admin/demandes?statut=ouverte" class="admin-filter-pill <?= $statutFilter === 'ouverte' ? 'active' : '' ?>">Ouvertes</a>
            <a href="<?= $baseUrl ?>/admin/demandes?statut=en_cours" class="admin-filter-pill <?= $statutFilter === 'en_cours' ? 'active' : '' ?>">En cours</a>
            <a href="<?= $baseUrl ?>/admin/demandes?statut=terminee" class="admin-filter-pill <?= $statutFilter === 'terminee' ? 'active' : '' ?>">Terminées</a>
            <a href="<?= $baseUrl ?>/admin/demandes?statut=annulee" class="admin-filter-pill <?= $statutFilter === 'annulee' ? 'active' : '' ?>">Annulées</a>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="admin-alert admin-alert--error"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); endif; ?>
    <?php endif; ?>

    <div class="admin-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?>
            </h2>
            <div class="admin-table-toolbar">
                <div class="admin-search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="demandes-search" placeholder="Client, compétence, titre…" class="admin-search-input">
                </div>
                <div class="admin-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="demandes-table" data-export-name="demandes">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table" id="demandes-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Titre</th>
                        <th>Compétence</th>
                        <th>Budget</th>
                        <th>Urgence</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th title="Visible sur la page publique /demandes">Public</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($demandes)): ?>
                    <tr><td colspan="10" class="admin-table-empty">Aucune demande trouvée.</td></tr>
                <?php else: foreach ($demandes as $d): ?>
                    <tr>
                        <td><?= (int)$d['id'] ?></td>
                        <td>
                            <strong><?= $e(trim(($d['client_prenom'] ?? '') . ' ' . ($d['client_nom'] ?? ''))) ?></strong>
                            <br><small class="text-muted"><?= $e($d['client_email'] ?? '') ?></small>
                        </td>
                        <td>
                            <span title="<?= $e($d['description'] ?? '') ?>" style="cursor:help;">
                                <?= $e(mb_strimwidth($d['titre'] ?? $d['description'] ?? '—', 0, 50, '…')) ?>
                            </span>
                        </td>
                        <td><?= $d['competence_nom'] !== null && $d['competence_nom'] !== '' ? $e($d['competence_nom']) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php $budget = (float)($d['budget_max'] ?? 0); ?>
                            <?= $budget > 0 ? number_format($budget, 0, ',', ' ') . ' ' . \App\Core\Security::escape(devise()) : '<span class="text-muted">Non défini</span>' ?>
                        </td>
                        <td>
                            <?php if (!empty($d['urgence'])): ?>
                                <span class="admin-badge admin-badge--warning">⚡ Urgent</span>
                            <?php else: ?>
                                <span class="text-muted">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $e(isset($d['created_at']) ? date('d/m/Y', strtotime($d['created_at'])) : '—') ?></td>
                        <td>
                            <?php $sc = $statutColors[$d['statut'] ?? ''] ?? 'default'; ?>
                            <span class="admin-badge admin-badge--<?= $sc ?>"><?= $statutLabels[$d['statut'] ?? ''] ?? $e($d['statut'] ?? '—') ?></span>
                        </td>
                        <td style="text-align:center;">
                            <?php if (($d['statut'] ?? '') === 'ouverte'): ?>
                                <a href="<?= $baseUrl ?>/demandes" target="_blank" title="Visible sur la page publique /demandes" style="color:#16a34a;text-decoration:none;display:inline-flex;align-items:center;gap:3px;font-size:0.78rem;font-weight:600;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Oui
                                </a>
                            <?php else: ?>
                                <span title="Non visible publiquement (statut : <?= $e($d['statut'] ?? '') ?>)" style="color:#94a3b8;font-size:0.78rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table-col-action">
                            <div class="admin-action-group">
                                <!-- Voir le client -->
                                <?php if (!empty($d['client_id'])): ?>
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$d['client_id'] ?>" class="admin-action-btn admin-action-btn--neutral" title="Voir le client">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Client
                                </a>
                                <?php endif; ?>

                                <?php $statut = $d['statut'] ?? ''; ?>

                                <!-- Marquer En cours (si ouverte) -->
                                <?php if ($statut === 'ouverte'): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/mettre-en-cours-demande/<?= (int)$d['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--info" title="Marquer en cours">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                        En cours
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Marquer Terminée (si en_cours) -->
                                <?php if ($statut === 'en_cours'): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/terminer-demande/<?= (int)$d['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--success" title="Marquer terminée">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Terminer
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Annuler (si ouverte ou en_cours) -->
                                <?php if (in_array($statut, ['ouverte', 'en_cours'], true)): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/fermer-demande/<?= (int)$d['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--danger" title="Annuler la demande" onclick="return confirm('Annuler cette demande ?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Annuler
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Rouvrir (si annulée ou terminée) -->
                                <?php if (in_array($statut, ['annulee', 'terminee'], true)): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/rouvrir-demande/<?= (int)$d['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--success" title="Rouvrir la demande">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        Rouvrir
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Supprimer définitivement -->
                                <form method="post" action="<?= $baseUrl ?>/admin/delete-demande/<?= (int)$d['id'] ?>" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cette demande ? Cette action est irréversible.');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--danger admin-demandes-btn-delete" title="Supprimer la demande">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        Supprimer
                                    </button>
                                </form>
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
    var inp = document.getElementById('demandes-search');
    if (!inp) return;
    inp.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        var rows = document.querySelectorAll('#demandes-table tbody tr');
        rows.forEach(function(tr) {
            if (tr.querySelector('td[colspan]')) return; // ne pas masquer la ligne vide
            tr.style.display = (!q || tr.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    });
})();
</script>
