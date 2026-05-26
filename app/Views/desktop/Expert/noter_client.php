<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$csrfField = \App\Core\Security::getCsrfField();
$reservation = $reservation ?? [];
$errors = $errors ?? [];
?>
<section class="section-desktop section-form page-expert page-expert-noter">
    <div class="page-expert__header">
        <a href="<?= $baseUrl ?>/expert/reservations" class="page-expert__back">← Mes réservations</a>
        <h1 class="page-expert__title">Noter le client</h1>
        <p class="page-expert__subtitle">Réservation : <?= $e($reservation['demande_titre'] ?? '') ?> — Client : <?= $e(trim(($reservation['client_prenom'] ?? $reservation['prenom'] ?? '') . ' ' . ($reservation['client_nom'] ?? $reservation['nom'] ?? ''))) ?></p>
    </div>
    <div class="page-expert__card section-form__card">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= $baseUrl ?>/expert/noter-client/<?= (int)($reservation['id'] ?? 0) ?>" class="form-desktop form-rating">
            <?= $csrfField ?>
            <div class="form-group">
                <label>Note (1 à 5 étoiles)</label>
                <div class="rating-stars" aria-label="Choisir une note de 1 à 5 étoiles">
                    <button type="button" class="rating-star" data-value="1">★</button>
                    <button type="button" class="rating-star" data-value="2">★</button>
                    <button type="button" class="rating-star" data-value="3">★</button>
                    <button type="button" class="rating-star" data-value="4">★</button>
                    <button type="button" class="rating-star" data-value="5">★</button>
                </div>
                <input type="hidden" name="note" id="note" value="" required>
            </div>
            <div class="form-group">
                <label for="commentaire">Commentaire (optionnel)</label>
                <textarea name="commentaire" id="commentaire" rows="4" placeholder="Décrivez votre expérience avec ce client..."></textarea>
            </div>
            <div class="form-actions">
                <a href="<?= $baseUrl ?>/expert/reservations" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
            </div>
        </form>
    </div>
</section>
<script>
(function(){
    var form = document.querySelector('.form-rating');
    if (!form) return;
    var input = form.querySelector('input[name="note"]');
    var stars = form.querySelectorAll('.rating-star');
    stars.forEach(function(btn){
        btn.addEventListener('click', function(){
            var v = parseInt(btn.getAttribute('data-value'), 10);
            input.value = v;
            stars.forEach(function(s, i){ s.classList.toggle('active', i < v); });
        });
    });
})();
</script>
