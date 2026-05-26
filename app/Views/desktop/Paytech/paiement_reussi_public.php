<?php
/**
 * GLOBALO — Succès paiement PayTech — URL publique pour config PayTech (web).
 */
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$guest     = (bool) ($guest ?? false);
?>
<div style="max-width:520px;margin:2.75rem auto;padding:0 1rem;">

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;text-align:center;">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:2rem;color:#fff;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1 style="font-size:1.45rem;font-weight:700;margin:0 0 .35rem;">Paiement envoyé avec succès</h1>
            <p style="opacity:.92;font-size:.9rem;margin:0;line-height:1.45;">
                Merci pour votre paiement via le service de paiement. La confirmation définitive est traitée automatiquement (notification serveur sous quelques instants si tout est configuré).
            </p>
        </div>
        <div style="padding:1.25rem 1.5rem 1.5rem;">
            <?php if (!$guest): ?>
                <?php
                $historique_url = $historique_url ?? ($baseUrl . '/paytech/historique');
                $secondaire_abo = $secondaire_abonnement ?? ($baseUrl . '/abonnement');
                ?>
                <a href="<?= $e($retour_url ?? ($baseUrl . '/client/portefeuille')) ?>"
                   style="display:block;width:100%;background:#0d9488;color:#fff;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;">
                    Continuer sur GLOBALO
                </a>
                <a href="<?= $e($historique_url) ?>"
                   style="display:block;margin-top:.6rem;font-size:.85rem;color:#0d9488;font-weight:600;text-decoration:none;">
                    Historique des paiements →
                </a>
                <a href="<?= $e($secondaire_abo) ?>"
                   style="display:block;margin-top:.55rem;font-size:.82rem;color:#64748b;text-decoration:none;">
                    Abonnements GLOBALO →
                </a>
            <?php else: ?>
                <p style="font-size:.82rem;color:#475569;line-height:1.5;text-align:center;margin:0 0 1rem;">
                    Connectez-vous pour suivre votre abonnement ou votre portefeuille.
                </p>
                <a href="<?= $e($lien_connexion ?? ($baseUrl . '/connexion')) ?>"
                   style="display:block;width:100%;background:#0d9488;color:#fff;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;">
                    Se connecter
                </a>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;justify-content:center;margin-top:.85rem;">
                    <a href="<?= $e($lien_abonnement ?? ($baseUrl . '/abonnement')) ?>" style="font-size:.82rem;color:#0d9488;font-weight:600;text-decoration:none;">Abonnements</a>
                    <a href="<?= $e($lien_accueil ?? ($baseUrl . '/')) ?>" style="font-size:.82rem;color:#64748b;text-decoration:none;">Accueil GLOBALO</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
