<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$experts = $experts ?? [];
$statutFilter = $statutFilter ?? null;
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();
$devise = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
// Chemin d’app (sans schéma/hôte) : les <img> héritent du protocole de la page → évite blocages mixed content.
$urlPath = parse_url($baseUrl !== '' ? $baseUrl . '/' : 'http://localhost/', PHP_URL_PATH);
$appPathPrefix = (is_string($urlPath) && $urlPath !== '' && $urlPath !== '/') ? rtrim($urlPath, '/') : '';
?>
<div class="page-admin page-admin-experts">
    <header class="admin-experts-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-experts-hero-content">
            <div class="admin-experts-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="admin-experts-hero-text">
                <h1>Validation des experts</h1>
                <p class="admin-experts-hero-subtitle">Valider les profils experts et gérer leur visibilité sur la plateforme</p>
            </div>
            <div class="admin-experts-filters">
                <span class="admin-experts-filters-label">Statut</span>
                <div class="admin-experts-pills">
                    <a href="<?= $baseUrl ?>/admin/experts" class="admin-experts-pill <?= $statutFilter === null ? 'active' : '' ?>">Tous</a>
                    <a href="<?= $baseUrl ?>/admin/experts?statut=non_valide" class="admin-experts-pill <?= $statutFilter === 'non_valide' ? 'active' : '' ?>">À valider</a>
                    <a href="<?= $baseUrl ?>/admin/experts?statut=valide" class="admin-experts-pill <?= $statutFilter === 'valide' ? 'active' : '' ?>">Validés</a>
                </div>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
    <div class="admin-experts-flash">
        <?php if (!empty($_SESSION['flash_success'])): ?>
        <p class="admin-experts-flash__success"><?= $e($_SESSION['flash_success']) ?></p>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <p class="admin-experts-flash__error"><?= $e($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); endif; ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-card admin-experts-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Liste des profils experts
            </h2>
            <div class="admin-experts-toolbar">
                <div class="admin-experts-search-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-experts-search-icon" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="admin-experts-search" class="admin-experts-search" placeholder="Nom, email ou titre…" aria-label="Rechercher">
                </div>
                <div class="admin-experts-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-experts-table" data-export-name="experts" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table admin-experts-table" id="admin-experts-table">
                <thead>
                    <tr>
                        <th class="admin-experts-table-photo">Photo</th>
                        <th>Profil / Titre</th>
                        <th>Expert</th>
                        <th>Email</th>
                        <th class="admin-experts-table-tarif">Tarif</th>
                        <th class="admin-experts-table-statut">Statut</th>
                        <th class="admin-table-col-action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($experts as $ex):
                        $userId = (int)($ex['utilisateur_id'] ?? 0);
                        $avatarDb = trim((string)($ex['user_avatar'] ?? $ex['avatar'] ?? ''));
                        $hasAvatar = $userId > 0 && $avatarDb !== '';
                        $avatarSrc = $hasAvatar
                            ? $appPathPrefix . '/fichier/user-avatar/' . $userId . '?t=' . time()
                            : '';
                    ?>
                    <tr data-search="<?= $e(mb_strtolower(($ex['titre'] ?? '') . ' ' . ($ex['prenom'] ?? '') . ' ' . ($ex['nom'] ?? '') . ' ' . ($ex['email'] ?? ''))) ?>">
                        <td class="admin-experts-table-photo">
                            <?php if ($avatarSrc !== ''): ?>
                            <img src="<?= $e($avatarSrc) ?>" alt="" class="admin-experts-avatar" width="40" height="40" loading="lazy" decoding="async" onerror="this.onerror=null;this.style.display='none';var n=this.nextElementSibling;if(n)n.style.display='inline-flex';">
                            <span class="admin-experts-avatar admin-experts-avatar--placeholder" aria-hidden="true" title="Photo indisponible" style="display:none">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 14a3 3 0 0 0 3-3 3 3 0 0 0-6 0 3 3 0 0 0 3 3z"/><path d="M8 20v-1a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4v1"/></svg>
                            </span>
                            <?php else: ?>
                            <span class="admin-experts-avatar admin-experts-avatar--placeholder" aria-hidden="true" title="Pas de photo">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 14a3 3 0 0 0 3-3 3 3 0 0 0-6 0 3 3 0 0 0 3 3z"/><path d="M8 20v-1a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4v1"/></svg>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="admin-experts-cell-titre"><?= $e($ex['titre'] ?? '—') ?></strong>
                        </td>
                        <td><?= $e(trim(($ex['prenom'] ?? '') . ' ' . ($ex['nom'] ?? ''))) ?></td>
                        <td>
                            <a href="mailto:<?= $e($ex['email'] ?? '') ?>" class="admin-experts-email"><?= $e($ex['email'] ?? '') ?></a>
                        </td>
                        <td class="admin-experts-table-tarif"><?= number_format((float)($ex['tarif_horaire'] ?? 0), 2, ',', ' ') ?> <?= $e($devise ?? 'XOF') ?>/h</td>
                        <td class="admin-experts-table-statut">
                            <?php if (!empty($ex['valide_par_admin'])): ?>
                                <span class="admin-experts-badge admin-experts-badge--valide">Validé</span>
                            <?php else: ?>
                                <span class="admin-experts-badge admin-experts-badge--attente">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table-col-action">
                            <div class="admin-experts-cell-actions">
                                <a href="<?= $baseUrl ?>/experts/show/<?= (int)($ex['id'] ?? 0) ?>" class="btn btn-outline btn-sm" title="Voir le profil public">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Profil
                                </a>
                                <?php if (!empty($ex['utilisateur_id'])): ?>
                                    <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$ex['utilisateur_id'] ?>" class="btn btn-outline btn-sm" title="Modifier le compte utilisateur">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Compte
                                    </a>
                                <?php endif; ?>
                                <?php if (empty($ex['valide_par_admin'])): ?>
                                    <form method="post" action="<?= $baseUrl ?>/admin/valider-expert/<?= (int)($ex['id'] ?? 0) ?>" style="display:inline;">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn btn-primary btn-sm" title="Valider ce profil">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            Valider
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= $baseUrl ?>/admin/invalider-expert/<?= (int)($ex['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Retirer la validation de ce profil ? L\'expert ne sera plus visible dans la liste publique.');">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn btn-outline btn-sm admin-btn-invalider" title="Retirer la validation">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                            Invalider
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/delete-expert/<?= (int)($ex['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Supprimer définitivement ce profil expert ? Le compte utilisateur restera, mais le profil expert sera supprimé.');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-outline btn-sm admin-experts-btn-delete" title="Supprimer le profil expert">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($experts)): ?>
                    <tr>
                        <td colspan="7" class="admin-table-empty">
                            Aucun profil expert<?= $statutFilter ? ' pour ce filtre' : '' ?>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-table-footer">
            <span class="admin-table-count"><?= count($experts) ?> profil(s) expert(s)</span>
        </div>
    </div>
</div>
<script>
(function() {
    var search = document.getElementById('admin-experts-search');
    var table = document.getElementById('admin-experts-table');
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
