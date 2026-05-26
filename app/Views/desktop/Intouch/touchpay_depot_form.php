<?php
/**
 * GLOBALO — Saisie montant avant dépôt legacy (/intouch/touchpay-depot)
 */
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape((string) ($s ?? ''));
$devise     = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$modeWidget = (bool) ($mode_widget ?? true);
$formAction = $baseUrl . '/intouch/touchpay-depot';
$retourPf   = $retour_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
?>

<section class="pay-depot-page">

    <a href="<?= $e($retourPf) ?>" class="pay-depot-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au portefeuille
    </a>

    <div class="pay-depot-card">
        <div class="pay-depot-hero pay-depot-hero--legacy">
            <h1 class="pay-depot-hero__title">Recharger le portefeuille</h1>
            <p class="pay-depot-hero__sub">Passerelle legacy · Mobile&nbsp;Money (alternatif sur ce serveur)</p>
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

            <div class="pay-depot-strip">Le service de paiement Mobile Money est disponible si configuré sur le serveur.</div>

            <?php if (!$modeWidget): ?>
            <div class="pay-depot-strip pay-depot-strip--green">Étape suivante : formulaire téléphone Mobile Money (Orange, Moov, Wave).</div>
            <?php endif; ?>

            <form method="get" action="<?= $e($formAction) ?>">
                <label class="pay-depot-label" for="legacy-depot-montant">Montant · <?= $e($devise) ?></label>
                <div class="pay-depot-rowbtn">
                    <input type="number" class="pay-depot-field" id="legacy-depot-montant" name="montant"
                           min="500" step="100" max="500000" required placeholder="Ex. 5000">
                    <button type="submit" class="pay-depot-submit pay-depot-submit--inline pay-depot-submit--legacy">Continuer →</button>
                </div>
                <p class="pay-depot-mini">Minimum 500 · maximum 500&nbsp;000 <?= $e($devise) ?></p>

                <div class="pay-depot-presets" aria-label="Montants rapides">
                    <?php foreach ([1000, 2000, 5000, 10000, 25000] as $preset): ?>
                        <button type="button" class="pay-depot-preset" onclick="document.getElementById('legacy-depot-montant').value='<?= $preset ?>';"><?= number_format($preset, 0, ',', ' ') ?></button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</section>
