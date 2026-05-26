<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$demandes = $demandes ?? [];
$proposerUrlPrefix = $proposer_url_prefix ?? (rtrim(BASE_URL ?? '', '/') . '/expert/proposer-demande/');
$count    = count($demandes);

$urgenceConfig = [
    'tres_urgent' => ['label' => 'Très urgent', 'color' => '#ef4444', 'bg' => '#fef2f2', 'dot' => '#ef4444'],
    'urgent'      => ['label' => 'Urgent',      'color' => '#f59e0b', 'bg' => '#fffbeb', 'dot' => '#f59e0b'],
    'normale'     => ['label' => 'Normale',     'color' => '#64748b', 'bg' => '#f8fafc', 'dot' => '#94a3b8'],
];
?>
<section class="section-desktop page-expert page-expert-demandes">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $baseUrl ?>/expert" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon missions-header__icon--purple" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Demandes clients</h1>
                    <p class="missions-header__sub">Demandes correspondant à vos compétences et disponibles à la réservation.</p>
                </div>
            </div>
        </div>
        <?php if ($count > 0): ?>
        <span class="missions-header__count" style="background:#f5f3ff;color:#7c3aed;"><?= $count ?> demande<?= $count > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <!-- Bandeau info -->
    <?php if ($count > 0): ?>
    <div class="urgences-rules" style="background:#f5f3ff;border-color:#ddd6fe;color:#5b21b6;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Proposez vos services : le client choisira la proposition qui lui convient.</span>
    </div>
    <?php endif; ?>

    <!-- État vide -->
    <?php if (empty($demandes)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <h2 class="missions-empty__title">Aucune demande disponible</h2>
        <p class="missions-empty__text">
            Aucune demande ne correspond à vos compétences pour le moment.<br>
            Ajoutez plus de compétences à votre profil pour recevoir davantage de demandes.
        </p>
        <div class="missions-empty__actions">
            <a href="<?= $baseUrl ?>/expert/profil" class="btn btn-primary missions-empty__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Mettre à jour mon profil
            </a>
            <a href="<?= $baseUrl ?>/expert/urgences" class="btn btn-outline missions-empty__btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Missions urgentes
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- Cartes demandes -->
    <ul class="demandes-list" aria-label="Demandes clients disponibles">
        <?php foreach ($demandes as $d):
            $urgence = $d['urgence'] ?? 'normale';
            $uConf   = $urgenceConfig[$urgence] ?? $urgenceConfig['normale'];
            $client  = trim(($d['client_prenom'] ?? '') . ' ' . ($d['client_nom'] ?? ''));
            $duree   = (float)($d['duree_estimee_heures'] ?? 1);
            $initiales = strtoupper(
                substr($client ?: 'C', 0, 1) .
                (strpos($client, ' ') !== false ? substr(strrchr($client, ' '), 1, 1) : '')
            );
        ?>
        <li class="demande-card">

            <!-- Urgence stripe -->
            <div class="demande-card__stripe" style="background:<?= $uConf['dot'] ?>;"></div>

            <!-- Avatar client -->
            <div class="demande-card__avatar" aria-hidden="true"><?= $e($initiales) ?></div>

            <!-- Corps -->
            <div class="demande-card__body">
                <div class="demande-card__top">
                    <h2 class="demande-card__title"><?= $e($d['titre'] ?? '') ?></h2>
                    <div class="demande-card__badges">
                        <?php if (!empty($d['competence_nom'])): ?>
                        <span class="demande-card__tag demande-card__tag--skill">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            <?= $e($d['competence_nom']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($urgence !== 'normale'): ?>
                        <span class="demande-card__tag" style="color:<?= $uConf['color'] ?>;background:<?= $uConf['bg'] ?>;">
                            <?php if ($urgence === 'tres_urgent'): ?>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            <?php endif; ?>
                            <?= $e($uConf['label']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="demande-card__meta">
                    <span class="demande-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?= $e($client ?: 'Client') ?>
                    </span>
                    <span class="demande-card__meta-sep" aria-hidden="true">·</span>
                    <span class="demande-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= $duree <= 1 ? '~1 h' : '~' . number_format($duree, 0) . ' h' ?> estimées
                    </span>
                </div>

                <div class="demande-card__actions">
                    <?php if (!empty($d['ma_proposition_id'])): ?>
                    <span class="demande-card__prop-sent">Proposition envoyée — en attente du client</span>
                    <?php else: ?>
                    <a href="<?= $e($proposerUrlPrefix) ?><?= (int)($d['id'] ?? 0) ?>" class="btn btn-primary btn-sm demande-card__btn-proposer">
                        Proposer mes services
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>

</section>
