<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$user = $user ?? null;
$redirectUrl = $redirectUrl ?? $baseUrl . '/client/demandes/nouvelle';
$demandes = $demandes ?? [];
$urgenceLb = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
?>
<div class="page-demandes-public">

    <header class="demandes-public-hero">
        <div class="demandes-public-hero__icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 4C12.95 4 4 12.95 4 24s8.95 20 20 20 20-8.95 20-20S35.05 4 24 4zm0 36c-8.82 0-16-7.18-16-16S15.18 8 24 8s16 7.18 16 16-7.18 16-16 16z" fill="currentColor" opacity="0.2"/><path d="M24 14v10l6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
        </div>
        <span class="demandes-public-hero__badge">Demandes d'assistance</span>
        <?php if (!empty($demandes)): ?>
        <span class="dem-pub-hero-count">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?> ouverte<?= count($demandes) > 1 ? 's' : '' ?> en ce moment
        </span>
        <?php endif; ?>
        <h1 class="demandes-public-hero__title">Créer une demande</h1>
        <p class="demandes-public-hero__lead">Décrivez votre besoin (projet, bug, exercice, mission urgente). Un expert disponible vous accompagnera en 1 à 3 heures. Inscription gratuite, paiement sécurisé en XOF.</p>
    </header>

    <section class="demandes-public-cta">
        <div class="demandes-public-cta__card">
            <div class="demandes-public-cta__content">
                <?php if ($user && ($user['role'] ?? '') === 'client'): ?>
                    <p class="demandes-public-cta__text">Vous êtes connecté en tant que client. Créez une nouvelle demande et choisissez un expert.</p>
                    <div class="demandes-public-cta__actions">
                        <a href="<?= $e($redirectUrl) ?>" class="btn btn-primary btn-lg">Créer une demande</a>
                        <a href="<?= $baseUrl ?>/client/demandes" class="btn btn-outline btn-lg">Voir mes demandes</a>
                    </div>
                <?php elseif ($user): ?>
                    <p class="demandes-public-cta__text">Les demandes sont réservées aux clients. Créez un compte client ou connectez-vous avec un compte client pour déposer une demande.</p>
                    <div class="demandes-public-cta__actions">
                        <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="btn btn-primary btn-lg">S'inscrire en tant que client</a>
                        <a href="<?= $baseUrl ?>/experts" class="btn btn-outline btn-lg">Voir les experts</a>
                    </div>
                <?php else: ?>
                    <p class="demandes-public-cta__text">Connectez-vous ou créez un compte gratuit pour déposer votre demande et être mis en relation avec un expert.</p>
                    <div class="demandes-public-cta__actions">
                        <a href="<?= $baseUrl ?>/auth/connexion?redirect=<?= urlencode($redirectUrl) ?>" class="btn btn-primary btn-lg">Se connecter</a>
                        <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="btn btn-outline btn-lg">Créer un compte gratuit</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($demandes)): ?>
    <?php $_nbDemOuv = count($demandes); ?>
    <section class="demandes-public-open" aria-labelledby="demandes-public-open-title">
        <h2 id="demandes-public-open-title" class="demandes-public-open__title">
            Demandes ouvertes
            <span class="dem-pub-count-badge" aria-label="<?= $_nbDemOuv ?> demandes ouvertes"><?= $_nbDemOuv ?></span>
        </h2>
        <p class="demandes-public-open__desc">
            <?= $_nbDemOuv ?> demande<?= $_nbDemOuv > 1 ? 's' : '' ?> ouverte<?= $_nbDemOuv > 1 ? 's' : '' ?> récente<?= $_nbDemOuv > 1 ? 's' : '' ?> sur la plateforme.
        </p>
        <ul class="demandes-public-open__list">
            <?php foreach ($demandes as $d):
                $cp = trim((string) ($d['client_prenom'] ?? ''));
                $cn = trim((string) ($d['client_nom'] ?? ''));
                $initialsList = strtoupper(mb_substr($cp, 0, 1) . mb_substr($cn, 0, 1));
                if ($initialsList === '') {
                    $initialsList = '?';
                }
                $colorsList   = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#d97706'];
                $avatarBgList = $colorsList[abs(crc32($cp . $cn)) % count($colorsList)];
                $clientLabelRaw = $cp !== '' ? ($cn !== '' ? $cp . ' ' . mb_substr($cn, 0, 1) . '.' : $cp) : '';
            ?>
            <?php
            $jobUrl = !empty($d['slug'])
                ? $baseUrl . '/jobs/' . $e($d['slug'])
                : null;
            ?>
            <li class="demandes-public-open__item">
                <?php
                $initials     = $initialsList;
                $avatarBg     = $avatarBgList;
                $avatarColumn = $d['client_avatar'] ?? null;
                $pays         = $d['client_pays'] ?? null;
                $alt          = $clientLabelRaw !== '' ? 'Client ' . $clientLabelRaw : '';
                $size         = 'md';
                require APP_PATH . '/Views/partials/public_user_thumb.php';
                ?>
                <div class="demandes-public-open__item-body">
                    <?php if ($jobUrl): ?>
                    <a href="<?= $jobUrl ?>" class="demandes-public-open__item-link" aria-label="Voir la mission : <?= $e($d['titre'] ?? '') ?>">
                        <p class="demandes-public-open__item-title"><?= $e($d['titre'] ?? '') ?></p>
                    </a>
                    <?php else: ?>
                    <p class="demandes-public-open__item-title"><?= $e($d['titre'] ?? '') ?></p>
                    <?php endif; ?>
                    <?php if ($clientLabelRaw !== ''): ?>
                    <span class="demandes-public-open__item-client">Demandeur : <?= $e($clientLabelRaw) ?></span>
                    <?php endif; ?>
                    <span class="demandes-public-open__item-meta">
                        <?php if (!empty($d['competence_nom'])): ?>
                            <span><?= $e($d['competence_nom']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($d['urgence']) && ($d['urgence'] ?? '') !== 'normale'): ?>
                            <span class="demandes-public-open__urgence"><?= $e($urgenceLb[$d['urgence']] ?? $d['urgence']) ?></span>
                        <?php endif; ?>
                        <span><?= date('d/m/Y', strtotime($d['created_at'] ?? 'now')) ?></span>
                    </span>
                    <?php if ($jobUrl): ?>
                    <a href="<?= $jobUrl ?>" class="demandes-public-open__item-cta">Voir la mission →</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$user): ?>
        <div class="demandes-public-open__cta">
            <a href="<?= $e($baseUrl . '/auth/connexion?redirect=' . urlencode($redirectUrl)) ?>" class="btn btn-primary">Se connecter pour créer une demande</a>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="demandes-public-steps">
        <h2 class="demandes-public-steps__title">Comment ça marche</h2>
        <div class="demandes-public-steps__grid">
            <article class="demandes-public-step">
                <span class="demandes-public-step__num" aria-hidden="true">1</span>
                <h3 class="demandes-public-step__heading">Créez une demande</h3>
                <p class="demandes-public-step__desc">Titre, description, compétence et durée estimée.</p>
            </article>
            <article class="demandes-public-step">
                <span class="demandes-public-step__num" aria-hidden="true">2</span>
                <h3 class="demandes-public-step__heading">Choisissez un expert</h3>
                <p class="demandes-public-step__desc">Réservez un créneau avec l'expert de votre choix.</p>
            </article>
            <article class="demandes-public-step">
                <span class="demandes-public-step__num" aria-hidden="true">3</span>
                <h3 class="demandes-public-step__heading">Session en direct</h3>
                <p class="demandes-public-step__desc">Travaillez ensemble (visio, chat, partage d'écran).</p>
            </article>
            <article class="demandes-public-step">
                <span class="demandes-public-step__num" aria-hidden="true">4</span>
                <h3 class="demandes-public-step__heading">Paiement sécurisé</h3>
                <p class="demandes-public-step__desc">En XOF via Jɛmɛnipay (Orange Money, Moov Africa) — sécurisé et rapide.</p>
            </article>
        </div>
    </section>

</div>
<style>
/* Pastille count badge dans le titre section */
.demandes-public-open__title {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
}
.dem-pub-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: #f97316;
    color: #fff;
    font-size: .8125rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.02em;
    vertical-align: middle;
    flex-shrink: 0;
}
/* Compteur dans le hero */
.dem-pub-hero-count {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin-top: .5rem;
    background: rgba(249,115,22,.12);
    color: #ea580c;
    border: 1px solid rgba(249,115,22,.3);
    border-radius: 999px;
    padding: .3rem .75rem;
    font-size: .8125rem;
    font-weight: 600;
    line-height: 1.3;
}
</style>
