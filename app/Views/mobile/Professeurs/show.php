<?php
use App\Helpers\PublicUserPresentation;

$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$professeur = $professeur ?? [];
$matieres = $matieres ?? [];
$user = $user ?? null;
$role = isset($user['role']) ? trim((string)$user['role']) : '';
$isEtudiant = !empty($isEtudiant) || ($role !== '' && strtolower($role) === 'etudiant');
$listBase = $publicProfesseursBase ?? ($baseUrl . '/professeurs');

$nomComplet = trim(($professeur['prenom'] ?? '') . ' ' . ($professeur['nom'] ?? ''));
$initiales = strtoupper(mb_substr($professeur['prenom'] ?? '', 0, 1) . mb_substr($professeur['nom'] ?? '', 0, 1));
if ($initiales === '') $initiales = strtoupper(mb_substr($professeur['titre'] ?? 'P', 0, 1));
$avatarUrl = PublicUserPresentation::publicAvatarUrl($professeur['avatar'] ?? null, $baseUrl);
$hasUpload = PublicUserPresentation::hasUploadedAvatar($professeur['avatar'] ?? null);
$paysPro   = $professeur['pays'] ?? null;
$flag      = PublicUserPresentation::countryFlagEmoji($paysPro);
$flagTitle = PublicUserPresentation::countryLabel($paysPro);
$verifie = !empty($professeur['valide_par_admin']);
$note = isset($professeur['note_moyenne']) && $professeur['note_moyenne'] !== null ? (float)$professeur['note_moyenne'] : null;
$nbAvis = (int)($professeur['nombre_avis'] ?? 0);
$tarif = number_format((float)($professeur['tarif_horaire'] ?? 0), 0, ',', ' ');
$disponible = !empty($professeur['disponible']) || (string)($professeur['disponible'] ?? '') === '1';
$colors = ['#7c3aed','#6d28d9','#5b21b6'];
$avatarColor = $colors[abs(crc32($initiales)) % count($colors)];
$reserverUrl = $baseUrl . '/app/reserver-professeur/' . (int)($professeur['id'] ?? 0);
?>

<div class="mob-expert-profile mob-professeur-profile">

    <div class="mob-expert-profile__nav">
        <a href="<?= $e($listBase) ?>" class="mob-expert-profile__back" aria-label="Retour">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <span class="mob-expert-profile__breadcrumb">Professeurs</span>
    </div>

    <div class="mob-expert-profile__hero">
        <div class="mob-expert-profile__avatar-wrap mob-prof-show-avatar-stack">
            <div class="mob-expert-profile__avatar-initials" style="background:<?= $e($avatarColor) ?>"><?= $e($initiales) ?></div>
            <img src="<?= $e($avatarUrl) ?>" alt="<?= $e($nomComplet) ?>" class="mob-expert-profile__avatar-img" loading="lazy" decoding="async" width="88" height="88"
                 <?php if ($hasUpload): ?>onerror="this.style.display='none';"<?php endif; ?>>
            <?php if ($flag !== ''): ?>
            <span class="public-user-thumb__flag" title="<?= $e($flagTitle) ?>"><?= $flag ?></span>
            <?php endif; ?>
            <span class="mob-expert-profile__dispo-dot mob-expert-profile__dispo-dot--<?= $disponible ? 'on' : 'off' ?>"></span>
        </div>
        <div class="mob-expert-profile__identity">
            <div class="mob-expert-profile__name-row">
                <h1 class="mob-expert-profile__name"><?= $e($professeur['titre'] ?? '') ?></h1>
                <?php if ($verifie): ?>
                <span class="mob-expert-profile__verified" title="Vérifié">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($nomComplet): ?>
            <p class="mob-expert-profile__fullname"><?= $e($nomComplet) ?></p>
            <?php endif; ?>
            <span class="mob-expert-profile__dispo-badge mob-expert-profile__dispo-badge--<?= $disponible ? 'on' : 'off' ?>">
                <?= $disponible ? '● Disponible' : '○ Indisponible' ?>
            </span>
        </div>
        <div class="mob-expert-profile__stats">
            <div class="mob-expert-profile__stat">
                <span class="mob-expert-profile__stat-value mob-professeur-profile__tarif"><?= $tarif ?> <?= $e(devise()) ?></span>
                <span class="mob-expert-profile__stat-label">par heure</span>
            </div>
            <?php if ($note !== null && $nbAvis > 0): ?>
            <div class="mob-expert-profile__stat-divider"></div>
            <div class="mob-expert-profile__stat">
                <span class="mob-expert-profile__stat-value mob-expert-profile__stat-value--star">★ <?= number_format($note, 1) ?></span>
                <span class="mob-expert-profile__stat-label"><?= $nbAvis ?> avis</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isEtudiant && $disponible): ?>
    <div class="mob-expert-profile__cta-wrap">
        <a href="<?= $e($reserverUrl) ?>" class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--primary mob-professeur-profile__cta">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Réserver une session
        </a>
    </div>
    <?php elseif ($isEtudiant && !$disponible): ?>
    <div class="mob-expert-profile__unavail">
        Ce professeur n'est pas disponible pour le moment.
        <a href="<?= $e($listBase) ?>" style="color:#7c3aed;font-weight:600">Autres professeurs</a>
    </div>
    <?php elseif (!$user): ?>
    <div class="mob-expert-profile__cta-wrap">
        <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--primary mob-professeur-profile__cta">
            S'inscrire (étudiant) pour réserver
        </a>
        <a href="<?= $baseUrl ?>/auth/connexion" class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--outline mob-professeur-profile__cta-outline">Se connecter</a>
    </div>
    <?php else: ?>
    <div class="mob-expert-profile__unavail">
        Pour réserver, utilisez un compte <strong>étudiant</strong>.
        <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" style="color:#7c3aed;font-weight:600">Créer un compte étudiant</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($professeur['description'])): ?>
    <div class="mob-expert-profile__section">
        <div class="mob-expert-profile__section-header">
            <h2 class="mob-expert-profile__section-title">À propos</h2>
        </div>
        <p class="mob-expert-profile__description"><?= nl2br($e($professeur['description'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($matieres)): ?>
    <div class="mob-expert-profile__section">
        <div class="mob-expert-profile__section-header">
            <h2 class="mob-expert-profile__section-title">Matières</h2>
        </div>
        <div class="mob-expert-profile__skills">
            <?php foreach ($matieres as $m): ?>
            <span class="mob-expert-profile__skill-tag mob-professeur-profile__skill"><?= $e($m['nom'] ?? '') ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
