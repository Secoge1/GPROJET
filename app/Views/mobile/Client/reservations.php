<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$reservations = $reservations ?? [];
$unreadReservationIds = isset($unreadReservationIds) && is_array($unreadReservationIds) ? array_map('intval', $unreadReservationIds) : [];
$reservationNotifExtraHint = !empty($reservationNotifExtraHint);

$statut_lb = ['en_attente'=>'En attente','acceptee'=>'Acceptée','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée','payee'=>'Payée'];
$statut_cl = ['en_attente'=>'#f59e0b','acceptee'=>'#2563eb','en_cours'=>'#16a34a','terminee'=>'#6b7280','annulee'=>'#dc2626','payee'=>'#7c3aed'];
$bp = $client_base_path ?? '/client';
?>

<div style="margin-bottom:1.25rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">Mes réservations</h1>
    <p style="margin:0.2rem 0 0;font-size:0.82rem;color:var(--text-muted)"><?= count($reservations) ?> réservation<?= count($reservations) > 1 ? 's' : '' ?></p>
    <?php if ($reservationNotifExtraHint): ?>
    <p style="margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted);line-height:1.35">Certaines alertes concernent les urgences ou un autre écran — elles sont marquées comme vues.</p>
    <?php endif; ?>
</div>

<?php if (empty($reservations)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1" style="margin-bottom:1rem"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <p class="mobile-empty-hint">Aucune réservation.</p>
    <a href="<?= $baseUrl . $bp ?>/demandes" class="btn-mobile btn-outline btn-sm" style="margin-top:0.75rem">Voir mes demandes</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($reservations as $r): ?>
    <?php $sc = $statut_cl[$r['statut']] ?? '#6b7280'; ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-left:3px solid <?= $sc ?>;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden">
        <a href="<?= $baseUrl . $bp ?>/reservations/<?= (int)$r['id'] ?>"
           style="display:block;text-decoration:none;color:inherit;padding:1rem;padding-bottom:<?= (($r['statut'] ?? '') === 'acceptee') ? '0.5rem' : '1rem' ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.5rem">
                <p style="margin:0;font-weight:<?= in_array((int)$r['id'], $unreadReservationIds, true) ? '800' : '700' ?>;font-size:0.92rem;color:var(--primary);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:0.35rem">
                    <?php if (in_array((int)$r['id'], $unreadReservationIds, true)): ?>
                    <span class="mobile-list-unread-dot mobile-list-unread-dot--inline" title="Nouvelle alerte" aria-label="Nouvelle alerte"></span>
                    <?php endif; ?>
                    <?= $e($r['expert_titre'] ?? $r['demande_titre'] ?? 'Réservation') ?>
                </p>
                <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
                    <?= $statut_lb[$r['statut']] ?? $e($r['statut']) ?>
                </span>
            </div>
            <?php if (!empty($r['expert_prenom'])): ?>
            <p style="margin:0 0 0.5rem;font-size:0.8rem;color:var(--text-muted)">
                Expert : <?= $e($r['expert_prenom'] . ' ' . ($r['expert_nom'] ?? '')) ?>
            </p>
            <?php endif; ?>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.78rem;color:var(--text-muted)">
                <?php if (!empty($r['date_debut_prevue'])): ?>
                <span>📅 <?= date('d/m/Y', strtotime($r['date_debut_prevue'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($r['montant_total'])): ?>
                <span style="font-weight:600;color:var(--primary)"><?= number_format((float)$r['montant_total'], 0, ',', ' ') ?> <?= $e(devise()) ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php if (($r['statut'] ?? '') === 'acceptee'): ?>
        <a href="<?= $baseUrl . $bp ?>/payer/<?= (int)$r['id'] ?>" class="client-payment-inline" role="button" style="margin:0 1rem 1rem;display:flex;flex-wrap:wrap">
            <span class="client-payment-inline__label">Paiement obligatoire — sans cela, la mission ne démarre pas</span>
            <span class="client-payment-inline__btn">Payer</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
