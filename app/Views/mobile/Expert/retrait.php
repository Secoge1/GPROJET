<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$solde     = (float)($solde ?? 0);
$demandes  = $demandes ?? [];
$errors    = $errors ?? [];
$devise    = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$prefix    = $expert_path_prefix ?? '/expert';
$operateur = strtoupper(trim((string)($operateur ?? '')));
$opLabels  = ['ORANGE' => 'Orange Money', 'MOOV' => 'Moov Africa', 'WAVE' => 'Wave'];
$opLibelle = $opLabels[$operateur] ?? $operateur;

$statut_lb = ['en_attente'=>'En attente','traitee'=>'Traité','refusee'=>'Refusé'];
$statut_cl = ['en_attente'=>'#f59e0b','traitee'=>'#16a34a','refusee'=>'#dc2626'];
?>

<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Demande de retrait</h1>
</div>

<?php if ($operateur !== ''): ?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.75rem 1rem;margin-bottom:1rem;background:#ecfdf5;border:1px solid #86efac;border-radius:var(--radius)">
    <div style="display:flex;align-items:center;gap:0.5rem;min-width:0">
        <span style="font-size:1.25rem" aria-hidden="true">📱</span>
        <div>
            <p style="margin:0;font-size:0.72rem;font-weight:600;color:#15803d;text-transform:uppercase;letter-spacing:0.04em">Opérateur choisi</p>
            <p style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)"><?= $e($opLibelle) ?></p>
        </div>
    </div>
    <a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>" style="flex-shrink:0;font-size:0.78rem;font-weight:600;color:var(--accent);text-decoration:none">Modifier</a>
</div>
<?php endif; ?>

<!-- Solde -->
<div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:var(--radius);padding:1rem 1.25rem;margin-bottom:1.25rem;color:#fff;display:flex;align-items:center;justify-content:space-between">
    <div>
        <p style="margin:0 0 0.1rem;font-size:0.78rem;opacity:0.85">Solde disponible</p>
        <p style="margin:0;font-size:1.35rem;font-weight:800"><?= number_format($solde, 0, ',', ' ') ?> <span style="font-size:0.8rem"><?= $e($devise) ?></span></p>
    </div>
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?><p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- Formulaire retrait -->
<?php if ($solde > 0): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
    <h2 style="margin:0 0 0.85rem;font-size:0.9rem;font-weight:700;color:var(--primary)">Nouvelle demande</h2>
    <form method="post" action="<?= $e($baseUrl . $prefix . '/retrait') ?>" class="form-mobile">
        <?= $csrfField ?>
        <input type="hidden" name="operateur" value="<?= $e($operateur) ?>">
        <div style="margin-bottom:0.85rem">
            <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Montant (<?= $e($devise) ?>) <span style="color:#dc2626">*</span></label>
            <input type="number" name="montant" min="500" max="<?= (int)$solde ?>" step="1" required
                   placeholder="Ex : 5000"
                   style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
            <p style="font-size:0.72rem;color:var(--text-muted);margin:0.25rem 0 0">Max : <?= number_format($solde, 0, ',', ' ') ?> <?= $e($devise) ?></p>
        </div>
        <div style="margin-bottom:0.65rem">
            <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">
                Numéro <?= $e($opLibelle) ?> <span style="color:#dc2626">*</span>
            </label>
            <p style="font-size:0.72rem;color:var(--text-muted);margin:0 0 0.4rem">Le numéro associé à votre compte <strong><?= $e($opLibelle) ?></strong> (avec indicatif pays).</p>
            <input type="text" name="iban" maxlength="34" required
                   placeholder="Ex : +223 70 00 00 00"
                   value="<?= $e($_POST['iban'] ?? '') ?>"
                   style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
            <p style="font-size:0.72rem;color:var(--text-muted);margin:0.25rem 0 0">Ce numéro doit correspondre à l’opérateur sélectionné ci-dessus.</p>
        </div>
        <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            Demander le retrait
        </button>
    </form>
</div>
<?php else: ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem;text-align:center">
    <p class="mobile-empty-hint" style="margin:0">Solde insuffisant pour faire un retrait.</p>
</div>
<?php endif; ?>

<!-- Historique retraits -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Historique des retraits</h2>
    </div>
    <?php if (empty($demandes)): ?>
    <div style="padding:1.25rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0">Aucune demande de retrait.</p>
    </div>
    <?php else: ?>
    <?php foreach ($demandes as $d): ?>
    <?php $sc = $statut_cl[$d['statut']] ?? '#6b7280'; ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid var(--border)">
        <div>
            <p style="margin:0 0 0.15rem;font-weight:700;font-size:0.9rem;color:var(--primary)"><?= number_format((float)$d['montant'], 0, ',', ' ') ?> <?= $e($devise) ?></p>
            <p style="margin:0;font-size:0.72rem;color:var(--text-muted)"><?= !empty($d['created_at']) ? date('d/m/Y', strtotime($d['created_at'])) : '' ?></p>
        </div>
        <span style="font-size:0.72rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
            <?= $statut_lb[$d['statut']] ?? $e($d['statut']) ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
