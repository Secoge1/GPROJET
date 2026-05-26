<?php
/**
 * GLOBALO — PayTech crédit portefeuille pour une réservation — bureau
 */
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$e               = static fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$reservationId   = (int) ($reservation_id ?? 0);
$payerBackUrl    = (string) ($payer_back_url ?? ($baseUrl . '/client/payer/' . max(1, $reservationId)));
$missionTitre    = trim((string) ($mission_titre ?? ''));
$montant         = (float) ($montant ?? 0);
$commission      = (float) ($commission ?? 0);
$total           = (float) ($total ?? $montant);
$devise          = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$paytech_ctx     = (string) ($paytech_context_hint ?? '');
if ($paytech_ctx === '') {
    $paytech_ctx = 'Sur service de paiement, choisissez votre moyen de paiement (Mobile Money ou carte selon votre pays). Devise : XOF.';
}
?>
<section class="paytech-checkout-page">
    <a href="<?= $e($payerBackUrl) ?>" class="paytech-checkout-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au paiement de la réservation
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="paytech-checkout-flash"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-paiement-session') ?>" id="form-paytech-session" class="paytech-checkout-page-form">
        <input type="hidden" name="reservation_id" value="<?= $e((string) $reservationId) ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">

        <div class="paytech-checkout-card">
            <header class="paytech-checkout-hero">
                <h1 class="paytech-checkout-hero__title">
                    Crédit mission · Réservation #<?= $e((string) $reservationId) ?>
                </h1>
                <p class="paytech-checkout-hero__sub">
                    Service de paiement <span aria-hidden="true">·</span> service de paiement — portefeuille GLOBALO
                </p>
            </header>

            <?php if ($missionTitre !== ''): ?>
                <p class="paytech-session-mission-title" style="margin:-0.25rem 1.5rem 1rem;font-size:.9rem;color:#475569;"><?= $e($missionTitre) ?></p>
            <?php endif; ?>

            <div class="paytech-checkout-logos" aria-labelledby="paytech-session-logos-label">
                <span id="paytech-session-logos-label" class="paytech-checkout-logos__caption">Opérateurs Mobile Money courants</span>
                <div class="paytech-checkout-logos__strip">
                    <?php
                    $mm_logo_size       = 'sm';
                    $mm_logo_wrap_class = 'mm-operator-logos paytech-checkout-logos-mm';
                    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;margin:0;';
                    require APP_PATH . '/Views/partials/mm_operator_logos.php';
                    ?>
                </div>
                <span class="paytech-checkout-cards-hint">+ carte bancaire et autres moyens sur Service de paiement</span>
            </div>

            <div class="paytech-checkout-sum">
                <div class="paytech-checkout-sum__row">
                    <span>Montant mission</span>
                    <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
                <?php if ($commission > 0): ?>
                <div class="paytech-checkout-sum__row">
                    <span>Frais de service</span>
                    <span><?= number_format($commission, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
                <?php endif; ?>
                <div class="paytech-checkout-sum__total">
                    <span>Total à payer</span>
                    <span class="paytech-checkout-sum__amount"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
            </div>

            <div class="paytech-checkout-body">
                <div class="paytech-checkout-info" role="region" aria-label="Informations paiement">
                    <div class="paytech-checkout-info__row">
                        <?php if (!empty($paytech_country_iso)): ?>
                        <span class="paytech-checkout-badge"><?= $e(strtoupper((string) $paytech_country_iso)) ?></span>
                        <?php endif; ?>
                        <p class="paytech-checkout-info__lead"><?= $e($paytech_ctx) ?></p>
                    </div>
                </div>

                <?php
                $paytech_phone_variant    = 'desktop';
                $paytech_phone_id_prefix  = 'pt-sess-desk';
                require APP_PATH . '/Views/partials/paytech_phone_fields.php';
                ?>

                <button type="submit" id="btn-payer-session" class="paytech-checkout-submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Payer <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
                </button>

                <p class="paytech-checkout-foot">
                    Redirection vers la passerelle sécurisée Service de paiement.
                </p>
            </div>
        </div>
    </form>
</section>

<script>
document.getElementById('form-paytech-session').addEventListener('submit', function() {
    var btn = document.getElementById('btn-payer-session');
    if (btn) { btn.disabled = true; btn.textContent = 'Redirection…'; }
});
</script>
