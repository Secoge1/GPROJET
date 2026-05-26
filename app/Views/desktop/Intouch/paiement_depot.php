<?php
/**
 * GLOBALO — Formulaire dépôt portefeuille via API InTouch (Pay-In digest)
 * Mode : api (saisie numéro + opérateur, pas de restriction domaine)
 */
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField  = \App\Core\Security::getCsrfField();
$montant    = (float) ($montant ?? 0);
$retourUrl  = $retour_url ?? ($baseUrl . '/client/portefeuille');
$devise     = $devise ?? 'XOF';
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $e($retourUrl) ?>" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour portefeuille
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;">
            <?= $e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#16a34a,#15803d);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Dépôt portefeuille</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Mobile Money · InTouch</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php
            $mm_logo_size = 'sm';
            $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php';
            ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;">
                <span>Montant à déposer</span>
                <span style="color:#16a34a;"><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div style="padding:1.5rem;">
            <form method="post" action="<?= $baseUrl ?>/intouch/initier-depot" id="form-depot-api">
                <?= $csrfField ?>
                <input type="hidden" name="montant" value="<?= $e((string) $montant) ?>">

                <div style="margin-bottom:1.25rem;">
                    <?php
                    $intouch_op_suffix = 'depot-api';
                    require APP_PATH . '/Views/partials/intouch_operator_picker.php';
                    ?>
                </div>

                <label for="phone-depot" style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:.5rem;">
                    Votre numéro Mobile Money (Mali : 8 chiffres après +223)
                </label>
                <div style="position:relative;margin-bottom:1.25rem;">
                    <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.9rem;">+223</span>
                    <input type="tel" id="phone-depot" name="phone"
                        placeholder="76 XX XX XX"
                        required maxlength="12" pattern="[0-9]{8}"
                        style="width:100%;padding:.75rem 1rem .75rem 3.5rem;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#16a34a'"
                        onblur="this.style.borderColor='#d1d5db'">
                </div>

                <p style="font-size:.8rem;color:#6b7280;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem;margin-bottom:1.25rem;">
                    Une demande de paiement sera envoyée sur votre numéro. Validez-la dans l'app ou le menu USSD de votre opérateur.
                </p>

                <button type="submit" style="width:100%;background:#16a34a;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Déposer <?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?>
                </button>
            </form>
        </div>
    </div>
</div>
