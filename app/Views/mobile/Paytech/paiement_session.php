<?php
/**
 * GLOBALO — PayTech crédit portefeuille pour une réservation (mission) — mobile
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
    $paytech_ctx = 'Sur service de paiement, choisissez votre opérateur (Orange Money, Moov Money, Wave…) ou une carte. Devise : XOF.';
}
?>
<div class="paytech-checkout-mob paytech-checkout-mob--session">
    <div class="paytech-checkout-mob__head">
        <a href="<?= $e($payerBackUrl) ?>" class="paytech-checkout-mob__back" aria-label="Retour">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div class="paytech-checkout-mob__title-wrap">
            <h1 class="paytech-checkout-mob__title">Mission · Service de paiement</h1>
            <p class="paytech-checkout-mob__sub">Crédit portefeuille — Mobile Money ou carte sur service de paiement</p>
        </div>
    </div>

    <ol class="paytech-checkout-mob-steps paytech-checkout-mob-steps--compact" aria-label="Étapes du paiement">
        <li><strong>1</strong> Total et numéro Mobile Money ci-dessous.</li>
        <li><strong>2</strong> Redirection Service de paiement.</li>
        <li><strong>3</strong> Choix opérateur et validation téléphone.</li>
    </ol>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mobile-alert mobile-alert--success" style="margin-bottom:1rem">
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mobile-alert mobile-alert--error" style="margin-bottom:1rem">
        <?= $e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-paiement-session') ?>" id="form-paytech-session-mob" class="paytech-checkout-mob-formwrap">
        <input type="hidden" name="reservation_id" value="<?= $e((string) $reservationId) ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">

        <div class="paytech-checkout-mob-card">
            <header class="paytech-checkout-mob-card__hero">
                <span class="paytech-checkout-mob-card__lbl">RÉSERVATION #<?= $e((string) $reservationId) ?></span>
                <p class="paytech-checkout-mob-card__amt"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></p>
                <span class="paytech-checkout-mob-card__hint">crédit portefeuille · frais inclus · redirection service de paiement</span>
            </header>

            <?php if ($missionTitre !== ''): ?>
            <p class="paytech-session-mission-title" style="margin:0 1rem .75rem;font-size:.82rem;line-height:1.35;color:var(--text-muted, #475569);"><?= $e($missionTitre) ?></p>
            <?php endif; ?>

            <div class="paytech-checkout-mob-logos">
                <?php if (!empty($paytech_country_iso)): ?>
                <span class="paytech-checkout-mob-logos__country"><?= $e(strtoupper((string) $paytech_country_iso)) ?></span>
                <?php endif; ?>
                <?php
                $mm_logo_size       = 'sm';
                $mm_logo_wrap_class = 'mm-operator-logos paytech-checkout-mob-mm';
                $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem;margin:.35rem 0 0;';
                require APP_PATH . '/Views/partials/mm_operator_logos.php';
                ?>
                <span class="paytech-checkout-mob-logos__more">Orange Money · Moov Money · Wave · carte · autres</span>
            </div>

            <div class="paytech-checkout-mob-sum">
                <div class="paytech-checkout-mob-sum__row">
                    <span>Montant mission</span>
                    <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
                <?php if ($commission > 0): ?>
                <div class="paytech-checkout-mob-sum__row">
                    <span>Frais</span>
                    <span><?= number_format($commission, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
                <?php endif; ?>
                <div class="paytech-checkout-mob-sum__total">
                    <span>Total</span>
                    <span><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>
            </div>

            <div class="paytech-checkout-mob-info">
                <p class="paytech-checkout-mob-info__lead"><?= $e($paytech_ctx) ?></p>
            </div>

            <?php
            $paytech_phone_variant    = 'mobile';
            $paytech_phone_id_prefix  = 'pt-sess-mob';
            require APP_PATH . '/Views/partials/paytech_phone_fields.php';
            ?>
        </div>

        <button type="submit" id="btn-payer-session-mob" class="btn-mobile btn-primary paytech-checkout-mob-submit" aria-busy="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <span class="paytech-checkout-mob-submit__txt">Payer <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
        </button>
    </form>

    <p class="paytech-checkout-mob-foot">Connexion sécurisée (HTTPS) vers Service de paiement — vous quitterez momentanément l’app Globalo.</p>
</div>

<script>
(function () {
    var form = document.getElementById('form-paytech-session-mob');
    if (!form) return;
    form.addEventListener('submit', function () {
        var btn = document.getElementById('btn-payer-session-mob');
        if (!btn) return;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        var txt = btn.querySelector('.paytech-checkout-mob-submit__txt');
        if (txt) {
            txt.textContent = 'Redirection vers Service de paiement…';
        }
    });
})();
</script>
