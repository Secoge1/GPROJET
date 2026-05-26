<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$bp        = $client_base_path ?? '/client';
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$demande   = $demande ?? [];
$experts   = $experts ?? [];
$errors    = $errors ?? [];
$data      = $data ?? ['expert_id' => 0, 'date_debut_prevue' => date('Y-m-d'), 'heure' => '14', 'minute' => '00'];
$demandeId = (int)($demande['id'] ?? 0);
$h         = str_pad((string) min(23, max(0, (int) ($data['heure'] ?? 14))), 2, '0', STR_PAD_LEFT);
$m         = str_pad((string) min(59, max(0, (int) ($data['minute'] ?? 0))), 2, '0', STR_PAD_LEFT);
$heureDebutValue = $h . ':' . $m;
$expertsReservables = array_values(array_filter($experts, static function (array $ex): bool {
    return (float) ($ex['tarif_horaire'] ?? 0) > 0;
}));
?>
<div class="mob-reserver">
    <header class="mob-reserver__header">
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="mob-reserver__back" aria-label="Retour">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div class="mob-reserver__head-text">
            <h1 class="mob-reserver__title">Réserver un expert</h1>
            <p class="mob-reserver__demande">Demande : <?= $e($demande['titre'] ?? '') ?> · <?= number_format((float) ($demande['duree_estimee_heures'] ?? 1), 2, ',', ' ') ?> h</p>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="mob-reserver__errors" role="alert">
        <?php foreach ($errors as $err): ?>
        <p>• <?= $e($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($experts)): ?>
    <div class="mob-reserver__empty">
        <div class="mob-reserver__empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <p class="mob-reserver__empty-title">Aucun expert disponible</p>
        <p class="mob-reserver__empty-desc">Aucun expert ne correspond à cette demande pour le moment.</p>
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="btn-mobile btn-outline btn-sm">← Retour</a>
    </div>
    <?php elseif (empty($expertsReservables)): ?>
    <div class="mob-reserver__empty mob-reserver__empty--tarif">
        <div class="mob-reserver__empty-icon mob-reserver__empty-icon--tarif" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <p class="mob-reserver__empty-title">Aucun tarif disponible</p>
        <p class="mob-reserver__empty-desc">Les experts correspondants n’ont pas encore défini de tarif horaire. Réessayez plus tard ou contactez le support.</p>
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="btn-mobile btn-outline btn-sm">← Retour</a>
    </div>
    <?php else: ?>
    <form method="post" action="<?= $baseUrl ?><?= $e($bp) ?>/reserver/<?= $demandeId ?>" class="mob-reserver__form">
        <?= $csrfField ?>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label">Choisir un expert <span class="mob-reserver__required">*</span></label>
            <p class="mob-reserver__hint">Classement automatique : note, avis, rapport qualité-prix et niveau sur la compétence<?= !empty($demande['urgence']) && ($demande['urgence'] ?? '') !== 'normale' ? ' (votre demande est marquée urgente : les profils les plus confirmés sont favorisés)' : '' ?>.</p>
            <div class="mob-reserver__experts">
                <?php
                $firstBookable = true;
                $avatarColors = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#dc2626', '#d97706'];
                foreach ($experts as $ex):
                    $fullName = trim(($ex['prenom'] ?? '') . ' ' . ($ex['nom'] ?? ''));
                    $profTitle = trim((string) ($ex['titre'] ?? ''));
                    $mainLine = $fullName !== '' ? $fullName : ($profTitle !== '' ? $profTitle : 'Expert');
                    $subLine = ($fullName !== '' && $profTitle !== '') ? $profTitle : '';
                    $tarif = (float) ($ex['tarif_horaire'] ?? 0);
                    $tarifIndispo = $tarif <= 0;
                    $selected = !$tarifIndispo && (int) ($data['expert_id'] ?? 0) === (int) $ex['id'];
                    $reqRadio = (!$tarifIndispo && $firstBookable) ? ' required' : '';
                    if (!$tarifIndispo) {
                        $firstBookable = false;
                    }
                    $initiales = strtoupper(mb_substr((string) ($ex['prenom'] ?? ''), 0, 1) . mb_substr((string) ($ex['nom'] ?? ''), 0, 1));
                    if ($initiales === '') {
                        $initiales = strtoupper(mb_substr((string) ($ex['titre'] ?? 'E'), 0, 1));
                    }
                    $avatarColor = $avatarColors[abs(crc32($initiales)) % count($avatarColors)];
                    $avatarUrl = !empty($ex['avatar']) ? $baseUrl . '/uploads/' . ltrim((string) $ex['avatar'], '/') : '';
                    $verifie = !empty($ex['valide_par_admin']) || !empty($ex['certifie']);
                ?>
                <label class="mob-reserver-expert <?= $selected ? 'mob-reserver-expert--selected' : '' ?><?= $tarifIndispo ? ' mob-reserver-expert--disabled' : '' ?><?= !empty($ex['recommendation_is_top']) && !$tarifIndispo ? ' mob-reserver-expert--recommended' : '' ?>">
                    <input type="radio" name="expert_id" value="<?= (int)$ex['id'] ?>"<?= $reqRadio ?> <?= $selected ? 'checked' : '' ?> <?= $tarifIndispo ? 'disabled' : '' ?> class="mob-reserver-expert__radio">
                    <span class="mob-reserver-expert__avatar-wrap mob-reserver-expert__avatar-wrap--stack" aria-hidden="true">
                        <span class="mob-reserver-expert__avatar-initials" style="background:<?= $e($avatarColor) ?>"><?= $e($initiales) ?></span>
                        <?php if ($avatarUrl !== ''): ?>
                        <img src="<?= $e($avatarUrl) ?>" alt="" class="mob-reserver-expert__avatar-img" width="48" height="48" loading="lazy" decoding="async" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <?php if ($verifie): ?>
                        <span class="mob-reserver-expert__verified" title="Profil vérifié par GLOBALO">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <?php endif; ?>
                    </span>
                    <span class="mob-reserver-expert__content">
                        <?php if (!empty($ex['recommendation_is_top']) && !$tarifIndispo):
                            $recReason = trim((string) ($ex['recommendation_reason'] ?? ''));
                            $recScore  = (string) ($ex['recommendation_score'] ?? '');
                            ?>
                        <span class="mob-reserver-expert__badge-wrap">
                            <span class="mob-reserver-expert__badge" title="<?= $e('Score ' . $recScore . '/100' . ($recReason !== '' ? ' — ' . $recReason : '')) ?>">Recommandé</span>
                            <?php if ($recReason !== ''): ?>
                            <span class="mob-reserver-expert__reason"><?= $e(ucfirst($recReason)) ?></span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <span class="mob-reserver-expert__title"><?= $e($mainLine) ?></span>
                        <?php if ($subLine !== ''): ?>
                        <span class="mob-reserver-expert__subtitle"><?= $e($subLine) ?></span>
                        <?php endif; ?>
                        <span class="mob-reserver-expert__meta">
                            <?php if ($tarifIndispo): ?>
                            Tarif à définir
                            <?php else: ?>
                            <?= number_format($tarif, 0, ',', ' ') ?> <?= $e(devise()) ?>/h
                            <?php endif; ?>
                            <?php if (!empty($ex['note_moyenne'])): ?> · ⭐ <?= number_format((float)$ex['note_moyenne'], 1) ?><?php endif; ?>
                        </span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label" for="mob-reserver-date">Date <span class="mob-reserver__required">*</span></label>
            <input type="date" id="mob-reserver-date" name="date_debut_prevue" required
                   value="<?= $e(!empty($data['date_debut_prevue']) ? $data['date_debut_prevue'] : date('Y-m-d')) ?>"
                   min="<?= date('Y-m-d') ?>"
                   class="mob-reserver__input">
        </div>

        <div class="mob-reserver__section">
            <label class="mob-reserver__label" for="mob-reserver-heure-debut">Heure de début <span class="mob-reserver__required">*</span></label>
            <input type="time" id="mob-reserver-heure-debut" name="heure_debut" required
                   value="<?= $e($heureDebutValue) ?>" step="900" class="mob-reserver__input">
        </div>

        <button type="submit" class="btn-mobile btn-primary mob-reserver__submit">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            Créer la réservation
        </button>
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="btn-mobile btn-outline mob-reserver__cancel">Annuler</a>
    </form>
    <?php endif; ?>
</div>
