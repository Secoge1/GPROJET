<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$demande     = $demande ?? null;
$reservation = $reservation ?? null;
$mission     = $mission ?? null;
$expertFound = !empty($reservation);
$bp = $client_base_path ?? '/client';
?>

<div style="text-align:center;padding:1rem 0 1.5rem">

    <?php if ($expertFound): ?>
    <!-- Expert trouvé ! -->
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
        <div style="font-size:2.5rem;margin-bottom:0.5rem">🎉</div>
        <h1 style="margin:0 0 0.35rem;font-size:1.2rem;font-weight:800;color:#15803d">Un expert a accepté !</h1>
        <p style="margin:0;font-size:0.88rem;color:#15803d">Procédez au paiement pour démarrer la mission.</p>
    </div>
    <a href="<?= $baseUrl . $bp ?>/reservations/<?= (int)$reservation['id'] ?>" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem;background:#7c3aed">
        💳 Voir et payer
    </a>

    <?php elseif ($mission && ($mission['statut'] ?? '') === 'en_attente'): ?>
    <!-- En attente -->
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
        <div style="font-size:2.5rem;margin-bottom:0.75rem">⏳</div>
        <h1 style="margin:0 0 0.5rem;font-size:1.2rem;font-weight:800;color:#92400e">En attente d'un expert…</h1>
        <p style="margin:0 0 0.5rem;font-size:0.88rem;color:#92400e">
            « <?= $e($demande['titre'] ?? '') ?> »
        </p>
        <p style="margin:0;font-size:0.8rem;color:#b45309">Les experts disponibles ont été notifiés. Rechargez la page pour actualiser.</p>
    </div>
    <!-- Indicateur de progression animé -->
    <div style="display:flex;justify-content:center;gap:0.4rem;margin-bottom:1.5rem">
        <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:pulse 1.2s ease-in-out infinite"></span>
        <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:pulse 1.2s ease-in-out 0.4s infinite"></span>
        <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:pulse 1.2s ease-in-out 0.8s infinite"></span>
    </div>
    <a href="<?= $baseUrl . ($bp === '/app' ? '/app/urgence-attente/' : $bp . '/urgence/attente/') . (int)$demande['id'] ?>" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        🔄 Actualiser
    </a>

    <?php else: ?>
    <!-- Demande expirée ou annulée -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
        <div style="font-size:2.5rem;margin-bottom:0.5rem">❌</div>
        <h1 style="margin:0 0 0.35rem;font-size:1.1rem;font-weight:700;color:var(--primary)">Demande expirée</h1>
        <p style="margin:0;font-size:0.85rem;color:var(--text-muted)">Cette demande n'est plus en attente.</p>
    </div>
    <?php endif; ?>

    <a href="<?= $baseUrl . '/client' ?>" style="display:flex;align-items:center;justify-content:center;padding:0.85rem 1.25rem;border-radius:var(--radius);font-family:var(--font);font-size:0.9rem;font-weight:600;text-decoration:none;color:var(--text-muted);background:var(--surface);border:1.5px solid var(--border);min-height:48px">← Tableau de bord</a>
</div>

<style>
@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.6; }
}
</style>
