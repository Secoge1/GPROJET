<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$statutFilter = isset($_GET['statut']) ? (string) $_GET['statut'] : null;
$csrfField    = \App\Core\Security::getCsrfField();
$reservations = $reservations ?? [];
$total        = count($reservations);

$statutConfig = [
    'en_attente' => ['label' => 'En attente',  'color' => '#b45309', 'bg' => '#fffbeb', 'border' => '#fde68a', 'dot' => '#f59e0b'],
    'acceptee'   => ['label' => 'Acceptée',    'color' => '#1d4ed8', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
    'en_cours'   => ['label' => 'En cours',    'color' => '#0f766e', 'bg' => '#f0fdfa', 'border' => '#99f6e4', 'dot' => '#14b8a6'],
    'terminee'   => ['label' => 'Terminée',    'color' => '#16a34a', 'bg' => '#dcfce7', 'border' => '#bbf7d0', 'dot' => '#22c55e'],
    'annulee'    => ['label' => 'Annulée',     'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb', 'dot' => '#9ca3af'],
];

$filters = [
    null          => 'Toutes',
    'en_attente'  => 'En attente',
    'acceptee'    => 'Acceptées',
    'en_cours'    => 'En cours',
    'terminee'    => 'Terminées',
    'annulee'     => 'Annulées',
];
?>
<section class="section-desktop page-expert page-expert-reservations">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $baseUrl ?>/expert" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon missions-header__icon--blue" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Mes réservations</h1>
                    <p class="missions-header__sub">Gérez les demandes et le suivi de vos missions.</p>
                </div>
            </div>
        </div>
        <?php if ($total > 0): ?>
        <span class="missions-header__count"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <!-- Filtres par statut -->
    <div class="reservations-filters" role="tablist" aria-label="Filtrer par statut">
        <?php foreach ($filters as $val => $label):
            $isActive = ($val === $statutFilter) || ($val === null && $statutFilter === null);
            $url = $val ? $baseUrl . '/expert/reservations?statut=' . $val : $baseUrl . '/expert/reservations';
            $conf = isset($statutConfig[$val]) ? $statutConfig[$val] : null;
        ?>
        <a href="<?= $url ?>"
           class="reservations-filter-pill <?= $isActive ? 'active' : '' ?>"
           role="tab"
           aria-selected="<?= $isActive ? 'true' : 'false' ?>"
           <?php if ($isActive && $conf): ?>style="color:<?= $conf['color'] ?>;background:<?= $conf['bg'] ?>;border-color:<?= $conf['border'] ?>;"<?php endif; ?>>
            <?php if ($conf): ?>
            <span class="reservations-filter-dot" style="background:<?= $conf['dot'] ?>;"></span>
            <?php endif; ?>
            <?= $e($label) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- État vide -->
    <?php if (empty($reservations)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <h2 class="missions-empty__title">
            <?= $statutFilter ? 'Aucune réservation "' . $e($filters[$statutFilter] ?? $statutFilter) . '"' : 'Aucune réservation' ?>
        </h2>
        <p class="missions-empty__text">
            <?= $statutFilter
                ? 'Essayez un autre filtre ou attendez de nouvelles demandes.'
                : 'Les clients pourront vous réserver une fois votre profil complété et validé.' ?>
        </p>
        <div class="missions-empty__actions">
            <?php if ($statutFilter): ?>
            <a href="<?= $baseUrl ?>/expert/reservations" class="btn btn-outline missions-empty__btn">Voir toutes les réservations</a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/expert/profil" class="btn btn-primary missions-empty__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Compléter mon profil
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>

    <!-- Liste des réservations -->
    <ul class="missions-list" aria-label="Liste des réservations">
        <?php foreach ($reservations as $r):
            $statut  = $r['statut'] ?? 'en_attente';
            $stConf  = $statutConfig[$statut] ?? $statutConfig['en_attente'];
            $client  = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''));
            $titre   = $r['demande_titre'] ?? $r['expert_titre'] ?? 'Réservation';
            $montant = (float)($r['montant_total'] ?? 0);
            $date    = !empty($r['date_debut_prevue']) ? date('d/m/Y', strtotime($r['date_debut_prevue'])) : null;
        ?>
        <li class="mission-card reservation-card">
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
                    <?php if ($montant > 0): ?>
                    <span class="mission-card__meta-sep" aria-hidden="true">·</span>
                    <span class="mission-card__meta-item mission-card__meta-amount">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <?= number_format($montant, 0, ',', ' ') ?> <?= $e(devise()) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="mission-card__actions reservation-card__actions">
                <!-- Messages -->
                <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="mission-card__action-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages
                </a>

                <!-- Accepter / Refuser -->
                <?php if ($statut === 'en_attente'): ?>
                <form method="post" action="<?= $baseUrl ?>/expert/accepter/<?= (int)$r['id'] ?>" style="display:contents;">
                    <?= $csrfField ?>
                    <button type="submit" class="mission-card__action-btn mission-card__action-btn--accept">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Accepter
                    </button>
                </form>
                <form method="post" action="<?= $baseUrl ?>/expert/refuser/<?= (int)$r['id'] ?>" style="display:contents;" onsubmit="return confirm('Refuser cette réservation ?')">
                    <?= $csrfField ?>
                    <button type="submit" class="mission-card__action-btn mission-card__action-btn--refuse">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Refuser
                    </button>
                </form>
                <?php endif; ?>

                <!-- Livrer le travail -->
                <?php if (in_array($statut, ['en_cours', 'terminee', 'payee'])): ?>
                <a href="<?= $baseUrl ?>/expert/livrer/<?= (int)$r['id'] ?>"
                   class="mission-card__action-btn mission-card__action-btn--deliver">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Livrer le travail
                </a>
                <?php endif; ?>

                <!-- Terminer -->
                <?php if ($statut === 'en_cours'): ?>
                <form method="post" action="<?= $baseUrl ?>/expert/terminer/<?= (int)$r['id'] ?>" style="display:contents;" onsubmit="return confirm('Marquer la prestation comme terminée ? Le client devra encore confirmer que sa demande est résolue.');">
                    <?= $csrfField ?>
                    <button type="submit" class="mission-card__action-btn mission-card__action-btn--finish">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Terminer la session
                    </button>
                </form>
                <?php endif; ?>

                <!-- Noter le client -->
                <?php if ($statut === 'terminee'):
                    $avisClientModel = new \App\Models\AvisClientModel();
                    $dejaNoteClient  = $avisClientModel->existsForReservation((int)$r['id']);
                    if (!$dejaNoteClient): ?>
                <a href="<?= $baseUrl ?>/expert/noter-client/<?= (int)$r['id'] ?>" class="mission-card__action-btn mission-card__action-btn--rate">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Noter le client
                </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>

</section>
