<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$professeur = $professeur ?? [];
$matieres = $matieres ?? [];
$errors = $errors ?? [];
$data = $data ?? ['matiere_id' => '', 'date_debut_prevue' => date('Y-m-d'), 'heure' => '14', 'minute' => '00', 'duree_heures' => '1'];
$tarif = number_format((float)($professeur['tarif_horaire'] ?? 0), 0, ',', ' ');
$retourProfUrl = $retourProfUrl ?? ($baseUrl . '/app/professeurs/' . (int)($professeur['id'] ?? 0));
$listeProfsUrl = $listeProfsUrl ?? ($baseUrl . '/app/professeurs');
$formReserverUrl = $formReserverUrl ?? ($baseUrl . '/app/reserver-professeur/' . (int)($professeur['id'] ?? 0));
$nomComplet = trim((string)($professeur['prenom'] ?? '') . ' ' . (string)($professeur['nom'] ?? ''));
$initiales = strtoupper(mb_substr((string)($professeur['prenom'] ?? ''), 0, 1) . mb_substr((string)($professeur['nom'] ?? ''), 0, 1));
if ($initiales === '') {
    $initiales = strtoupper(mb_substr((string)($professeur['titre'] ?? 'P'), 0, 1));
}
$avatarColors = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#dc2626', '#d97706'];
$avatarColor = $avatarColors[abs(crc32($initiales)) % count($avatarColors)];
$avatarUrl = !empty($professeur['avatar']) ? $baseUrl . '/uploads/' . ltrim((string) $professeur['avatar'], '/') : '';
$verifieProf = !empty($professeur['valide_par_admin']) || !empty($professeur['email_verifie']);
?>
<div class="mob-reserver mob-reserver--professeur">
    <header class="mob-reserver__header">
        <a href="<?= $e($retourProfUrl) ?>" class="mob-reserver__back" aria-label="Retour">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div class="mob-reserver__head-text">
            <h1 class="mob-reserver__title">Réserver une session</h1>
            <p class="mob-reserver__demande"><?= $e($professeur['titre'] ?? '') ?> — <?= $tarif ?> <?= $e(devise()) ?>/h</p>
        </div>
    </header>

    <div class="mob-reserver__identity-card">
        <div class="mob-reserver-expert__avatar-wrap mob-reserver-expert__avatar-wrap--stack mob-reserver-expert__avatar-wrap--lg">
            <span class="mob-reserver-expert__avatar-initials" style="background:<?= $e($avatarColor) ?>"><?= $e($initiales) ?></span>
            <?php if ($avatarUrl !== ''): ?>
            <img src="<?= $e($avatarUrl) ?>" alt="" class="mob-reserver-expert__avatar-img" width="56" height="56" loading="lazy" decoding="async" onerror="this.style.display='none'">
            <?php endif; ?>
            <?php if ($verifieProf): ?>
            <span class="mob-reserver-expert__verified" title="Profil vérifié par GLOBALO">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <?php endif; ?>
        </div>
        <div class="mob-reserver__identity-text">
            <?php if ($nomComplet !== ''): ?>
            <p class="mob-reserver__identity-name"><?= $e($nomComplet) ?></p>
            <?php endif; ?>
            <?php if ($verifieProf): ?>
            <span class="mob-reserver__identity-badge">Profil vérifié</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="mob-reserver__errors" role="alert">
        <?php foreach ($errors as $err): ?><p>• <?= $e($err) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $e($formReserverUrl) ?>" class="mob-reserver__form">
        <?= $csrfField ?>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label" for="mob-rp-matiere">Matière (optionnel)</label>
            <select name="matiere_id" id="mob-rp-matiere" class="mob-reserver__input">
                <option value="">— Choisir —</option>
                <?php foreach ($matieres as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= (int)($data['matiere_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= $e($m['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label" for="mob-rp-date">Date <span class="mob-reserver__required">*</span></label>
            <input type="date" id="mob-rp-date" name="date_debut_prevue" required
                   value="<?= $e($data['date_debut_prevue'] ?? date('Y-m-d')) ?>"
                   min="<?= date('Y-m-d') ?>" class="mob-reserver__input">
        </div>

        <div class="mob-reserver__time-row">
            <div class="mob-reserver__section">
                <label class="mob-reserver__label">Heure</label>
                <input type="number" name="heure" min="0" max="23" value="<?= $e($data['heure'] ?? '14') ?>" class="mob-reserver__input">
            </div>
            <div class="mob-reserver__section">
                <label class="mob-reserver__label">Min</label>
                <input type="number" name="minute" min="0" max="59" value="<?= $e($data['minute'] ?? '00') ?>" class="mob-reserver__input">
            </div>
        </div>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label" for="mob-rp-duree">Durée</label>
            <select name="duree_heures" id="mob-rp-duree" class="mob-reserver__input">
                <option value="0.5" <?= ((float)($data['duree_heures'] ?? 1) == 0.5) ? 'selected' : '' ?>>30 min</option>
                <option value="1" <?= ((float)($data['duree_heures'] ?? 1) == 1) ? 'selected' : '' ?>>1 h</option>
                <option value="1.5" <?= ((float)($data['duree_heures'] ?? 1) == 1.5) ? 'selected' : '' ?>>1 h 30</option>
                <option value="2" <?= ((float)($data['duree_heures'] ?? 1) == 2) ? 'selected' : '' ?>>2 h</option>
            </select>
        </div>

        <div class="mob-reserver__actions">
            <button type="submit" class="btn-mobile btn-primary">Envoyer la demande</button>
            <a href="<?= $e($listeProfsUrl) ?>" class="btn-mobile btn-outline">Annuler</a>
        </div>
    </form>
</div>
