<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$csrfField    = \App\Core\Security::getCsrfField();
$u            = $userToEdit ?? [];
$errors       = $errors ?? [];
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$userId       = (int)($u['id'] ?? 0);
$isSelf       = (bool) ($isSelf ?? false);
$rolesValides = $rolesValides ?? ['client', 'expert', 'etudiant', 'professeur', 'admin'];
$currentRole  = $u['role'] ?? 'client';
$roleLabels   = [
    'client'     => ['emoji' => '💼', 'label' => 'Client',      'desc' => 'Cherche de l\'aide pour ses tâches'],
    'expert'     => ['emoji' => '🎯', 'label' => 'Expert',      'desc' => 'Propose ses services et compétences'],
    'etudiant'   => ['emoji' => '🎓', 'label' => 'Étudiant',    'desc' => 'Demande de l\'aide pour ses exercices'],
    'professeur' => ['emoji' => '👨‍🏫', 'label' => 'Professeur', 'desc' => 'Corrige et aide les étudiants'],
    'admin'      => ['emoji' => '⚙️', 'label' => 'Admin',       'desc' => 'Accès complet au back-office'],
];
$avatarUrl = $userId && !empty($u['avatar']) ? $baseUrl . '/fichier/user-avatar/' . $userId : '';
if ($avatarUrl === '') {
    $avatarUrl = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%2364748b" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 14a3 3 0 0 0 3-3 3 3 0 0 0-6 0 3 3 0 0 0 3 3z"/><path d="M8 20v-1a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4v1"/></svg>');
}
$hasAvatar = $userId && !empty($u['avatar']);
$pieceUrl = $userId && !empty($u['piece_identite']) ? $baseUrl . '/fichier/user-piece/' . $userId : null;
$hasPiece = $userId && !empty($u['piece_identite']);
?>
<div class="page-admin page-admin-edit-user">
    <header class="admin-edit-user-hero">
        <a href="<?= $baseUrl ?>/admin/users" class="admin-back-link" aria-label="Retour à la liste des utilisateurs">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Utilisateurs
        </a>
        <div class="admin-edit-user-hero-content">
            <div class="admin-edit-user-hero-avatar">
                <img src="<?= $e($avatarUrl) ?><?= (strpos($avatarUrl, 'data:') === 0 ? '' : '?t=' . time()) ?>" alt="Photo de profil" width="64" height="64">
            </div>
            <div class="admin-edit-user-hero-text">
                <h1>Modifier l'utilisateur</h1>
                <p class="admin-edit-user-hero-subtitle"><?= $e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?> — <?= $e($u['email'] ?? '') ?></p>
                <p class="admin-edit-user-hero-role">
                    Type :
                    <strong><?= $e($roleLabels[$currentRole]['emoji'] ?? '') ?> <?= $e($roleLabels[$currentRole]['label'] ?? ucfirst($currentRole)) ?></strong>
                </p>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <?php
        $flashMsg  = $_SESSION['flash_success'];
        $isRoleMsg = strpos($flashMsg, '→') !== false;
        $isWarn    = $isRoleMsg && strpos($flashMsg, 'Attention') !== false;
        $flashStyle = $isWarn
            ? 'background:#fffbeb;border:1.5px solid #fcd34d;color:#92400e;'
            : 'background:#f0fdf4;border:1.5px solid #86efac;color:#166534;';
        $flashIcon = $isWarn
            ? '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'
            : '<polyline points="20 6 9 17 4 12"/>';
    ?>
    <div role="status" style="display:flex;align-items:center;gap:.75rem;border-radius:12px;padding:.9rem 1.1rem;margin-bottom:1rem;font-size:.9rem;font-weight:500;<?= $flashStyle ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><?= $flashIcon ?></svg>
        <?= $e($flashMsg) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="admin-edit-user-alert alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
            <li><?= $e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" class="admin-edit-user-form" action="<?= $baseUrl ?>/admin/edit-user/<?= $userId ?>" enctype="multipart/form-data">
        <?= $csrfField ?>

        <div class="admin-edit-user-blocks">
            <section class="admin-edit-user-block admin-edit-user-block--avatar">
                <div class="admin-edit-user-block-header">
                    <span class="admin-edit-user-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="8" r="3"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                    </span>
                    <h2>Photo de profil</h2>
                </div>
                <div class="admin-edit-user-block-body">
                    <div class="admin-edit-user-avatar-preview">
                        <div class="admin-edit-user-avatar-box">
                            <img id="avatar-preview-img" src="<?= $e($avatarUrl) ?><?= (strpos($avatarUrl, 'data:') === 0 ? '' : '?t=' . time()) ?>" alt="Aperçu" width="120" height="120">
                        </div>
                        <div class="admin-edit-user-avatar-upload">
                            <div class="form-group">
                                <label for="avatar">Nouvelle photo</label>
                                <input type="file" name="avatar" id="avatar" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" class="admin-edit-user-file">
                                <small class="form-hint">PNG, JPG, GIF ou WebP. Max 5 Mo.</small>
                            </div>
                            <?php if ($hasAvatar): ?>
                            <div class="form-group">
                                <label class="admin-edit-user-checkbox">
                                    <input type="checkbox" name="avatar_supprimer" value="1">
                                    <span>Supprimer la photo actuelle</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-edit-user-block admin-edit-user-block--piece">
                <div class="admin-edit-user-block-header">
                    <span class="admin-edit-user-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </span>
                    <h2>Pièce d'identité</h2>
                </div>
                <div class="admin-edit-user-block-body">
                    <p class="admin-edit-user-desc">Copie de la pièce d'identité (carte d'identité, passeport) pour renforcer la sécurité du système.</p>
                    <?php if ($hasPiece): ?>
                    <div class="admin-edit-user-piece-current">
                        <a href="<?= $e($pieceUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Voir la pièce actuelle</a>
                        <label class="admin-edit-user-checkbox">
                            <input type="checkbox" name="piece_identite_supprimer" value="1">
                            <span>Supprimer le document</span>
                        </label>
                    </div>
                    <?php else: ?>
                    <p class="admin-edit-user-no-doc">Aucun document déposé.</p>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="piece_identite"><?= $hasPiece ? 'Remplacer par un nouveau fichier' : 'Déposer un fichier' ?></label>
                        <input type="file" name="piece_identite" id="piece_identite" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,application/pdf" class="admin-edit-user-file">
                        <small class="form-hint">Image (PNG, JPG, GIF, WebP) ou PDF. Max 5 Mo.</small>
                    </div>
                    <?php if ($hasPiece && array_key_exists('piece_identite_verifie', $u)): ?>
                    <div class="form-group admin-edit-user-verif-piece">
                        <p class="admin-edit-user-desc"><strong>Vérification :</strong> pièce lisible, photo et nom/prénom reconnus.</p>
                        <label class="admin-edit-user-checkbox">
                            <input type="checkbox" name="piece_identite_verifie" value="1" <?= !empty($u['piece_identite_verifie']) ? 'checked' : '' ?>>
                            <span>Marquer la pièce comme vérifiée</span>
                        </label>
                        <div class="form-group">
                            <label for="piece_identite_rejet_raison">Raison du rejet (si non conforme)</label>
                            <input type="text" name="piece_identite_rejet_raison" id="piece_identite_rejet_raison" value="<?= $e($u['piece_identite_rejet_raison'] ?? '') ?>" maxlength="500" placeholder="Ex. photo illisible, nom non reconnu…">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-edit-user-block admin-edit-user-block--identite">
                <div class="admin-edit-user-block-header">
                    <span class="admin-edit-user-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <h2>Identité & connexion</h2>
                </div>
                <div class="admin-edit-user-block-body">
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" name="email" id="email" value="<?= $e($u['email'] ?? '') ?>" required maxlength="255" placeholder="utilisateur@exemple.com">
                        <small class="form-hint">Identifiant de connexion.</small>
                    </div>
                    <div class="form-group form-row">
                        <div class="form-group-inline">
                            <label for="prenom">Prénom</label>
                            <input type="text" name="prenom" id="prenom" value="<?= $e($u['prenom'] ?? '') ?>" required maxlength="100">
                        </div>
                        <div class="form-group-inline">
                            <label for="nom">Nom</label>
                            <input type="text" name="nom" id="nom" value="<?= $e($u['nom'] ?? '') ?>" required maxlength="100">
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-edit-user-block admin-edit-user-block--role" id="admin-role-block">
                <div class="admin-edit-user-block-header">
                    <span class="admin-edit-user-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </span>
                    <h2>Type de compte</h2>
                </div>
                <div class="admin-edit-user-block-body">
                    <?php if ($isSelf): ?>
                    <p class="admin-role-self-warn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Vous ne pouvez pas modifier le type de votre propre compte administrateur.
                    </p>
                    <input type="hidden" name="role" value="<?= $e($currentRole) ?>">
                    <?php else: ?>
                    <div class="admin-role-grid" role="group" aria-label="Type de compte">
                        <?php foreach ($rolesValides as $r):
                            $rl = $roleLabels[$r] ?? ['emoji' => '👤', 'label' => ucfirst($r), 'desc' => ''];
                            $isSelected = ($currentRole === $r);
                        ?>
                        <label class="admin-role-card <?= $isSelected ? 'admin-role-card--selected' : '' ?>" id="admin-role-card-<?= $r ?>">
                            <input type="radio" name="role" value="<?= $e($r) ?>"
                                   <?= $isSelected ? 'checked' : '' ?>
                                   class="admin-role-radio"
                                   data-role="<?= $e($r) ?>">
                            <span class="admin-role-emoji"><?= $rl['emoji'] ?></span>
                            <span class="admin-role-label"><?= $e($rl['label']) ?></span>
                            <span class="admin-role-desc"><?= $e($rl['desc']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-role-warn" id="admin-role-warn" style="display:none">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span id="admin-role-warn-text">
                            Changement de <strong id="admin-role-old"></strong> vers <strong id="admin-role-new"></strong>.
                            Les profils associés au nouveau type seront créés automatiquement si nécessaire.
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-edit-user-block admin-edit-user-block--password">
                <div class="admin-edit-user-block-header">
                    <span class="admin-edit-user-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <h2>Mot de passe</h2>
                </div>
                <div class="admin-edit-user-block-body">
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="new_password" minlength="<?= (int) PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" placeholder="Laisser vide pour ne pas modifier">
                        <small class="form-hint">Minimum <?= (int) PASSWORD_MIN_LENGTH ?> caractères.</small>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirm">Confirmer le mot de passe</label>
                        <input type="password" name="new_password_confirm" id="new_password_confirm" minlength="<?= (int) PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" placeholder="Identique au nouveau mot de passe">
                    </div>
                </div>
            </section>
        </div>

        <div class="admin-edit-user-actions">
            <button type="submit" class="btn btn-primary btn-lg admin-edit-user-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer les modifications
            </button>
            <a href="<?= $baseUrl ?>/admin/users" class="btn btn-outline btn-lg">Annuler</a>
        </div>
    </form>
</div>
<style>
/* ── Grille rôles ── */
.admin-role-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .6rem;
    margin-bottom: 1rem;
}
.admin-role-card {
    display: flex; flex-direction: column; align-items: center; text-align: center;
    padding: .85rem .6rem; gap: .3rem;
    border: 2px solid #e2e8f0; border-radius: 12px;
    cursor: pointer; background: #f8fafc;
    transition: border-color .15s, background .15s;
}
.admin-role-card:hover { border-color: #94a3b8; background: #f1f5f9; }
.admin-role-card--selected { border-color: #2563eb; background: #eff6ff; }
.admin-role-radio { display: none; }
.admin-role-emoji { font-size: 1.5rem; }
.admin-role-label { font-size: .8rem; font-weight: 700; color: #1e293b; }
.admin-role-desc  { font-size: .68rem; color: #64748b; line-height: 1.3; }

/* ── Avertissement changement ── */
.admin-role-warn {
    display: flex; align-items: flex-start; gap: .6rem;
    background: #fffbeb; border: 1px solid #fcd34d;
    color: #92400e; border-radius: 10px;
    padding: .8rem 1rem; font-size: .85rem; line-height: 1.5;
    margin-top: .25rem;
}
/* ── Message compte propre ── */
.admin-role-self-warn {
    display: flex; align-items: center; gap: .5rem;
    background: #f0f9ff; border: 1px solid #bae6fd;
    color: #0c4a6e; border-radius: 10px;
    padding: .75rem 1rem; font-size: .85rem;
}
</style>

<script>
(function() {
    /* ── Avatar preview ── */
    var avatarInput = document.getElementById('avatar');
    var previewImg = document.getElementById('avatar-preview-img');
    if (avatarInput && previewImg) {
        avatarInput.addEventListener('change', function() {
            var file = this.files && this.files[0];
            if (file && file.type.indexOf('image/') === 0) {
                var r = new FileReader();
                r.onload = function() { previewImg.src = r.result; };
                r.readAsDataURL(file);
            }
        });
    }

    /* ── Sélection de rôle ── */
    var radios  = document.querySelectorAll('.admin-role-radio');
    var warn    = document.getElementById('admin-role-warn');
    var warnOld = document.getElementById('admin-role-old');
    var warnNew = document.getElementById('admin-role-new');
    var roleLabels = {
        client: 'Client', expert: 'Expert', etudiant: 'Étudiant',
        professeur: 'Professeur', admin: 'Admin'
    };
    var originalRole = (function() {
        var checked = document.querySelector('.admin-role-radio:checked');
        return checked ? checked.value : '';
    })();

    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            /* Mise à jour visuelle des cartes */
            document.querySelectorAll('.admin-role-card').forEach(function(c) {
                c.classList.remove('admin-role-card--selected');
            });
            var lbl = radio.closest('.admin-role-card');
            if (lbl) lbl.classList.add('admin-role-card--selected');

            /* Avertissement si changement */
            var selectedRole = radio.value;
            if (warn) {
                if (selectedRole !== originalRole) {
                    if (warnOld) warnOld.textContent = roleLabels[originalRole] || originalRole;
                    if (warnNew) warnNew.textContent = roleLabels[selectedRole] || selectedRole;
                    warn.style.display = 'flex';
                } else {
                    warn.style.display = 'none';
                }
            }
        });
    });
})();
</script>

<!-- ── Bloc envoi email individuel ─────────────────────────────── -->
<div class="admin-table-card" style="margin-top:1.5rem;padding:1.75rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="margin:0 0 .25rem;font-size:.95rem;font-weight:700;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:.4rem;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Envoyer un email à cet utilisateur
            </h2>
            <p style="margin:0;font-size:.82rem;color:#64748b;">L'email sera formaté avec le template GLOBALO et une notification interne sera créée.</p>
        </div>
        <a href="<?= $baseUrl ?>/admin/send-mail-user/<?= $userId ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:.45rem;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Envoyer un email
        </a>
    </div>
</div>
