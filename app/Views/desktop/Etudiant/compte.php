<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$basePath   = ($user['role'] ?? '') === 'professeur' ? '/professeur' : '/etudiant';
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$userToEdit = $userToEdit ?? [];
$errors     = $errors ?? [];
$csrfField  = \App\Core\Security::getCsrfField();
$avatarUrl  = !empty($userToEdit['avatar'])
    ? $baseUrl . '/uploads/' . $e($userToEdit['avatar'])
    : null;
?>
<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <h1 class="etd-page__title">Mon compte</h1>
            <p class="etd-page__sub">Gérez votre photo de profil et vos informations personnelles</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="etd-alert etd-alert--error">
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="etd-form-block" style="max-width:560px">
        <form method="post" action="<?= $baseUrl . $basePath ?>/compte" enctype="multipart/form-data" class="etd-form">
            <?= $csrfField ?>

            <div class="etd-avatar-zone">
                <?php if ($avatarUrl): ?>
                <img src="<?= $avatarUrl ?>" alt="Avatar" class="etd-avatar-preview" id="avatar-preview">
                <?php else: ?>
                <div class="etd-avatar-placeholder" id="avatar-preview">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <?php endif; ?>
                <div class="etd-avatar-info">
                    <p><strong><?= $e(trim(($userToEdit['prenom'] ?? '') . ' ' . ($userToEdit['nom'] ?? ''))) ?></strong></p>
                    <p class="etd-muted"><?= $e($userToEdit['email'] ?? '') ?></p>
                </div>
            </div>

            <div class="form-group">
                <label for="avatar">Changer la photo de profil</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                <span class="form-hint">Formats acceptés : JPG, PNG, WebP. Max 5 Mo.</span>
            </div>

            <div class="etd-form-actions">
                <button type="submit" class="etd-btn etd-btn--primary">Enregistrer</button>
                <a href="<?= $baseUrl . $basePath ?>" class="etd-btn etd-btn--ghost">Retour</a>
            </div>
        </form>
    </div>
</div>
