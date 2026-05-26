<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$data = $data ?? ['titre' => '', 'description' => '', 'competence_id' => ''];
$competences = $competences ?? [];
$errors = $errors ?? [];
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
?>
<div class="urgence-page">

    <!-- Bannière hero -->
    <div class="urgence-hero">
        <div class="urgence-hero__deco" aria-hidden="true"></div>
        <div class="urgence-hero__inner">
            <a href="<?= $baseUrl ?>/client" class="urgence-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="urgence-hero__content">
                <div class="urgence-hero__icon-wrap" aria-hidden="true">
                    <span class="urgence-pulse"></span>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <div>
                    <h1 class="urgence-hero__title">Besoin d'aide maintenant</h1>
                    <p class="urgence-hero__subtitle">Décrivez votre besoin — tous les experts disponibles sont alertés instantanément. <strong>Le premier qui accepte prend la mission.</strong></p>
                </div>
            </div>
            <div class="urgence-hero__stats">
                <div class="urgence-stat">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Réponse en moins de 5 min
                </div>
                <div class="urgence-stat">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    Experts disponibles 24h/7j
                </div>
                <div class="urgence-stat">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Paiement sécurisé après mission
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="urgence-alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="post" action="<?= $baseUrl ?>/client/urgence" class="urgence-form">
        <?= $csrfField ?>

        <div class="urgence-form__card">
            <!-- Titre du besoin -->
            <div class="urgence-form__field">
                <label for="titre" class="urgence-form__label">
                    <span class="urgence-form__label-num">1</span>
                    Décrivez votre besoin en une phrase
                    <span class="urgence-form__required">*</span>
                </label>
                <input type="text" id="titre" name="titre" required maxlength="200"
                       class="urgence-form__input"
                       placeholder="Ex. Mise en forme Excel urgent, bug PHP à corriger…"
                       value="<?= $e($data['titre']) ?>">
            </div>

            <!-- Domaine -->
            <div class="urgence-form__field">
                <label for="competence_id" class="urgence-form__label">
                    <span class="urgence-form__label-num">2</span>
                    Domaine d'expertise
                    <span class="urgence-form__optional">optionnel</span>
                </label>
                <div class="urgence-form__select-wrap">
                    <select id="competence_id" name="competence_id" class="urgence-form__select">
                        <option value="">— Alerter tous les domaines —</option>
                        <?php foreach ($competences as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($data['competence_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $e($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="urgence-form__select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <span class="urgence-form__hint">Laissez vide pour alerter tous les experts disponibles.</span>
            </div>

            <!-- Détails -->
            <div class="urgence-form__field">
                <label for="description" class="urgence-form__label">
                    <span class="urgence-form__label-num">3</span>
                    Détails supplémentaires
                    <span class="urgence-form__optional">optionnel</span>
                </label>
                <textarea id="description" name="description" rows="4"
                          class="urgence-form__textarea"
                          placeholder="Précisez le contexte, les fichiers concernés, le niveau d'urgence…"><?= $e($data['description']) ?></textarea>
            </div>

            <!-- Actions -->
            <div class="urgence-form__actions">
                <a href="<?= $baseUrl ?>/client" class="urgence-form__cancel">
                    Annuler
                </a>
                <button type="submit" class="urgence-form__submit">
                    <span class="urgence-submit-pulse" aria-hidden="true"></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Lancer l'alerte experts
                </button>
            </div>
        </div>
    </form>

</div>
