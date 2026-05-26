<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$reservation = $reservation ?? [];
$reservationId = (int)($reservation['id'] ?? 0);
$canStart = $can_start ?? false;
?>
<section class="section-desktop">
    <h1>Session — Réservation #<?= $reservationId ?></h1>
    <p><a href="<?= $baseUrl ?>/messages/conversation/<?= $reservationId ?>" class="btn btn-outline">← Retour à la conversation</a></p>
    <div class="card-desktop" style="max-width:500px;margin:2rem 0;padding:2rem;text-align:center;">
        <h2>Appel vidéo / partage d'écran</h2>
        <p>La visioconférence et le partage d'écran seront disponibles dans une prochaine version.</p>
        <p>En attendant, utilisez la <a href="<?= $baseUrl ?>/messages/conversation/<?= $reservationId ?>">messagerie</a> pour échanger avec <?= $reservation['statut'] === 'en_cours' ? 'votre expert' : 'l\'expert' ?>.</p>
        <?php if ($canStart): ?>
        <p class="text-muted">Cette session est réservée pour la demande : <?= \App\Core\Security::escape($reservation['demande_titre'] ?? '') ?>.</p>
        <?php endif; ?>
    </div>
</section>
