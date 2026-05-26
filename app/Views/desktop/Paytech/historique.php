<?php
/**
 * GLOBALO — Historique paiements PayTech
 */
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$transactions = $transactions ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$statusLabels = [
    'pending' => ['label' => 'En attente', 'bg' => '#fef9c3', 'color' => '#854d0e'],
    'success' => ['label' => 'Confirmé',   'bg' => '#dcfce7', 'color' => '#166534'],
    'failed'  => ['label' => 'Annulé',     'bg' => '#fee2e2', 'color' => '#991b1b'],
];
$typeLabels = [
    'abonnement'          => 'Abonnement',
    'abonnement_client'   => 'Abo. Client',
    'abonnement_expert'   => 'Abo. Expert',
    'abonnement_etudiant' => 'Abo. Étudiant',
    'abonnement_professeur' => 'Abo. Professeur',
    'depot_portefeuille'  => 'Dépôt portefeuille',
];
?>
<div style="max-width:760px;margin:2rem auto;padding:0 1rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;margin:0;">Historique des paiements</h1>
        <a href="<?= $baseUrl ?>/abonnement" style="font-size:.85rem;color:#0d9488;text-decoration:none;">← Abonnement</a>
    </div>

    <?php if (empty($transactions)): ?>
        <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:2.5rem;text-align:center;color:#64748b;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:.75rem;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <p style="margin:0;font-size:.9rem;">Aucun paiement trouvé.</p>
        </div>
    <?php else: ?>
        <div style="border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;background:#fff;">
            <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:.75rem 1rem;text-align:left;color:#64748b;font-weight:600;">Référence</th>
                        <th style="padding:.75rem 1rem;text-align:left;color:#64748b;font-weight:600;">Type</th>
                        <th style="padding:.75rem 1rem;text-align:right;color:#64748b;font-weight:600;">Montant</th>
                        <th style="padding:.75rem 1rem;text-align:center;color:#64748b;font-weight:600;">Statut</th>
                        <th style="padding:.75rem 1rem;text-align:left;color:#64748b;font-weight:600;">Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <?php
                    $st = $statusLabels[$tx['status']] ?? ['label' => $e($tx['status']), 'bg' => '#f1f5f9', 'color' => '#475569'];
                    $tl = $typeLabels[$tx['type']] ?? $e($tx['type'] ?? '-');
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:.75rem 1rem;font-family:monospace;font-size:.8rem;color:#475569;"><?= $e($tx['payment_id']) ?></td>
                        <td style="padding:.75rem 1rem;color:#0f172a;"><?= $e($tl) ?></td>
                        <td style="padding:.75rem 1rem;text-align:right;font-weight:600;color:#0f172a;">
                            <?= number_format((float) ($tx['total_amount'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?>
                        </td>
                        <td style="padding:.75rem 1rem;text-align:center;">
                            <span style="display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.75rem;font-weight:600;background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="padding:.75rem 1rem;color:#64748b;white-space:nowrap;">
                            <?= $e(date('d/m/Y H:i', strtotime((string) ($tx['created_at'] ?? '')))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
