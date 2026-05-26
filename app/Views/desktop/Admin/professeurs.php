<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$professeurs = $professeurs ?? [];
$statutFilter = $statutFilter ?? null;
$dispoFilter = $dispoFilter ?? null;
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();
$devise = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$qStatut = function (?string $s) use ($dispoFilter) {
    $q = [];
    if ($s !== null && $s !== '') {
        $q['statut'] = $s;
    }
    if ($dispoFilter !== null && $dispoFilter !== '') {
        $q['disponible'] = $dispoFilter;
    }
    return empty($q) ? '' : ('?' . http_build_query($q));
};
$qDispo = function (?string $d) use ($statutFilter) {
    $q = [];
    if ($statutFilter !== null && $statutFilter !== '') {
        $q['statut'] = $statutFilter;
    }
    if ($d !== null && $d !== '') {
        $q['disponible'] = $d;
    }
    return empty($q) ? '' : ('?' . http_build_query($q));
};
?>
<div class="page-admin page-admin-experts">
    <header class="admin-experts-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-experts-hero-content">
            <div class="admin-experts-hero-icon" aria-hidden="true" style="background:#ede9fe;color:#7c3aed">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div class="admin-experts-hero-text">
                <h1>Professeurs</h1>
                <p class="admin-experts-hero-subtitle">Validation, disponibilité, tarif, matières et texte du profil public</p>
            </div>
            <div class="admin-experts-filters">
                <span class="admin-experts-filters-label">Validation</span>
                <div class="admin-experts-pills">
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qStatut(null)) ?>" class="admin-experts-pill <?= $statutFilter === null ? 'active' : '' ?>">Tous</a>
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qStatut('non_valide')) ?>" class="admin-experts-pill <?= $statutFilter === 'non_valide' ? 'active' : '' ?>">À valider</a>
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qStatut('valide')) ?>" class="admin-experts-pill <?= $statutFilter === 'valide' ? 'active' : '' ?>">Validés</a>
                </div>
            </div>
            <div class="admin-experts-filters" style="margin-top:.75rem">
                <span class="admin-experts-filters-label">Disponibilité</span>
                <div class="admin-experts-pills">
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qDispo(null)) ?>" class="admin-experts-pill <?= $dispoFilter === null ? 'active' : '' ?>">Tous</a>
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qDispo('1')) ?>" class="admin-experts-pill <?= $dispoFilter === '1' ? 'active' : '' ?>">Disponibles</a>
                    <a href="<?= $baseUrl ?>/admin/professeurs<?= $e($qDispo('0')) ?>" class="admin-experts-pill <?= $dispoFilter === '0' ? 'active' : '' ?>">Indisponibles</a>
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
            <h2>Liste des profils</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table admin-experts-table" id="admin-professeurs-table">
                <thead>
                    <tr>
                        <th>Profil / Titre</th>
                        <th>Professeur</th>
                        <th>Email</th>
                        <th class="admin-experts-table-tarif">Tarif</th>
                        <th>Matières</th>
                        <th class="admin-experts-table-statut">Dispo.</th>
                        <th class="admin-experts-table-statut">Valid.</th>
                        <th class="admin-table-col-action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($professeurs as $p): ?>
                    <tr>
                        <td><strong><?= $e($p['titre'] ?? '—') ?></strong></td>
                        <td><?= $e(trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''))) ?></td>
                        <td><a href="mailto:<?= $e($p['email'] ?? '') ?>"><?= $e($p['email'] ?? '') ?></a></td>
                        <td class="admin-experts-table-tarif"><?= number_format((float)($p['tarif_horaire'] ?? 0), 0, ',', ' ') ?> <?= $e($devise ?? 'XOF') ?>/h</td>
                        <td>
                            <?php $nbm = (int)($p['nb_matieres'] ?? 0); ?>
                            <?php if ($nbm > 0): ?>
                            <span class="admin-experts-badge admin-experts-badge--valide"><?= $nbm ?> matière<?= $nbm > 1 ? 's' : '' ?></span>
                            <?php else: ?>
                            <span class="admin-experts-badge admin-experts-badge--attente" title="À renseigner dans « Gérer »">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-experts-table-statut">
                            <?php if (!empty($p['disponible'])): ?>
                            <span class="admin-experts-badge admin-experts-badge--valide">Oui</span>
                            <?php else: ?>
                            <span class="admin-experts-badge admin-experts-badge--attente">Non</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-experts-table-statut">
                            <?php if (!empty($p['valide_par_admin'])): ?>
                            <span class="admin-experts-badge admin-experts-badge--valide">Oui</span>
                            <?php else: ?>
                            <span class="admin-experts-badge admin-experts-badge--attente">Non</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table-col-action">
                            <a href="<?= $baseUrl ?>/admin/edit-professeur/<?= (int)($p['id'] ?? 0) ?>" class="btn btn-primary btn-sm" title="Toutes les options du profil">Gérer</a>
                            <form method="post" action="<?= $baseUrl ?>/admin/toggle-disponibilite-professeur/<?= (int)($p['id'] ?? 0) ?>" style="display:inline;">
                                <?= $csrfField ?>
                                <button type="submit" class="btn btn-outline btn-sm" title="Basculer disponible / indisponible">
                                    <?= !empty($p['disponible']) ? 'Indispo.' : 'Dispo.' ?>
                                </button>
                            </form>
                            <?php if (!empty($p['valide_par_admin'])): ?>
                            <a href="<?= $baseUrl ?>/professeurs/show/<?= (int)($p['id'] ?? 0) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener" title="Fiche publique">Public</a>
                            <?php endif; ?>
                            <?php if (!empty($p['utilisateur_id'])): ?>
                            <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$p['utilisateur_id'] ?>" class="btn btn-outline btn-sm" title="Compte utilisateur">Compte</a>
                            <?php endif; ?>
                            <?php if (empty($p['valide_par_admin'])): ?>
                            <form method="post" action="<?= $baseUrl ?>/admin/valider-professeur/<?= (int)($p['id'] ?? 0) ?>" style="display:inline;">
                                <?= $csrfField ?>
                                <button type="submit" class="btn btn-outline btn-sm" style="border-color:#86efac;color:#15803d" title="Valider ce profil">Valider</button>
                            </form>
                            <?php else: ?>
                            <form method="post" action="<?= $baseUrl ?>/admin/invalider-professeur/<?= (int)($p['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Retirer la validation ? Le profil ne sera plus public et passera en indisponible.');">
                                <?= $csrfField ?>
                                <button type="submit" class="btn btn-outline btn-sm" title="Retirer la validation">Invalider</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($professeurs)): ?>
                    <tr>
                        <td colspan="8" class="admin-table-empty">Aucun profil professeur<?= ($statutFilter || $dispoFilter !== null) ? ' pour ces filtres' : '' ?>.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-table-footer">
            <span class="admin-table-count"><?= count($professeurs) ?> professeur(s)</span>
        </div>
    </div>
</div>

<?php
$exercicesOrphelins = $exercicesOrphelins ?? [];
$typeLabel = ['devoir'=>'Devoir','examen'=>'Examen','tp'=>'TP','projet'=>'Projet',
              'dissertation'=>'Dissertation','qcm'=>'QCM','oral'=>'Oral','autre'=>'Autre'];
?>
<?php if (!empty($exercicesOrphelins)): ?>
<div class="admin-section" style="margin-top:2rem">
    <div class="admin-section__header" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:.75rem">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <strong style="color:#dc2626">Exercices bloqués (<?= count($exercicesOrphelins) ?>)</strong>
            <p style="margin:.15rem 0 0;font-size:.8rem;color:#b91c1c">Ces exercices sont en statut « En cours » depuis plus de 7 jours ou sans professeur valide assigné. Les nouveaux professeurs ne peuvent pas les voir. Vous pouvez les remettre en état « ouvert ».</p>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="table-desktop admin-table" style="font-size:.85rem">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Étudiant</th>
                    <th>Bloqué depuis</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exercicesOrphelins as $ex): ?>
                <tr>
                    <td><?= (int)$ex['id'] ?></td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e($ex['titre'] ?? '') ?></td>
                    <td><span style="background:#f1f5f9;padding:.15rem .55rem;border-radius:20px;font-size:.75rem"><?= $e($typeLabel[$ex['type_exercice'] ?? ''] ?? ($ex['type_exercice'] ?? '')) ?></span></td>
                    <td><?= $e(($ex['etudiant_prenom'] ?? '') . ' ' . ($ex['etudiant_nom'] ?? '')) ?></td>
                    <td style="color:#b91c1c;font-size:.8rem"><?= $e($ex['updated_at'] ?? '') ?></td>
                    <td>
                        <form method="POST" action="<?= $baseUrl ?>/admin/reset-exercice/<?= (int)$ex['id'] ?>"
                              onsubmit="return confirm('Remettre l\'exercice #<?= (int)$ex['id'] ?> en état ouvert ?')">
                            <?= $csrfField ?>
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--warning" style="font-size:.78rem;padding:.3rem .75rem">
                                ↺ Remettre ouvert
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
