<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$r = $reservation ?? [];
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$statutLabels = [
    'en_attente' => 'En attente',
    'acceptee' => 'Acceptée',
    'en_cours' => 'En cours',
    'terminee' => 'Terminée',
    'annulee' => 'Annulée',
];
$statutClass = 'admin-badge--statut';
if (in_array($r['statut'] ?? '', ['terminee'], true)) $statutClass = 'admin-badge--actif';
if (in_array($r['statut'] ?? '', ['annulee'], true)) $statutClass = 'admin-badge--inactif';
?>
<div class="page-admin page-admin-reservation">
    <header class="admin-dashboard-header">
        <div class="admin-page-head">
            <a href="<?= $baseUrl ?>/admin/revenus" class="admin-back-link" aria-label="Retour aux revenus">← Revenus</a>
            <h1>Réservation #<?= (int)($r['id'] ?? 0) ?></h1>
            <p class="admin-dashboard-subtitle"><?= $e($r['demande_titre'] ?? '') ?></p>
        </div>
    </header>

    <div class="admin-reservation-cards">
        <div class="admin-table-card admin-table-card--full">
            <div class="admin-table-card-header">
                <h2>Détail</h2>
                <span class="admin-reservation-badge admin-badge <?= $statutClass ?>"><?= $e($statutLabels[$r['statut'] ?? ''] ?? ($r['statut'] ?? '—')) ?></span>
            </div>
            <div class="admin-parametres-body admin-reservation-body">
                <dl class="admin-reservation-dl">
                    <dt>Demande</dt>
                    <dd><strong><?= $e($r['demande_titre'] ?? '') ?></strong></dd>
                    <dt>Montant total</dt>
                    <dd><strong><?= number_format((float)($r['montant_total'] ?? 0), 0, ',', ' ') ?> <?= \App\Core\Security::escape(devise()) ?></strong></dd>
                    <dt>Date de création</dt>
                    <dd><?= $e(date('d/m/Y à H:i', strtotime($r['created_at'] ?? 'now'))) ?></dd>
                    <dt>Dernière mise à jour</dt>
                    <dd><?= $e(date('d/m/Y à H:i', strtotime($r['updated_at'] ?? $r['created_at'] ?? 'now'))) ?></dd>
                </dl>
            </div>
        </div>

        <div class="admin-table-card admin-table-card--full">
            <div class="admin-table-card-header">
                <h2>Client</h2>
            </div>
            <div class="admin-parametres-body admin-reservation-body">
                <p class="admin-reservation-party">
                    <strong><?= $e(trim(($r['client_prenom'] ?? '') . ' ' . ($r['client_nom'] ?? ''))) ?></strong>
                </p>
                <?php if (!empty($r['client_id'])): ?>
                <p><a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$r['client_id'] ?>" class="admin-reservation-link">Voir le compte client</a></p>
                <?php endif; ?>
                <p><a href="<?= $baseUrl ?>/admin/users" class="admin-reservation-link">Voir tous les utilisateurs</a></p>
            </div>
        </div>

        <div class="admin-table-card admin-table-card--full">
            <div class="admin-table-card-header">
                <h2>Expert</h2>
            </div>
            <div class="admin-parametres-body admin-reservation-body">
                <p class="admin-reservation-party"><strong><?= $e($r['expert_titre'] ?? '—') ?></strong></p>
                <p class="admin-reservation-party-meta"><?= $e(trim(($r['expert_prenom'] ?? '') . ' ' . ($r['expert_nom'] ?? ''))) ?></p>
                <?php if (!empty($r['expert_utilisateur_id'])): ?>
                <p><a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$r['expert_utilisateur_id'] ?>" class="admin-reservation-link">Voir le compte expert</a></p>
                <?php endif; ?>
                <?php if (!empty($r['expert_id'])): ?>
                <p><a href="<?= $baseUrl ?>/experts/show/<?= (int)$r['expert_id'] ?>" class="admin-reservation-link" target="_blank" rel="noopener">Voir la fiche publique</a></p>
                <?php endif; ?>
                <p><a href="<?= $baseUrl ?>/admin/experts" class="admin-reservation-link">Voir tous les experts</a></p>
            </div>
        </div>
    </div>

    <p class="admin-reservation-actions">
        <a href="<?= $baseUrl ?>/admin/revenus" class="btn btn-primary">Retour aux revenus</a>
        <a href="<?= $baseUrl ?>/admin" class="btn btn-outline">Tableau de bord</a>
        <?php
        // Afficher le bouton de remboursement uniquement si un paiement est en escrow bloqué
        $paiementEscrow = null;
        try {
            $paiementEscrow = (new \App\Models\PaiementModel())->getByReservation((int)($r['id'] ?? 0));
        } catch (\Throwable $e) {}
        if ($paiementEscrow && ($paiementEscrow['statut_escrow'] ?? '') === 'bloque'):
        ?>
        <form method="post" action="<?= $baseUrl ?>/admin/rembourser-reservation/<?= (int)($r['id'] ?? 0) ?>" style="display:inline">
            <?= \App\Core\Security::getCsrfField() ?>
            <button type="submit" class="btn btn-outline" style="border-color:#dc2626;color:#dc2626"
                    onclick="return confirm('Rembourser le client pour cette réservation ? L\'argent sera restitué depuis l\'escrow.')">
                Rembourser le client
            </button>
        </form>
        <?php endif; ?>
    </p>
</div>
