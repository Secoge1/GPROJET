<?php
/**
 * GLOBALO — Page d'échec / abandon paiement Checkout InTouch
 */
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$tx        = $transaction ?? [];
$retryUrl  = $retry_url ?? ($baseUrl . '/abonnement');
$isDepot   = $is_depot ?? false;
$paymentId = $tx['payment_id'] ?? '';
?>
<div style="max-width:480px;margin:3rem auto;padding:0 1rem;text-align:center;">

    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">

        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:2rem;color:#fff;">
            <div style="font-size:3rem;margin-bottom:.5rem;">✕</div>
            <h1 style="font-size:1.4rem;font-weight:700;margin:0;">Paiement annulé</h1>
            <p style="opacity:.85;font-size:.9rem;margin:.25rem 0 0;">La transaction n'a pas abouti</p>
        </div>

        <div style="padding:1.75rem;">
            <?php if ($paymentId): ?>
            <p style="font-size:.82rem;color:#6b7280;margin:0 0 1.25rem;">
                Référence : <code style="background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;"><?= $e($paymentId) ?></code>
            </p>
            <?php endif; ?>

            <p style="color:#374151;font-size:.95rem;margin:0 0 1.5rem;line-height:1.6;">
                Le paiement a été interrompu ou annulé. Aucun montant n'a été débité.<br>
                Vous pouvez réessayer à tout moment.
            </p>

            <a href="<?= $e($retryUrl) ?>" style="display:block;width:100%;background:#2563eb;color:#fff;border:none;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;margin-bottom:.75rem;">
                ↩ Réessayer
            </a>

            <?php if ($paymentId): ?>
            <a href="<?= $baseUrl ?>/intouch/verification/<?= rawurlencode($paymentId) ?>" style="display:block;font-size:.85rem;color:#6b7280;text-decoration:none;margin-top:.5rem;">
                Vérifier le statut de la transaction
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>