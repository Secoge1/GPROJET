<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$reservation = $reservation ?? [];
$errors    = $errors ?? [];
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero cl-page__hero--narrow">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client/reservations/<?= (int)$reservation['id'] ?>" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Réservation
            </a>
            <h1 class="cl-page__title">Noter l'expert</h1>
            <p class="cl-page__sub">Mission : <?= $e($reservation['demande_titre'] ?? '') ?></p>
        </div>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="cl-form" style="max-width:560px">
        <div class="cl-card cl-form__section">

            <!-- Sélecteur étoiles -->
            <div class="cl-form__field">
                <label class="cl-form__label">Votre note globale <span class="cl-form__required">*</span></label>
                <div class="cl-star-picker" role="radiogroup" aria-label="Note de 1 à 5 étoiles">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="cl-star-picker__btn" data-value="<?= $i ?>" aria-label="<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>">★</button>
                    <?php endfor; ?>
                </div>
                <p class="cl-star-picker__label" id="star-label">Cliquez pour choisir</p>
            </div>

            <form method="post" action="<?= $baseUrl ?>/client/noter/<?= (int)$reservation['id'] ?>">
                <?= $csrfField ?>
                <input type="hidden" name="note" id="note" value="" required>

                <div class="cl-form__field">
                    <label for="commentaire" class="cl-form__label">Commentaire <span class="cl-form__optional">(optionnel)</span></label>
                    <textarea name="commentaire" id="commentaire" rows="5"
                              placeholder="Décrivez votre expérience avec cet expert — qualité du travail, communication, respect des délais…"
                              class="cl-form__textarea"></textarea>
                </div>

                <div class="cl-form__footer">
                    <a href="<?= $baseUrl ?>/client/reservations" class="cl-btn cl-btn--outline">Annuler</a>
                    <button type="submit" class="cl-btn cl-btn--amber" id="submit-note" disabled>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Envoyer l'avis
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var input  = document.getElementById('note');
    var submit = document.getElementById('submit-note');
    var lbl    = document.getElementById('star-label');
    var stars  = document.querySelectorAll('.cl-star-picker__btn');
    var labels = ['', 'Très décevant', 'Décevant', 'Correct', 'Bien', 'Excellent !'];
    var selected = 0;

    function paint(hover) {
        var n = hover || selected;
        stars.forEach(function (s, i) {
            s.classList.toggle('cl-star-picker__btn--on',  i < n);
            s.classList.toggle('cl-star-picker__btn--hover', hover && i < hover);
        });
        if (lbl) lbl.textContent = n ? labels[n] : 'Cliquez pour choisir';
    }

    stars.forEach(function (btn) {
        btn.addEventListener('mouseenter', function () { paint(+this.getAttribute('data-value')); });
        btn.addEventListener('mouseleave', function () { paint(0); });
        btn.addEventListener('click', function () {
            selected = +this.getAttribute('data-value');
            input.value = selected;
            if (submit) submit.disabled = false;
            paint(0);
        });
    });
})();
</script>
