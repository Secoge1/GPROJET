<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$missions = $missions ?? [];
$count    = count($missions);

$statutConfig = [
    'en_attente' => ['label' => 'En attente',  'color' => '#b45309', 'bg' => '#fffbeb', 'border' => '#fde68a', 'dot' => '#f59e0b'],
    'acceptee'   => ['label' => 'Acceptée',    'color' => '#1d4ed8', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
    'en_cours'   => ['label' => 'En cours',    'color' => '#0f766e', 'bg' => '#f0fdfa', 'border' => '#99f6e4', 'dot' => '#14b8a6'],
    'terminee'   => ['label' => 'Terminée',    'color' => '#16a34a', 'bg' => '#dcfce7', 'border' => '#bbf7d0', 'dot' => '#22c55e'],
    'annulee'    => ['label' => 'Annulée',     'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb', 'dot' => '#9ca3af'],
];
?>
<section class="section-desktop page-expert page-expert-missions">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $baseUrl ?>/expert" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Mes missions</h1>
                    <p class="missions-header__sub">Vos réservations et missions en cours ou terminées.</p>
                </div>
            </div>
        </div>
        <?php if ($count > 0): ?>
        <span class="missions-header__count"><?= $count ?> mission<?= $count > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <!-- État vide -->
    <?php if (empty($missions)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
        </div>
        <h2 class="missions-empty__title">Aucune mission pour l'instant</h2>
        <p class="missions-empty__text">Vos missions apparaissent ici dès qu'un client réserve vos services ou qu'une urgence vous est attribuée.</p>
        <div class="missions-empty__actions">
            <a href="<?= $baseUrl ?>/expert/urgences" class="btn btn-primary missions-empty__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Missions urgentes
            </a>
            <a href="<?= $baseUrl ?>/expert/demandes" class="btn btn-outline missions-empty__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Voir les demandes
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- Cartes missions -->
    <ul class="missions-list" aria-label="Liste des missions">
        <?php foreach ($missions as $m):
            $statut  = $m['statut'] ?? 'en_attente';
            $stConf  = $statutConfig[$statut] ?? $statutConfig['en_attente'];
            $client  = trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''));
            $titre   = $m['demande_titre'] ?? $m['expert_titre'] ?? 'Mission sans titre';
            $date    = !empty($m['date_debut_prevue']) ? date('d/m/Y', strtotime($m['date_debut_prevue'])) : null;
        ?>
        <li class="mission-card">
            <div class="mission-card__stripe" style="background:<?= $stConf['dot'] ?>;"></div>

            <div class="mission-card__body">
                <div class="mission-card__top">
                    <h2 class="mission-card__title"><?= $e($titre) ?></h2>
                    <span class="mission-card__badge" style="color:<?= $stConf['color'] ?>;background:<?= $stConf['bg'] ?>;border-color:<?= $stConf['border'] ?>;">
                        <span class="mission-card__badge-dot" style="background:<?= $stConf['dot'] ?>;"></span>
                        <?= $stConf['label'] ?>
                    </span>
                </div>

                <div class="mission-card__meta">
                    <?php if ($client): ?>
                    <span class="mission-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?= $e($client) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($date): ?>
                    <span class="mission-card__meta-sep" aria-hidden="true">·</span>
                    <span class="mission-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?= $e($date) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($m['montant_total'])): ?>
                    <span class="mission-card__meta-sep" aria-hidden="true">·</span>
                    <span class="mission-card__meta-item mission-card__meta-amount">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <?= number_format((float)$m['montant_total'], 0, ',', ' ') ?> <?= $e(devise()) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($m['id'])): ?>
            <div class="mission-card__actions">
                <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$m['id'] ?>" class="mission-card__action-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages
                </a>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>

</section>
