<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $client_base_path ?? '/client';
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$demande = $demande ?? [];
$experts = $experts ?? [];
$errors = $errors ?? [];
$data = $data ?? ['expert_id' => 0, 'date_debut_prevue' => date('Y-m-d'), 'heure' => '14', 'minute' => '00'];
$demandeTitre = $e($demande['titre'] ?? '');
$demandeId = (int)($demande['id'] ?? 0);
$demandeDuree = (float)($demande['duree_estimee_heures'] ?? 0);
?>
<div class="cl-page cl-page--reserver">
    <header class="cl-reserver-hero">
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="cl-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Mes demandes
        </a>
        <div class="cl-reserver-hero__main">
            <div class="cl-reserver-hero__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <h1 class="cl-reserver-hero__title">Réserver un expert</h1>
                <?php if ($demandeTitre !== ''): ?>
                <p class="cl-reserver-hero__demande">Demande : <strong><?= $demandeTitre ?></strong> · <?= $demandeDuree ?> h</p>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error" role="alert">
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if (empty($experts)): ?>
    <div class="cl-card cl-empty-card cl-empty-card--reserver">
        <div class="cl-empty-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <h3>Aucun expert disponible</h3>
        <p>Aucun expert ne correspond à cette demande pour le moment. Réessayez plus tard.</p>
        <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="cl-btn cl-btn--outline">Retour aux demandes</a>
    </div>
    <?php else: ?>
    <form method="post" action="<?= $baseUrl ?><?= $e($bp) ?>/reserver/<?= $demandeId ?>" class="cl-reserver-form">
        <?= $csrfField ?>

        <div class="cl-card cl-reserver-form__card">
            <h2 class="cl-reserver-form__section-title">Choisir l'expert</h2>
            <p class="cl-reserver-hint">Suggestion automatique selon la note, les avis, le rapport qualité-prix et le niveau sur la compétence<?= !empty($demande['urgence']) && ($demande['urgence'] ?? '') !== 'normale' ? ' (demande urgente : profils confirmés favorisés).' : '.' ?></p>
            <div class="cl-reserver-experts">
                <?php
                $avatarColors = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#dc2626', '#d97706'];
                foreach ($experts as $expert):
                    $selected = (int)($data['expert_id'] ?? 0) === (int)$expert['id'];
                    $recTop = !empty($expert['recommendation_is_top']) && (float)($expert['tarif_horaire'] ?? 0) > 0;
                    $recReason = trim((string) ($expert['recommendation_reason'] ?? ''));
                    $recScore = (string) ($expert['recommendation_score'] ?? '');
                    $initiales = strtoupper(mb_substr((string) ($expert['prenom'] ?? ''), 0, 1) . mb_substr((string) ($expert['nom'] ?? ''), 0, 1));
                    if ($initiales === '') {
                        $initiales = strtoupper(mb_substr((string) ($expert['titre'] ?? 'E'), 0, 1));
                    }
                    $avatarColor = $avatarColors[abs(crc32($initiales)) % count($avatarColors)];
                    $avatarUrl = !empty($expert['avatar']) ? $baseUrl . '/uploads/' . ltrim((string) $expert['avatar'], '/') : '';
                    $verifie = !empty($expert['valide_par_admin']) || !empty($expert['certifie']);
                ?>
                <label class="cl-reserver-expert <?= $selected ? 'cl-reserver-expert--selected' : '' ?><?= $recTop ? ' cl-reserver-expert--recommended' : '' ?>">
                    <input type="radio" name="expert_id" value="<?= (int)$expert['id'] ?>" required <?= $selected ? 'checked' : '' ?> class="cl-reserver-expert__radio">
                    <span class="cl-reserver-expert__avatar-wrap cl-reserver-expert__avatar-wrap--stack" aria-hidden="true">
                        <span class="cl-reserver-expert__avatar-initials" style="background:<?= $e($avatarColor) ?>"><?= $e($initiales) ?></span>
                        <?php if ($avatarUrl !== ''): ?>
                        <img src="<?= $e($avatarUrl) ?>" alt="" class="cl-reserver-expert__avatar-img" width="52" height="52" loading="lazy" decoding="async" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <?php if ($verifie): ?>
                        <span class="cl-reserver-expert__verified" title="Profil vérifié par GLOBALO">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <?php endif; ?>
                    </span>
                    <span class="cl-reserver-expert__content">
                        <?php if ($recTop): ?>
                        <span class="cl-reserver-expert__badge-row">
                            <span class="cl-reserver-expert__badge" title="<?= $e('Score ' . $recScore . '/100' . ($recReason !== '' ? ' — ' . $recReason : '')) ?>">Recommandé</span>
                            <?php if ($recReason !== ''): ?>
                            <span class="cl-reserver-expert__reason"><?= $e(ucfirst($recReason)) ?></span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <span class="cl-reserver-expert__title"><?= $e($expert['titre']) ?></span>
                        <span class="cl-reserver-expert__name"><?= $e($expert['prenom'] . ' ' . $expert['nom']) ?></span>
                        <span class="cl-reserver-expert__meta">
                            <?= number_format((float)($expert['tarif_horaire'] ?? 0), 0, ',', ' ') ?> <?= $e(devise()) ?>/h
                            <?php if (!empty($expert['note_moyenne'])): ?> · ⭐ <?= number_format((float)$expert['note_moyenne'], 1) ?><?php endif; ?>
                        </span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cl-card cl-reserver-form__card">
            <h2 class="cl-reserver-form__section-title">Date et heure</h2>
            <div class="cl-reserver-form__row">
                <div class="cl-reserver-form__field">
                    <label for="date_debut_prevue">Date</label>
                    <input type="date" name="date_debut_prevue" id="date_debut_prevue" required value="<?= $e(!empty($data['date_debut_prevue']) ? $data['date_debut_prevue'] : date('Y-m-d')) ?>" min="<?= date('Y-m-d') ?>" class="cl-form__input">
                </div>
                <div class="cl-reserver-form__field cl-reserver-form__field--time">
                    <label>Heure</label>
                    <div class="cl-reserver-form__time">
                        <input type="number" name="heure" min="0" max="23" value="<?= $e($data['heure']) ?>" class="cl-form__input" aria-label="Heure">
                        <span>h</span>
                        <input type="number" name="minute" min="0" max="59" value="<?= $e($data['minute']) ?>" class="cl-form__input" aria-label="Minutes">
                        <span>min</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="cl-reserver-form__actions">
            <button type="submit" class="cl-btn cl-btn--amber">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Créer la réservation
            </button>
            <a href="<?= $baseUrl ?><?= $e($bp) ?>/demandes" class="cl-btn cl-btn--outline">Annuler</a>
        </div>
    </form>
    <?php endif; ?>
</div>
