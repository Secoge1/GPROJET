<?php
/**
 * GLOBALO — Checkout widget dépôt portefeuille (legacy)
 */
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape((string) ($s ?? ''));
$montant     = (float) ($montant ?? 0);
$total       = (float) ($total ?? $montant);
$paymentId   = $payment_id ?? '';
$scriptUrl   = $touchpay_script_url ?? 'https://touchpay.gutouch.net/touchpayv2/script/touchpaynr/prod_touchpay-0.0.1.js';
$sendArgs    = $touchpay_send_args ?? [];
$retourUrl   = $retour_url ?? ($baseUrl . '/client/portefeuille');
$apiDepotUrl = $baseUrl . '/intouch/touchpay-depot/' . (int) $montant;
$argsJson    = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$devise      = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>

<section class="pay-depot-page">

    <a href="<?= $e($retourUrl) ?>" class="pay-depot-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au portefeuille
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="pay-depot-alert pay-depot-alert--err" style="margin-bottom:1rem"><?= $e((string) $_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="pay-depot-card">
        <div class="pay-depot-hero pay-depot-hero--legacy">
            <h1 class="pay-depot-hero__title">Paiement Mobile Money</h1>
            <p class="pay-depot-hero__sub">Dépôt portefeuille · passage sécurisé partenaire</p>
        </div>

        <div class="pay-depot-logos">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div class="pay-depot-sum">
            <div class="pay-depot-sum__row">
                <span>Montant dépôt</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <div class="pay-depot-sum__total">
                <span>Total à payer</span>
                <span class="pay-depot-sum__total-val"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <p class="pay-depot-mini" style="margin-top:.65rem;color:#64748b;">Réf. <code style="font-size:.76rem;background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;"><?= $e((string) $paymentId) ?></code></p>
        </div>

        <div class="pay-depot-panel">
            <div class="pay-depot-strip">Activez le service de paiement Mobile Money depuis la configuration du serveur.</div>

            <p style="font-size:.875rem;color:#475569;margin:0 0 1.15rem;line-height:1.55;">
                Sélectionnez <strong>Payer maintenant</strong> pour ouvrir la page Mobile Money sécurisée du partenaire. Votre portefeuille GLOBALO sera crédité après validation.
            </p>

            <button type="button" id="btn-checkout-depot" onclick="calltouchpay_depot()" class="pay-depot-submit pay-depot-submit--legacy" style="box-shadow:none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-depot-err" style="color:#dc2626;font-size:.85rem;margin:.5rem 0 0;min-height:1.1rem;"></p>

            <a id="tp-depot-fallback" href="<?= $e($apiDepotUrl) ?>" class="pay-depot-fallback">
                Ou saisir manuellement le numéro Mobile Money (formulaire API)
            </a>
        </div>
    </div>
</section>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript" onerror="tpDepotHandleError()"></script>
<script type="text/javascript">
var _tpDepotArgs   = <?= $argsJson ?>;
var _tpDepotApiUrl = <?= json_encode($apiDepotUrl) ?>;

function tpDepotHandleError() {
    var btn = document.getElementById('btn-checkout-depot');
    var err = document.getElementById('checkout-depot-err');
    var lnk = document.getElementById('tp-depot-fallback');
    if (btn) btn.style.display = 'none';
    if (err) err.textContent = 'Passerelle widget indisponible. Ouverture du formulaire Mobile Money…';
    if (lnk) { lnk.style.background = '#0d9488'; lnk.style.color = '#fff'; lnk.style.borderColor = '#0d9488'; }
    setTimeout(function() { window.location.href = _tpDepotApiUrl; }, 1400);
}

function calltouchpay_depot() {
    var btn   = document.getElementById('btn-checkout-depot');
    var errEl = document.getElementById('checkout-depot-err');
    if (errEl) errEl.textContent = '';
    if (typeof sendPaymentInfos !== 'function') {
        tpDepotHandleError();
        return;
    }
    if (btn) { btn.disabled = true; btn.textContent = 'Redirection en cours…'; }
    try {
        sendPaymentInfos.apply(null, _tpDepotArgs);
    } catch (ex) {
        if (btn) { btn.disabled = false; }
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>
