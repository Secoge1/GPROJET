<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn ($s) => \App\Core\Security::escape($s ?? '');
$demande  = $demande ?? [];
$reservation = $reservation ?? null;
$client_base_path = $client_base_path ?? '/client';
$demandesListUrl = $demandesListUrl ?? $baseUrl . $client_base_path . '/demandes';

$statut     = $demande['statut'] ?? 'ouverte';
$urgence    = $demande['urgence'] ?? 'normale';
$demandeId  = (int) ($demande['id'] ?? 0);

if (!function_exists('clDemandeBadge')) {
    function clDemandeBadge(string $s): string {
        $map = [
            'ouverte' => ['cl-badge--green', 'Ouverte'],
            'en_cours' => ['cl-badge--blue', 'En cours'],
            'terminee' => ['cl-badge--gray', 'Terminée'],
            'annulee' => ['cl-badge--red', 'Annulée'],
        ];
        [$cls, $label] = $map[$s] ?? ['cl-badge--gray', $s];
        return '<span class="cl-badge ' . $cls . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
    }
}
?>
<div class="cl-page cl-page--demande-detail">

    <div class="cl-page__hero cl-page__hero--narrow">
        <div class="cl-page__hero-left">
            <a href="<?= $e($demandesListUrl) ?>" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Mes demandes
            </a>
            <h1 class="cl-page__title">Détail de la demande</h1>
        </div>
    </div>

    <div class="cl-card" style="margin-bottom:1rem">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.75rem">
            <h2 style="margin:0;font-size:1.05rem;font-weight:800;color:#1c1917"><?= $e($demande['titre'] ?? '') ?></h2>
            <?= clDemandeBadge($statut) ?>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem">
            <?php if (!empty($demande['competence_nom'])): ?>
            <span class="cl-meta-chip"><?= $e($demande['competence_nom']) ?></span>
            <?php endif; ?>
            <?php if (!empty($demande['duree_estimee_heures'])): ?>
            <span class="cl-meta-chip"><?= $e((string) $demande['duree_estimee_heures']) ?> h estimée</span>
            <?php endif; ?>
            <?php if ($urgence !== 'normale'): ?>
            <span class="cl-badge <?= $urgence === 'tres_urgent' ? 'cl-badge--red' : 'cl-badge--orange' ?>">
                <?= $urgence === 'tres_urgent' ? 'Très urgent' : 'Urgent' ?>
            </span>
            <?php endif; ?>
        </div>
        <?php if (!empty($demande['description'])): ?>
        <p style="margin:0 0 1rem;font-size:.9rem;color:#44403c;line-height:1.6"><?= nl2br($e($demande['description'])) ?></p>
        <?php endif; ?>
        <p style="margin:0;font-size:.78rem;color:#78716c">
            Créée le <?= !empty($demande['created_at']) ? date('d/m/Y à H:i', strtotime($demande['created_at'])) : '—' ?>
        </p>
    </div>

    <?php
    $demande_recommendations = $demande_recommendations ?? null;
    $demande_welcome_hint    = $demande_welcome_hint ?? false;
    require APP_PATH . '/Views/partials/demande_recommendations.php';
    ?>

    <?php
    $propositions = $propositions ?? [];
    $can_choose = !empty($can_choose_proposition);
    $client_base_path = $client_base_path ?? '/client';
    require APP_PATH . '/Views/partials/demande_propositions_list.php';
    ?>

    <?php require APP_PATH . '/Views/partials/demande_cloture_client.php'; ?>

    <?php if ($reservation): ?>
    <div class="cl-card" style="margin-bottom:1rem">
        <h3 style="margin:0 0 .5rem;font-size:.9rem">Réservation associée</h3>
        <p style="margin:0;font-size:.85rem">Statut : <?= $e($reservation['statut'] ?? '') ?></p>
        <?php if (!empty($reservation['id'])): ?>
        <a href="<?= $e($baseUrl . $client_base_path . '/reservations/' . (int) $reservation['id']) ?>" class="cl-btn cl-btn--outline cl-btn--sm" style="margin-top:.75rem;display:inline-flex">Voir la réservation</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($statut === 'ouverte' && !$reservation): ?>
    <div style="display:flex;flex-wrap:wrap;gap:.65rem">
        <a href="<?= $e($baseUrl . $client_base_path . '/reserver/' . $demandeId) ?>" class="cl-btn cl-btn--amber">
            Réserver un expert
        </a>
        <a href="<?= $e($baseUrl . '/experts?demande_id=' . $demandeId) ?>" class="cl-btn cl-btn--outline">
            Parcourir les experts
        </a>
    </div>
    <?php endif; ?>

</div>
