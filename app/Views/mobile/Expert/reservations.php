<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$reservations = $reservations ?? [];
$filtreStatut = $_GET['statut'] ?? '';
$csrfField    = \App\Core\Security::getCsrfField();
$unreadReservationIds = isset($unreadReservationIds) && is_array($unreadReservationIds) ? array_map('intval', $unreadReservationIds) : [];
$reservationNotifExtraHint = !empty($reservationNotifExtraHint);

$statut_lb = ['en_attente'=>'En attente','acceptee'=>'Acceptée','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée','payee'=>'Payée'];
$statut_cl = ['en_attente'=>'#f59e0b','acceptee'=>'#2563eb','en_cours'=>'#16a34a','terminee'=>'#6b7280','annulee'=>'#dc2626','payee'=>'#7c3aed'];
?>

<div style="margin-bottom:1rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">Mes réservations</h1>
    <?php if ($reservationNotifExtraHint): ?>
    <p style="margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted);line-height:1.35">Certaines alertes concernent les urgences ou un écran différent — elles sont marquées comme vues.</p>
    <?php endif; ?>
</div>

<!-- Filtres rapides -->
<div style="display:flex;gap:0.4rem;overflow-x:auto;padding-bottom:0.5rem;margin-bottom:1rem;-webkit-overflow-scrolling:touch">
    <?php foreach ([''=>'Toutes','en_attente'=>'En attente','en_cours'=>'En cours','terminee'=>'Terminées'] as $val => $lbl): ?>
    <a href="<?= $baseUrl ?>/expert/reservations<?= $val ? '?statut=' . $val : '' ?>"
       style="flex-shrink:0;padding:0.4rem 0.85rem;border-radius:999px;font-size:0.8rem;font-weight:600;text-decoration:none;border:1.5px solid <?= $filtreStatut === $val ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $filtreStatut === $val ? 'var(--accent)' : 'transparent' ?>;color:<?= $filtreStatut === $val ? '#fff' : 'var(--text-muted)' ?>">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($reservations)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <p class="mobile-empty-hint">Aucune réservation<?= $filtreStatut ? ' dans ce statut' : '' ?>.</p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($reservations as $r): ?>
    <?php $sc = $statut_cl[$r['statut']] ?? '#6b7280'; ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-left:3px solid <?= $sc ?>;border-radius:var(--radius);padding:1rem;box-shadow:var(--shadow)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.5rem">
            <p style="margin:0;font-weight:<?= in_array((int)$r['id'], $unreadReservationIds, true) ? '800' : '700' ?>;font-size:0.9rem;color:var(--primary);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:0.35rem">
                <?php if (in_array((int)$r['id'], $unreadReservationIds, true)): ?>
                <span class="mobile-list-unread-dot mobile-list-unread-dot--inline" title="Nouvelle alerte" aria-label="Nouvelle alerte"></span>
                <?php endif; ?>
                <?= $e($r['demande_titre'] ?? '') ?>
            </p>
            <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.2rem 0.55rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
                <?= $statut_lb[$r['statut']] ?? $e($r['statut']) ?>
            </span>
        </div>
        <p style="margin:0 0 0.6rem;font-size:0.78rem;color:var(--text-muted)">
            Client : <?= $e(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?>
            <?php if (!empty($r['date_debut_prevue'])): ?> · <?= date('d/m/Y', strtotime($r['date_debut_prevue'])) ?><?php endif; ?>
        </p>

        <!-- Actions selon statut -->
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
            <?php if (($r['statut'] ?? '') === 'en_attente'): ?>
            <form method="post" action="<?= $baseUrl ?>/expert/accepter/<?= (int)$r['id'] ?>" style="margin:0">
                <?= $csrfField ?>
                <button type="submit" class="btn-mobile btn-primary btn-sm" style="cursor:pointer">✅ Accepter</button>
            </form>
            <form method="post" action="<?= $baseUrl ?>/expert/refuser/<?= (int)$r['id'] ?>" style="margin:0" onsubmit="return confirm('Refuser cette réservation ?')">
                <?= $csrfField ?>
                <button type="submit" class="btn-mobile btn-outline btn-sm" style="cursor:pointer;border-color:#dc2626;color:#dc2626">❌ Refuser</button>
            </form>
            <?php endif; ?>
            <?php if (($r['statut'] ?? '') === 'en_cours'): ?>
            <a href="<?= $baseUrl ?>/expert/livrer/<?= (int)$r['id'] ?>" class="btn-mobile btn-primary btn-sm" style="display:inline-flex">📤 Livrer</a>
            <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="btn-mobile btn-outline btn-sm" style="display:inline-flex">Message</a>
            <form method="post" action="<?= $baseUrl ?>/expert/terminer/<?= (int)$r['id'] ?>" style="margin:0" onsubmit="return confirm('Marquer la prestation terminée ? Le client confirmera ensuite la résolution de sa demande.');">
                <?= $csrfField ?>
                <button type="submit" class="btn-mobile btn-outline btn-sm" style="cursor:pointer">✅ Terminer</button>
            </form>
            <?php endif; ?>
            <?php if (in_array($r['statut'] ?? '', ['terminee','payee'])): ?>
            <?php
            $avisClientModel = new \App\Models\AvisClientModel();
            $dejaNote        = $avisClientModel->existsForReservation((int)$r['id']);
            ?>
            <?php if (!$dejaNote): ?>
            <a href="<?= $baseUrl ?>/expert/noter-client/<?= (int)$r['id'] ?>" class="btn-mobile btn-outline btn-sm" style="display:inline-flex">⭐ Noter le client</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($r['montant_total'])): ?>
        <p style="margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted);text-align:right">
            <strong style="color:var(--primary)"><?= number_format((float)$r['montant_total'], 0, ',', ' ') ?> <?= $e(devise()) ?></strong>
        </p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
