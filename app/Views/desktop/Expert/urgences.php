<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$missions = $missions ?? [];
$e        = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$count    = count($missions);
?>
<section class="section-desktop page-expert page-expert-urgences">

    <!-- En-tête hero urgences -->
    <div class="urgences-hero">
        <div class="urgences-hero__left">
            <div class="urgences-hero__icon-wrap" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
            </div>
            <div>
                <h1 class="urgences-hero__title">Missions urgentes</h1>
                <p class="urgences-hero__sub">Le <strong>premier expert à accepter</strong> décroche la mission.</p>
            </div>
        </div>
        <div class="urgences-hero__right">
            <?php if ($count > 0): ?>
            <div class="urgences-live-badge">
                <span class="urgences-live-dot"></span>
                <?= $count ?> en attente
            </div>
            <?php else: ?>
            <div class="urgences-live-badge urgences-live-badge--quiet">
                <span class="urgences-live-dot urgences-live-dot--quiet"></span>
                Aucune mission
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash error -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="urgences-alert" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <!-- État vide -->
    <?php if (empty($missions)): ?>
    <div class="urgences-empty">
        <div class="urgences-empty__illustration" aria-hidden="true">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
            <div class="urgences-empty__ripple"></div>
        </div>
        <h2 class="urgences-empty__title">Aucune mission urgente</h2>
        <p class="urgences-empty__text">
            Tout est calme pour l'instant. Dès qu'un client lance <em>"Besoin d'aide maintenant"</em>,
            la mission apparaît ici et une notification vous est envoyée.
        </p>
        <a href="<?= $baseUrl ?>/expert" class="btn btn-outline urgences-empty__btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Retour au tableau de bord
        </a>
    </div>

    <?php else: ?>

    <!-- Bandeau info règles -->
    <div class="urgences-rules">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Chaque mission est attribuée au <strong>premier expert</strong> qui accepte. Agissez vite !</span>
    </div>

    <!-- Liste des missions urgentes -->
    <ul class="urgences-list" aria-label="Missions urgentes disponibles">
        <?php foreach ($missions as $idx => $m):
            $clientNom  = trim(($m['client_prenom'] ?? '') . ' ' . ($m['client_nom'] ?? ''));
            $duree      = (float)($m['duree_estimee_heures'] ?? 1);
            $desc       = $m['description'] ?? '';
            $descCourt  = mb_strlen($desc) > 220 ? mb_substr($desc, 0, 220) . '…' : $desc;
        ?>
        <li class="urgences-card" aria-label="Mission urgente : <?= $e($m['titre'] ?? '') ?>">

            <!-- Indicateur urgence + numéro -->
            <div class="urgences-card__badge-wrap">
                <span class="urgences-card__num">#<?= $idx + 1 ?></span>
                <span class="urgences-card__urgent-dot" aria-hidden="true"></span>
            </div>

            <!-- Corps de la carte -->
            <div class="urgences-card__body">
                <div class="urgences-card__top">
                    <h2 class="urgences-card__title"><?= $e($m['titre'] ?? '') ?></h2>
                    <?php if (!empty($m['competence_nom'])): ?>
                    <span class="urgences-card__tag">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <?= $e($m['competence_nom']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($descCourt): ?>
                <p class="urgences-card__desc"><?= $e($descCourt) ?></p>
                <?php endif; ?>

                <div class="urgences-card__meta">
                    <span class="urgences-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <?= $e($clientNom ?: 'Client') ?>
                    </span>
                    <span class="urgences-card__meta-sep" aria-hidden="true">·</span>
                    <span class="urgences-card__meta-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= $duree <= 1 ? '~1 h' : '~' . number_format($duree, 0) . ' h' ?>
                    </span>
                </div>
            </div>

            <!-- Action accepter -->
            <div class="urgences-card__action">
                <form method="post" action="<?= $baseUrl ?>/expert/urgence-accept/<?= (int)($m['demande_id'] ?? 0) ?>" onsubmit="this.querySelector('button').disabled=true;">
                    <?= \App\Core\Security::getCsrfField() ?>
                    <button type="submit" class="btn urgences-card__btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Accepter
                    </button>
                </form>
            </div>

        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>

</section>
