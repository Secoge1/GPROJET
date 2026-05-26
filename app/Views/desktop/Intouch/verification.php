<?php
$baseUrl              = rtrim(BASE_URL ?? '', '/');
$transaction          = $transaction ?? [];
$instructions         = $instructions ?? [];
$paymentId            = $payment_id ?? '';
$intouchPushPending   = !empty($intouch_push_pending);
$adminPending         = !empty($admin_pending);
$e                    = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField            = \App\Core\Security::getCsrfField();
$total                = number_format((float) ($instructions['montant_total'] ?? 0), 0, ',', ' ');
$reference            = $e($instructions['reference'] ?? $paymentId);
$etapes               = $instructions['etapes'] ?? [];
?>
<div class="page-intouch-verification" style="max-width:560px;margin:2.5rem auto;padding:0 1rem;">
    <?php
    $mm_logo_size = 'sm';
    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem;margin-bottom:1rem;';
    require APP_PATH . '/Views/partials/mm_operator_logos.php';
    ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;">
            <?= $e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($intouchPushPending): ?>
    <div style="background:#f0fdfa;border:1.5px solid #5eead4;border-radius:14px;padding:1.5rem;text-align:center;margin-bottom:1.5rem;">
        <h2 style="color:#0f766e;margin:0 0 .5rem;font-size:1.15rem;">Demande envoyée sur votre téléphone</h2>
        <p style="color:#115e59;font-size:.9rem;margin:0;">
            Validez le paiement sur votre application Mobile Money. La confirmation est automatique en général sous quelques minutes.
        </p>
        <a href="<?= $baseUrl ?>/intouch/verification/<?= $e($paymentId) ?>" style="display:inline-block;margin-top:1rem;color:#0d9488;font-weight:600;font-size:.9rem;">Actualiser cette page</a>
    </div>
    <?php elseif ($adminPending): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:1.5rem;text-align:center;margin-bottom:1.5rem;">
        <h2 style="color:#16a34a;margin:0 0 .5rem;font-size:1.15rem;">En attente de validation</h2>
        <p style="color:#166534;font-size:.9rem;margin:0;">Notre équipe finalise votre dossier si nécessaire.</p>
        <a href="<?= $baseUrl ?>/intouch/historique" style="display:inline-block;margin-top:1rem;color:#16a34a;font-weight:600;">Historique →</a>
    </div>
    <?php endif; ?>

    <div class="card" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;margin-bottom:1.25rem;">
        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.25rem 1.5rem;color:#fff;">
            <h1 style="font-size:1.2rem;font-weight:700;margin:0 0 .25rem;">Paiement InTouch</h1>
            <p style="opacity:.85;font-size:.85rem;margin:0;">Référence : <strong><?= $reference ?></strong></p>
        </div>
        <div style="background:#fff;padding:1rem 1.5rem;text-align:center;border-bottom:1px solid #e2e8f0;">
            <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem;">Montant total</div>
            <div style="font-size:1.5rem;font-weight:700;color:#0d9488;"><?= $total ?> <span style="font-size:.85rem;">XOF</span></div>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            <ol style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.6rem;">
                <?php foreach ($etapes as $i => $etape): ?>
                <li style="display:flex;align-items:flex-start;gap:.6rem;font-size:.875rem;color:#374151;">
                    <span style="flex-shrink:0;width:22px;height:22px;background:#ccfbf1;color:#0f766e;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;"><?= $i + 1 ?></span>
                    <?= is_string($etape) ? $etape : '' ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>

    <?php if (($transaction['status'] ?? '') === 'pending' && $intouchPushPending): ?>
    <div class="card" style="border-radius:14px;padding:1.5rem;">
        <h2 style="font-size:1rem;font-weight:700;margin:0 0 .5rem;">Référence opérateur (optionnel)</h2>
        <p style="font-size:.82rem;color:#64748b;margin:0 0 1rem;">Utile si le support doit retrouver votre paiement manuellement.</p>
        <form method="post" action="<?= $baseUrl ?>/intouch/soumettre" id="form-soumettre">
            <?= $csrfField ?>
            <input type="hidden" name="payment_id" value="<?= $e($paymentId) ?>">
            <input type="text" name="transaction_code" placeholder="Ex. référence SMS opérateur" maxlength="40" pattern="[A-Za-z0-9\-]{4,40}" style="width:100%;padding:.75rem;border:1.5px solid #d1d5db;border-radius:10px;margin-bottom:1rem;">
            <button type="submit" style="width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.75rem;font-weight:600;">Enregistrer</button>
        </form>
    </div>
    <?php elseif (!$adminPending && ($transaction['status'] ?? '') === 'pending'): ?>
    <div class="card" style="border-radius:14px;padding:1.5rem;">
        <form method="post" action="<?= $baseUrl ?>/intouch/soumettre" id="form-soumettre">
            <?= $csrfField ?>
            <input type="hidden" name="payment_id" value="<?= $e($paymentId) ?>">
            <input type="text" name="transaction_code" required maxlength="40" style="width:100%;padding:.75rem;border:1.5px solid #d1d5db;border-radius:10px;margin-bottom:1rem;">
            <button type="submit" style="width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.75rem;font-weight:600;">Enregistrer</button>
        </form>
    </div>
    <?php endif; ?>

    <p style="text-align:center;margin-top:1rem;">
        <a href="<?= $baseUrl ?>/intouch/historique" style="color:#0d9488;font-size:.9rem;">Voir l’historique</a>
    </p>
</div>
