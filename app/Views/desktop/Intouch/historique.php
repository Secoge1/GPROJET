<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$transactions = $transactions ?? [];
$e            = fn($s) => \App\Core\Security::escape($s ?? '');

$statusConfig = [
    'pending' => ['label' => 'En attente', 'dot' => '#d97706'],
    'success' => ['label' => 'Validé', 'dot' => '#16a34a'],
    'failed'  => ['label' => 'Refusé', 'dot' => '#dc2626'],
];
?>
<div style="max-width:700px;margin:2.5rem auto;padding:0 1rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
        <h1 style="font-size:1.3rem;font-weight:700;margin:0;">Historique des paiements</h1>
        <a href="<?= $baseUrl ?>/abonnement" style="font-size:.85rem;font-weight:600;color:#0d9488;">← Abonnement</a>
    </div>
    <?php
    $mm_logo_size = 'sm';
    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-start;gap:.5rem;margin-bottom:1rem;';
    require APP_PATH . '/Views/partials/mm_operator_logos.php';
    ?>
    <?php if (empty($transactions)): ?>
    <p style="color:#64748b;">Aucun paiement pour le moment.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:.75rem;">
        <?php foreach ($transactions as $tx):
            $cfg = $statusConfig[$tx['status']] ?? $statusConfig['pending'];
        ?>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
            <div>
                <div style="font-weight:600;"><?= $e($tx['provider'] ?? '') ?> — <?= $e($tx['abonnement_type'] ?? '') ?></div>
                <div style="font-size:.78rem;font-family:monospace;color:#64748b;"><?= $e($tx['payment_id']) ?></div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;"><?= number_format((float) ($tx['total_amount'] ?? 0), 0, ',', ' ') ?> XOF</div>
                <span style="font-size:.78rem;color:<?= $cfg['dot'] ?>;"><?= $cfg['label'] ?></span>
            </div>
            <?php if ($tx['status'] === 'pending'): ?>
            <div style="width:100%;border-top:1px solid #f1f5f9;padding-top:.5rem;">
                <a href="<?= $baseUrl ?>/intouch/verification/<?= $e($tx['payment_id']) ?>" style="color:#0d9488;font-weight:600;font-size:.85rem;">Détail →</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
