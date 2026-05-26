<?php
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$csrfField        = \App\Core\Security::getCsrfField();
$u                = $userToEdit ?? [];
$errors           = $errors ?? [];
$e                = fn($s) => \App\Core\Security::escape($s ?? '');
$userId           = (int)($u['id'] ?? 0);
$hasAvatar        = $userId && !empty($u['avatar']);
$avatarUrl        = $userId ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time() : null;
$hasPiece         = $userId && !empty($u['piece_identite']);
$pieceUrl         = $hasPiece ? $baseUrl . '/fichier/user-piece/' . $userId : null;
$compteBackUrl    = $compteBackUrl    ?? $baseUrl . '/client';
$compteFormAction = $compteFormAction ?? $baseUrl . '/client/compte';
$nomComplet       = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero cl-page__hero--narrow">
        <div class="cl-page__hero-left">
            <a href="<?= $e($compteBackUrl) ?>" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div style="display:flex;align-items:center;gap:1rem;margin-top:.5rem">
                <!-- Avatar mini dans l'en-tête -->
                <div class="cl-compte-hdr-avatar">
                    <svg id="avatar-mini-placeholder" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php if ($avatarUrl): ?>
                    <img id="avatar-mini-img" src="<?= $e($avatarUrl) ?>" alt="Photo de profil" width="52" height="52"
                         onload="this.style.display='block';var p=this.parentElement.querySelector('#avatar-mini-placeholder'); if(p) p.style.display='none';"
                         onerror="var p=this.parentElement.querySelector('#avatar-mini-placeholder'); if(p) p.style.display='block';"
                         style="display:none;border-radius:50%;">
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="cl-page__title" style="margin:0">Mon compte</h1>
                    <?php if ($nomComplet || !empty($u['email'])): ?>
                    <p class="cl-page__sub" style="margin:.1rem 0 0">
                        <?= $e($nomComplet) ?><?= $nomComplet && !empty($u['email']) ? ' · ' : '' ?><?= $e($u['email'] ?? '') ?>
                        <?php if (($u['auth_provider'] ?? 'email') === 'google'): ?>
                        <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;font-weight:600;padding:.15rem .55rem;border-radius:999px;background:#fef3c7;color:#92400e;margin-left:.4rem;vertical-align:middle">
                            <svg width="10" height="10" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                            Compte Google
                        </span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash succès -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="cl-alert cl-alert--success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
        <span><?= $e($_SESSION['flash_success']) ?></span>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $e($compteFormAction) ?>" enctype="multipart/form-data" class="cl-form" style="max-width:700px">
        <?= $csrfField ?>

        <!-- Section Photo -->
        <div class="cl-card cl-form__section">
            <div class="cl-form__section-head">
                <div class="cl-form__section-num">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <div>
                    <h2 class="cl-form__section-title">Photo de profil</h2>
                    <p class="cl-form__section-sub">Visible par les experts sur votre espace client.</p>
                </div>
            </div>

            <div class="cl-compte-avatar-layout">
                <!-- Prévisualisation -->
                <div class="cl-compte-avatar-preview-wrap">
                    <div class="cl-compte-avatar-ring">
                        <div id="avatar-preview-placeholder" class="cl-compte-avatar-placeholder">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <?php if ($avatarUrl): ?>
                        <img id="avatar-preview-img" src="<?= $e($avatarUrl) ?>" alt="Photo actuelle" class="cl-compte-avatar-img"
                             style="display:none;"
                             onload="this.style.display='block';var p=document.getElementById('avatar-preview-placeholder');if(p)p.style.display='none';"
                             onerror="var p=document.getElementById('avatar-preview-placeholder');if(p)p.style.display='flex';">
                        <?php endif; ?>
                    </div>
                    <p class="cl-form__hint" style="text-align:center;margin-top:.5rem">Aperçu</p>
                </div>

                <!-- Zone upload -->
                <div style="flex:1;min-width:0">
                    <label for="avatar" class="cl-upload-drop" id="avatar-drop-zone">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span class="cl-upload-drop__text">
                            <strong>Cliquez ou glissez une image</strong>
                            <span>PNG, JPG, GIF ou WebP · Max 5 Mo</span>
                        </span>
                        <input type="file" name="avatar" id="avatar" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" class="cl-upload-input">
                    </label>
                    <?php if ($hasAvatar): ?>
                    <label class="cl-compte-delete-check">
                        <input type="checkbox" name="avatar_supprimer" value="1">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                        Supprimer la photo actuelle
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section Pièce d'identité -->
        <div class="cl-card cl-form__section">
            <div class="cl-form__section-head">
                <div class="cl-form__section-num" style="background:#7c3aed">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <div>
                    <h2 class="cl-form__section-title">Pièce d'identité</h2>
                    <p class="cl-form__section-sub">Carte nationale ou passeport — non visible par les experts.</p>
                </div>
            </div>

            <?php if ($hasPiece): ?>
            <div class="cl-piece-status">
                <div class="cl-piece-status__badge">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Document déposé
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:.65rem">
                    <a href="<?= $e($pieceUrl) ?>" target="_blank" rel="noopener" class="cl-btn cl-btn--outline cl-btn--sm">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Voir le document
                    </a>
                    <label class="cl-compte-delete-check">
                        <input type="checkbox" name="piece_identite_supprimer" value="1">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                        Supprimer le document
                    </label>
                </div>
            </div>
            <?php else: ?>
            <div class="cl-piece-empty">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <span>Aucun document déposé</span>
            </div>
            <?php endif; ?>

            <label for="piece_identite" class="cl-upload-drop" id="piece-drop-zone" style="margin-top:1rem">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span class="cl-upload-drop__text">
                    <strong><?= $hasPiece ? 'Remplacer le document' : 'Déposer un fichier' ?></strong>
                    <span>Image (PNG, JPG, WebP) ou PDF · Max 5 Mo</span>
                </span>
                <input type="file" name="piece_identite" id="piece_identite" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,application/pdf" class="cl-upload-input">
            </label>
        </div>

        <!-- Actions -->
        <div class="cl-form__footer">
            <a href="<?= $e($compteBackUrl) ?>" class="cl-btn cl-btn--outline">Annuler</a>
            <button type="submit" class="cl-btn cl-btn--amber">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    /* Prévisualisation avatar */
    var input       = document.getElementById('avatar');
    var preview     = document.getElementById('avatar-preview-img');
    var placeholder = document.getElementById('avatar-preview-placeholder');
    if (input && preview) {
        input.addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (f && f.type.indexOf('image/') === 0) {
                var r = new FileReader();
                r.onload = function () {
                    preview.src = r.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                r.readAsDataURL(f);
            }
        });
    }
    /* Drag-over zones */
    ['avatar-drop-zone', 'piece-drop-zone'].forEach(function (id) {
        var zone = document.getElementById(id);
        if (!zone) return;
        zone.addEventListener('dragover',  function (e) { e.preventDefault(); zone.classList.add('cl-upload-drop--over'); });
        zone.addEventListener('dragleave', function ()  { zone.classList.remove('cl-upload-drop--over'); });
        zone.addEventListener('drop',      function (e) {
            e.preventDefault(); zone.classList.remove('cl-upload-drop--over');
            var fi = zone.querySelector('input[type="file"]');
            if (fi && e.dataTransfer.files.length) {
                fi.files = e.dataTransfer.files;
                fi.dispatchEvent(new Event('change'));
            }
        });
    });
})();
</script>
