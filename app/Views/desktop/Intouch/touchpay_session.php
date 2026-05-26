<?php
/**
 * GLOBALO — Checkout Page InTouch (script2) — Paiement direct session
 */
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$reservation   = $reservation ?? [];
$reservationId = (int) ($reservation_id ?? $reservation['id'] ?? 0);
$montant       = (float) ($montant ?? 0);
$total         = (float) ($total ?? $montant);
$paymentId     = $payment_id ?? '';
$scriptUrl     = $touchpay_script_url ?? 'https://touchpay.gutouch.net/touchpayv2/script/touchpaynr/prod_touchpay-0.0.1.js';
$sendArgs      = $touchpay_send_args ?? [];
$payerUrl      = $payer_url ?? ($baseUrl . '/client/payer/' . $reservationId);
$apiUrl        = $baseUrl . '/intouch/touchpay-depot/' . (int) $total;
$argsJson      = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$devise        = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $e($payerUrl) ?>" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au paiement
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Paiement de la session</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Réservation #<?= $reservationId ?> · Mobile Money</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <?php if (!empty($reservation['demande_titre'])): ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Mission</span>
                <span style="font-weight:600;max-width:60%;text-align:right;"><?= $e($reservation['demande_titre']) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Montant session</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total à payer</span>
                <span style="color:#7c3aed;"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div style="background:#f5f3ff;border-bottom:1px solid #e2e8f0;padding:1rem 1.5rem;">
            <p style="font-size:.8rem;font-weight:700;color:#6d28d9;margin:0 0 .5rem;text-transform:uppercase;letter-spacing:.04em;">Comment ça marche</p>
            <ol style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.4rem;">
                <?php foreach (['Cliquez sur « Payer maintenant » → page InTouch sécurisée.', 'Votre portefeuille est crédité après confirmation Mobile Money.', 'Revenez sur la page de paiement pour finaliser la mission.'] as $i => $step): ?>
                <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.82rem;color:#374151;">
                    <span style="flex-shrink:0;width:20px;height:20px;background:#7c3aed;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;"><?= $i + 1 ?></span>
                    <?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div style="padding:1.5rem;">
            <button type="button" id="btn-checkout-session" onclick="calltouchpay_session()"
                style="width:100%;background:#7c3aed;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;margin-bottom:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer maintenant — <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-session-err" style="color:#dc2626;font-size:.85rem;margin:0 0 .75rem;min-height:1rem;"></p>

            <a id="tp-session-fallback" href="<?= $e($apiUrl) ?>"
               style="display:block;text-align:center;padding:.8rem;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:.9rem;color:#374151;font-weight:500;text-decoration:none;margin-top:.5rem;">
                Payer par formulaire (saisie numéro Mobile Money)
            </a>

            <p style="font-size:.8rem;color:#6b7280;margin:.75rem 0 0;line-height:1.5;">
                Après paiement, revenez sur <a href="<?= $e($payerUrl) ?>" style="color:#7c3aed;font-weight:600;">la page de paiement</a> pour finaliser la mission.
            </p>
        </div>
    </div>
</div>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript" onerror="tpSessionHandleError()"></script>
<script type="text/javascript">
var _tpSessionArgs   = <?= $argsJson ?>;
var _tpSessionApiUrl = <?= json_encode($apiUrl) ?>;

function tpSessionHandleError() {
    var btn = document.getElementById('btn-checkout-session');
    var err = document.getElementById('checkout-session-err');
    var lnk = document.getElementById('tp-session-fallback');
    if (btn) btn.style.display = 'none';
    if (err) err.textContent = 'Service InTouch indisponible. Redirection vers le formulaire\u2026';
    if (lnk) { lnk.style.background = '#7c3aed'; lnk.style.color = '#fff'; lnk.style.borderColor = '#7c3aed'; }
    setTimeout(function() { window.location.href = _tpSessionApiUrl; }, 1500);
}

function calltouchpay_session() {
    var btn   = document.getElementById('btn-checkout-session');
    var errEl = document.getElementById('checkout-session-err');
    if (errEl) errEl.textContent = '';
    if (typeof sendPaymentInfos !== 'function') {
        tpSessionHandleError();
        return;
    }
    if (btn) { btn.disabled = true; btn.textContent = 'Redirection en cours\u2026'; }
    try {
        sendPaymentInfos.apply(null, _tpSessionArgs);
    } catch (ex) {
        if (btn) btn.disabled = false;
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>
