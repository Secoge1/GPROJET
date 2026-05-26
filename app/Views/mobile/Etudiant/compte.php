<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$basePath   = $base_path ?? (($user['role'] ?? '') === 'professeur' ? '/professeur' : '/etudiant');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$userToEdit = $userToEdit ?? [];
$errors     = $errors ?? [];
$csrfField  = \App\Core\Security::getCsrfField();
$avatarUrl  = !empty($userToEdit['avatar'])
    ? $baseUrl . '/uploads/' . $e($userToEdit['avatar'])
    : null;
$initiales  = mb_strtoupper(mb_substr(trim(($userToEdit['prenom'] ?? '') . ' ' . ($userToEdit['nom'] ?? '')), 0, 1));
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
    <a href="<?= $baseUrl . $basePath ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Mon compte</h1>
</div>

<!-- Erreurs -->
<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?>
    <p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Aperçu avatar -->
<div style="display:flex;align-items:center;gap:1rem;padding:1.25rem;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.25rem">
    <?php if ($avatarUrl): ?>
    <img src="<?= $avatarUrl ?>" alt="Avatar" id="avatar-preview"
         style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-soft)">
    <?php else: ?>
    <div id="avatar-preview"
         style="width:72px;height:72px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:700;color:var(--accent);flex-shrink:0">
        <?= $initiales ?>
    </div>
    <?php endif; ?>
    <div>
        <p style="margin:0 0 0.2rem;font-weight:700;font-size:1rem;color:var(--primary)">
            <?= $e(trim(($userToEdit['prenom'] ?? '') . ' ' . ($userToEdit['nom'] ?? ''))) ?>
        </p>
        <p style="margin:0;font-size:0.82rem;color:var(--text-muted)"><?= $e($userToEdit['email'] ?? '') ?></p>
        <p style="margin:0.35rem 0 0;font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:var(--accent-soft);color:var(--accent);display:inline-block"><?= ($user['role'] ?? '') === 'professeur' ? 'Professeur' : 'Étudiant' ?></p>
    </div>
</div>

<form method="post" action="<?= $baseUrl . $basePath ?>/compte" enctype="multipart/form-data" class="form-mobile">
    <?= $csrfField ?>

    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">Photo de profil</h2>

        <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">
            Changer la photo
        </label>
        <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp"
               onchange="previewAvatarMobile(this)"
               style="display:block;width:100%;font-size:15px;padding:0.5rem 0">
        <p style="font-size:0.75rem;color:var(--text-muted);margin:0.25rem 0 0">Formats : JPG, PNG, WebP · Max 5 Mo</p>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer
    </button>
    <a href="<?= $baseUrl . $basePath ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center">Retour</a>
</form>

<!-- Abonnement / renouvellement (Mobile Money, Wave) — étudiant & professeur -->
<div style="margin-top:1rem;margin-bottom:0.5rem">
    <a href="<?= $baseUrl ?>/app/abonnement" class="btn-mobile btn-primary"
       style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;box-sizing:border-box;background:#0066CC;border-color:#0052a3;text-decoration:none;font-weight:600">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        Renouveler mon abonnement
    </a>
    <p style="margin:0.45rem 0 0;font-size:0.72rem;color:var(--text-muted);text-align:center;line-height:1.35">
        Paiement Wave — même parcours que sur ordinateur
    </p>
</div>

<!-- Liens rapides -->
<div style="margin-top:1.25rem;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <a href="<?= $baseUrl . $basePath ?>/profil" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem"><?= ($user['role'] ?? '') === 'professeur' ? 'Profil professeur' : 'Profil universitaire' ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= ($user['role'] ?? '') === 'professeur' ? $baseUrl . $basePath . '/exercices-disponibles' : $baseUrl . $basePath . '/exercices' ?>" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem"><?= ($user['role'] ?? '') === 'professeur' ? 'Exercices à corriger' : 'Mes exercices' ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $baseUrl ?>/auth/deconnexion" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:#dc2626">
        <span style="font-size:0.9rem">Déconnexion</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
</div>

<script>
function previewAvatarMobile(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('avatar-preview');
            if (preview) {
                preview.outerHTML = '<img src="' + e.target.result + '" alt="Avatar" id="avatar-preview" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-soft)">';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
