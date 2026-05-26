<?php
/**
 * GLOBALO — Saisie montant dépôt PayTech (/paytech/depot)
 */
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape((string) ($s ?? ''));
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$paytech_ctx     = $paytech_context_hint ?? '';
$retourPf        = $retour_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
?>

<section class="pay-depot-page">

    <a href="<?= $e($retourPf) ?>" class="pay-depot-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au portefeuille
    </a>

    <div class="pay-depot-card">
        <div class="pay-depot-hero pay-depot-hero--paytech">
            <h1 class="pay-depot-hero__title">Recharger votre portefeuille</h1>
            <p class="pay-depot-hero__sub">Paiement Mobile Money sécurisé — Orange Money, Moov Africa, Wave</p>
        </div>

        <div class="pay-depot-logos">
            <?php
            $mm_logo_size = 'sm';
            $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php';
            ?>
        </div>

        <div class="pay-depot-panel">
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="pay-depot-alert pay-depot-alert--err"><?= $e((string) $_SESSION['flash_error']) ?></div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?php if ($paytech_ctx !== ''): ?>
            <div class="pay-depot-strip"><?= $e($paytech_ctx) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-depot') ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <?php
                $paytech_phone_variant    = 'desktop';
                $paytech_phone_id_prefix  = 'pt-dep-desk';
                require APP_PATH . '/Views/partials/paytech_phone_fields.php';
                ?>

                <label class="pay-depot-label" for="paytech-depot-montant">Montant à ajouter · <?= $e($devise) ?></label>
                <div class="pay-depot-rowbtn">
                    <input type="number" class="pay-depot-field" id="paytech-depot-montant" name="montant"
                           min="500" step="100" max="500000" required placeholder="Ex. 5000">
                    <button type="submit" class="pay-depot-submit pay-depot-submit--inline">Continuer →</button>
                </div>
                <p class="pay-depot-mini">Montants entre <strong>500</strong> et <strong>500&nbsp;000</strong> <?= $e($devise) ?></p>

                <div class="pay-depot-presets" aria-label="Montants rapides">
                    <?php foreach ([1000, 2000, 5000, 10000, 25000] as $preset): ?>
                        <button type="button" class="pay-depot-preset" onclick="document.getElementById('paytech-depot-montant').value='<?= $preset ?>';"><?= number_format($preset, 0, ',', ' ') ?></button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</section>
