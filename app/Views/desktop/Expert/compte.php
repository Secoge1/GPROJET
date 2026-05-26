<?php
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$csrfField       = \App\Core\Security::getCsrfField();
$u               = $userToEdit ?? [];
$errors          = $errors ?? [];
$e               = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$userId          = (int)($u['id'] ?? 0);
$hasAvatar       = $userId && !empty($u['avatar']);
$avatarUrl       = $hasAvatar
    ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time()
    : null;
$hasPiece        = $userId && !empty($u['piece_identite']);
$pieceUrl        = $hasPiece ? $baseUrl . '/fichier/user-piece/' . $userId : null;
$compteBackUrl   = $compteBackUrl  ?? $baseUrl . '/expert';
$compteFormAction= $compteFormAction ?? $baseUrl . '/expert/compte';
$nomComplet      = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
?>
<section class="section-desktop page-compte-redesign">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $e($compteBackUrl) ?>" class="page-expert__back" aria-label="Retour">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Retour
            </a>
            <div class="missions-header__title-wrap">
                <!-- Avatar en en-tête -->
                <div class="compte-hdr-avatar" aria-hidden="true">
                    <?php if ($avatarUrl): ?>
                    <img src="<?= $e($avatarUrl) ?>" alt="Photo de profil" width="48" height="48">
                    <?php else: ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="missions-header__title">Mon compte</h1>
                    <p class="missions-header__sub"><?= $e($nomComplet) ?> · <?= $e($u['email'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="profil-alert" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $e($compteFormAction) ?>" enctype="multipart/form-data" class="compte-form-redesign">
        <?= $csrfField ?>

        <!-- Section Photo de profil -->
        <div class="profil-section">
            <div class="profil-section__header">
                <div class="profil-section__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <div>
                    <h2 class="profil-section__title">Photo de profil</h2>
                    <p class="profil-section__sub">Visible par les clients sur votre profil public.</p>
                </div>
            </div>

            <div class="compte-avatar-layout">
                <!-- Prévisualisation -->
                <div class="compte-avatar-preview-wrap">
                    <div class="compte-avatar-preview-ring">
                        <?php if ($avatarUrl): ?>
                        <img id="avatar-preview-img" src="<?= $e($avatarUrl) ?>" alt="Photo actuelle" class="compte-avatar-preview-img">
                        <?php else: ?>
                        <div id="avatar-preview-placeholder" class="compte-avatar-preview-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <img id="avatar-preview-img" src="" alt="" class="compte-avatar-preview-img" style="display:none;">
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="compte-avatar-preview-hint">Aperçu</p>
                </div>

                <!-- Upload -->
                <div class="compte-avatar-upload-area">
                    <label for="avatar" class="compte-upload-drop" id="avatar-drop-zone">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span class="compte-upload-drop__text">
                            <strong>Cliquez ou glissez une image</strong>
                            <span>PNG, JPG, GIF ou WebP · Max 5 Mo</span>
                        </span>
                        <input type="file" name="avatar" id="avatar" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" class="compte-upload-input">
                    </label>
                    <?php if ($hasAvatar): ?>
                    <label class="compte-delete-check">
                        <input type="checkbox" name="avatar_supprimer" value="1">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            Supprimer la photo actuelle
                        </span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section Pièce d'identité -->
        <div class="profil-section">
            <div class="profil-section__header">
                <div class="profil-section__icon profil-section__icon--purple" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <div>
                    <h2 class="profil-section__title">Pièce d'identité</h2>
                    <p class="profil-section__sub">Carte d'identité ou passeport pour vérification. Non visible par les clients.</p>
                </div>
            </div>

            <?php if ($hasPiece): ?>
            <div class="compte-piece-status">
                <div class="compte-piece-status__badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Document déposé
                </div>
                <div class="compte-piece-status__actions">
                    <a href="<?= $e($pieceUrl) ?>" target="_blank" rel="noopener" class="compte-piece-status__view">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Voir le document
                    </a>
                    <label class="compte-delete-check">
                        <input type="checkbox" name="piece_identite_supprimer" value="1">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            Supprimer le document
                        </span>
                    </label>
                </div>
            </div>
            <?php else: ?>
            <div class="compte-piece-empty">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <span>Aucun document déposé</span>
            </div>
            <?php endif; ?>

            <label for="piece_identite" class="compte-upload-drop" id="piece-drop-zone" style="margin-top:1rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span class="compte-upload-drop__text">
                    <strong><?= $hasPiece ? 'Remplacer par un nouveau fichier' : 'Déposer un fichier' ?></strong>
                    <span>Image (PNG, JPG, WebP) ou PDF · Max 5 Mo</span>
                </span>
                <input type="file" name="piece_identite" id="piece_identite" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,application/pdf" class="compte-upload-input">
            </label>
        </div>

        <!-- Actions -->
        <div class="profil-form__footer">
            <a href="<?= $e($compteBackUrl) ?>" class="btn btn-outline profil-form__cancel">Annuler</a>
            <button type="submit" class="btn btn-primary profil-form__submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer
            </button>
        </div>
    </form>

</section>

<script>
(function() {
    /* Prévisualisation avatar */
    var input   = document.getElementById('avatar');
    var preview = document.getElementById('avatar-preview-img');
    var placeholder = document.getElementById('avatar-preview-placeholder');
    if (input && preview) {
        input.addEventListener('change', function() {
            var f = this.files && this.files[0];
            if (f && f.type.indexOf('image/') === 0) {
                var r = new FileReader();
                r.onload = function() {
                    preview.src = r.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                r.readAsDataURL(f);
            }
        });
    }

    /* Drag-over sur les zones de dépôt */
    ['avatar-drop-zone', 'piece-drop-zone'].forEach(function(id) {
        var zone = document.getElementById(id);
        if (!zone) return;
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('is-over'); });
        zone.addEventListener('dragleave', function()  { zone.classList.remove('is-over'); });
        zone.addEventListener('drop',     function(e) {
            e.preventDefault();
            zone.classList.remove('is-over');
            var fileInput = zone.querySelector('input[type="file"]');
            if (fileInput && e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    });
})();
</script>
