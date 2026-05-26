<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$guest   = (bool) ($guest ?? false);
?>
<div style="padding:0 .2rem 1.5rem;text-align:center;">

    <div style="border-radius:var(--radius);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);">
        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.45rem .95rem;color:#fff;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .65rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1 style="font-size:1.1rem;font-weight:800;margin:0 0 .4rem;line-height:1.2;">Paiement envoyé</h1>
            <p style="opacity:.92;font-size:.8rem;margin:0;line-height:1.45;">
                Confirmation finale traitée automatiquement (notifications serveur sous peu).
            </p>
        </div>
        <div style="padding:1rem;">
            <?php if (!$guest): ?>
                <?php
                $historique_url = $historique_url ?? ($baseUrl . '/paytech/historique');
                $secondaire_abo = $secondaire_abonnement ?? ($baseUrl . '/abonnement');
                ?>
                <a href="<?= $e($retour_url ?? ($baseUrl . '/client/portefeuille')) ?>"
                   class="btn-mobile btn-primary" style="display:block;text-decoration:none;width:100%;box-sizing:border-box;">
                    Continuer sur GLOBALO
                </a>
                <a href="<?= $e($historique_url) ?>" style="display:block;margin-top:.65rem;font-size:.82rem;color:#0d9488;font-weight:600;text-decoration:none;">
                    Historique des paiements →
                </a>
                <a href="<?= $e($secondaire_abo) ?>" style="display:block;margin-top:.45rem;font-size:.76rem;color:var(--text-muted);text-decoration:none;">
                    Abonnements GLOBALO →
                </a>
            <?php else: ?>
                <p style="font-size:.79rem;color:var(--text-muted);margin:0 0 1rem;line-height:1.45;text-align:center;">
                    Connectez-vous pour suivre abonnement ou portefeuille.
                </p>
                <a href="<?= $e($lien_connexion ?? ($baseUrl . '/connexion')) ?>" class="btn-mobile btn-primary" style="display:block;text-decoration:none;width:100%;box-sizing:border-box;">Se connecter</a>
                <div style="display:flex;flex-wrap:wrap;gap:.35rem .9rem;justify-content:center;margin-top:.75rem;">
                    <a href="<?= $e($lien_abonnement ?? ($baseUrl . '/abonnement')) ?>" style="font-size:.78rem;color:#0d9488;font-weight:600;text-decoration:none;">Abonnements</a>
                    <a href="<?= $e($lien_accueil ?? ($baseUrl . '/')) ?>" style="font-size:.76rem;color:var(--text-muted);text-decoration:none;">Accueil</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
