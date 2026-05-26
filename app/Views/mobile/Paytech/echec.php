<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$tx       = $transaction ?? [];
$retryUrl = $retry_url ?? ($baseUrl . '/abonnement');
?>
<div style="padding:0 .25rem 1.5rem;text-align:center;">
    <div style="border-radius:var(--radius);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);">
        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:1.5rem 1rem;color:#fff;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <h1 style="font-size:1.1rem;font-weight:800;margin:0 0 .35rem;">Paiement annulé</h1>
            <p style="opacity:.9;font-size:.82rem;margin:0;line-height:1.4;">Aucun débit n’a été enregistré côté GLOBALO.</p>
        </div>
        <?php if (!empty($tx['payment_id'])): ?>
        <div style="padding:.85rem 1rem;border-bottom:1px solid var(--border);background:#fef2f2;">
            <p style="font-size:.76rem;color:var(--text-muted);margin:0;text-align:left;">Réf.&nbsp;<code><?= $e((string) $tx['payment_id']) ?></code></p>
        </div>
        <?php endif; ?>
        <div style="padding:1rem;">
            <p style="font-size:.8rem;color:var(--text-muted);margin:0 0 1rem;line-height:1.5;text-align:left;">
                Réessayez quand vous voulez ou contactez le support si besoin.
            </p>
            <a href="<?= $e($retryUrl) ?>" class="btn-mobile btn-primary" style="display:block;text-decoration:none;text-align:center;width:100%;box-sizing:border-box;">
                Réessayer
            </a>
            <a href="<?= $e($baseUrl . '/paytech/historique') ?>" style="display:block;margin-top:.65rem;font-size:.8rem;color:#64748b;text-decoration:none;">
                Historique →
            </a>
        </div>
    </div>
</div>
