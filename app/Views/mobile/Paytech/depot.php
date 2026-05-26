<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$montant  = (float) ($montant ?? 0);
$devise   = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$paytech_ctx     = $paytech_context_hint ?? '';
$retourPf        = $retour_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
?>
<div style="padding:0 .15rem 1.25rem;">

    <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:1rem;">
        <a href="<?= $e($retourPf) ?>" style="color:var(--text-muted);display:flex;align-items:center;text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1 style="font-size:1.05rem;font-weight:700;margin:0;color:var(--primary);">Confirmer le dépôt</h1>
    </div>

    <div style="border-radius:var(--radius);background:var(--card-bg);border:1px solid var(--border);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1rem 1.1rem;color:#fff;text-align:center;">
            <p style="margin:0;font-weight:800;font-size:1.5rem;line-height:1;"><?= number_format($montant, 0, ',', ' ') ?> <span style="font-size:.85rem;font-weight:600"><?= $e($devise) ?></span></p>
            <p style="margin:.35rem 0 0;font-size:.8rem;opacity:.92;">Vers page sécurisée Service de paiement</p>
        </div>
        <div style="padding:1rem;">
            <?php if ($paytech_ctx !== ''): ?>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.65rem .8rem;margin-bottom:.65rem;font-size:.76rem;line-height:1.45;color:#1e40af;">
                <?= $e($paytech_ctx) ?>
                <?php if (!empty($paytech_country_iso)): ?>
                    <span style="display:block;margin-top:.35rem;font-size:.7rem;">Région indicative : <?= $e(strtoupper((string) $paytech_country_iso)) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-depot') ?>" id="paytech-depot-confirm-form-mob">
                <?= \App\Core\Security::getCsrfField() ?>
                <input type="hidden" name="montant" value="<?= (int) $montant ?>">
                <?php
                $paytech_phone_variant    = 'mobile';
                $paytech_phone_id_prefix  = 'pt-depconf-mob';
                require APP_PATH . '/Views/partials/paytech_phone_fields.php';
                ?>
                <button type="submit" id="paytech-depot-submit-mob" class="btn-mobile btn-primary" style="width:100%;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:.45rem;margin-bottom:.65rem;font-size:.92rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Payer <?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?>
                </button>
            </form>

            <a href="<?= $e($baseUrl . '/paytech/depot') ?>" style="display:block;text-align:center;font-size:.82rem;color:#0d9488;font-weight:600;text-decoration:none;">
                Modifier le montant
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('paytech-depot-confirm-form-mob').addEventListener('submit', function () {
    var btn = document.getElementById('paytech-depot-submit-mob');
    if (btn) { btn.disabled = true; btn.textContent = 'Redirection…'; }
});
</script>
