<?php
$csrfField           = \App\Core\Security::getCsrfField();
$baseUrl             = rtrim(BASE_URL ?? '', '/');
$e                   = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$data                = $data ?? [];
$expertCompetences   = $expertCompetences ?? [];
$autre_competence_id = $autre_competence_id ?? 0;
$utilisateur         = $utilisateur ?? [];
$userId              = (int)($utilisateur['id'] ?? 0);
$hasAvatar           = $userId && !empty($utilisateur['avatar']);
$avatarUrl           = $hasAvatar
    ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time()
    : null;
$niveaux = [
    'debutant'     => 'Débutant',
    'intermediaire'=> 'Intermédiaire',
    'confirme'     => 'Confirmé',
    'expert'       => 'Expert',
];
?>
<section class="section-desktop page-expert page-expert-profil">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $baseUrl ?>/expert" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Mon profil expert</h1>
                    <p class="missions-header__sub">Complétez votre profil pour être visible et recevoir des missions.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="profil-alert" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Carte identité (avatar) -->
    <div class="profil-identity-card">
        <div class="profil-identity-card__avatar-wrap">
            <?php if ($avatarUrl): ?>
            <img src="<?= $e($avatarUrl) ?>" alt="Photo de profil" class="profil-identity-card__avatar">
            <?php else: ?>
            <div class="profil-identity-card__avatar profil-identity-card__avatar--placeholder" aria-hidden="true">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/expert/compte" class="profil-identity-card__avatar-edit" title="<?= $hasAvatar ? 'Changer la photo' : 'Ajouter une photo' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
        </div>
        <div class="profil-identity-card__info">
            <strong class="profil-identity-card__name">
                <?= $e(trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''))) ?>
            </strong>
            <span class="profil-identity-card__email"><?= $e($utilisateur['email'] ?? '') ?></span>
            <?php if (!empty($data['titre'])): ?>
            <span class="profil-identity-card__titre"><?= $e($data['titre']) ?></span>
            <?php endif; ?>
        </div>
        <div class="profil-identity-card__actions">
            <?php if (!$hasAvatar): ?>
            <a href="<?= $baseUrl ?>/expert/compte" class="profil-identity-card__photo-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Ajouter une photo
            </a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/expert/compte" class="profil-identity-card__photo-btn profil-identity-card__photo-btn--change">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Changer la photo
            </a>
            <?php endif; ?>
            <span class="profil-identity-card__hint">La photo est visible par les clients</span>
        </div>
    </div>

    <!-- Formulaire profil -->
    <form method="post" action="<?= $baseUrl ?>/expert/profil" class="profil-form">
        <?= $csrfField ?>

        <!-- Section 1 : Présentation -->
        <div class="profil-section">
            <div class="profil-section__header">
                <div class="profil-section__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <h2 class="profil-section__title">Présentation</h2>
                    <p class="profil-section__sub">Décrivez votre expertise pour attirer les bons clients.</p>
                </div>
            </div>

            <div class="profil-form__group">
                <label class="profil-form__label" for="titre">
                    Titre du profil
                    <span class="profil-form__required">*</span>
                </label>
                <input type="text" id="titre" name="titre" required maxlength="150"
                    value="<?= $e($data['titre'] ?? '') ?>"
                    placeholder="Ex. Développeur Flutter senior, Comptable expert…"
                    class="profil-form__input">
                <span class="profil-form__hint">Affiché en tête de votre profil public</span>
            </div>

            <div class="profil-form__group">
                <label class="profil-form__label" for="description">Description</label>
                <textarea id="description" name="description" rows="5"
                    placeholder="Présentez votre parcours, vos spécialités et ce que vous apportez aux clients…"
                    class="profil-form__textarea"><?= $e($data['description'] ?? '') ?></textarea>
            </div>

            <div class="profil-form__row">
                <div class="profil-form__group">
                    <label class="profil-form__label" for="tarif_horaire">
                        Tarif horaire
                        <span class="profil-form__currency"><?= $e(devise()) ?>/h</span>
                    </label>
                    <div class="profil-form__input-wrap">
                        <span class="profil-form__input-prefix">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <input type="number" id="tarif_horaire" name="tarif_horaire" min="0" step="0.01"
                            value="<?= $e($data['tarif_horaire'] ?? '') ?>"
                            placeholder="0"
                            class="profil-form__input profil-form__input--with-icon">
                    </div>
                </div>

                <div class="profil-form__group">
                    <label class="profil-form__label" for="niveau_experience">Niveau d'expérience</label>
                    <select id="niveau_experience" name="niveau_experience" class="profil-form__select">
                        <?php foreach ($niveaux as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($data['niveau_experience'] ?? '') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="profil-form__disponibilite">
                <label class="profil-form__toggle-label">
                    <input type="checkbox" name="disponible" value="1"
                        <?= !empty($data['disponible']) ? 'checked' : '' ?>
                        class="profil-form__toggle-input">
                    <span class="profil-form__toggle-track">
                        <span class="profil-form__toggle-thumb"></span>
                    </span>
                    <span class="profil-form__toggle-text">
                        <strong>Disponible pour recevoir des missions</strong>
                        <span>Votre profil sera visible dans les recherches</span>
                    </span>
                </label>
            </div>
        </div>

        <!-- Section 2 : Compétences -->
        <div class="profil-section">
            <div class="profil-section__header">
                <div class="profil-section__icon profil-section__icon--purple" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div>
                    <h2 class="profil-section__title">Compétences</h2>
                    <p class="profil-section__sub">Cochez les domaines dans lesquels vous intervenez.</p>
                </div>
            </div>

            <div class="profil-competences-grid">
                <?php foreach ($competences as $c): ?>
                <label class="profil-competence-chip <?= in_array((int)$c['id'], $expertCompetences) ? 'is-checked' : '' ?>">
                    <input type="checkbox"
                        name="competences[]"
                        value="<?= (int)$c['id'] ?>"
                        <?= in_array((int)$c['id'], $expertCompetences) ? 'checked' : '' ?>
                        data-competence-id="<?= (int)$c['id'] ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="profil-competence-chip__check"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?= $e($c['nom']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <?php if ($autre_competence_id): ?>
            <div class="profil-form__group profil-autres-wrap" id="competences-autres-wrap"
                style="<?= in_array($autre_competence_id, $expertCompetences) ? '' : 'display:none' ?>">
                <label class="profil-form__label" for="competences_autres">
                    Précisez vos autres compétences
                </label>
                <input type="text" id="competences_autres" name="competences_autres" maxlength="255"
                    value="<?= $e($data['competences_autres'] ?? '') ?>"
                    placeholder="Ex. Power BI, Python, Notion…"
                    class="profil-form__input">
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer formulaire -->
        <div class="profil-form__footer">
            <a href="<?= $baseUrl ?>/expert" class="btn btn-outline profil-form__cancel">
                Annuler
            </a>
            <button type="submit" class="btn btn-primary profil-form__submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer le profil
            </button>
        </div>
    </form>

</section>

<?php if ($autre_competence_id): ?>
<script>
(function() {
    var autreId  = <?= (int)$autre_competence_id ?>;
    var wrap     = document.getElementById('competences-autres-wrap');
    var chips    = document.querySelectorAll('.profil-competence-chip input[data-competence-id="' + autreId + '"]');

    /* toggle "is-checked" sur les chips au clic */
    document.querySelectorAll('.profil-competence-chip input[type="checkbox"]').forEach(function(cb) {
        cb.addEventListener('change', function() {
            cb.closest('.profil-competence-chip').classList.toggle('is-checked', cb.checked);
        });
    });

    function toggleAutres() {
        var checked = false;
        chips.forEach(function(cb) { if (cb.checked) checked = true; });
        if (wrap) wrap.style.display = checked ? '' : 'none';
    }
    chips.forEach(function(cb) { cb.addEventListener('change', toggleAutres); });
    toggleAutres();
})();
</script>
<?php endif; ?>
