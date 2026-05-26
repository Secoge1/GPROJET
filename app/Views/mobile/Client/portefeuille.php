<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$solde        = (float)($solde ?? 0);
$transactions = $transactions ?? [];
$waveDepots   = $wave_depots ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$flashError   = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$type_lb = ['depot'=>'Dépôt','paiement'=>'Paiement','remboursement'=>'Remboursement','bonus'=>'Bonus'];
$type_cl = ['depot'=>'#16a34a','paiement'=>'#dc2626','remboursement'=>'#2563eb','bonus'=>'#7c3aed'];
?>

<h1 style="margin:0 0 1.25rem;font-size:1.2rem;font-weight:700;color:var(--primary)">Mon portefeuille</h1>

<!-- Solde -->
<div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.25rem;color:#fff;text-align:center">
    <p style="margin:0 0 0.5rem;font-size:0.85rem;opacity:0.85">Solde disponible</p>
    <p style="margin:0;font-size:2rem;font-weight:800;letter-spacing:-0.02em">
        <?= number_format($solde, 0, ',', ' ') ?> <span style="font-size:1rem;font-weight:500"><?= $e($devise) ?></span>
    </p>
</div>

<?php if ($flashError): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:0.85rem;color:#dc2626">⚠️ <?= $e($flashError) ?></p>
</div>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:0.85rem;color:#16a34a">✅ <?= $e($flashSuccess) ?></p>
</div>
<?php endif; ?>

<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
    <h2 style="margin:0 0 0.75rem;font-size:0.9rem;font-weight:700;color:var(--primary);">
        Recharger le portefeuille
    </h2>

    <?php if (!empty($paytech_configured)): ?>
    <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:8px;padding:.85rem;margin-bottom:1rem;">
        <p style="margin:0 0 .5rem;font-size:.8rem;font-weight:600;color:#0f766e;">Service de paiement — service de paiement</p>
        <p style="margin:0 0 .85rem;font-size:.76rem;color:var(--text-muted);line-height:1.45;">Paiement Mobile Money sécurisé — confirmation automatique.</p>
        <a href="<?= $baseUrl ?>/paytech/depot" style="display:block;text-align:center;padding:.75rem;background:#0d9488;color:#fff;border-radius:var(--radius);font-weight:700;font-size:.85rem;text-decoration:none;">Continuer avec le service de paiement</a>
        <a href="<?= $baseUrl ?>/paytech/historique" style="display:block;text-align:center;margin-top:.55rem;font-size:.76rem;color:#0d9488;font-weight:600;">Historique des paiements →</a>
    </div>
    <?php elseif (!empty($touchpay_configured) || !empty($intouch_api_configured)): ?>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.85rem;margin-bottom:1rem;">
        <p style="margin:0 0 .75rem;font-size:.76rem;color:var(--text-muted);line-height:1.45;">Repli Mobile Money tant que Service de paiement n’est pas configuré sur le serveur.</p>
        <a href="<?= $baseUrl ?>/intouch/touchpay-depot" style="display:block;text-align:center;padding:.75rem;background:#475569;color:#fff;border-radius:var(--radius);font-weight:700;font-size:.85rem;text-decoration:none;">Recharger en Mobile Money</a>
    </div>
    <?php else: ?>
    <p style="margin:0;font-size:.82rem;color:#b45309;">Aucune passerelle de dépôt configurée. Contactez l'administrateur pour activer le service de paiement Mobile Money.</p>
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
        $wsl = $codeOk ? 'À valider (admin)' : 'Saisir le code';
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
                    <?= !empty($wd['created_at']) ? date('d/m/Y à H:i', strtotime($wd['created_at'])) : '' ?>
                </p>
            </div>
            <span style="flex-shrink:0;font-size:.72rem;font-weight:700;color:<?= $wsc ?>;background:<?= $wsc ?>18;padding:.2rem .5rem;border-radius:20px">
                <?= $wsl ?>
            </span>
        </div>
        <?php if ($ws === 'pending'):
            $wdProvMob = strtolower((string) ($wd['provider'] ?? ''));
            $suivMob   = ($wdProvMob === 'paytech')
                ? ($baseUrl . '/paytech/historique')
                : ($baseUrl . '/intouch/verification/' . rawurlencode((string) ($wd['payment_id'] ?? '')));
            $lblFollow = ($wdProvMob === 'paytech') ? 'Suivre le statut du paiement →' : 'Saisir le code de transaction →';
        ?>
        <a href="<?= $e($suivMob) ?>"
           style="display:inline-block;margin-top:.55rem;font-size:.78rem;font-weight:600;color:#fff;background:#0284c7;padding:.4rem .75rem;border-radius:8px;text-decoration:none">
            <?= $e($lblFollow) ?>
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
                <?= !empty($t['created_at']) ? date('d/m/Y à H:i', strtotime($t['created_at'])) : '' ?>
            </p>
        </div>
        <span style="font-weight:700;font-size:.9rem;color:<?= $isPositive ? '#16a34a' : '#dc2626' ?>">
            <?= $isPositive ? '+' : '-' ?><?= number_format($mnt, 0, ',', ' ') ?> <?= $e($devise) ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
