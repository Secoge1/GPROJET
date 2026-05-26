<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$basePath     = $base_path ?? ((($user['role'] ?? '') === 'professeur') ? '/professeur' : '/etudiant');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$solde        = (float)($solde ?? 0);
$transactions = $transactions ?? [];
$waveDepots   = $wave_depots ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$isProfesseur = ($user['role'] ?? '') === 'professeur';

$flashError   = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$type_lb = ['depot'=>'Dépôt','paiement'=>'Paiement','remboursement'=>'Remboursement','bonus'=>'Bonus'];
$type_cl = ['depot'=>'#16a34a','paiement'=>'#dc2626','remboursement'=>'#2563eb','bonus'=>'#7c3aed'];
?>

<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl . $basePath ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary);flex:1">Mon portefeuille</h1>
    <?php if ($isProfesseur): ?>
    <a href="<?= $baseUrl . $basePath ?>/retrait-choix" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 0.85rem;font-size:0.8rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Retrait
    </a>
    <?php endif; ?>
</div>

<!-- Solde -->
<div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.25rem;color:#fff;text-align:center">
    <p style="margin:0 0 0.5rem;font-size:0.85rem;opacity:0.85">Solde disponible</p>
    <p style="margin:0;font-size:2rem;font-weight:800;letter-spacing:-0.02em">
        <?= number_format($solde, 0, ',', ' ') ?> <span style="font-size:1rem;font-weight:500"><?= $e($devise) ?></span>
    </p>
</div>

<?php if ($flashError): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:0.85rem;color:#dc2626">&#9888;&#65039; <?= $e($flashError) ?></p>
</div>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:0.85rem;color:#16a34a">&#9989; <?= $e($flashSuccess) ?></p>
</div>
<?php endif; ?>

<!-- Recharger le portefeuille -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h2 style="margin:0;font-size:.9rem;font-weight:700;color:var(--primary)">Recharger votre portefeuille</h2>
        <span style="font-size:.7rem;background:#dcfce7;color:#16a34a;font-weight:700;padding:.2rem .55rem;border-radius:20px">Mobile Money</span>
    </div>

    <?php if (!empty($paytech_configured)): ?>
    <div style="padding:1rem">
        <!-- Logos opérateurs -->
        <p style="margin:0 0 .6rem;font-size:.75rem;color:var(--text-muted);font-weight:500">Opérateurs acceptés</p>
        <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.95rem">
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/wave.png" alt="Wave" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/orange-money.png" alt="Orange Money" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/moov-africa.png" alt="Moov Africa" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
        </div>
        <!-- CTA -->
        <a href="<?= $baseUrl ?>/paytech/depot"
           style="display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.82rem;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:12px;font-weight:700;font-size:.88rem;text-decoration:none;box-shadow:0 4px 14px rgba(22,163,74,.3)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Récharger votre Portefeuille
        </a>
        <a href="<?= $baseUrl ?>/paytech/historique"
           style="display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.6rem;font-size:.77rem;color:var(--text-muted);font-weight:600;text-decoration:none;padding:.4rem">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Historique des recharges
        </a>
    </div>
    <?php elseif (!empty($touchpay_configured) || !empty($intouch_api_configured)): ?>
    <div style="padding:1rem">
        <p style="margin:0 0 .6rem;font-size:.75rem;color:var(--text-muted);font-weight:500">Opérateurs acceptés</p>
        <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.95rem">
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/wave.png" alt="Wave" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/orange-money.png" alt="Orange Money" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .5rem;height:48px">
                <img src="<?= $baseUrl ?>/assets/images/operators/moov-africa.png" alt="Moov Africa" style="max-height:36px;max-width:100%;width:auto;object-fit:contain">
            </div>
        </div>
        <a href="<?= $baseUrl ?>/intouch/touchpay-depot"
           style="display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.82rem;background:linear-gradient(135deg,#475569,#334155);color:#fff;border-radius:12px;font-weight:700;font-size:.88rem;text-decoration:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Récharger votre Portefeuille
        </a>
    </div>
    <?php else: ?>
    <div style="padding:1.25rem 1rem;display:flex;align-items:flex-start;gap:.6rem">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0;margin-top:.1rem"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="margin:0;font-size:.82rem;color:#b45309;line-height:1.45">Aucune passerelle de paiement configurée. Contactez l'administrateur.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Recharges Mobile Money récentes -->
<?php if (!empty($waveDepots)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:.88rem;font-weight:700;color:var(--primary)">Recharges Mobile Money récentes</h2>
    </div>
    <?php
    $statusLabel = ['pending'=>'En attente','success'=>'Validé','failed'=>'Refusé'];
    $statusColor = ['pending'=>'#d97706','success'=>'#16a34a','failed'=>'#dc2626'];
    ?>
    <?php foreach ($waveDepots as $wd): ?>
    <?php
    $ws  = $wd['status'] ?? 'pending';
    if ($ws === 'pending') {
        $codeOk = !empty($wd['transaction_code']);
        $wsl = $codeOk ? 'A valider (admin)' : 'Saisir le code';
        $wsc = $codeOk ? '#d97706' : '#0284c7';
    } else {
        $wsl = $statusLabel[$ws] ?? ucfirst($ws);
        $wsc = $statusColor[$ws] ?? '#6b7280';
    }
    ?>
    <div style="padding:.65rem 1rem;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
            <div style="min-width:0">
                <p style="margin:0 0 .1rem;font-size:.84rem;font-weight:600;color:var(--primary)">
                    <?= number_format((float)($wd['amount'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?>
                </p>
                <p style="margin:0;font-size:.68rem;color:var(--text-muted);font-family:monospace">
                    <?= $e($wd['payment_id'] ?? '') ?>
                </p>
                <p style="margin:.15rem 0 0;font-size:.7rem;color:var(--text-muted)">
                    <?= !empty($wd['created_at']) ? date('d/m/Y H:i', strtotime($wd['created_at'])) : '' ?>
                </p>
            </div>
            <span style="flex-shrink:0;font-size:.72rem;font-weight:700;color:<?= $wsc ?>;background:<?= $wsc ?>18;padding:.2rem .5rem;border-radius:20px">
                <?= $wsl ?>
            </span>
        </div>
        <?php if ($ws === 'pending'):
            $wdProvEtM = strtolower((string) ($wd['provider'] ?? ''));
            $suivEtM   = ($wdProvEtM === 'paytech')
                ? ($baseUrl . '/paytech/historique')
                : ($baseUrl . '/intouch/verification/' . rawurlencode((string) ($wd['payment_id'] ?? '')));
            $lblEt = ($wdProvEtM === 'paytech') ? 'Suivre le statut du paiement' : 'Saisir le code de transaction';
        ?>
        <a href="<?= $e($suivEtM) ?>"
           style="display:inline-flex;align-items:center;gap:.3rem;margin-top:.55rem;font-size:.78rem;font-weight:600;color:#fff;background:#0284c7;padding:.4rem .75rem;border-radius:8px;text-decoration:none">
            <?= $e($lblEt) ?> &rarr;
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Historique transactions portefeuille -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:.88rem;font-weight:700;color:var(--primary)">Historique des transactions</h2>
    </div>
    <?php if (empty($transactions)): ?>
    <div style="padding:1.5rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0">Aucune transaction pour le moment.</p>
    </div>
    <?php else: ?>
    <?php foreach ($transactions as $t): ?>
    <?php
    $type       = $t['type'] ?? 'depot';
    $tc         = $type_cl[$type] ?? '#6b7280';
    $tl         = $type_lb[$type] ?? ucfirst($type);
    $mnt        = (float)($t['montant'] ?? 0);
    $isPositive = in_array($type, ['depot', 'remboursement', 'bonus']);
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;border-bottom:1px solid var(--border)">
        <div>
            <p style="margin:0 0 .1rem;font-size:.85rem;font-weight:600;color:var(--primary)"><?= $tl ?></p>
            <p style="margin:0;font-size:.7rem;color:var(--text-muted)">
                <?= !empty($t['created_at']) ? date('d/m/Y H:i', strtotime($t['created_at'])) : '' ?>
            </p>
        </div>
        <span style="font-weight:700;font-size:.9rem;color:<?= $isPositive ? '#16a34a' : '#dc2626' ?>">
            <?= $isPositive ? '+' : '-' ?><?= number_format($mnt, 0, ',', ' ') ?> <?= $e($devise) ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
