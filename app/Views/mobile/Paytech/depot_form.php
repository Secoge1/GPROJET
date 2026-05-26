<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$devise   = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$paytech_ctx     = $paytech_context_hint ?? '';
$retourPf        = $retour_portefeuille_url ?? ($baseUrl . '/client/portefeuille');
?>
<div style="padding:0 .15rem 1.25rem;">

    <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:1rem;">
        <a href="<?= $e($retourPf) ?>" style="color:var(--text-muted);display:flex;align-items:center;text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1 style="font-size:1.05rem;font-weight:700;margin:0;color:var(--primary);">Recharger (Mobile Money)</h1>
    </div>

    <div style="border-radius:var(--radius);background:var(--card-bg);border:1px solid var(--border);overflow:hidden;margin-bottom:.85rem;">
        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1rem;text-align:center;color:#fff;">
            <p style="margin:0;font-size:.82rem;line-height:1.4;font-weight:600;">Montant puis redirection sécurisée</p>
        </div>
        <div style="padding:.85rem 1rem 1rem;text-align:center;">
            <?php
            $mm_logo_size = 'sm';
            $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem;margin:0 0 .5rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php';
            ?>
        </div>
        <div style="padding:0 1rem 1rem;">

            <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="mobile-alert mobile-alert--error" style="margin-bottom:.75rem;"><?= $e((string) $_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); endif; ?>

            <?php if ($paytech_ctx !== ''): ?>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.65rem .8rem;margin-bottom:.65rem;font-size:.76rem;line-height:1.45;color:#1e40af;">
                <?= $e($paytech_ctx) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= $e($baseUrl . '/paytech/initier-depot') ?>">
                <?= \App\Core\Security::getCsrfField() ?>
                <?php
                $paytech_phone_variant    = 'mobile';
                $paytech_phone_id_prefix  = 'pt-dep-mob';
                require APP_PATH . '/Views/partials/paytech_phone_fields.php';
                ?>

                <label for="paytech-depot-montant-mob" style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin:0 0 .35rem;">
                    Montant · <?= $e($devise) ?>
                </label>
                <input type="number" id="paytech-depot-montant-mob" name="montant" min="500" step="100" max="500000" required
                       placeholder="Ex. 5000"
                       style="width:100%;box-sizing:border-box;padding:.85rem .9rem;font-size:1rem;border-radius:10px;border:1px solid var(--border);background:var(--card-bg,#fff);margin-bottom:.55rem;">

                <p style="font-size:.71rem;color:var(--text-muted);margin:0 0 .65rem;">
                    Entre <strong>500</strong> et <strong>500&nbsp;000</strong> <?= $e($devise) ?>
                </p>

                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.85rem;">
                    <?php foreach ([1000, 2000, 5000, 10000, 25000] as $preset): ?>
                    <button type="button" class="btn-mobile btn-mobile-outline"
                            style="flex:1;min-width:28%;padding:.52rem;font-size:.78rem;"
                            onclick="document.getElementById('paytech-depot-montant-mob').value='<?= $preset ?>';">
                        <?= number_format($preset, 0, ',', ' ') ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-mobile btn-primary" style="width:100%;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:.4rem;font-size:.92rem;">
                    Continuer vers le paiement
                </button>
            </form>

            <p style="margin:.75rem 0 0;font-size:.68rem;color:var(--text-muted);text-align:center;line-height:1.35;">
                Confirmation automatique après paiement Mobile Money.
            </p>
        </div>
    </div>
</div>
