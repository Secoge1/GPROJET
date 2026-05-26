<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn ($s) => \App\Core\Security::escape((string) ($s ?? ''));
$transactions = $transactions ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$statusLabels = [
    'pending' => ['label' => 'En attente', 'bg' => '#fef9c3', 'color' => '#854d0e'],
    'success' => ['label' => 'Confirmé',   'bg' => '#dcfce7', 'color' => '#166534'],
    'failed'  => ['label' => 'Annulé',    'bg' => '#fee2e2', 'color' => '#991b1b'],
];
$typeLabels = [
    'abonnement'               => 'Abonnement',
    'abonnement_client'       => 'Abo. client',
    'abonnement_expert'       => 'Abo. expert',
    'abonnement_etudiant'     => 'Abo. étudiant',
    'abonnement_professeur'    => 'Abo. professeur',
    'depot_portefeuille'      => 'Dépôt portefeuille',
    'paiement_session_paytech'=> 'Mission (crédit)',
];
?>
<div style="padding:0 .15rem 1.25rem;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <h1 style="font-size:1.05rem;font-weight:700;margin:0;color:var(--primary);">Historique des paiements</h1>
        <a href="<?= $e($baseUrl . '/abonnement') ?>" style="font-size:.76rem;color:#0d9488;font-weight:600;text-decoration:none;">← Abonnement</a>
    </div>

    <?php if (empty($transactions)): ?>
    <div style="border-radius:var(--radius);background:var(--card-bg);border:1px solid var(--border);padding:2rem 1rem;text-align:center;color:var(--text-muted);font-size:.85rem;">
        Aucun paiement Service de paiement trouvé.
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:.65rem;">
        <?php foreach ($transactions as $tx): ?>
            <?php
            $st = $statusLabels[$tx['status']] ?? ['label' => $e((string) ($tx['status'] ?? '')), 'bg' => '#f1f5f9', 'color' => '#475569'];
            $tl = $typeLabels[$tx['type']] ?? ($e((string) ($tx['type'] ?? '—')));
            ?>
            <div style="border-radius:var(--radius);border:1px solid var(--border);background:var(--card-bg);padding:.85rem 1rem;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.35rem;">
                    <span style="font-size:.8rem;font-weight:700;color:var(--text);"><?= $e($tl) ?></span>
                    <span style="display:inline-block;padding:.15rem .5rem;border-radius:999px;font-size:.67rem;font-weight:700;background:<?= $e($st['bg']) ?>;color:<?= $e($st['color']) ?>;">
                        <?= $e($st['label']) ?>
                    </span>
                </div>
                <div style="font-family:monospace;font-size:.7rem;color:var(--text-muted);word-break:break-all;margin-bottom:.45rem;"><?= $e((string) ($tx['payment_id'] ?? '')) ?></div>
                <div style="display:flex;justify-content:space-between;align-items:baseline;font-size:.88rem;">
                    <span style="color:var(--text-muted);font-size:.74rem;">
                        <?= $e(date('d/m/Y H:i', strtotime((string) ($tx['created_at'] ?? 'now')))) ?>
                    </span>
                    <strong style="font-size:.95rem;color:var(--text);"><?= number_format((float) ($tx['total_amount'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
