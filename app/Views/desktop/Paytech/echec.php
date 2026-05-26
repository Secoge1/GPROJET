<?php
/**
 * GLOBALO — Page d'échec / abandon PayTech
 */
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$tx       = $transaction ?? [];
$isDepot  = (bool) ($is_depot ?? false);
$retryUrl = $retry_url ?? ($baseUrl . '/abonnement');
?>
<div style="max-width:520px;margin:3rem auto;padding:0 1rem;text-align:center;">

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:2rem;color:#fff;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <h1 style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem;">Paiement annulé</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Aucun montant n'a été débité de votre compte.</p>
        </div>

        <?php if (!empty($tx['payment_id'])): ?>
        <div style="padding:1rem 1.5rem;border-bottom:1px solid #e2e8f0;background:#fef2f2;">
            <p style="font-size:.82rem;color:#64748b;margin:0;">
                Réf. <code><?= $e($tx['payment_id']) ?></code>
            </p>
        </div>
        <?php endif; ?>

        <div style="padding:1.5rem;">
            <p style="font-size:.9rem;color:#475569;margin:0 0 1.25rem;line-height:1.6;">
                Vous pouvez réessayer à tout moment. Si vous rencontrez un problème persistant, contactez le support.
            </p>
            <a href="<?= $e($retryUrl) ?>"
               style="display:block;width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;margin-bottom:.75rem;">
                Réessayer le paiement
            </a>
            <a href="<?= $baseUrl ?>/paytech/historique"
               style="display:block;font-size:.85rem;color:#64748b;text-decoration:none;">
                Voir l'historique des paiements
            </a>
        </div>
    </div>
</div>
