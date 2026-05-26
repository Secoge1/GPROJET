<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$demandes = $demandes ?? [];

if (!function_exists('clDemandeBadge')) {
    function clDemandeBadge(string $s): string {
        $map = [
            'ouverte'    => ['cl-badge--green',  'Ouverte'],
            'en_cours'   => ['cl-badge--blue',   'En cours'],
            'terminee'   => ['cl-badge--gray',   'Terminée'],
            'annulee'    => ['cl-badge--red',    'Annulée'],
            'acceptee'   => ['cl-badge--amber',  'Acceptée'],
            'en_attente' => ['cl-badge--orange', 'En attente'],
        ];
        [$cls, $label] = $map[$s] ?? ['cl-badge--gray', ucfirst(str_replace('_', ' ', $s))];
        return "<span class=\"cl-badge {$cls}\">{$label}</span>";
    }
}

if (!function_exists('clUrgenceBadge')) {
    function clUrgenceBadge(string $u): string {
        if ($u === 'urgent') {
            return '<span class="cl-badge cl-badge--orange">Urgent</span>';
        } elseif ($u === 'tres_urgent') {
            return '<span class="cl-badge cl-badge--red">Très urgent</span>';
        }
        return '';
    }
}
?>
<div class="cl-page cl-page--demandes">

    <!-- En-tête -->
    <div class="cl-page__hero">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <h1 class="cl-page__title">Mes demandes</h1>
            <p class="cl-page__sub">Suivez vos demandes et réservez un expert pour les demandes ouvertes.</p>
        </div>
        <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-btn cl-btn--amber">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouvelle demande
        </a>
    </div>

    <?php if (empty($demandes)): ?>
    <!-- État vide -->
    <div class="cl-card cl-empty-card">
        <div class="cl-empty-card__icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <h3>Aucune demande pour le moment</h3>
        <p>Créez votre première demande d'assistance et trouvez l'expert idéal.</p>
        <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-btn cl-btn--amber">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Créer une demande
        </a>
    </div>
    <?php else: ?>

    <!-- Résumé rapide -->
    <div class="cl-summary-bar">
        <span class="cl-summary-bar__item">
            <strong><?= count($demandes) ?></strong> demande<?= count($demandes) > 1 ? 's' : '' ?> au total
        </span>
        <?php $ouvertes = array_filter($demandes, fn($d) => ($d['statut'] ?? '') === 'ouverte'); ?>
        <?php if (count($ouvertes) > 0): ?>
        <span class="cl-summary-bar__item cl-summary-bar__item--green">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= count($ouvertes) ?> ouverte<?= count($ouvertes) > 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Liste des demandes -->
    <div class="cl-card cl-card--flush">
        <ul class="cl-demand-cards">
            <?php foreach ($demandes as $d): ?>
            <li class="cl-demand-item">
                <div class="cl-demand-item__main">
                    <div class="cl-demand-item__top">
                        <a href="<?= $baseUrl ?>/client/demandes/<?= (int)$d['id'] ?>" class="cl-demand-item__title" style="text-decoration:none;color:inherit"><?= $e($d['titre']) ?></a>
                        <?= clDemandeBadge($d['statut'] ?? '') ?>
                        <?= clUrgenceBadge($d['urgence'] ?? 'normale') ?>
                    </div>
                    <div class="cl-demand-item__meta">
                        <?php if (!empty($d['competence_nom'])): ?>
                        <span class="cl-meta-chip">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                            <?= $e($d['competence_nom']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($d['duree_estimee_heures'])): ?>
                        <span class="cl-meta-chip">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= (int)$d['duree_estimee_heures'] ?>h estimée<?= (int)$d['duree_estimee_heures'] > 1 ? 's' : '' ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($d['created_at'])): ?>
                        <span class="cl-meta-chip">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cl-demand-item__actions">
                    <?php if (($d['statut'] ?? '') === 'ouverte'): ?>
                    <a href="<?= $baseUrl ?>/client/reserver/<?= (int)$d['id'] ?>" class="cl-btn cl-btn--amber cl-btn--sm">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Réserver un expert
                    </a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>
