<?php $baseUrl = rtrim(BASE_URL ?? '', '/'); ?>
<section class="section-desktop">
    <h1>Mes prestations</h1>
    <p class="text-muted">Sessions que vous avez réalisées (réservations terminées).</p>
    <?php if (empty($prestations)): ?>
        <p>Aucune prestation pour le moment.</p>
    <?php else: ?>
        <ul class="list-reservations">
            <?php foreach ($prestations as $p): ?>
                <li>
                    <strong><?= \App\Core\Security::escape($p['demande_titre'] ?? '') ?></strong>
                    — Client : <?= \App\Core\Security::escape(trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''))) ?>
                    — <?= number_format((float)($p['montant_total'] ?? 0), 2, ',', ' ') ?> <?= \App\Core\Security::escape(devise()) ?>
                    — <?= \App\Core\Security::escape($p['date_debut_prevue'] ?? '') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
