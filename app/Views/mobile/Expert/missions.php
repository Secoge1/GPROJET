<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$missions = $missions ?? [];
$unreadReservationIds = isset($unreadReservationIds) && is_array($unreadReservationIds) ? array_map('intval', $unreadReservationIds) : [];
$reservationNotifExtraHint = !empty($reservationNotifExtraHint);

$statut_lb = ['en_cours'=>'En cours','terminee'=>'Terminée','en_attente'=>'En attente','annulee'=>'Annulée'];
$statut_cl = ['en_cours'=>'#16a34a','terminee'=>'#6b7280','en_attente'=>'#f59e0b','annulee'=>'#dc2626'];
?>

<div style="margin-bottom:1.25rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">Mes missions</h1>
    <p style="margin:0.2rem 0 0;font-size:0.82rem;color:var(--text-muted)"><?= count($missions) ?> mission<?= count($missions) > 1 ? 's' : '' ?></p>
    <?php if ($reservationNotifExtraHint): ?>
    <p style="margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted);line-height:1.35">Certaines alertes concernent les urgences ou un écran différent — elles sont marquées comme vues.</p>
    <?php endif; ?>
</div>

<?php if (empty($missions)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1" style="margin-bottom:1rem"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    <p class="mobile-empty-hint">Aucune mission pour le moment.</p>
    <a href="<?= $baseUrl ?>/expert/demandes" class="btn-mobile btn-outline" style="display:inline-flex;margin-top:0.75rem">Voir les demandes</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($missions as $m): ?>
    <?php $sc = $statut_cl[$m['statut']] ?? '#6b7280'; ?>
    <?php $mid = (int)($m['id'] ?? 0); ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-left:3px solid <?= $sc ?>;border-radius:var(--radius);padding:1rem;box-shadow:var(--shadow)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.5rem">
            <p style="margin:0;font-weight:<?= $mid && in_array($mid, $unreadReservationIds, true) ? '800' : '700' ?>;font-size:0.92rem;color:var(--primary);flex:1;min-width:0;display:flex;align-items:center;gap:0.35rem"><?php if ($mid && in_array($mid, $unreadReservationIds, true)): ?><span class="mobile-list-unread-dot mobile-list-unread-dot--inline" title="Nouvelle alerte" aria-label="Nouvelle alerte"></span><?php endif; ?><?= $e($m['demande_titre'] ?? '') ?></p>
            <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
                <?= $statut_lb[$m['statut']] ?? $e($m['statut']) ?>
            </span>
        </div>
        <p style="margin:0 0 0.6rem;font-size:0.78rem;color:var(--text-muted)">
            Client : <?= $e(trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''))) ?>
            <?php if (!empty($m['duree_heures'])): ?> · <?= $m['duree_heures'] ?>h<?php endif; ?>
            <?php if (!empty($m['montant_net_expert'])): ?> · <strong><?= number_format((float)$m['montant_net_expert'], 0, ',', ' ') ?> <?= $e(devise()) ?></strong><?php endif; ?>
        </p>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
            <?php if (($m['statut'] ?? '') === 'en_cours'): ?>
            <a href="<?= $baseUrl ?>/expert/livrer/<?= (int)($m['reservation_id'] ?? $m['id']) ?>" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.3rem">
                📤 Livrer
            </a>
            <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)($m['reservation_id'] ?? $m['id']) ?>" class="btn-mobile btn-outline btn-sm" style="display:inline-flex">Message</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
