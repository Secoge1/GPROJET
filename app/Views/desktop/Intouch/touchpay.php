<?php
/**
 * GLOBALO — Checkout Page InTouch (script2) — Abonnement
 * sendPaymentInfos(order_number, agency_code, secure_code, domain_name,
 *   url_success, url_failed, amount, city, email, firstName, lastName, phone)
 */
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$abonnementType = $abonnement_type ?? 'client';
$montant        = (float) ($montant ?? 0);
$commission     = (float) ($commission ?? 0);
$total          = (float) ($total ?? 0);
$paymentId      = $payment_id ?? '';
$scriptUrl      = $touchpay_script_url ?? 'https://touchpay.gutouch.net/touchpayv2/script/touchpaynr/prod_touchpay-0.0.1.js';
$sendArgs       = $touchpay_send_args ?? [];
$altUrl         = $paiement_classique_url ?? ($baseUrl . '/intouch/paiement/' . rawurlencode($abonnementType));
$argsJson       = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$typeLabels     = ['client' => 'Client', 'expert' => 'Expert', 'etudiant' => 'Étudiant', 'professeur' => 'Professeur'];
$devise         = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $baseUrl ?>/abonnement" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour abonnement
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;">
            <?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Paiement — Abonnement <?= $e($typeLabels[$abonnementType] ?? $abonnementType) ?></h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Checkout InTouch · Mobile Money</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Référence</span>
                <span style="font-size:.78rem;word-break:break-all;color:#0f172a;"><?= $e($paymentId) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Abonnement</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <?php if ($commission > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Frais de service</span>
                <span><?= number_format($commission, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total</span>
                <span style="color:#0d9488;"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div style="padding:1.5rem;">
            <p style="font-size:.85rem;color:#64748b;margin:0 0 1.1rem;line-height:1.5;">
                Cliquez sur <strong>Payer maintenant</strong> pour être redirigé sur la page de paiement sécurisée InTouch.
            </p>

            <div id="tp-loading" style="display:none;text-align:center;padding:.75rem;color:#64748b;font-size:.88rem;">
                Chargement du service de paiement…
            </div>

            <button type="button" id="btn-checkout-abo" onclick="calltouchpay()"
                style="width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;margin-bottom:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer maintenant — <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-err" style="color:#dc2626;font-size:.85rem;margin:0 0 .75rem;min-height:1rem;"></p>

            <a id="tp-fallback-link" href="<?= $e($altUrl) ?>"
               style="display:block;text-align:center;padding:.8rem;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:.9rem;color:#374151;font-weight:500;text-decoration:none;margin-top:.5rem;">
                Payer par formulaire (saisie numéro Mobile Money)
            </a>
        </div>
    </div>
</div>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript"
    onerror="tpHandleScriptError()"></script>
<script type="text/javascript">
var _tpArgs    = <?= $argsJson ?>;
var _tpAltUrl  = <?= json_encode($altUrl) ?>;

function tpHandleScriptError() {
    /* Script InTouch non chargé → redirection automatique vers formulaire */
    var btn = document.getElementById('btn-checkout-abo');
    var err = document.getElementById('checkout-err');
    var lnk = document.getElementById('tp-fallback-link');
    if (btn) btn.style.display = 'none';
    if (err) err.textContent = 'Service de paiement InTouch indisponible. Redirection vers le formulaire…';
    if (lnk) lnk.style.background = '#0d9488', lnk.style.color = '#fff', lnk.style.borderColor = '#0d9488';
    setTimeout(function() { window.location.href = _tpAltUrl; }, 1500);
}

function calltouchpay() {
    var btn   = document.getElementById('btn-checkout-abo');
    var errEl = document.getElementById('checkout-err');
    if (errEl) errEl.textContent = '';
    if (typeof sendPaymentInfos !== 'function') {
        tpHandleScriptError();
        return;
    }
    if (btn) { btn.disabled = true; btn.textContent = 'Redirection en cours…'; }
    try {
        sendPaymentInfos.apply(null, _tpArgs);
    } catch (ex) {
        if (btn) { btn.disabled = false; btn.innerHTML = 'Payer maintenant'; }
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>