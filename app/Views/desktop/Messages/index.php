<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$role         = $user['role'] ?? 'client';
$dashboardUrl = $baseUrl . ($role === 'expert' ? '/expert' : '/client');
$reservations = $reservations ?? [];
$count        = count($reservations);
$unreadConversationIds = isset($unreadConversationIds) && is_array($unreadConversationIds) ? array_map('intval', $unreadConversationIds) : [];

$statutConfig = [
    'en_attente' => ['label' => 'En attente', 'color' => '#b45309', 'bg' => '#fffbeb'],
    'acceptee'   => ['label' => 'Acceptée',   'color' => '#1d4ed8', 'bg' => '#eff6ff'],
    'en_cours'   => ['label' => 'En cours',   'color' => '#0f766e', 'bg' => '#f0fdfa'],
    'terminee'   => ['label' => 'Terminée',   'color' => '#16a34a', 'bg' => '#dcfce7'],
    'annulee'    => ['label' => 'Annulée',    'color' => '#6b7280', 'bg' => '#f9fafb'],
];
?>
<section class="section-desktop page-messages-index">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $dashboardUrl ?>" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Messages</h1>
                    <p class="missions-header__sub">
                        <?= $role === 'expert'
                            ? 'Vos échanges avec vos clients, par mission.'
                            : 'Vos conversations avec les experts, par réservation.' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php if ($count > 0): ?>
        <span class="missions-header__count"><?= $count ?> conversation<?= $count > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <!-- État vide -->
    <?php if (empty($reservations)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <h2 class="missions-empty__title">Aucune conversation</h2>
        <p class="missions-empty__text">Les conversations apparaissent ici une fois une réservation acceptée entre un client et un expert.</p>
        <a href="<?= $role === 'expert' ? $baseUrl . '/expert/missions' : $baseUrl . '/client/demandes' ?>" class="btn btn-primary missions-empty__btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <?= $role === 'expert' ? 'Voir mes missions' : 'Voir mes demandes' ?>
        </a>
    </div>

    <?php else: ?>

    <!-- Liste des conversations -->
    <ul class="msg-list" aria-label="Liste des conversations">
        <?php foreach ($reservations as $r):
            $titre  = $r['demande_titre'] ?? $r['expert_titre'] ?? 'Réservation #' . $r['id'];
            $statut = $r['statut'] ?? 'en_attente';
            $stConf = $statutConfig[$statut] ?? ['label' => ucfirst($statut), 'color' => '#64748b', 'bg' => '#f8fafc'];
            $interlocuteur = $role === 'expert'
                ? trim(($r['client_prenom'] ?? '') . ' ' . ($r['client_nom'] ?? ''))
                : trim(($r['expert_prenom'] ?? $r['prenom'] ?? '') . ' ' . ($r['expert_nom'] ?? $r['nom'] ?? ''));
            $initiales = strtoupper(
                substr($interlocuteur ?: '?', 0, 1) .
                (strpos($interlocuteur, ' ') !== false ? substr(strrchr($interlocuteur, ' '), 1, 1) : '')
            );
        ?>
        <li>
            <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="msg-card<?= in_array((int)$r['id'], $unreadConversationIds, true) ? ' msg-card--unread' : '' ?>" aria-label="Conversation : <?= $e($titre) ?>">

                <!-- Avatar initiales -->
                <div class="msg-card__avatar" aria-hidden="true">
                    <?= $e($initiales ?: '?') ?>
                    <?php if (in_array((int)$r['id'], $unreadConversationIds, true)): ?>
                    <span class="mobile-list-unread-dot" title="Nouveau message" aria-label="Nouveau message"></span>
                    <?php endif; ?>
                </div>

                <!-- Contenu -->
                <div class="msg-card__body">
                    <div class="msg-card__top">
                        <span class="msg-card__titre"><?= $e($titre) ?></span>
                        <span class="msg-card__statut" style="color:<?= $stConf['color'] ?>;background:<?= $stConf['bg'] ?>;">
                            <?= $e($stConf['label']) ?>
                        </span>
                    </div>
                    <?php if ($interlocuteur): ?>
                    <span class="msg-card__interlocuteur">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?= $e($interlocuteur) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Flèche -->
                <div class="msg-card__arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>

</section>
