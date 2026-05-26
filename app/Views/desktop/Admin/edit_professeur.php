<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$p = $prof ?? [];
$matieresGrouped = $matieresGrouped ?? [];
$matiereIdsSelected = array_flip(array_map('intval', $matiereIdsSelected ?? []));
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$pid = (int)($p['id'] ?? 0);
$devise = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$note = isset($p['note_moyenne']) && $p['note_moyenne'] !== null && $p['note_moyenne'] !== ''
    ? number_format((float)$p['note_moyenne'], 1, ',', ' ')
    : '—';
$nbAvis = (int)($p['nombre_avis'] ?? 0);
?>
<div class="page-admin page-admin-experts">
    <header class="admin-experts-hero">
        <a href="<?= $baseUrl ?>/admin/professeurs" class="admin-back-link" aria-label="Retour à la liste des professeurs">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Professeurs
        </a>
        <div class="admin-experts-hero-content">
            <div class="admin-experts-hero-icon" aria-hidden="true" style="background:#ede9fe;color:#7c3aed">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div class="admin-experts-hero-text">
                <h1>Gérer le professeur</h1>
                <p class="admin-experts-hero-subtitle">
                    <?= $e(trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''))) ?> — <?= $e($p['email'] ?? '') ?>
                    · Compte <?= !empty($p['user_actif']) ? 'actif' : 'inactif' ?>
                </p>
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

    <div class="admin-table-card admin-experts-table-card" style="margin-bottom:1.25rem">
        <div class="admin-table-card-header">
            <h2>Raccourcis</h2>
        </div>
        <div style="padding:1rem 1.25rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">
            <?php if (!empty($p['valide_par_admin'])): ?>
            <a href="<?= $baseUrl ?>/professeurs/show/<?= $pid ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Voir la fiche publique</a>
            <?php else: ?>
            <span class="admin-experts-badge admin-experts-badge--attente" title="Validez le profil pour activer le lien public">Fiche publique après validation</span>
            <?php endif; ?>
            <?php if (!empty($p['utilisateur_id'])): ?>
            <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$p['utilisateur_id'] ?>" class="btn btn-outline btn-sm">Compte utilisateur (email, rôle…)</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" action="<?= $baseUrl ?>/admin/edit-professeur/<?= $pid ?>" class="admin-edit-professeur-form">
        <?= $csrfField ?>

        <div class="admin-table-card admin-experts-table-card" style="margin-bottom:1.25rem">
            <div class="admin-table-card-header">
                <h2>Statuts &amp; visibilité</h2>
            </div>
            <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:1rem">
                <label class="admin-edit-user-checkbox" style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer">
                    <input type="checkbox" name="valide_par_admin" value="1" <?= !empty($p['valide_par_admin']) ? 'checked' : '' ?>>
                    <span>
                        <strong>Validé par l’admin</strong><br>
                        <small style="color:#64748b">Visible dans la liste publique des professeurs et réservable (si disponible).</small>
                    </span>
                </label>
                <label class="admin-edit-user-checkbox" style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer">
                    <input type="checkbox" name="disponible" value="1" <?= !empty($p['disponible']) ? 'checked' : '' ?>>
                    <span>
                        <strong>Disponible pour les réservations</strong><br>
                        <small style="color:#64748b">Les étudiants peuvent envoyer une demande de session.</small>
                    </span>
                </label>
                <p style="margin:0;font-size:.8125rem;color:#64748b">
                    Réputation (lecture seule) : note <?= $e($note) ?> / 5 · <?= $nbAvis ?> avis
                </p>
            </div>
        </div>

        <div class="admin-table-card admin-experts-table-card" style="margin-bottom:1.25rem">
            <div class="admin-table-card-header">
                <h2>Texte &amp; tarif</h2>
            </div>
            <div style="padding:1rem 1.25rem;display:grid;gap:1rem;max-width:640px">
                <div class="form-group" style="margin:0">
                    <label for="prof_titre">Titre affiché <span style="color:#dc2626">*</span></label>
                    <input type="text" id="prof_titre" name="titre" required maxlength="150" class="form-control"
                           value="<?= $e($p['titre'] ?? '') ?>" placeholder="Ex. Cours de mathématiques — L2">
                </div>
                <div class="form-group" style="margin:0">
                    <label for="prof_desc">Description</label>
                    <textarea id="prof_desc" name="description" rows="6" class="form-control" placeholder="Parcours, méthode, public visé…"><?= $e($p['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label for="prof_tarif">Tarif horaire (<?= $e($devise) ?>)</label>
                    <input type="text" id="prof_tarif" name="tarif_horaire" inputmode="decimal" class="form-control" style="max-width:200px"
                           value="<?= $e((string)($p['tarif_horaire'] ?? '0')) ?>">
                </div>
            </div>
        </div>

        <div class="admin-table-card admin-experts-table-card" style="margin-bottom:1.25rem">
            <div class="admin-table-card-header">
                <h2>Matières enseignées</h2>
            </div>
            <div style="padding:1rem 1.25rem">
                <p style="margin:0 0 1rem;font-size:.875rem;color:#64748b">
                    Cochez les matières pour le filtre public et la fiche professeur. Table SQL : <code>professeur_matieres</code>.
                </p>
                <?php if (empty($matieresGrouped)): ?>
                <p class="admin-table-empty">Aucune matière active en base.</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:1.25rem;max-height:420px;overflow-y:auto;padding-right:.25rem">
                    <?php foreach ($matieresGrouped as $categorie => $liste): ?>
                    <fieldset style="border:1px solid #e2e8f0;border-radius:10px;padding:.75rem 1rem;margin:0">
                        <legend style="font-size:.8rem;font-weight:700;color:#475569;padding:0 .35rem"><?= $e($categorie) ?></legend>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.5rem;margin-top:.5rem">
                            <?php foreach ($liste as $m):
                                $mid = (int)($m['id'] ?? 0);
                            ?>
                            <label class="admin-edit-user-checkbox" style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.875rem">
                                <input type="checkbox" name="matieres[]" value="<?= $mid ?>" <?= isset($matiereIdsSelected[$mid]) ? 'checked' : '' ?>>
                                <?= $e($m['nom'] ?? '') ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.75rem;padding:0 0 2rem">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="<?= $baseUrl ?>/admin/professeurs" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
