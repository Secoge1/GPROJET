<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$professeurs = $professeurs ?? [];
$matieres = $matieres ?? [];
$user = $user ?? null;
$isEtudiant = $user && ($user['role'] ?? '') === 'etudiant';
$nb = count($professeurs);
$listBase = $publicProfesseursBase ?? ($baseUrl . '/professeurs');
$hasSearch = !empty($_GET['q']) || !empty($_GET['matiere']);
?>
<div class="mob-experts mob-professeurs">

    <div class="mob-experts__header">
        <span class="mob-experts__header-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
            <?= $isEtudiant ? 'Professeurs d\'université' : 'Professeurs disponibles' ?>
        </span>
        <h1 class="mob-experts__title">
            <?= $isEtudiant ? 'Réserver un professeur' : 'Professeurs disponibles' ?>
        </h1>
        <p class="mob-experts__lead">
            <?= $isEtudiant
                ? 'Sessions de cours, tutorat et aide par matière. Réservez en quelques clics.'
                : 'Parcourez les professeurs vérifiés et leurs matières.' ?>
        </p>
    </div>

    <form action="<?= $e($listBase) ?>" method="get" class="mob-experts__search-form" role="search">
        <input type="search" name="q" class="mobile-search"
               value="<?= $e($_GET['q'] ?? '') ?>"
               placeholder="<?= $isEtudiant ? 'Nom, matière…' : 'Rechercher…' ?>"
               autocomplete="off"
               aria-label="Rechercher un professeur">
        <?php if (!empty($_GET['matiere'])): ?>
        <input type="hidden" name="matiere" value="<?= (int)$_GET['matiere'] ?>">
        <?php endif; ?>
    </form>

    <?php if (!empty($matieres)): ?>
    <div class="mob-experts__cats" role="list" aria-label="Filtrer par matière">
        <a href="<?= $e($listBase) ?>" class="mobile-cat <?= empty($_GET['matiere']) ? 'active' : '' ?>" role="listitem">Tous</a>
        <?php foreach ($matieres as $m): ?>
        <a href="<?= $e($listBase) ?>?matiere=<?= (int)$m['id'] ?><?= !empty($_GET['q']) ? '&q=' . urlencode((string)$_GET['q']) : '' ?>"
           class="mobile-cat <?= ((int)($_GET['matiere'] ?? 0)) === (int)$m['id'] ? 'active' : '' ?>"
           role="listitem"><?= $e($m['nom']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasSearch || $nb > 0): ?>
    <div class="mob-experts__count">
        <?php if ($hasSearch): ?>
            <?php if ($nb > 0): ?>
            <strong><?= $nb ?></strong> résultat<?= $nb > 1 ? 's' : '' ?>
            <?php else: ?>
            Aucun résultat
            <?php endif; ?>
        <?php else: ?>
            <strong><?= $nb ?></strong> professeur<?= $nb > 1 ? 's' : '' ?> disponible<?= $nb > 1 ? 's' : '' ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($professeurs)): ?>
    <div class="mob-experts__empty">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
        <p><?= $hasSearch ? 'Aucun professeur ne correspond à votre recherche.' : 'Aucun professeur disponible pour le moment.' ?></p>
        <?php if ($hasSearch): ?>
        <a href="<?= $e($listBase) ?>" class="mobile-cat active" style="margin-top:.25rem">Voir tous</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <ul class="mob-experts-list" aria-label="Liste des professeurs">
        <?php foreach ($professeurs as $p):
            $initials = strtoupper(trim(mb_substr($p['prenom'] ?? '', 0, 1) . mb_substr($p['nom'] ?? '', 0, 1)));
            if ($initials === '') { $initials = strtoupper(mb_substr($p['titre'] ?? '', 0, 1)) ?: '?'; }
            $avatarColors = ['#7c3aed','#6d28d9','#5b21b6','#4c1d95','#2563eb'];
            $colorIdx = abs(crc32($p['nom'] ?? '')) % count($avatarColors);
            $note = isset($p['note_moyenne']) && $p['note_moyenne'] !== null ? (float)$p['note_moyenne'] : null;
            $nbAvis = (int)($p['nombre_avis'] ?? 0);
            $fullNameRaw = trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''));
            $fullName = $e($fullNameRaw);
            $href = $listBase . '/' . (int)$p['id'];
        ?>
        <li>
            <a href="<?= $e($href) ?>" class="mob-expert-card mob-professeurs__card" aria-label="Profil <?= $fullName ?>">
                <div class="mob-expert-card__avatar-wrap">
                    <?php
                    $avatarBg     = $avatarColors[$colorIdx];
                    $avatarColumn = $p['avatar'] ?? null;
                    $pays         = $p['pays'] ?? null;
                    $alt          = $fullNameRaw !== '' ? 'Photo de ' . $fullNameRaw : '';
                    $size         = 'md';
                    require APP_PATH . '/Views/partials/public_user_thumb.php';
                    ?>
                    <span class="mob-expert-card__dispo mob-professeurs__dispo" title="Disponible"></span>
                </div>
                <div class="mob-expert-card__body">
                    <p class="mob-expert-card__name"><?= $fullName ?></p>
                    <p class="mob-expert-card__titre"><?= $e($p['titre'] ?? '') ?></p>
                    <div class="mob-expert-card__foot">
                        <?php if ($note !== null): ?>
                        <span class="mob-expert-card__note">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?= number_format($note, 1) ?>
                            <?php if ($nbAvis > 0): ?><span class="mob-expert-card__avis">(<?= $nbAvis ?>)</span><?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($p['tarif_horaire'])): ?>
                        <span class="mob-expert-card__tarif mob-professeurs__tarif"><?= number_format((float)$p['tarif_horaire'], 0, ',', ' ') ?> <?= $e(devise()) ?>/h</span>
                        <?php endif; ?>
                    </div>
                </div>
                <svg class="mob-expert-card__arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!$user): ?>
    <div class="mob-experts__cta">
        <p class="mob-experts__cta-title">Étudiant ?</p>
        <p class="mob-experts__cta-subtitle">Créez un compte gratuit pour réserver des sessions avec nos professeurs.</p>
        <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="btn-publish">S'inscrire</a>
    </div>
    <?php endif; ?>

</div>
