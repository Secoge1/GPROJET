<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$abonnementType = $abonnement_type ?? 'client';
$montant        = (float) ($montant ?? 0);
$commission     = (float) ($commission ?? 0);
$total          = (float) ($total ?? 0);
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField      = \App\Core\Security::getCsrfField();

$typeLabels = [
    'client'     => 'Client',
    'expert'     => 'Expert',
    'etudiant'   => 'Étudiant',
    'professeur' => 'Professeur',
];
?>
<div class="page-intouch-paiement" style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $baseUrl ?>/abonnement" style="display:inline-flex;align-items:center;gap:0.4rem;color:var(--color-primary,#2563eb);font-size:0.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger" style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;">
            <?= $e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 0.25rem;">Payer en Mobile Money</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Mobile Money · Abonnement <?= $e($typeLabels[$abonnementType] ?? $abonnementType) ?></p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php
            $mm_logo_size = 'sm';
            $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php';
            ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Abonnement</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> XOF</span>
            </div>
            <?php if ($commission > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Frais de service</span>
                <span><?= number_format($commission, 0, ',', ' ') ?> XOF</span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total à payer</span>
                <span style="color:#0d9488;"><?= number_format($total, 0, ',', ' ') ?> XOF</span>
            </div>
        </div>

        <div style="padding:1.5rem;">
            <form method="post" action="<?= $baseUrl ?>/intouch/initier" id="form-intouch-paiement">
                <?= $csrfField ?>
                <input type="hidden" name="abonnement_type" value="<?= $e($abonnementType) ?>">

                <div style="margin-bottom:1.25rem;">
                    <?php
                    $intouch_op_suffix = 'abo-intouch';
                    require APP_PATH . '/Views/partials/intouch_operator_picker.php';
                    ?>
                </div>

                <label for="phone" style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:.5rem;">
                    Votre numéro (Mali : 8 chiffres après +223)
                </label>
                <div style="position:relative;margin-bottom:1.25rem;">
                    <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.9rem;">+223</span>
                    <input type="tel" id="phone" name="phone" placeholder="76 XX XX XX" required maxlength="12" pattern="[0-9]{8}"
                        style="width:100%;padding:.75rem 1rem .75rem 3.5rem;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;box-sizing:border-box;">
                </div>

                <p style="font-size:.8rem;color:#6b7280;background:#f0fdfa;border-radius:8px;padding:.75rem;margin-bottom:1.25rem;">
                    Une demande de paiement sera envoyée sur votre numéro. Validez-la dans l’app ou le menu USSD de votre opérateur.
                </p>

                <button type="submit" id="btn-continuer" style="width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;cursor:pointer;">
                    Continuer
                </button>
            </form>
        </div>
    </div>
</div>
