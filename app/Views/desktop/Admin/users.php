<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$users = $users ?? [];
$roleFilter = $roleFilter ?? null;
$currentUserId = (int)($user['id'] ?? 0);
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();
?>
<div class="page-admin page-admin-users">
    <header class="admin-users-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-users-hero-content">
            <div class="admin-users-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="admin-users-hero-text">
                <h1>Utilisateurs</h1>
                <p class="admin-users-hero-subtitle">Gérer les comptes inscrits sur la plateforme</p>
            </div>
            <div class="admin-users-filters">
                <span class="admin-users-filters-label">Rôle</span>
                <div class="admin-users-pills">
                    <a href="<?= $baseUrl ?>/admin/users" class="admin-users-pill <?= $roleFilter === null ? 'active' : '' ?>">Tous</a>
                    <a href="<?= $baseUrl ?>/admin/users?role=client" class="admin-users-pill <?= $roleFilter === 'client' ? 'active' : '' ?>">Clients</a>
                    <a href="<?= $baseUrl ?>/admin/users?role=expert" class="admin-users-pill <?= $roleFilter === 'expert' ? 'active' : '' ?>">Experts</a>
                    <a href="<?= $baseUrl ?>/admin/users?role=professeur" class="admin-users-pill <?= $roleFilter === 'professeur' ? 'active' : '' ?>">Professeurs</a>
                    <a href="<?= $baseUrl ?>/admin/users?role=etudiant" class="admin-users-pill <?= $roleFilter === 'etudiant' ? 'active' : '' ?>">Étudiants</a>
                    <a href="<?= $baseUrl ?>/admin/users?role=admin" class="admin-users-pill <?= $roleFilter === 'admin' ? 'active' : '' ?>">Admins</a>
                </div>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
    <div class="admin-users-flash">
        <?php if (!empty($_SESSION['flash_success'])): ?>
        <p class="admin-users-flash__success"><?= $e($_SESSION['flash_success']) ?></p>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <p class="admin-users-flash__error"><?= $e($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); endif; ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-card admin-users-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Liste des utilisateurs
            </h2>
            <div class="admin-users-toolbar">
                <div class="admin-users-search-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-users-search-icon" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="admin-users-search" class="admin-users-search" placeholder="Email ou nom…" aria-label="Rechercher">
                </div>
                <div class="admin-users-actions">
                    <a href="<?= $baseUrl ?>/admin/send-mail-group" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:.4rem;" title="Envoyer un email groupé">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Email groupé
                    </a>
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="admin-users-table" data-export-name="utilisateurs" title="Export Excel">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-pdf" title="Export PDF">PDF</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print" title="Imprimer">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table admin-users-table" id="admin-users-table">
                <thead>
                    <tr>
                        <th class="admin-users-table-id">ID</th>
                        <th>Email</th>
                        <th>Nom / Prénom</th>
                        <th class="admin-users-table-role">Rôle</th>
                        <th class="admin-users-table-statut">Statut</th>
                        <th>Inscrit le</th>
                        <th class="admin-table-col-action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr data-search="<?= $e(mb_strtolower(($u['email'] ?? '') . ' ' . ($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?>">
                        <td class="admin-users-table-id"><?= (int)$u['id'] ?></td>
                        <td>
                            <a href="mailto:<?= $e($u['email']) ?>" class="admin-users-email"><?= $e($u['email']) ?></a>
                        </td>
                        <td><?= $e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></td>
                        <td class="admin-users-table-role">
                            <span class="admin-users-badge admin-users-badge--<?= $e($u['role'] ?? 'client') ?>"><?= $e($u['role'] ?? '') ?></span>
                        </td>
                        <td class="admin-users-table-statut">
                            <?php if ($u['actif']): ?>
                                <span class="admin-users-badge admin-users-badge--actif">Actif</span>
                            <?php else: ?>
                                <span class="admin-users-badge admin-users-badge--inactif">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $e(date('d/m/Y à H:i', strtotime($u['created_at'] ?? 'now'))) ?></td>
                        <td class="admin-table-col-action">
                            <div class="admin-users-cell-actions">
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm" title="Modifier identifiants et mot de passe">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Modifier
                                </a>
                                <a href="<?= $baseUrl ?>/admin/send-mail-user/<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm" title="Envoyer un email à <?= $e(trim(($u['prenom']??'').''.$u['nom']??'')) ?>">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    Email
                                </a>
                                <?php if (!empty($u['expert_profil_id'])): ?>
                                    <a href="<?= $baseUrl ?>/experts/show/<?= (int)$u['expert_profil_id'] ?>" class="btn btn-outline btn-sm" title="Voir le profil public">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Profil
                                    </a>
                                <?php endif; ?>
                                <?php if ((int)$u['id'] !== $currentUserId): ?>
                                    <form method="post" action="<?= $baseUrl ?>/admin/toggle-actif/<?= (int)$u['id'] ?>" style="display:inline;" <?= $u['actif'] ? 'onsubmit="return confirm(\'Désactiver ce compte ?\')"' : '' ?>>
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn btn-sm <?= $u['actif'] ? 'btn-outline admin-btn-desactiver' : 'btn-outline admin-btn-activer' ?>" title="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?>">
                                            <?php if ($u['actif']): ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                                Désactiver
                                            <?php else: ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                Activer
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?= $baseUrl ?>/admin/delete-user/<?= (int)$u['id'] ?>" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.');">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn btn-sm btn-outline admin-users-btn-delete" title="Supprimer l\'utilisateur">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="admin-users-you">Vous</span>
                                <?php endif; ?>
                                <?php if (in_array($u['role'] ?? '', ['client', 'expert', 'professeur', 'etudiant'], true)): ?>
                                <form method="post" action="<?= $baseUrl ?>/admin/activer-abonnement/<?= (int)$u['id'] ?>" style="display:inline;" onsubmit="return confirm('Activer un abonnement 30 jours pour cet utilisateur (paiement reçu manuellement) ?');">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="type" value="<?= $e($u['role']) ?>">
                                    <input type="hidden" name="duree_jours" value="30">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Activer abonnement 30j (paiement reçu manuellement)" style="color:var(--color-primary,#16a34a);border-color:var(--color-primary,#16a34a);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/></svg>
                                        Abo +30j
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="admin-table-empty">
                            Aucun utilisateur<?= $roleFilter ? ' pour ce filtre' : '' ?>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-table-footer">
            <span class="admin-table-count"><?= count($users) ?> utilisateur(s)</span>
        </div>
    </div>
</div>
<script>
(function() {
    var search = document.getElementById('admin-users-search');
    var table = document.getElementById('admin-users-table');
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
