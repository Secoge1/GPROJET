<?php
/**
 * GLOBALO — Page de succès PayTech
 */
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$tx      = $transaction ?? [];
$isDepot = (bool) ($is_depot ?? false);
$retour  = $retour_url ?? ($baseUrl . '/abonnement');
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$statut  = $tx['status'] ?? 'pending';
?>
<div style="max-width:520px;margin:3rem auto;padding:0 1rem;text-align:center;">

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:2rem;color:#fff;">
            <?php if ($statut === 'success'): ?>
                <div style="width:64px;height:64px;background:rgba(255,255,255,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h1 style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem;">Paiement confirmé !</h1>
                <p style="opacity:.85;font-size:.9rem;margin:0;">Votre <?= $isDepot ? 'dépôt a été crédité' : 'abonnement est actif' ?>.</p>
            <?php else: ?>
                <div style="width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h1 style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem;">Paiement en attente</h1>
                <p style="opacity:.85;font-size:.9rem;margin:0;">Votre paiement est en cours de traitement. Votre compte sera mis à jour sous quelques minutes.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($tx['payment_id'])): ?>
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
            <div style="display:flex;justify-content:space-between;font-size:.88rem;color:#64748b;margin-bottom:.4rem;">
                <span>Référence</span>
                <code style="font-size:.78rem;color:#0f172a;"><?= $e($tx['payment_id']) ?></code>
            </div>
            <?php if (!empty($tx['total_amount'])): ?>
            <div style="display:flex;justify-content:space-between;font-size:.88rem;color:#64748b;">
                <span>Montant</span>
                <strong><?= number_format((float) $tx['total_amount'], 0, ',', ' ') ?> <?= $e($devise) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="padding:1.5rem;">
            <a href="<?= $e($retour) ?>"
               style="display:block;width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;">
                <?= $e($succes_button_label ?? ($isDepot ? 'Voir mon portefeuille' : 'Accéder à mon espace')) ?>
            </a>
            <a href="<?= $baseUrl ?>/paytech/historique"
               style="display:block;margin-top:.75rem;font-size:.85rem;color:#64748b;text-decoration:none;">
                Voir l'historique des paiements
            </a>
        </div>
    </div>
</div>
