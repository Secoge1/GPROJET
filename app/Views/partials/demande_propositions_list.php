<?php
/**
 * Liste des propositions reçues sur une demande (vue client).
 * @var list<array<string,mixed>> $propositions
 * @var string $client_base_path
 * @var bool $can_choose
 */
$propositions = $propositions ?? [];
$canChoose = !empty($can_choose);
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $client_base_path ?? '/client';
$csrf = \App\Core\Security::getCsrfField();

if (empty($propositions)) {
    return;
}
?>
<section class="prop-list" aria-labelledby="prop-list-title">
    <p class="prop-list__notice">
        Les propositions sont des offres de service : elles <strong>ne clôturent pas</strong> votre demande.
        Seule votre confirmation, après la prestation reçue, marquera la demande comme terminée.
    </p>
    <h3 id="prop-list-title" class="prop-list__title">
        Propositions reçues
        <span class="prop-list__count"><?= count($propositions) ?></span>
    </h3>
    <ul class="prop-list__items">
        <?php foreach ($propositions as $p):
            $statut = (string) ($p['statut'] ?? 'en_attente');
            $expertNom = trim(($p['expert_prenom'] ?? '') . ' ' . ($p['expert_nom'] ?? ''));
            $initiales = strtoupper(substr($expertNom ?: 'E', 0, 1));
        ?>
        <li class="prop-card prop-card--<?= $e($statut) ?>">
            <div class="prop-card__head">
                <div class="prop-card__avatar" aria-hidden="true"><?= $e($initiales) ?></div>
                <div class="prop-card__who">
                    <p class="prop-card__name"><?= $e($expertNom ?: 'Expert') ?></p>
                    <?php if (!empty($p['expert_titre'])): ?>
                    <p class="prop-card__role"><?= $e($p['expert_titre']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="prop-card__badge prop-card__badge--<?= $e($statut) ?>">
                    <?= $statut === 'en_attente' ? 'En attente' : ($statut === 'acceptee' ? 'Proposition retenue' : ($statut === 'refusee' ? 'Refusée' : 'Retirée')) ?>
                </span>
            </div>
            <?php if (!empty($p['presentation'])): ?>
            <p class="prop-card__pitch"><strong><?= $e($p['presentation']) ?></strong></p>
            <?php endif; ?>
            <div class="prop-card__meta">
                <span><strong><?= number_format((float)($p['tarif_propose'] ?? 0), 0, ',', ' ') ?> FCFA</strong></span>
                <span>Délai : <?= (int)($p['delai_jours'] ?? 0) ?> jour<?= (int)($p['delai_jours'] ?? 0) > 1 ? 's' : '' ?></span>
                <?php if (!empty($p['competences_cles'])): ?>
                <span><?= $e($p['competences_cles']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($p['message'])): ?>
            <p class="prop-card__message"><?= nl2br($e($p['message'])) ?></p>
            <?php endif; ?>
            <?php if ($canChoose && $statut === 'en_attente'): ?>
            <div class="prop-card__actions">
                <form method="post" action="<?= $baseUrl . $bp ?>/accepter-proposition/<?= (int)$p['id'] ?>" class="prop-card__form">
                    <?= $csrf ?>
                    <button type="submit" class="cl-btn cl-btn--amber cl-btn--sm" onclick="return confirm('Sélectionner cet expert et créer une réservation ? Votre demande restera ouverte jusqu\'à confirmation de la résolution après la prestation.');">
                        Choisir cet expert
                    </button>
                </form>
                <form method="post" action="<?= $baseUrl . $bp ?>/refuser-proposition/<?= (int)$p['id'] ?>" class="prop-card__form">
                    <?= $csrf ?>
                    <button type="submit" class="cl-btn cl-btn--outline cl-btn--sm">Refuser</button>
                </form>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
