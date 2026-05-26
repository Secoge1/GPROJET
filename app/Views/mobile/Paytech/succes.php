<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$tx      = $transaction ?? [];
$isDepot = (bool) ($is_depot ?? false);
$retour  = $retour_url ?? ($baseUrl . '/abonnement');
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$statut  = $tx['status'] ?? 'pending';
$cta     = $succes_button_label ?? ($isDepot ? 'Voir mon portefeuille' : 'Accéder à mon espace');
?>
<div style="padding:0 .25rem 1.5rem;text-align:center;">
    <div style="border-radius:var(--radius);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.5rem 1rem;color:#fff;">
            <?php if ($statut === 'success'): ?>
            <div style="width:52px;height:52px;background:rgba(255,255,255,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1 style="font-size:1.15rem;font-weight:800;margin:0 0 .35rem;line-height:1.2;">Confirmé !</h1>
            <p style="opacity:.9;font-size:.82rem;margin:0;line-height:1.45;"><?= $isDepot ? 'Votre portefeuille a été crédité.' : 'Votre abonnement sera actif dès synchro finale.' ?></p>
            <?php else: ?>
            <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h1 style="font-size:1.1rem;font-weight:800;margin:0 0 .35rem;">En attente</h1>
            <p style="opacity:.88;font-size:.8rem;margin:0;">Traitement sous quelques instants.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($tx['payment_id'])): ?>
        <div style="padding:1rem;text-align:left;border-bottom:1px solid var(--border);">
            <div style="font-size:.76rem;color:var(--text-muted);margin-bottom:.25rem;">Réf.</div>
            <code style="font-size:.75rem;color:var(--text);word-break:break-all;"><?= $e((string) $tx['payment_id']) ?></code>
            <?php if (!empty($tx['total_amount'])): ?>
            <div style="margin-top:.75rem;display:flex;justify-content:space-between;font-size:.85rem;">
                <span style="color:var(--text-muted)">Montant</span>
                <strong><?= number_format((float) $tx['total_amount'], 0, ',', ' ') ?> <?= $e($devise) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="padding:1rem;">
            <a href="<?= $e($retour) ?>" class="btn-mobile btn-primary" style="display:block;text-decoration:none;text-align:center;width:100%;box-sizing:border-box;">
                <?= $e($cta) ?>
            </a>
            <a href="<?= $e($baseUrl . '/paytech/historique') ?>" style="display:block;margin-top:.75rem;font-size:.8rem;color:#0d9488;font-weight:600;text-decoration:none;">
                Historique des paiements →
            </a>
        </div>
    </div>
</div>
