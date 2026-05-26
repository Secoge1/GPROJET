<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$reservation = $reservation ?? [];
$montant     = (float) ($montant ?? 0);
$solde       = (float) ($solde ?? 0);
$devise      = $devise ?? 'XOF';
$reservationId = (int) ($reservation['id'] ?? 0);
$bp = $client_base_path ?? '/client';
$suffisant   = $solde >= $montant && $montant > 0;
$touchpayOk  = !empty($touchpay_configured);
$paytechOk   = !empty($paytech_configured);
$manque      = max(0.0, $montant - $solde);
$coverPct    = ($montant > 0 && $solde >= 0) ? (int) min(100, floor(100 * $solde / $montant)) : 100;
$commissionPourcent = (float) ($commission_pourcent ?? 0);
$commission        = (float) ($commission ?? 0);
$titreMission      = isset($reservation['demande_titre']) ? trim((string) $reservation['demande_titre']) : '';

$mmInstantUrl        = $paytechOk
    ? ($baseUrl . '/paytech/paiement-session/' . $reservationId)
    : ($touchpayOk ? ($baseUrl . '/intouch/touchpay-session/' . $reservationId) : '');
$mmInstantAvailable  = $mmInstantUrl !== '';

$gatewayLabel        = $paytechOk ? 'Service de paiement' : ($touchpayOk ? 'Mobile Money' : '');
$ctaPrimaryLabel    = $paytechOk ? 'Compléter le solde' : 'Payer en Mobile Money';
$ctaPrimarySub      = $paytechOk
    ? 'Montant mission · passerelle de paiement sécurisée'
    : 'Redirection paiement agrégé sécurisé';
$ctaAltLabel        = $paytechOk ? 'Montant différent au choix' : '';
$ctaAltHint         = $paytechOk ? 'Dépôt portefeuille sur une autre somme si besoin' : '';

$flashShown = !empty($_SESSION['flash_error']);
$flashMsg   = $_SESSION['flash_error'] ?? '';
if ($flashShown) {
    unset($_SESSION['flash_error']);
}
?>

<article class="pay-cli-res pay-cli-res-mob">

    <a href="<?= $e($baseUrl . $bp . '/reservations/' . max(1, $reservationId)) ?>" class="pay-cli-res-mob__back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Réservation
    </a>

    <header class="pay-cli-res-mob__head">
        <h1 class="pay-cli-res-mob__title">Paiement sécurisé</h1>
        <p class="pay-cli-res-mob__sub">
            Les fonds sont bloqués en <strong>escrow</strong> jusqu’à la fin de mission<?php if ($gatewayLabel !== ''): ?> · <?= $e($gatewayLabel) ?><?php endif; ?>.
        </p>
    </header>

    <?php if ($flashShown): ?>
    <div class="pay-cli-res-mob__flash" role="alert"><?= $e((string) $flashMsg) ?></div>
    <?php endif; ?>

    <section class="pay-cli-res-mob__card">
        <div class="pay-cli-res-mob__hero">
            <span class="pay-cli-res-mob__hero-tag">À régler pour la mission</span>
            <p class="pay-cli-res-mob__hero-sum"><?= number_format($montant, 0, ',', ' ') ?> <span class="pay-cli-res-mob__hero-ccy"><?= $e($devise) ?></span></p>
        </div>
        <div class="pay-cli-res-mob__body">
            <span class="pay-cli-res-mob__sec-lbl">Mission</span>
            <p class="pay-cli-res-mob__mission"><?= $titreMission !== '' ? $e($titreMission) : $e('Réservation #' . max(1, $reservationId)) ?></p>
            <?php if ($commissionPourcent > 0): ?>
            <p class="pay-cli-res-mob__comm">Commission plateforme <?= $e(number_format($commissionPourcent, 1, ',', ' ')) ?> % · <?= $e(number_format($commission, 0, ',', ' ') . ' ' . $devise) ?></p>
            <?php endif; ?>

            <?php if ($montant > 0): ?>
            <div class="pay-cli-res-mob__progress-wrap">
                <div class="pay-cli-res-mob__track" aria-hidden="true"><div class="pay-cli-res-mob__fill<?= !$suffisant ? ' is-low' : '' ?>" style="width:<?= (int) $coverPct ?>%;"></div></div>
                <div class="pay-cli-res-mob__progress-meta">
                    <span>Couverture portefeuille</span>
                    <span><?= (int) $coverPct ?> %</span>
                </div>
            </div>
            <?php endif; ?>

            <dl class="pay-cli-res-mob__balances">
                <div class="pay-cli-res-mob__bal-row">
                    <dt>Votre portefeuille</dt>
                    <dd class="<?= $suffisant ? 'is-ok' : 'is-warn' ?>"><?= number_format($solde, 0, ',', ' ') ?> <?= $e($devise) ?></dd>
                </div>
                <?php if (!$suffisant && $montant > 0): ?>
                <div class="pay-cli-res-mob__bal-row pay-cli-res-mob__bal-row--gap">
                    <dt>À compléter</dt>
                    <dd class="is-bad"><?= number_format($manque, 0, ',', ' ') ?> <?= $e($devise) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </section>

    <?php if (!$suffisant && $montant > 0 && ($paytechOk || $touchpayOk)): ?>
    <div class="pay-cli-res-mob__mm-strip" aria-label="Moyens de paiement courants">
        <?php if ($paytechOk): ?>
        <?php
        $mm_logo_size       = 'xs';
        $mm_logo_wrap_class = 'mm-operator-logos pay-cli-res-mob-mm';
        $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.45rem;';
        require APP_PATH . '/Views/partials/mm_operator_logos.php';
        ?>
        <span class="pay-cli-res-mob__mm-extra">carte ou autres suivant pays · service de paiement</span>
        <?php elseif ($touchpayOk): ?>
        <?php
        $mm_logo_size       = 'xs';
        $mm_logo_wrap_class = 'mm-operator-logos pay-cli-res-mob-mm';
        $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.45rem;';
        require APP_PATH . '/Views/partials/mm_operator_logos.php';
        ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!$suffisant && $montant > 0): ?>
    <div class="pay-cli-res-mob__alert pay-cli-res-mob__alert--warn" role="status">
        <div class="pay-cli-res-mob__alert-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
        </div>
        <div>
            <p class="pay-cli-res-mob__alert-title">Solde portefeuille insuffisant</p>
            <p class="pay-cli-res-mob__alert-txt"><?php if ($paytechOk): ?>Créditez au moins <strong><?= $e(number_format($manque, 0, ',', ' ') . ' ' . $devise) ?></strong> (ou le montant exact de la mission), puis confirmez sur cette page.<?php elseif ($touchpayOk): ?>Ajoutez des fonds en Mobile Money ou rechargez votre portefeuille, puis revenez ici pour confirmer.<?php else: ?>Rechargez votre portefeuille pour continuer.<?php endif; ?></p>
        </div>
    </div>

    <nav class="pay-cli-res-mob__actions" aria-label="Étapes de paiement">
        <?php if ($mmInstantAvailable): ?>
        <a href="<?= $e($mmInstantUrl) ?>" class="pay-cli-res-mob__btn pay-cli-res-mob__btn--primary">
            <span class="pay-cli-res-mob__btn-main"><?= $e($ctaPrimaryLabel) ?></span>
            <span class="pay-cli-res-mob__btn-hint"><?= $e($ctaPrimarySub) ?></span>
        </a>
        <?php endif; ?>
        <?php if ($paytechOk): ?>
        <a href="<?= $e($baseUrl . '/paytech/depot') ?>" class="pay-cli-res-mob__btn pay-cli-res-mob__btn--secondary">
            <span class="pay-cli-res-mob__btn-main"><?= $mmInstantAvailable ? $e($ctaAltLabel ?: 'Montant différent au choix') : 'Recharger le portefeuille' ?></span>
            <?php if ($ctaAltHint !== '' && $mmInstantAvailable): ?>
            <span class="pay-cli-res-mob__btn-hint"><?= $e($ctaAltHint) ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (!$paytechOk && $touchpayOk && !$mmInstantAvailable): ?>
        <a href="<?= $e($baseUrl . '/intouch/touchpay-session/' . $reservationId) ?>" class="pay-cli-res-mob__btn pay-cli-res-mob__btn--primary">
            <span class="pay-cli-res-mob__btn-main">Payer avec Mobile Money</span>
            <span class="pay-cli-res-mob__btn-hint">Redirection TouchPay agrégé</span>
        </a>
        <?php endif; ?>

        <div class="pay-cli-res-mob__row-links">
            <a href="<?= $e($baseUrl . $bp . '/portefeuille') ?>" class="pay-cli-res-mob__link-outline">Voir le portefeuille</a>
            <a href="<?= $e($baseUrl . $bp . '/reservations/' . max(1, $reservationId)) ?>" class="pay-cli-res-mob__link-text">Retour réservation</a>
        </div>
    </nav>

    <?php elseif ($montant <= 0): ?>
    <p class="pay-cli-res-mob__invalid">Montant invalide. <a href="<?= $e($baseUrl . $bp . '/reservations/' . max(1, $reservationId)) ?>">Retour</a></p>
    <?php else: ?>
    <div class="pay-cli-res-mob__alert pay-cli-res-mob__alert--ok" role="status">
        <div class="pay-cli-res-mob__alert-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
            <p class="pay-cli-res-mob__alert-title">Portefeuille suffisant</p>
            <p class="pay-cli-res-mob__alert-txt">Confirmation : les fonds seront placés en escrow jusqu’à clôture de la mission.</p>
        </div>
    </div>
    <form method="post" action="<?= $e($baseUrl . $bp . '/payer/' . $reservationId) ?>" class="pay-cli-res-mob__form-shell">
        <?= \App\Core\Security::getCsrfField() ?>
        <button type="submit" class="pay-cli-res-mob__btn pay-cli-res-mob__btn--primary pay-cli-res-mob__btn--submit">
            <span class="pay-cli-res-mob__btn-main">Confirmer le paiement escrow</span>
            <span class="pay-cli-res-mob__btn-hint"><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?> depuis votre solde</span>
        </button>
        <a href="<?= $e($baseUrl . $bp . '/reservations/' . max(1, $reservationId)) ?>" class="pay-cli-res-mob__link-centered">Annuler</a>
    </form>
    <?php endif; ?>

    <footer class="pay-cli-res-mob__trust">
        <span class="pay-cli-res-mob__trust-badge" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <p class="pay-cli-res-mob__trust-text">Escrow GLOBALO&nbsp;: les fonds ne sont libérés qu’après validation de la mission.</p>
    </footer>
</article>
