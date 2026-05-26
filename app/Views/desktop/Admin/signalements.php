<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$signalements = $signalements ?? [];
$statutFilter = $statutFilter ?? null;
$statutLabels = [
    'nouveau'  => 'Nouveau',
    'en_cours' => 'En cours',
    'traite'   => 'Traité',
    'rejete'   => 'Rejeté',
];
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();

$nbNouveau  = count(array_filter($signalements, fn($s) => ($s['statut'] ?? '') === 'nouveau'));
$nbEnCours  = count(array_filter($signalements, fn($s) => ($s['statut'] ?? '') === 'en_cours'));
?>
<div class="page-admin page-admin-signalements">
    <header class="admin-signalements-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-signalements-hero-content">
            <div class="admin-signalements-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            </div>
            <div class="admin-signalements-hero-text">
                <h1>Signalements</h1>
                <p class="admin-signalements-hero-subtitle">Gérer les signalements des utilisateurs</p>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <?php if ($nbNouveau > 0): ?><span class="admin-badge admin-badge--warning"><?= $nbNouveau ?> nouveau<?= $nbNouveau > 1 ? 'x' : '' ?></span><?php endif; ?>
                <?php if ($nbEnCours > 0): ?><span class="admin-badge admin-badge--info"><?= $nbEnCours ?> en cours</span><?php endif; ?>
            </div>
            <div class="admin-signalements-filters">
                <span class="admin-signalements-filters-label">Statut</span>
                <div class="admin-signalements-pills">
                    <a href="<?= $baseUrl ?>/admin/signalements" class="admin-signalements-pill <?= $statutFilter === null ? 'active' : '' ?>">Tous</a>
                    <a href="<?= $baseUrl ?>/admin/signalements?statut=nouveau" class="admin-signalements-pill <?= $statutFilter === 'nouveau' ? 'active' : '' ?>">Nouveau</a>
                    <a href="<?= $baseUrl ?>/admin/signalements?statut=en_cours" class="admin-signalements-pill <?= $statutFilter === 'en_cours' ? 'active' : '' ?>">En cours</a>
                    <a href="<?= $baseUrl ?>/admin/signalements?statut=traite" class="admin-signalements-pill <?= $statutFilter === 'traite' ? 'active' : '' ?>">Traité</a>
                    <a href="<?= $baseUrl ?>/admin/signalements?statut=rejete" class="admin-signalements-pill <?= $statutFilter === 'rejete' ? 'active' : '' ?>">Rejeté</a>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-table-card admin-signalements-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                Liste des signalements
            </h2>
            <div class="admin-signalements-toolbar">
                <div class="admin-signalements-search-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-signalements-search-icon" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="admin-signalements-search" class="admin-signalements-search" placeholder="Signaleur, type, motif…" aria-label="Rechercher">
                </div>
                <div class="admin-signalements-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-signalements-table" data-export-name="signalements" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table admin-signalements-table" id="admin-signalements-table">
                <thead>
                    <tr>
                        <th class="admin-signalements-table-id">ID</th>
                        <th>Signaleur</th>
                        <th>Type</th>
                        <th>Cible</th>
                        <th>Motif</th>
                        <th class="admin-signalements-table-statut">Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signalements as $s): ?>
                    <?php $sStatut = $s['statut'] ?? 'nouveau'; ?>
                    <tr data-search="<?= $e(mb_strtolower(($s['signaleur_nom'] ?? '') . ' ' . ($s['cible_type'] ?? '') . ' ' . ($s['motif'] ?? ''))) ?>">
                        <td class="admin-signalements-table-id"><?= (int)$s['id'] ?></td>
                        <td>
                            <?= $e($s['signaleur_nom'] ?? '—') ?>
                            <?php if (!empty($s['signaleur_email'])): ?>
                                <br><small class="text-muted"><?= $e($s['signaleur_email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-signalements-badge admin-signalements-badge--cible"><?= $e($s['cible_type'] ?? '') ?></span></td>
                        <td>
                            <?php $cibleId = (int)($s['cible_id'] ?? 0); ?>
                            <?php if ($cibleId > 0 && ($s['cible_type'] ?? '') === 'utilisateur'): ?>
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= $cibleId ?>" class="admin-link" title="Voir le profil">#<?= $cibleId ?></a>
                            <?php elseif ($cibleId > 0): ?>
                                #<?= $cibleId ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-signalements-table-motif" title="<?= $e($s['motif'] ?? '') ?>" style="cursor:help;">
                            <?= $e(mb_substr((string)($s['motif'] ?? ''), 0, 60)) ?><?= mb_strlen((string)($s['motif'] ?? '')) > 60 ? '…' : '' ?>
                        </td>
                        <td class="admin-signalements-table-statut">
                            <span class="admin-signalements-badge admin-signalements-badge--<?= $e($sStatut) ?>"><?= $e($statutLabels[$sStatut] ?? $sStatut) ?></span>
                        </td>
                        <td><?= $e(date('d/m/Y H:i', strtotime($s['created_at'] ?? 'now'))) ?></td>
                        <td class="admin-table-col-action">
                            <div class="admin-action-group">
                                <?php if ($sStatut === 'nouveau'): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/traiter-signalement/<?= (int)$s['id'] ?>">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="statut" value="en_cours">
                                    <button class="admin-action-btn admin-action-btn--info" title="Prendre en charge">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                        En cours
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($sStatut, ['nouveau', 'en_cours'], true)): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/traiter-signalement/<?= (int)$s['id'] ?>">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="statut" value="traite">
                                    <button class="admin-action-btn admin-action-btn--success" title="Marquer traité">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Traité
                                    </button>
                                </form>
                                <form method="post" action="<?= $baseUrl ?>/admin/traiter-signalement/<?= (int)$s['id'] ?>">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="statut" value="rejete">
                                    <button class="admin-action-btn admin-action-btn--danger" title="Rejeter" onclick="return confirm('Rejeter ce signalement ?')">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Rejeter
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($sStatut, ['traite', 'rejete'], true)): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/traiter-signalement/<?= (int)$s['id'] ?>">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="statut" value="nouveau">
                                    <button class="admin-action-btn admin-action-btn--neutral" title="Rouvrir">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        Rouvrir
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($signalements)): ?>
                    <tr>
                        <td colspan="8" class="admin-table-empty">Aucun signalement<?= $statutFilter ? ' pour ce filtre' : '' ?>.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-table-footer">
            <span class="admin-table-count"><?= count($signalements) ?> signalement(s)</span>
        </div>
    </div>
</div>
<script>
(function() {
    var search = document.getElementById('admin-signalements-search');
    var table = document.getElementById('admin-signalements-table');
    if (!search || !table) return;
    var rows = table.querySelectorAll('tbody tr[data-search]');
    search.addEventListener('input', function() {
        var q = (this.value || '').trim().toLowerCase();
        rows.forEach(function(tr) {
            var text = (tr.getAttribute('data-search') || '');
            tr.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
