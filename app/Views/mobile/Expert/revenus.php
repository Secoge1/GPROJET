<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$solde        = (float)($solde ?? 0);
$totalGains   = (float)($totalGains ?? 0);
$transactions = $transactions ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$prefix       = $expert_path_prefix ?? '/expert';
$waveDepotsRv = $wave_depots ?? [];
$paytechOkRv  = !empty($paytech_configured);
$mmFallbackRv = isset($mm_fallback_deposit_url) && (string) $mm_fallback_deposit_url !== '' ? (string) $mm_fallback_deposit_url : null;
$retraitPossible = $solde >= 10;

$type_lb = ['mission'=>'Mission','bonus'=>'Bonus','retrait'=>'Retrait','remboursement'=>'Remboursement','paiement_session'=>'Paiement mission'];
$type_cl = ['mission'=>'#16a34a','bonus'=>'#7c3aed','retrait'=>'#dc2626','remboursement'=>'#2563eb'];
?>

<h1 style="margin:0 0 1.25rem;font-size:1.2rem;font-weight:700;color:var(--primary)">Mes revenus</h1>

<!-- Solde principal -->
<div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:var(--radius);padding:1.25rem;margin-bottom:0.75rem;color:#fff">
    <p style="margin:0 0 0.25rem;font-size:0.8rem;opacity:0.85">Solde disponible</p>
    <p style="margin:0;font-size:1.75rem;font-weight:800;letter-spacing:-0.02em"><?= number_format($solde, 0, ',', ' ') ?> <span style="font-size:0.9rem;font-weight:500"><?= $e($devise) ?></span></p>
</div>

<!-- Total gains -->
<div class="mob-revenus-gains-row">
    <span class="mob-revenus-gains-row__num"><?= number_format($totalGains, 0, ',', ' ') ?></span>
    <span class="mob-revenus-gains-row__lbl">Total gains cumulés · <?= $e($devise) ?></span>
</div>

<!-- Retrait — CTA pleine largeur (lisible au pouce sur app) -->
<a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>"
   class="mob-revenus-withdraw <?= $retraitPossible ? '' : 'mob-revenus-withdraw--low' ?>"
   <?= $retraitPossible ? '' : 'aria-describedby="mob-retrait-hint"' ?>>
    <span class="mob-revenus-withdraw__badge" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><path d="M3 21v-4a4 4 0 0 1 4-4h14"/></svg>
    </span>
    <span class="mob-revenus-withdraw__main">
        <span class="mob-revenus-withdraw__title">Demander un retrait</span>
        <span class="mob-revenus-withdraw__sub"><?= $retraitPossible ? 'Orange Money · Moov · Wave' : 'Solde minimum 10 ' . $e($devise) ?></span>
    </span>
    <svg class="mob-revenus-withdraw__arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
</a>
<?php if (!$retraitPossible): ?>
<p id="mob-retrait-hint" style="margin:-0.2rem 0 1rem;font-size:.71rem;color:var(--text-muted);padding:0 .15rem;line-height:1.35;">
    Créditez votre solde pour atteindre le minimum puis lancez un retrait.
</p>
<?php endif; ?>


<?php if ($paytechOkRv): ?>
<div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:var(--radius);padding:1rem;margin-bottom:1rem;">
    <p style="margin:0 0 .45rem;font-size:.82rem;font-weight:700;color:#0f766e;">Créditer le portefeuille · Service de paiement</p>
    <p style="margin:0 0 .85rem;font-size:.76rem;color:var(--text-muted);line-height:1.45;">Paiement Mobile Money sécurisé — confirmation automatique.</p>
    <a href="<?= $e($baseUrl . '/paytech/depot') ?>" style="display:block;text-align:center;padding:.72rem;background:#0d9488;color:#fff;border-radius:var(--radius);font-weight:700;font-size:.82rem;text-decoration:none;">Continuer avec le service de paiement</a>
    <a href="<?= $e($baseUrl . '/paytech/historique') ?>" style="display:block;text-align:center;margin-top:.5rem;font-size:.74rem;color:#0d9488;font-weight:600;text-decoration:none;">Historique des paiements →</a>
</div>
<?php elseif ($mmFallbackRv !== null): ?>
<div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:var(--radius);padding:1rem;margin-bottom:1rem;">
    <p style="margin:0 0 .65rem;font-size:.76rem;color:var(--text-muted);">Pour activer le service de paiement direct, contactez l'administrateur.</p>
    <a href="<?= $e($mmFallbackRv) ?>" style="display:block;text-align:center;padding:.72rem;background:#475569;color:#fff;border-radius:var(--radius);font-weight:700;font-size:.82rem;text-decoration:none;">Recharger en Mobile Money</a>
</div>
<?php else: ?>
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius);padding:.85rem;margin-bottom:1rem;font-size:.73rem;color:#92400e;">
    Aucune passerelle de dépôt configurée. Contactez l'administrateur pour activer le service de paiement.
</div>
<?php endif; ?>

<?php if (!empty($waveDepotsRv)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem;">
    <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:.85rem;font-weight:700;color:var(--primary)">Dépôts en cours</h2>
    </div>
    <?php foreach ($waveDepotsRv as $wd): ?>
    <?php
    $st = strtolower((string) ($wd['status'] ?? ''));
    $pr = strtolower((string) ($wd['provider'] ?? ''));
    ?>
    <div style="padding:.65rem 1rem;border-bottom:1px solid var(--border);font-size:.78rem;">
        <div style="font-family:monospace;color:var(--text-muted);font-size:.7rem;margin-bottom:.2rem"><?= $e((string)($wd['payment_id'] ?? '')) ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-weight:700;color:#16a34a"><?= number_format((float)($wd['amount'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?></span>
            <span style="color:var(--text-muted)"><?= $e($st) ?></span>
        </div>
        <?php if ($st === 'pending'): ?>
        <?php $hf = ($pr === 'paytech') ? ($baseUrl . '/paytech/historique') : ($baseUrl . '/intouch/verification/' . rawurlencode((string)($wd['payment_id'] ?? ''))); ?>
        <a href="<?= $e($hf) ?>" style="display:inline-block;margin-top:.35rem;color:#d97706;font-weight:700;font-size:.74rem;text-decoration:none;">Suivre →</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Historique -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Historique des transactions</h2>
    </div>
    <?php if (empty($transactions)): ?>
    <div style="padding:1.5rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0">Aucune transaction pour le moment.</p>
    </div>
    <?php else: ?>
    <?php foreach ($transactions as $t): ?>
    <?php
    $type = $t['type'] ?? 'mission';
    $tc   = $type_cl[$type] ?? '#6b7280';
    $tl   = $type_lb[$type] ?? (str_replace('_', ' ', ucfirst((string) $type)));
    $mnt  = (float)($t['montant_net_expert'] ?? $t['montant'] ?? 0);
    $isRetrait = $type === 'retrait';
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid var(--border)">
        <div>
            <p style="margin:0 0 0.15rem;font-size:0.87rem;font-weight:600;color:var(--primary)"><?= $tl ?></p>
            <p style="margin:0;font-size:0.72rem;color:var(--text-muted)"><?= !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '' ?></p>
        </div>
        <span style="font-weight:700;font-size:0.92rem;color:<?= $isRetrait ? '#dc2626' : '#16a34a' ?>">
            <?= $isRetrait ? '-' : '+' ?><?= number_format($mnt, 0, ',', ' ') ?> <?= $e($devise) ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
