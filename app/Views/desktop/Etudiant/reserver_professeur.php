<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$professeur = $professeur ?? [];
$matieres = $matieres ?? [];
$errors = $errors ?? [];
$data = $data ?? ['matiere_id' => '', 'date_debut_prevue' => date('Y-m-d'), 'heure' => '14', 'minute' => '00', 'duree_heures' => '1'];
$tarif = number_format((float)($professeur['tarif_horaire'] ?? 0), 0, ',', ' ');
$retourProfUrl = $retourProfUrl ?? ($baseUrl . '/professeurs/show/' . (int)($professeur['id'] ?? 0));
$listeProfsUrl = $listeProfsUrl ?? ($baseUrl . '/professeurs');
$formReserverUrl = $formReserverUrl ?? ($baseUrl . '/etudiant/reserver-professeur/' . (int)($professeur['id'] ?? 0));
$initiales = strtoupper(mb_substr((string)($professeur['prenom'] ?? ''), 0, 1) . mb_substr((string)($professeur['nom'] ?? ''), 0, 1));
if ($initiales === '') {
    $initiales = strtoupper(mb_substr((string)($professeur['titre'] ?? 'P'), 0, 1));
}
$avatarColors = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#dc2626', '#d97706'];
$avatarColor = $avatarColors[abs(crc32($initiales)) % count($avatarColors)];
$avatarUrl = !empty($professeur['avatar']) ? $baseUrl . '/uploads/' . ltrim((string) $professeur['avatar'], '/') : '';
$verifieProf = !empty($professeur['valide_par_admin']) || !empty($professeur['email_verifie']);
?>
<div class="cl-page cl-page--reserver cl-page--reserver-professeur">
    <header class="cl-reserver-hero">
        <a href="<?= $e($retourProfUrl) ?>" class="cl-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Retour au professeur
        </a>
        <div class="cl-reserver-hero__main">
            <div class="cl-reserver-expert__avatar-wrap cl-reserver-expert__avatar-wrap--stack cl-reserver-hero__prof-avatar" aria-hidden="true">
                <span class="cl-reserver-expert__avatar-initials" style="background:<?= $e($avatarColor) ?>"><?= $e($initiales) ?></span>
                <?php if ($avatarUrl !== ''): ?>
                <img src="<?= $e($avatarUrl) ?>" alt="" class="cl-reserver-expert__avatar-img" width="64" height="64" loading="lazy" decoding="async" onerror="this.style.display='none'">
                <?php endif; ?>
                <?php if ($verifieProf): ?>
                <span class="cl-reserver-expert__verified" title="Profil vérifié par GLOBALO">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="cl-reserver-hero__title">Réserver une session</h1>
                <p class="cl-reserver-hero__demande"><strong><?= $e($professeur['titre'] ?? '') ?></strong> — <?= $e(trim(($professeur['prenom'] ?? '') . ' ' . ($professeur['nom'] ?? ''))) ?> · <?= $tarif ?> <?= $e(devise()) ?>/h</p>
                <?php if ($verifieProf): ?>
                <p class="cl-reserver-hero__verified-label">Profil vérifié par GLOBALO</p>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error" role="alert">
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $e($formReserverUrl) ?>" class="cl-reserver-form">
        <?= $csrfField ?>

        <div class="cl-card cl-reserver-form__card">
            <h2 class="cl-reserver-form__section-title">Matière (optionnel)</h2>
            <div class="cl-reserver-form__field">
                <select name="matiere_id" id="matiere_id" class="cl-form__input">
                    <option value="">— Choisir une matière —</option>
                    <?php foreach ($matieres as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= (int)($data['matiere_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= $e($m['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="cl-card cl-reserver-form__card">
            <h2 class="cl-reserver-form__section-title">Date et heure</h2>
            <div class="cl-reserver-form__row">
                <div class="cl-reserver-form__field">
                    <label for="date_debut_prevue">Date</label>
                    <input type="date" name="date_debut_prevue" id="date_debut_prevue" required
                           value="<?= $e($data['date_debut_prevue'] ?? date('Y-m-d')) ?>"
                           min="<?= date('Y-m-d') ?>" class="cl-form__input">
                </div>
                <div class="cl-reserver-form__field cl-reserver-form__field--time">
                    <label>Heure</label>
                    <div class="cl-reserver-form__time">
                        <input type="number" name="heure" min="0" max="23" value="<?= $e($data['heure'] ?? '14') ?>" class="cl-form__input" aria-label="Heure">
                        <span>h</span>
                        <input type="number" name="minute" min="0" max="59" value="<?= $e($data['minute'] ?? '00') ?>" class="cl-form__input" aria-label="Minutes">
                        <span>min</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="cl-card cl-reserver-form__card">
            <h2 class="cl-reserver-form__section-title">Durée</h2>
            <div class="cl-reserver-form__field">
                <select name="duree_heures" id="duree_heures" class="cl-form__input">
                    <option value="0.5" <?= ($data['duree_heures'] ?? 1) == 0.5 ? 'selected' : '' ?>>30 minutes</option>
                    <option value="1" <?= ((float)($data['duree_heures'] ?? 1) == 1) ? 'selected' : '' ?>>1 heure</option>
                    <option value="1.5" <?= ((float)($data['duree_heures'] ?? 1) == 1.5) ? 'selected' : '' ?>>1 h 30</option>
                    <option value="2" <?= ((float)($data['duree_heures'] ?? 1) == 2) ? 'selected' : '' ?>>2 heures</option>
                </select>
            </div>
        </div>

        <div class="cl-reserver-form__actions">
            <button type="submit" class="cl-btn cl-btn--violet">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Envoyer la demande
            </button>
            <a href="<?= $e($listeProfsUrl) ?>" class="cl-btn cl-btn--outline">Annuler</a>
        </div>
    </form>
</div>
