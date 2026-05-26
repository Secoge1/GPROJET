<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$reservation = $reservation ?? [];
$reservationId = (int)($reservation['id'] ?? 0);
?>
<section class="card-mobile">
    <h1>Session #<?= $reservationId ?></h1>
    <p><a href="<?= $baseUrl ?>/messages/conversation/<?= $reservationId ?>" class="btn-mobile btn-outline">← Conversation</a></p>
    <p>Visioconférence bientôt disponible. Utilisez la messagerie en attendant.</p>
</section>
