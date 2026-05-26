<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$transaction = $transaction ?? [];
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$role        = $user['role'] ?? 'client';
$isDepot     = ($is_depot ?? false) || ($transaction['type'] ?? '') === 'depot_portefeuille';
if ($role === 'expert') {
    $dashUrl = $baseUrl . '/expert';
} elseif (in_array($role, ['etudiant', 'professeur'], true)) {
    $dashUrl = $baseUrl . '/etudiant';
} else {
    $dashUrl = $baseUrl . '/client';
}
$status = $transaction['status'] ?? 'pending';

// Paiement de session : extraire le reservationId depuis les notes
$sessionReservationId = null;
if (($transaction['type'] ?? '') === 'paiement_session_touchpay' && !empty($transaction['notes'])) {
    if (str_starts_with((string) $transaction['notes'], 'session:')) {
        $sessionReservationId = (int) substr((string) $transaction['notes'], 8);
    }
}

if ($isDepot) {
    $statusConfig = [
        'pending' => ['icon' => '⏳', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'title' => 'Dépôt en suivi', 'msg' => 'La confirmation peut être automatique (webhook InTouch) ou manuelle par l’équipe.'],
        'success' => ['icon' => '✅', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac', 'title' => 'Dépôt validé', 'msg' => 'Votre portefeuille a été crédité.'],
        'failed'  => ['icon' => '❌', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca', 'title' => 'Dépôt refusé', 'msg' => 'Contactez le support si besoin.'],
    ];
} else {
    $statusConfig = [
        'pending' => ['icon' => '⏳', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'title' => 'Paiement en suivi', 'msg' => 'Validation automatique ou par l’équipe sous 24h.'],
        'success' => ['icon' => '✅', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac', 'title' => 'Abonnement actif', 'msg' => 'Votre abonnement GLOBALO est à jour.'],
        'failed'  => ['icon' => '❌', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca', 'title' => 'Transaction refusée', 'msg' => 'Consultez l’historique ou contactez le support.'],
    ];
}
$cfg = $statusConfig[$status] ?? $statusConfig['pending'];
?>
<div style="max-width:480px;margin:3rem auto;padding:0 1rem;text-align:center;">
    <?php
    $mm_logo_size = 'sm';
    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem;margin-bottom:1rem;';
    require APP_PATH . '/Views/partials/mm_operator_logos.php';
    ?>
    <div style="background:<?= $cfg['bg'] ?>;border:1.5px solid <?= $cfg['border'] ?>;border-radius:16px;padding:2rem;margin-bottom:1.5rem;">
        <div style="font-size:3.5rem;margin-bottom:.75rem;"><?= $cfg['icon'] ?></div>
        <h1 style="color:<?= $cfg['color'] ?>;font-size:1.3rem;font-weight:700;margin:0 0 .5rem;"><?= $cfg['title'] ?></h1>
        <p style="color:#374151;font-size:.9rem;margin:0 0 1rem;"><?= $cfg['msg'] ?></p>
        <div style="display:inline-flex;font-size:.8rem;font-family:monospace;color:#374151;">Réf : <?= $e($transaction['payment_id'] ?? '') ?></div>
    </div>
    <div class="card" style="border-radius:12px;padding:1.25rem;text-align:left;margin-bottom:1.25rem;">
        <table style="width:100%;font-size:.875rem;">
            <tr><td style="color:#6b7280;">Total</td><td style="text-align:right;font-weight:700;"><?= number_format((float) ($transaction['total_amount'] ?? 0), 0, ',', ' ') ?> XOF</td></tr>
            <tr><td style="color:#6b7280;">Fournisseur</td><td style="text-align:right;">InTouch</td></tr>
        </table>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
        <?php if ($sessionReservationId): ?>
        <a href="<?= $baseUrl ?>/client/payer/<?= $sessionReservationId ?>"
           style="background:#7c3aed;color:#fff;text-decoration:none;border-radius:10px;padding:.75rem 1.5rem;font-weight:600;font-size:.9rem;">
            Finaliser le paiement de la mission →
        </a>
        <?php endif; ?>
        <a href="<?= $baseUrl ?>/intouch/historique" style="background:#0d9488;color:#fff;text-decoration:none;border-radius:10px;padding:.75rem 1.5rem;font-weight:600;font-size:.9rem;">Historique</a>
        <a href="<?= $dashUrl ?>" style="background:#f1f5f9;color:#374151;text-decoration:none;border-radius:10px;padding:.75rem 1.5rem;font-weight:600;font-size:.9rem;">Tableau de bord</a>
    </div>
</div>
