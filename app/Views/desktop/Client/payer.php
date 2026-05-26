<?php
$baseUrl           = rtrim(BASE_URL ?? '', '/');
$e                 = static fn (?string $s): string => \App\Core\Security::escape((string) ($s ?? ''));
$devise            = $devise ?? 'XOF';
$montant           = (float) ($montant ?? 0);
$solde             = (float) ($solde ?? 0);
$reservation       = $reservation ?? [];
$reservationId     = (int) ($reservation['id'] ?? 0);
$touchpayOk        = !empty($touchpay_configured);
$paytechOk         = !empty($paytech_configured);
$commissionPourcent = (float) ($commission_pourcent ?? 0);
$commission        = (float) ($commission ?? 0);
$montantNetExpert  = (float) ($montant_net_expert ?? 0);

$mmInstantUrl        = $paytechOk
    ? ($baseUrl . '/paytech/paiement-session/' . $reservationId)
    : ($touchpayOk ? ($baseUrl . '/intouch/touchpay-session/' . $reservationId) : '');
$mmInstantAvailable  = $mmInstantUrl !== '';

$flashError = $_SESSION['flash_error'] ?? '';
if ($flashError !== '') {
    unset($_SESSION['flash_error']);
}

$suffisant   = $solde >= $montant && $montant > 0;
$manque      = max(0, $montant - $solde);
$coverPct    = ($montant > 0 && $solde >= 0) ? (int) min(100, floor(100 * $solde / $montant)) : 100;
$titreMission = isset($reservation['demande_titre']) ? trim((string) $reservation['demande_titre']) : '';

$providerChip = '';
if ($paytechOk) {
    $providerChip = 'Paiement mobile';
} elseif ($touchpayOk) {
    $providerChip = 'Mobile Money';
}
?>

<section class="section-desktop pay-res-page">

    <a href="<?= $e($baseUrl . '/client/reservations/' . max(1, $reservationId)) ?>" class="pay-res-page__back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour à la réservation
    </a>

    <?php if ($flashError !== ''): ?>
        <div class="alert alert-error" role="alert" style="margin-bottom:1.25rem"><?= $e($flashError) ?></div>
    <?php endif; ?>

    <header class="pay-res-page__head">
        <h1 class="pay-res-page__title">Paiement sécurisé</h1>
        <p class="pay-res-page__subtitle">
            Le montant est conservé par la plateforme (escrow) jusqu'à la fin de la mission,
            puis versé à l'expert après validation.
            <?php if ($providerChip !== ''): ?> · <?= $e($providerChip) ?><?php endif; ?>
        </p>
    </header>

    <div class="pay-res-page__grid">

        <div class="pay-res-card">
            <div class="pay-res-card__body">
                <span class="pay-res-card__label">Mission</span>
                <span class="pay-res-mission__badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Réservation #<?= (int) max(1, $reservationId) ?>
                </span>
                <p class="pay-res-mission__title"><?= $titreMission !== '' ? $e($titreMission) : 'Prestation réservée' ?></p>

                <?php if ($commissionPourcent > 0): ?>
                <dl class="pay-res-rows" style="margin-top:1rem">
                    <div class="pay-res-row">
                        <dt>Commission plateforme</dt>
                        <dd><?= $e(number_format((float) $commissionPourcent, 1, ',', ' ') . ' % · ' . number_format($commission, 0, ',', ' ') . ' ' . $devise) ?></dd>
                    </div>
                    <div class="pay-res-row">
                        <dt>Montant net expert (après mission)</dt>
                        <dd><?= $e(number_format($montantNetExpert, 0, ',', ' ') . ' ' . $devise) ?></dd>
                    </div>
                </dl>
                <?php endif; ?>
            </div>
        </div>

        <div class="pay-res-card pay-res-card--total">
            <div class="pay-res-card__hero">
                <p class="pay-res-card__hero-label">Total à payer</p>
                <p class="pay-res-card__hero-amount"><?= number_format($montant, 0, ',', ' ') ?> <span class="pay-res-card__hero-devise"><?= $e($devise) ?></span></p>
            </div>
            <div class="pay-res-card__body">

                <dl class="pay-res-rows">
                    <div class="pay-res-row">
                        <dt>Solde portefeuille</dt>
                        <dd class="<?= $suffisant ? 'is-ok' : 'is-warn' ?>"><?= $e(number_format($solde, 0, ',', ' ') . ' ' . $devise) ?></dd>
                    </div>
                    <?php if (!$suffisant && $montant > 0): ?>
                    <div class="pay-res-row">
                        <dt>À couvrir</dt>
                        <dd class="is-warn"><?= $e(number_format($manque, 0, ',', ' ') . ' ' . $devise) ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>

                <?php if ($montant > 0): ?>
                <div class="pay-res-progress">
                    <div class="pay-res-progress__track">
                        <div class="pay-res-progress__fill" style="width: <?= (int) $coverPct ?>%;" data-low="<?= !$suffisant ? '1' : '0' ?>"></div>
                    </div>
                    <div class="pay-res-progress__meta">
                        <span>Part couverte par votre solde</span>
                        <span><?= (int) $coverPct ?> %</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$suffisant && $montant > 0): ?>
                <div class="pay-res-alert" role="status">
                    <div class="pay-res-alert__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div>
                        <p class="pay-res-alert__title">Solde insuffisant</p>
                        <p class="pay-res-alert__text">
                            Encore <strong><?= $e(number_format($manque, 0, ',', ' ') . ' ' . $devise) ?></strong> nécessaires.
                            <?= $paytechOk
                                ? 'Rechargez votre portefeuille puis confirmez ici, ou réglez directement le montant exact de la mission.'
                                : ($touchpayOk
                                    ? 'Vous pouvez régler cette mission tout de suite via Mobile Money, ou créditer votre portefeuille.'
                                    : 'Créditez votre portefeuille pour continuer.')
                            ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($suffisant): ?>
                <div class="pay-res-alert pay-res-alert--ok" role="status">
                    <div class="pay-res-alert__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <p class="pay-res-alert__title">Prêt à confirmer</p>
                        <p class="pay-res-alert__text">Après paiement depuis votre solde, la mission sera en escrow jusqu'à sa clôture.</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$suffisant && $montant > 0): ?>
                <div class="pay-res-actions">
                    <?php if ($mmInstantAvailable): ?>
                    <a href="<?= $e($mmInstantUrl) ?>" class="pay-res-btn <?= $paytechOk ? 'pay-res-btn--paytech' : 'pay-res-btn--mm' ?>">
                        <?php if ($paytechOk): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        Payer tout de suite (Mobile Money)
                        <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Payer tout de suite (Mobile Money)
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($paytechOk && $mmInstantAvailable): ?>
                    <a href="<?= $e($baseUrl . '/paytech/depot') ?>" class="pay-res-btn pay-res-btn--ghost">Recharger un autre montant</a>
                    <?php endif; ?>
                    <?php if ($paytechOk && !$mmInstantAvailable): ?>
                    <a href="<?= $e($baseUrl . '/paytech/depot') ?>" class="pay-res-btn pay-res-btn--paytech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        Recharger via Mobile Money
                    </a>
                    <?php endif; ?>
                    <?php if (!$paytechOk && !$mmInstantAvailable && $touchpayOk): ?>
                    <a href="<?= $e($baseUrl . '/intouch/touchpay-session/' . $reservationId) ?>" class="pay-res-btn pay-res-btn--mm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Payer tout de suite (Mobile Money)
                    </a>
                    <?php endif; ?>
                    <a href="<?= $e($baseUrl . '/client/portefeuille') ?>" class="pay-res-btn pay-res-btn--ghost">Voir mon portefeuille</a>
                    <a href="<?= $e($baseUrl . '/client/reservations/' . max(1, $reservationId)) ?>" class="pay-res-btn pay-res-btn--ghost">Annuler</a>
                </div>
                <?php if ($paytechOk): ?>
                    <p class="pay-res-hint"><?= $mmInstantAvailable ? 'Ou rechargez d’abord le portefeuille sur une autre page puis revenez confirmer depuis le solde.' : 'Après paiement, revenez sur cette page puis confirmez avec votre solde.' ?></p>
                <?php elseif ($touchpayOk && $mmInstantAvailable): ?>
                    <div class="pay-res-hint" style="margin-top:1rem">
                        Paiement Mobile Money sur la page sécurisée du fournisseur configuré.
                    </div>
                <?php endif; ?>
                <?php else: ?>
                <form method="post" action="<?= $e($baseUrl . '/client/payer/' . $reservationId) ?>" style="margin-top:1rem">
                    <?= \App\Core\Security::getCsrfField() ?>
                    <div class="pay-res-actions">
                        <button type="submit" class="pay-res-btn pay-res-btn--primary-client">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Débloquer <?= $e(number_format($montant, 0, ',', ' ') . ' ' . $devise) ?> depuis le solde
                        </button>
                        <a href="<?= $e($baseUrl . '/client/reservations/' . max(1, $reservationId)) ?>" class="pay-res-btn pay-res-btn--ghost">Annuler</a>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="pay-res-trust">
        <div class="pay-res-trust__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </div>
        <p style="margin:0">
            <strong>Escrow Globalo.</strong>
            Orange Money · Moov · Wave où disponibles.&nbsp;
            <?php if ($paytechOk): ?>Paiement mobile disponible pour les recharges.&nbsp;<?php else: ?>Paiement Mobile Money.&nbsp;<?php endif; ?>
        </p>
    </div>
</section>
