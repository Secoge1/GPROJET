<?php
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$e                = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField        = \App\Core\Security::getCsrfField();
$u                = $userToEdit ?? [];
$errors           = $errors ?? [];
$userId           = (int)($u['id'] ?? 0);
$hasAvatar        = $userId && !empty($u['avatar']);
$avatarUrl        = $hasAvatar ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time() : null;
$hasPiece         = $userId && !empty($u['piece_identite']);
$pieceUrl         = $hasPiece ? $baseUrl . '/fichier/user-piece/' . $userId : null;
$pieceExt         = $hasPiece ? strtolower(pathinfo((string) ($u['piece_identite'] ?? ''), PATHINFO_EXTENSION)) : '';
$pieceThumbOk     = $hasPiece && in_array($pieceExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
$compteFormAction = $compteFormAction ?? $baseUrl . '/expert/compte';
$nomComplet       = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
$initiales        = mb_strtoupper(mb_substr($nomComplet ?: 'E', 0, 1));
?>

<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
    <a href="<?= $baseUrl ?>/expert" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
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

<!-- Profil utilisateur -->
<div style="display:flex;align-items:center;gap:1rem;padding:1.25rem;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.25rem">
    <?php if ($avatarUrl): ?>
    <img src="<?= $e($avatarUrl) ?>" alt="Avatar" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-soft);flex-shrink:0">
    <?php else: ?>
    <div style="width:72px;height:72px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:700;color:var(--accent);flex-shrink:0"><?= $initiales ?></div>
    <?php endif; ?>
    <div>
        <p style="margin:0 0 0.2rem;font-weight:700;font-size:1rem;color:var(--primary)"><?= $e($nomComplet) ?></p>
        <p style="margin:0 0 0.3rem;font-size:0.82rem;color:var(--text-muted)"><?= $e($u['email'] ?? '') ?></p>
        <span style="font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:#fefce8;color:#a16207">Expert</span>
    </div>
</div>

<form method="post" action="<?= $e($compteFormAction) ?>" enctype="multipart/form-data" class="form-mobile">
    <?= $csrfField ?>

    <!-- Photo de profil -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">📷 Photo de profil</h2>
        <p style="margin:0 0 0.65rem;font-size:0.78rem;color:var(--text-muted)">Visible par les clients sur votre profil public.</p>
        <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp"
               style="display:block;width:100%;font-size:15px;padding:0.5rem 0;margin-bottom:0.35rem">
        <p style="margin:0;font-size:0.75rem;color:var(--text-muted)">PNG, JPG, WebP · Max 5 Mo</p>
        <?php if ($hasAvatar): ?>
        <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;font-size:0.82rem;color:#dc2626;cursor:pointer">
            <input type="checkbox" name="avatar_supprimer" value="1" style="accent-color:#dc2626">
            Supprimer la photo actuelle
        </label>
        <?php endif; ?>
    </div>

    <!-- Pièce d'identité (indicateur visuel type « présence ») -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">🪪 Pièce d'identité</h2>
        <div style="display:flex;align-items:flex-start;gap:0.85rem;margin-bottom:0.85rem">
            <?php if ($hasPiece): ?>
            <div style="flex-shrink:0;width:58px;height:58px;border-radius:50%;overflow:hidden;border:3px solid #22c55e;box-shadow:0 0 0 4px rgba(34,197,94,0.2);background:#ecfdf5">
                <?php if ($pieceThumbOk && $pieceUrl): ?>
                <img src="<?= $e($pieceUrl) ?>?t=<?= (int) time() ?>" alt="" width="58" height="58" style="width:100%;height:100%;object-fit:cover;display:block">
                <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#dcfce7,#bbf7d0)">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#15803d" stroke-width="1.6"/><path d="M14 2v6h6M9 15l2 2 4-4" stroke="#15803d" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.2rem;font-size:0.9rem;font-weight:700;color:#15803d">Pièce enregistrée</p>
                <p style="margin:0 0 0.45rem;font-size:0.76rem;color:var(--text-muted);line-height:1.35">Votre document est bien enregistré pour vérification.</p>
                <a href="<?= $e($pieceUrl) ?>" target="_blank" rel="noopener" style="display:inline-block;font-size:0.8rem;font-weight:600;color:var(--accent);text-decoration:none">Voir le document</a>
            </div>
            <?php else: ?>
            <div style="flex-shrink:0;width:58px;height:58px;border-radius:50%;border:2px dashed #cbd5e1;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;opacity:0.95" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="var(--text-muted)" stroke-width="1.5" stroke-dasharray="3 2"/><path d="M14 2v6h6" stroke="var(--text-muted)" stroke-width="1.5"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.2rem;font-size:0.9rem;font-weight:700;color:var(--text-muted)">Aucune pièce déposée</p>
                <p style="margin:0;font-size:0.76rem;color:var(--text-muted);line-height:1.35">Ajoutez une carte d’identité ou un passeport (image ou PDF).</p>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($hasPiece): ?>
        <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.82rem;color:#dc2626;cursor:pointer">
            <input type="checkbox" name="piece_identite_supprimer" value="1" style="accent-color:#dc2626">
            Supprimer le document
        </label>
        <?php endif; ?>
        <input type="file" name="piece_identite" accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf"
               style="display:block;width:100%;font-size:15px;padding:0.5rem 0;margin-bottom:0.35rem">
        <p style="margin:0;font-size:0.75rem;color:var(--text-muted)">Image ou PDF · Max 5 Mo</p>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
    </button>
</form>

<!-- Abonnement / renouvellement -->
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
    <a href="<?= $baseUrl ?>/expert/profil" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem">Mon profil expert</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $baseUrl ?>/expert/revenus" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem">Mes revenus</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $baseUrl ?>/auth/deconnexion" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:#dc2626">
        <span style="font-size:0.9rem">Déconnexion</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
</div>
