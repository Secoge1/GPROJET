<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$guest   = (bool) ($guest ?? false);
?>
<div style="padding:0 .2rem 1.5rem;text-align:center;">

    <div style="border-radius:var(--radius);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);">
        <div style="background:linear-gradient(135deg,#78716c,#57534e);padding:1.45rem .95rem;color:#fff;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .65rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </div>
            <h1 style="font-size:1.08rem;font-weight:800;margin:0 0 .4rem;line-height:1.2;">Annulé ou interrompu</h1>
            <p style="opacity:.92;font-size:.79rem;margin:0;line-height:1.45;">
                Pas de débit définitif enregistré par GLOBALO depuis cette page.
            </p>
        </div>
        <div style="padding:1rem;">
            <?php if (!$guest): ?>
                <?php
                $retry_p = $retry_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
                $retry_a = $retry_abonnement_url ?? ($baseUrl . '/paytech/checkout');
                ?>
                <a href="<?= $e($retry_a) ?>" class="btn-mobile btn-primary" style="display:block;text-decoration:none;width:100%;box-sizing:border-box;">Réessayer le paiement</a>
                <a href="<?= $e($retry_p) ?>" class="btn-mobile btn-mobile-outline" style="display:block;margin-top:.55rem;text-decoration:none;width:100%;box-sizing:border-box;text-align:center;">Portefeuille / tableau de bord</a>
            <?php else: ?>
                <p style="font-size:.78rem;color:var(--text-muted);margin:0 0 1rem;line-height:1.45;">
                    Réessayez après connexion depuis abonnement ou recharge.
                </p>
                <a href="<?= $e($lien_connexion ?? ($baseUrl . '/connexion')) ?>" class="btn-mobile btn-primary" style="display:block;text-decoration:none;width:100%;box-sizing:border-box;">Se connecter</a>
                <div style="display:flex;flex-wrap:wrap;gap:.35rem .9rem;justify-content:center;margin-top:.75rem;">
                    <a href="<?= $e($lien_abonnement ?? ($baseUrl . '/abonnement')) ?>" style="font-size:.76rem;color:#0d9488;font-weight:600;text-decoration:none;">Voir les offres</a>
                    <a href="<?= $e($lien_accueil ?? ($baseUrl . '/')) ?>" style="font-size:.74rem;color:var(--text-muted);text-decoration:none;">Accueil</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
