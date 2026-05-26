<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$reservations = $reservations ?? [];

if (!function_exists('clReservBadge')) { function clReservBadge(string $s): string {
    $map = [
        'en_attente' => ['cl-badge--orange', 'En attente'],
        'acceptee'   => ['cl-badge--amber',  'Acceptée'],
        'payee'      => ['cl-badge--blue',   'Payée'],
        'en_cours'   => ['cl-badge--blue',   'En cours'],
        'terminee'   => ['cl-badge--green',  'Terminée'],
        'annulee'    => ['cl-badge--red',    'Annulée'],
        'refusee'    => ['cl-badge--red',    'Refusée'],
    ];
    [$cls, $lbl] = $map[$s] ?? ['cl-badge--gray', ucfirst(str_replace('_', ' ', $s))];
    return "<span class=\"cl-badge {$cls}\">{$lbl}</span>";
} }
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <h1 class="cl-page__title">Mes réservations</h1>
            <p class="cl-page__sub">Consultez le détail de vos réservations et le suivi des missions.</p>
        </div>
        <a href="<?= $baseUrl ?>/experts" class="cl-btn cl-btn--outline">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Voir les experts
        </a>
    </div>

    <?php if (empty($reservations)): ?>
    <div class="cl-card cl-empty-card">
        <div class="cl-empty-card__icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h3>Aucune réservation</h3>
        <p>Créez une demande d'assistance pour démarrer une réservation avec un expert.</p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
            <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-btn cl-btn--outline">Créer une demande</a>
            <a href="<?= $baseUrl ?>/experts" class="cl-btn cl-btn--amber">Voir les experts</a>
        </div>
    </div>
    <?php else: ?>

    <!-- Résumé -->
    <div class="cl-summary-bar">
        <span class="cl-summary-bar__item"><strong><?= count($reservations) ?></strong> réservation<?= count($reservations) > 1 ? 's' : '' ?></span>
        <?php $acceptees = array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'acceptee'); ?>
        <?php if (count($acceptees)): ?>
        <span class="cl-summary-bar__item cl-summary-bar__item--amber">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= count($acceptees) ?> en attente de paiement
        </span>
        <?php endif; ?>
    </div>

    <div class="cl-card cl-card--flush">
        <ul class="cl-resv-list">
            <?php foreach ($reservations as $r): ?>
            <li class="cl-resv-item">
                <div class="cl-resv-item__left">
                    <div class="cl-resv-item__avatar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <span class="cl-resv-item__title"><?= $e($r['expert_titre'] ?? 'Réservation') ?></span>
                        <div class="cl-resv-item__meta">
                            <?php if (!empty($r['prenom']) || !empty($r['nom'])): ?>
                            <span class="cl-meta-chip"><?= $e(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?></span>
                            <?php endif; ?>
                            <span class="cl-meta-chip"><?= number_format((float)($r['montant_total'] ?? 0), 0, ',', ' ') ?> <?= $e(devise()) ?></span>
                            <?php if (!empty($r['date_debut_prevue'])): ?>
                            <span class="cl-meta-chip"><?= date('d/m/Y', strtotime($r['date_debut_prevue'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="cl-resv-item__right">
                    <?= clReservBadge($r['statut'] ?? '') ?>
                    <div class="cl-resv-item__btns">
                        <?php if (($r['statut'] ?? '') === 'acceptee'): ?>
                        <a href="<?= $baseUrl ?>/client/payer/<?= (int)$r['id'] ?>" class="cl-btn cl-btn--amber cl-btn--sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            Payer
                        </a>
                        <?php endif; ?>
                        <?php if (in_array($r['statut'] ?? '', ['en_cours', 'payee'])): ?>
                        <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="cl-btn cl-btn--outline cl-btn--sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Messagerie
                        </a>
                        <?php endif; ?>
                        <a href="<?= $baseUrl ?>/client/reservations/<?= (int)$r['id'] ?>" class="cl-btn cl-btn--ghost cl-btn--sm">
                            Détail →
                        </a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>
