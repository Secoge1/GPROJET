<?php
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$e                = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField        = \App\Core\Security::getCsrfField();
$u                = $userToEdit ?? [];
$errors           = $errors ?? [];
$userId           = (int)($u['id'] ?? 0);
$hasAvatar        = $userId && !empty($u['avatar']);
$avatarUrl        = $userId ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time() : null;
$hasPiece         = $userId && !empty($u['piece_identite']);
$pieceUrl         = $hasPiece ? $baseUrl . '/fichier/user-piece/' . $userId : null;
$pieceExt         = $hasPiece ? strtolower(pathinfo((string) ($u['piece_identite'] ?? ''), PATHINFO_EXTENSION)) : '';
$pieceThumbOk     = $hasPiece && in_array($pieceExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
$bp = $client_base_path ?? '/client';
$compteBackUrl = $compteBackUrl ?? $baseUrl . $bp;
$compteFormAction = $compteFormAction ?? $baseUrl . $bp . '/compte';
$nomComplet       = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
$initiales        = mb_strtoupper(mb_substr($nomComplet ?: 'C', 0, 1));
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
    <a href="<?= $compteBackUrl ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Mon compte</h1>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?><p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Infos utilisateur -->
<div style="display:flex;align-items:center;gap:1rem;padding:1.25rem;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.25rem">
    <div id="avatar-placeholder" style="width:72px;height:72px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:700;color:var(--accent);flex-shrink:0">
        <?= $initiales ?>
    </div>
    <?php if ($avatarUrl): ?>
    <img id="avatar-img" src="<?= $e($avatarUrl) ?>" alt="Avatar"
         onload="this.style.display='block';var p=document.getElementById('avatar-placeholder');if(p)p.style.display='none';"
         onerror="var p=document.getElementById('avatar-placeholder');if(p)p.style.display='flex';"
         style="display:none;width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-soft);flex-shrink:0">
    <?php endif; ?>
    <div>
        <p style="margin:0 0 0.2rem;font-weight:700;font-size:1rem;color:var(--primary)"><?= $e($nomComplet) ?></p>
        <p style="margin:0 0 0.3rem;font-size:0.82rem;color:var(--text-muted)"><?= $e($u['email'] ?? '') ?></p>
        <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap">
            <span style="font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">Client</span>
            <?php if (($u['auth_provider'] ?? 'email') === 'google'): ?>
            <span style="font-size:0.72rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:#fef3c7;color:#92400e;display:inline-flex;align-items:center;gap:.25rem">
                <svg width="11" height="11" viewBox="0 0 48 48" style="vertical-align:-1px"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Google
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="post" action="<?= $e($compteFormAction) ?>" enctype="multipart/form-data" class="form-mobile">
    <?= $csrfField ?>

    <!-- Photo de profil -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">📷 Photo de profil</h2>
        <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:0.75rem">
            <div style="flex-shrink:0;text-align:center">
                <div id="mob-client-avatar-frame" style="width:92px;height:92px;margin:0 auto;border-radius:50%;overflow:hidden;position:relative;box-sizing:border-box;border:<?= $hasAvatar ? '3px solid var(--accent-soft)' : '2px dashed #94a3b8' ?>;background:linear-gradient(160deg,#f8fafc 0%,#e2e8f0 100%);box-shadow:0 4px 14px rgba(15,23,42,0.08)">
                    <img id="mob-client-avatar-upload-preview"
                         src="<?= $hasAvatar ? $e($avatarUrl) : '' ?>"
                         alt=""
                         width="92" height="92"
                         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:<?= $hasAvatar ? 'block' : 'none' ?>">
                    <div id="mob-client-avatar-upload-ph" style="position:absolute;inset:0;display:<?= $hasAvatar ? 'none' : 'flex' ?>;align-items:center;justify-content:center;flex-direction:column;gap:0.2rem;padding:0.35rem;text-align:center" aria-hidden="true">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.35"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span style="font-size:0.62rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;line-height:1.15">Aperçu</span>
                    </div>
                </div>
                <p style="margin:0.35rem 0 0;font-size:0.68rem;color:var(--text-muted);max-width:92px;line-height:1.25">Ce que vous enverrez après enregistrement</p>
            </div>
            <div style="flex:1;min-width:0;padding-top:0.15rem">
                <label for="avatar-input" style="display:block;font-size:0.78rem;font-weight:600;color:var(--text-muted);margin-bottom:0.35rem">Fichier</label>
                <input type="file" name="avatar" id="avatar-input" accept="image/png,image/jpeg,image/jpg,image/webp"
                       style="display:block;width:100%;font-size:15px;padding:0.35rem 0;margin-bottom:0.35rem">
                <p style="margin:0;font-size:0.75rem;color:var(--text-muted)">PNG, JPG, WebP · Max 5 Mo</p>
                <?php if ($hasAvatar): ?>
                <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;font-size:0.82rem;color:#dc2626;cursor:pointer">
                    <input type="checkbox" name="avatar_supprimer" value="1" id="avatar-supprimer" style="accent-color:#dc2626">
                    Supprimer la photo actuelle
                </label>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pièce d'identité -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">🪪 Pièce d'identité</h2>
        <div style="display:flex;align-items:flex-start;gap:0.85rem;margin-bottom:0.85rem">
            <?php if ($hasPiece): ?>
            <div style="flex-shrink:0;width:72px;height:56px;border-radius:10px;overflow:hidden;border:2px solid #22c55e;box-shadow:0 0 0 3px rgba(34,197,94,0.18);background:#ecfdf5">
                <?php if ($pieceThumbOk && $pieceUrl): ?>
                <img src="<?= $e($pieceUrl) ?>?t=<?= (int) time() ?>" alt="" width="72" height="56" style="width:100%;height:100%;object-fit:cover;display:block">
                <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#dcfce7,#bbf7d0)">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#15803d" stroke-width="1.6"/><path d="M14 2v6h6M9 15l2 2 4-4" stroke="#15803d" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.15rem;font-size:0.9rem;font-weight:700;color:#15803d">Document déposé</p>
                <p style="margin:0 0 0.4rem;font-size:0.76rem;color:var(--text-muted);line-height:1.35">Votre pièce est bien enregistrée. Vous pouvez la consulter ou la remplacer ci-dessous.</p>
                <a href="<?= $e($pieceUrl) ?>" target="_blank" rel="noopener" style="display:inline-block;font-size:0.8rem;font-weight:600;color:var(--accent);text-decoration:none">Voir le document →</a>
            </div>
            <?php else: ?>
            <div style="flex-shrink:0;width:72px;height:56px;border-radius:10px;border:2px dashed #cbd5e1;background:linear-gradient(180deg,#f8fafc,#f1f5f9);display:flex;align-items:center;justify-content:center;box-shadow:inset 0 1px 0 rgba(255,255,255,0.8)" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="3 2"/><path d="M14 2v6h6" stroke="#94a3b8" stroke-width="1.5"/><path d="M9 17h6M9 13h10" stroke="#cbd5e1" stroke-width="1.2" stroke-linecap="round"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.15rem;font-size:0.9rem;font-weight:700;color:var(--primary)">Aucune pièce pour l’instant</p>
                <p style="margin:0;font-size:0.76rem;color:var(--text-muted);line-height:1.35">Ajoutez une CNI, un passeport ou un titre équivalent (image ou PDF).</p>
            </div>
            <?php endif; ?>
        </div>
        <div id="mob-client-piece-staging-row" style="display:none;margin:0 0 0.85rem;padding:0.65rem 0.75rem;border-radius:var(--radius);background:var(--accent-soft);border:1px dashed var(--border);box-shadow:inset 0 1px 0 rgba(255,255,255,0.5)">
            <p style="margin:0 0 0.45rem;font-size:0.7rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:0.05em">Nouvelle sélection (aperçu)</p>
            <div style="display:flex;align-items:center;gap:0.65rem">
                <div style="flex-shrink:0;width:72px;height:56px;border-radius:8px;overflow:hidden;border:1px solid var(--border);background:#fff">
                    <img id="mob-client-piece-staging-img" alt="" width="72" height="56" style="display:none;width:100%;height:100%;object-fit:cover">
                    <div id="mob-client-piece-staging-pdf" style="display:none;width:100%;height:100%;align-items:center;justify-content:center;flex-direction:column;background:#fef2f2;gap:0.15rem">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#dc2626" stroke-width="1.5"/><path d="M14 2v6h6M9 17h6M9 13h10" stroke="#dc2626" stroke-width="1.2"/></svg>
                        <span style="font-size:0.6rem;font-weight:700;color:#b91c1c;text-transform:uppercase">PDF</span>
                    </div>
                    <div id="mob-client-piece-staging-generic" style="display:none;width:100%;height:100%;align-items:center;justify-content:center;background:#f8fafc">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="#64748b" stroke-width="1.5"/></svg>
                    </div>
                </div>
                <p id="mob-client-piece-staging-name" style="margin:0;font-size:0.78rem;color:var(--text);font-weight:500;word-break:break-all;line-height:1.3"></p>
            </div>
        </div>
        <?php if ($hasPiece): ?>
        <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.82rem;color:#dc2626;cursor:pointer">
            <input type="checkbox" name="piece_identite_supprimer" value="1" style="accent-color:#dc2626">
            Supprimer le document
        </label>
        <?php endif; ?>
        <label for="piece-identite-input" style="display:block;font-size:0.78rem;font-weight:600;color:var(--text-muted);margin-bottom:0.35rem">Ajouter ou remplacer</label>
        <input type="file" id="piece-identite-input" name="piece_identite" accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf"
               style="display:block;width:100%;font-size:15px;padding:0.35rem 0;margin-bottom:0.35rem">
        <p style="margin:0;font-size:0.75rem;color:var(--text-muted)">Image ou PDF · Max 5 Mo · Non visible par les experts</p>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
    </button>
</form>

<!-- Abonnement / renouvellement (Mobile Money, Wave) -->
<div style="margin-top:1rem;margin-bottom:0.75rem">
    <a href="<?= $baseUrl ?>/app/abonnement" class="btn-mobile btn-primary"
       style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;box-sizing:border-box;background:#0066CC;border-color:#0052a3;text-decoration:none;font-weight:600">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        Renouveler mon abonnement
    </a>
    <p style="margin:0.45rem 0 0;font-size:0.72rem;color:var(--text-muted);text-align:center;line-height:1.35">
        Paiement Mobile Money sécurisé — même page que sur ordinateur
    </p>
</div>

<!-- Liens utiles -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:0.5rem">
    <a href="<?= $baseUrl . $bp ?>/portefeuille" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem">Mon portefeuille</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $baseUrl ?>/auth/deconnexion" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:#dc2626">
        <span style="font-size:0.9rem">Déconnexion</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
</div>

<script>
(function () {
    var avatarServer = <?= json_encode($hasAvatar ? $avatarUrl : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
    var avatarInput = document.getElementById('avatar-input');
    var previewImg = document.getElementById('mob-client-avatar-upload-preview');
    var ph = document.getElementById('mob-client-avatar-upload-ph');
    var frame = document.getElementById('mob-client-avatar-frame');

    function setAvatarPreviewFromFile(file) {
        if (!file || !previewImg || !ph || !frame) return;
        if (!file.type || file.type.indexOf('image/') !== 0) return;
        var r = new FileReader();
        r.onload = function () {
            previewImg.src = r.result;
            previewImg.style.display = 'block';
            ph.style.display = 'none';
            frame.style.border = '3px solid var(--accent-soft)';
        };
        r.readAsDataURL(file);
    }

    function resetAvatarPreviewUi() {
        if (!previewImg || !ph || !frame) return;
        var del = document.getElementById('avatar-supprimer');
        if (del && del.checked) {
            previewImg.removeAttribute('src');
            previewImg.style.display = 'none';
            ph.style.display = 'flex';
            frame.style.border = '2px dashed #94a3b8';
            return;
        }
        if (avatarServer) {
            previewImg.src = avatarServer;
            previewImg.style.display = 'block';
            ph.style.display = 'none';
            frame.style.border = '3px solid var(--accent-soft)';
        } else {
            previewImg.removeAttribute('src');
            previewImg.style.display = 'none';
            ph.style.display = 'flex';
            frame.style.border = '2px dashed #94a3b8';
        }
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (f) setAvatarPreviewFromFile(f);
            else resetAvatarPreviewUi();
        });
    }
    var delBox = document.getElementById('avatar-supprimer');
    if (delBox) delBox.addEventListener('change', resetAvatarPreviewUi);

    var pieceInput = document.getElementById('piece-identite-input');
    var stagingRow = document.getElementById('mob-client-piece-staging-row');
    var stagImg = document.getElementById('mob-client-piece-staging-img');
    var stagPdf = document.getElementById('mob-client-piece-staging-pdf');
    var stagGen = document.getElementById('mob-client-piece-staging-generic');
    var stagName = document.getElementById('mob-client-piece-staging-name');

    function showPieceStaging(file) {
        if (!pieceInput || !stagingRow || !stagName || !stagImg || !stagPdf || !stagGen) return;
        if (!file || !pieceInput.files || !pieceInput.files.length) {
            stagingRow.style.display = 'none';
            stagName.textContent = '';
            if (stagImg) stagImg.src = '';
            if (stagImg) stagImg.style.display = 'none';
            if (stagPdf) stagPdf.style.display = 'none';
            if (stagGen) stagGen.style.display = 'none';
            return;
        }
        stagName.textContent = file.name || '';
        stagImg.style.display = 'none';
        stagPdf.style.display = 'none';
        stagGen.style.display = 'none';
        stagingRow.style.display = 'block';
        var t = file.type || '';
        if (t === 'application/pdf') {
            stagPdf.style.display = 'flex';
            return;
        }
        if (t.indexOf('image/') === 0) {
            var r = new FileReader();
            r.onload = function () {
                stagImg.src = r.result;
                stagImg.style.display = 'block';
            };
            r.readAsDataURL(file);
            return;
        }
        stagGen.style.display = 'flex';
    }

    if (pieceInput && stagImg && stagPdf && stagGen) {
        pieceInput.addEventListener('change', function () {
            showPieceStaging(this.files && this.files[0]);
        });
    }
})();
</script>
