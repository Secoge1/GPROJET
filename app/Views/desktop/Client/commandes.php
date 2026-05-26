<?php $baseUrl = rtrim(BASE_URL ?? '', '/'); ?>
<section class="section-desktop">
    <h1>Mes commandes</h1>
    <p class="text-muted">Vos commandes correspondent à vos réservations de sessions avec les experts.</p>
    <?php if (empty($reservations)): ?>
        <p>Aucune commande.</p>
    <?php else: ?>
        <ul class="list-reservations">
            <?php foreach ($reservations as $r): ?>
                <li>
                    <strong><?= \App\Core\Security::escape($r['expert_titre'] ?? $r['demande_titre'] ?? '') ?></strong>
                    — <?= \App\Core\Security::escape($r['statut']) ?>
                    — <?= number_format((float)$r['montant_total'], 2, ',', ' ') ?> <?= \App\Core\Security::escape(devise()) ?>
                    — <a href="<?= $baseUrl ?>/client/reservations/<?= (int)$r['id'] ?>">Détail</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
