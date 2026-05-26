<?php
/**
 * GLOBALO — Annulation paiement PayTech — URL publique pour config PayTech (web).
 */
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$guest     = (bool) ($guest ?? false);
?>
<div style="max-width:520px;margin:2.75rem auto;padding:0 1rem;">

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;text-align:center;">

        <div style="background:linear-gradient(135deg,#78716c,#57534e);padding:2rem;color:#fff;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </div>
            <h1 style="font-size:1.42rem;font-weight:700;margin:0 0 .35rem;">Paiement annulé ou interrompu</h1>
            <p style="opacity:.92;font-size:.9rem;margin:0;line-height:1.45;">
                Aucun débit définitif n’a été enregistré par GLOBALO depuis cette page. Si un prélèvement apparaît quand même, contactez le support du service de paiement puis notre équipe.
            </p>
        </div>
        <div style="padding:1.25rem 1.5rem 1.5rem;">
            <?php if (!$guest): ?>
                <?php
                $retry_p = $retry_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
                $retry_a = $retry_abonnement_url ?? ($baseUrl . '/paytech/checkout');
                ?>
                <a href="<?= $e($retry_a) ?>"
                   style="display:block;width:100%;background:#0d9488;color:#fff;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;">
                    Réessayer le paiement (Service de paiement)
                </a>
                <a href="<?= $e($retry_p) ?>"
                   style="display:block;margin-top:.65rem;padding:.82rem;font-size:.95rem;font-weight:600;text-decoration:none;border:1px solid #d6d3d1;border-radius:10px;color:#44403c;box-sizing:border-box;">
                    Portefeuille / tableau de bord
                </a>
            <?php else: ?>
                <p style="font-size:.82rem;color:#57534e;line-height:1.45;margin:0 0 1rem;">
                    Pour réessayer après connexion&nbsp;: abonnements ou rechargement de portefeuille.
                </p>
                <a href="<?= $e($lien_connexion ?? ($baseUrl . '/connexion')) ?>"
                   style="display:block;width:100%;background:#57534e;color:#fff;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;">
                    Se connecter puis réessayer
                </a>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;justify-content:center;margin-top:.85rem;">
                    <a href="<?= $e($lien_abonnement ?? ($baseUrl . '/abonnement')) ?>" style="font-size:.82rem;color:#0d9488;font-weight:600;text-decoration:none;">Voir les offres</a>
                    <a href="<?= $e($lien_accueil ?? ($baseUrl . '/')) ?>" style="font-size:.82rem;color:#78716c;text-decoration:none;">Accueil GLOBALO</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
