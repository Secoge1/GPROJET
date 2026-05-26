<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$experts     = $experts ?? [];
$competences = $competences ?? [];
$user        = $user ?? null;
$isApp       = !empty($isApp);
$isEtudiant  = $user && ($user['role'] ?? '') === 'etudiant';
$nbExperts   = count($experts);
$hasSearch   = !empty($_GET['q']) || !empty($_GET['competence']);
$expertsListPath = $baseUrl . ($isApp ? '/app/experts' : '/experts');
$qVal = $e($_GET['q'] ?? '');
?>
<div class="mob-experts">

    <!-- En-tête -->
    <div class="mob-experts__header">
        <span class="mob-experts__header-badge">
            <svg width="10" height="10" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
            <?= $isEtudiant ? 'Tuteurs disponibles' : 'Experts disponibles' ?>
        </span>
        <h1 class="mob-experts__title">
            <?= $isEtudiant ? 'Trouver un tuteur' : __("experts.title") ?>
        </h1>
        <p class="mob-experts__lead">
            <?= $isEtudiant
                ? 'Trouvez un expert pour vous aider dans vos matières universitaires.'
                : __("experts.subtitle") ?>
        </p>
    </div>

    <?php if (!$isApp): ?>
    <!-- Barre de recherche (mobile web ; version /app → entête globale) -->
    <form action="<?= $expertsListPath ?>" method="get" class="mob-experts__search-form" role="search">
        <input type="search" name="q"
               class="mobile-search"
               value="<?= $qVal ?>"
               placeholder="<?= $isEtudiant ? 'Matière, domaine…' : $e(__("experts.filters.search") . '…') ?>"
               autocomplete="off"
               aria-label="Rechercher un expert">
        <?php if (!empty($_GET['competence'])): ?>
        <input type="hidden" name="competence" value="<?= (int) $_GET['competence'] ?>">
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <!-- Filtre compétences -->
    <?php if (!empty($competences)): ?>
    <div class="mob-experts__cats" role="list" aria-label="Filtrer par compétence">
        <a href="<?= $expertsListPath ?><?= !empty($_GET['q']) ? '?q=' . urlencode((string) $_GET['q']) : '' ?>"
           class="mobile-cat <?= empty($_GET['competence']) ? 'active' : '' ?>"
           role="listitem">Tous</a>
        <?php foreach ($competences as $c): ?>
        <?php
            $catQs = ['competence' => (string)(int)$c['id']];
            if (!empty($_GET['q'])) {
                $catQs['q'] = (string) $_GET['q'];
            }
            $catHref = $expertsListPath . '?' . http_build_query($catQs);
        ?>
        <a href="<?= $e($catHref) ?>"
           class="mobile-cat <?= ((int)($_GET['competence'] ?? 0)) === (int)$c['id'] ? 'active' : '' ?>"
           role="listitem">
            <?= $e($c['nom']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Compteur résultats -->
    <?php if ($hasSearch || $nbExperts > 0): ?>
    <div class="mob-experts__count">
        <?php if ($hasSearch): ?>
            <?php if ($nbExperts > 0): ?>
            <strong><?= $nbExperts ?></strong> résultat<?= $nbExperts > 1 ? 's' : '' ?> trouvé<?= $nbExperts > 1 ? 's' : '' ?>
            <?php else: ?>
            Aucun résultat pour cette recherche
            <?php endif; ?>
        <?php else: ?>
            <strong><?= $nbExperts ?></strong> expert<?= $nbExperts > 1 ? 's' : '' ?> disponible<?= $nbExperts > 1 ? 's' : '' ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Liste experts -->
    <?php if (empty($experts)): ?>
    <div class="mob-experts__empty">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p><?= $hasSearch ? 'Aucun expert ne correspond à votre recherche.' : 'Aucun expert disponible pour le moment.' ?></p>
        <?php if ($hasSearch): ?>
        <a href="<?= $expertsListPath ?>" class="mobile-cat active" style="margin-top:.25rem">Voir tous les experts</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <ul class="mob-experts-list" aria-label="Liste des experts">
        <?php foreach ($experts as $exp):
            $initials = strtoupper(trim(mb_substr($exp['prenom'] ?? '', 0, 1) . mb_substr($exp['nom'] ?? '', 0, 1)));
            if ($initials === '') { $initials = strtoupper(mb_substr($exp['titre'] ?? '', 0, 1)) ?: '?'; }
            $avatarColors = ['#16a34a','#0d9488','#7c3aed','#d97706','#2563eb'];
            $colorIdx = abs(crc32($exp['nom'] ?? '')) % count($avatarColors);
            $note = isset($exp['note_moyenne']) && $exp['note_moyenne'] !== null ? (float)$exp['note_moyenne'] : null;
            $nbAvis = (int)($exp['nombre_avis'] ?? 0);
            $fullNameRaw = trim(($exp['prenom'] ?? '') . ' ' . ($exp['nom'] ?? ''));
            $fullName = $e($fullNameRaw);
        ?>
        <li>
            <?php $expertPublicHref = $baseUrl . ($isApp ? '/app/experts/' : '/experts/') . (int) $exp['id']; ?>
            <a href="<?= $expertPublicHref ?>"
               class="mob-expert-card"
               aria-label="Voir le profil de <?= $fullName ?>">

                <!-- Photo / Initiales -->
                <div class="mob-expert-card__avatar-wrap mob-expert-card__avatar-wrap--stack">
                    <?php
                    $avatarBg     = $avatarColors[$colorIdx];
                    $avatarColumn = $exp['avatar'] ?? null;
                    $pays         = $exp['pays'] ?? null;
                    $alt          = $fullNameRaw !== '' ? 'Photo de ' . $fullNameRaw : '';
                    $size         = 'md';
                    require APP_PATH . '/Views/partials/public_user_thumb.php';
                    ?>
                    <span class="mob-expert-card__dispo" title="Disponible" aria-label="Disponible"></span>
                </div>

                <!-- Infos -->
                <div class="mob-expert-card__body">
                    <p class="mob-expert-card__name"><?= $fullName ?></p>
                    <p class="mob-expert-card__titre"><?= $e($exp['titre'] ?? '') ?></p>
                    <div class="mob-expert-card__foot">
                        <?php if ($note !== null): ?>
                        <span class="mob-expert-card__note">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?= number_format($note, 1) ?>
                            <?php if ($nbAvis > 0): ?>
                            <span class="mob-expert-card__avis">(<?= $nbAvis ?>)</span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($exp['tarif_horaire'])): ?>
                        <span class="mob-expert-card__tarif">
                            <?= number_format((float)$exp['tarif_horaire'], 0, ',', ' ') ?> <?= $e(devise()) ?>/h
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Flèche -->
                <svg class="mob-expert-card__arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- CTA Inscription (visiteur non connecté) -->
    <?php if (!$user): ?>
    <div class="mob-experts__cta">
        <p class="mob-experts__cta-title">Prêt à démarrer ?</p>
        <p class="mob-experts__cta-subtitle">Inscrivez-vous gratuitement et accédez à tous les experts.</p>
        <a href="<?= $baseUrl ?>/auth/inscription" class="btn-publish">Créer un compte gratuit</a>
    </div>
    <?php endif; ?>

</div>
