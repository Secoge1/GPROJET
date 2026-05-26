<?php
/**
 * Propositions professeurs sur un exercice (vue étudiant).
 * @var list<array<string,mixed>> $propositions
 * @var string $base_path
 * @var bool $can_choose
 */
$propositions = $propositions ?? [];
$canChoose = !empty($can_choose);
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $base_path ?? '/etudiant';
$csrf = \App\Core\Security::getCsrfField();

if (empty($propositions)) {
    return;
}
?>
<section class="prop-list" aria-labelledby="prop-ex-list-title">
    <p class="prop-list__notice">
        Les propositions sont des offres de service : elles <strong>ne marquent pas</strong> votre exercice comme corrigé ou résolu.
        Seule votre confirmation, après la correction reçue, clôturera la demande.
    </p>
    <h3 id="prop-ex-list-title" class="prop-list__title">
        Propositions de professeurs
        <span class="prop-list__count"><?= count($propositions) ?></span>
    </h3>
    <ul class="prop-list__items">
        <?php foreach ($propositions as $p):
            $statut = (string) ($p['statut'] ?? 'en_attente');
            $profNom = trim(($p['prof_prenom'] ?? '') . ' ' . ($p['prof_nom'] ?? ''));
            $initiales = strtoupper(substr($profNom ?: 'P', 0, 1));
        ?>
        <li class="prop-card prop-card--<?= $e($statut) ?>">
            <div class="prop-card__head">
                <div class="prop-card__avatar" aria-hidden="true"><?= $e($initiales) ?></div>
                <div class="prop-card__who">
                    <p class="prop-card__name"><?= $e($profNom ?: 'Professeur') ?></p>
                    <?php if (!empty($p['prof_titre'])): ?>
                    <p class="prop-card__role"><?= $e($p['prof_titre']) ?></p>
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
            </div>
            <?php if (!empty($p['message'])): ?>
            <p class="prop-card__message"><?= nl2br($e($p['message'])) ?></p>
            <?php endif; ?>
            <?php if ($canChoose && $statut === 'en_attente'): ?>
            <div class="prop-card__actions">
                <form method="post" action="<?= $baseUrl . $bp ?>/accepter-proposition-exercice/<?= (int)$p['id'] ?>" class="prop-card__form">
                    <?= $csrf ?>
                    <button type="submit" class="etd-btn etd-btn--primary etd-btn--sm" onclick="return confirm('Choisir ce professeur ? Votre exercice restera ouvert jusqu\'à votre confirmation après réception de la correction.');">
                        Choisir ce professeur
                    </button>
                </form>
                <form method="post" action="<?= $baseUrl . $bp ?>/refuser-proposition-exercice/<?= (int)$p['id'] ?>" class="prop-card__form">
                    <?= $csrf ?>
                    <button type="submit" class="etd-btn etd-btn--ghost etd-btn--sm">Refuser</button>
                </form>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
