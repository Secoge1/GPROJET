<?php
/**
 * GLOBALO — Confirmation dépôt avant redirection PayTech
 */
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape((string) ($s ?? ''));
$montant = (float) ($montant ?? 0);
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$paytech_ctx        = $paytech_context_hint ?? '';
$retourPf           = $retour_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
?>

<section class="pay-depot-page">

    <a href="<?= $e($retourPf) ?>" class="pay-depot-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au portefeuille
    </a>

    <div class="pay-depot-card">
        <div class="pay-depot-hero pay-depot-hero--paytech">
            <h1 class="pay-depot-hero__title">Vers Service de paiement</h1>
            <p class="pay-depot-hero__sub">Vous allez finaliser <?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?> sur service de paiement</p>
        </div>

        <div class="pay-depot-sum">
            <div class="pay-depot-sum__row">
                <span>Mission / crédit portefeuille</span>
            </div>
            <div class="pay-depot-sum__total">
                <span>Total à payer</span>
                <span class="pay-depot-sum__total-val"><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div class="pay-depot-panel">
            <?php if ($paytech_ctx !== ''): ?>
            <div class="pay-depot-strip"><?= $e($paytech_ctx) ?>
                <?php if (!empty($paytech_country_iso)): ?>
                    <span style="display:block;margin-top:.45rem;font-size:.76rem;color:#2563eb;">Région indicative : <?= $e(strtoupper((string) $paytech_country_iso)) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-depot') ?>" id="paytech-depot-confirm-form">
                <?= \App\Core\Security::getCsrfField() ?>
                <input type="hidden" name="montant" value="<?= (int) $montant ?>">
                <?php
                $paytech_phone_variant    = 'desktop';
                $paytech_phone_id_prefix  = 'pt-depconf-desk';
                require APP_PATH . '/Views/partials/paytech_phone_fields.php';
                ?>
                <button type="submit" class="pay-depot-submit" id="paytech-depot-submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Payer <?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?> avec le service de paiement
                </button>
            </form>

            <a href="<?= $e($baseUrl . '/paytech/depot') ?>" class="pay-depot-link-muted">← Modifier le montant</a>
        </div>
    </div>
</section>

<script>
document.getElementById('paytech-depot-confirm-form').addEventListener('submit', function () {
    var btn = document.getElementById('paytech-depot-submit');
    if (btn) { btn.disabled = true; btn.innerHTML = 'Redirection vers Service de paiement…'; }
});
</script>
