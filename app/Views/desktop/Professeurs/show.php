<?php
use App\Helpers\PublicUserPresentation;

$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$professeur  = $professeur ?? [];
$matieres    = $matieres ?? [];
$profileUrl  = $profileUrl ?? $baseUrl . '/professeurs/show/' . (int)($professeur['id'] ?? 0);
$isLoggedIn  = !empty($user);
$role        = $user['role'] ?? '';

$nomComplet  = trim(($professeur['prenom'] ?? '') . ' ' . ($professeur['nom'] ?? ''));
$initiales   = strtoupper(mb_substr($professeur['prenom'] ?? '', 0, 1) . mb_substr($professeur['nom'] ?? '', 0, 1));
if ($initiales === '') $initiales = strtoupper(mb_substr($professeur['titre'] ?? 'P', 0, 1));
$avatarUrl   = PublicUserPresentation::publicAvatarUrl($professeur['avatar'] ?? null, $baseUrl);
$hasUpload   = PublicUserPresentation::hasUploadedAvatar($professeur['avatar'] ?? null);
$pays        = $professeur['pays'] ?? null;
$flag        = PublicUserPresentation::countryFlagEmoji($pays);
$flagTitle   = PublicUserPresentation::countryLabel($pays);
$verifie     = !empty($professeur['valide_par_admin']);
$note        = $professeur['note_moyenne'] !== null ? (float)$professeur['note_moyenne'] : null;
$nbAvis      = (int)($professeur['nombre_avis'] ?? 0);
$tarif       = number_format((float)($professeur['tarif_horaire'] ?? 0), 0, ',', ' ');
$colors      = ['#7c3aed','#6d28d9','#5b21b6'];
$avatarColor = $colors[abs(crc32($initiales)) % count($colors)];
?>
<section class="section-desktop expert-show-page professeur-show-page">

    <a href="<?= $baseUrl ?>/professeurs" class="page-expert__back expert-show__back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour aux professeurs
    </a>

    <div class="expert-show__layout">

        <div class="expert-show__main">
            <div class="expert-show__hero-card">
                <div class="expert-show__avatar-wrap prof-show-avatar-stack">
                    <div class="expert-show__avatar-fallback" style="background:<?= $e($avatarColor) ?>;"><?= $e($initiales) ?></div>
                    <img src="<?= $e($avatarUrl) ?>" alt="<?= $e($nomComplet) ?>" class="expert-show__avatar-img<?= $hasUpload ? '' : ' public-user-thumb__img--overlay' ?>" loading="lazy" decoding="async" width="100" height="100"
                         <?php if ($hasUpload): ?>onerror="this.style.display='none';"<?php endif; ?>>
                    <?php if ($flag !== ''): ?>
                    <span class="public-user-thumb__flag" title="<?= $e($flagTitle) ?>"><?= $flag ?></span>
                    <?php endif; ?>
                    <?php if ($verifie): ?>
                    <div class="expert-show__verified-ring" title="Professeur vérifié"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <?php endif; ?>
                </div>
                <div class="expert-show__identity">
                    <div class="expert-show__identity-top">
                        <div>
                            <h1 class="expert-show__titre"><?= $e($professeur['titre'] ?? '') ?></h1>
                            <p class="expert-show__nom"><?= $e($nomComplet) ?></p>
                        </div>
                        <?php if ($verifie): ?>
                        <span class="expert-show__badge-verified">Vérifié</span>
                        <?php endif; ?>
                    </div>
                    <div class="expert-show__stats">
                        <div class="expert-show__stat">
                            <?php if ($note !== null && $nbAvis > 0): ?>
                            <span class="expert-show__stars"><?php for ($i = 1; $i <= 5; $i++): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $i <= round($note) ? '#f59e0b' : 'none' ?>" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?></span>
                            <span><?= number_format($note, 1) ?></span> <span><?= $nbAvis ?> avis</span>
                            <?php else: ?>
                            <span class="expert-show__no-avis">Pas encore d'avis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($matieres)): ?>
                    <div class="expert-show__skills">
                        <?php foreach ($matieres as $m): ?>
                        <span class="expert-show__skill-chip expert-show__skill-chip--prof"><?= $e($m['nom']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($professeur['description'])): ?>
            <div class="expert-show__section">
                <h2 class="expert-show__section-title">À propos</h2>
                <div class="expert-show__description"><?= nl2br($e($professeur['description'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <aside class="expert-show__sidebar">
            <div class="expert-show__tarif-card">
                <div class="expert-show__tarif-top">
                    <div class="expert-show__tarif-amount">
                        <span class="expert-show__tarif-val expert-show__tarif-val--prof"><?= $e($tarif) ?></span>
                        <span class="expert-show__tarif-devise"><?= $e(devise()) ?></span>
                        <span class="expert-show__tarif-unit">/ heure</span>
                    </div>
                </div>

                <?php if ($isLoggedIn && $role === 'etudiant'): ?>
                <a href="<?= $baseUrl ?>/etudiant/reserver-professeur/<?= (int)$professeur['id'] ?>" class="btn expert-show__cta expert-show__cta--prof">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Réserver une session
                </a>
                <?php elseif ($isLoggedIn): ?>
                <p class="expert-show__cta-note">Pour réserver un professeur, connectez-vous avec un compte <strong>étudiant</strong>.</p>
                <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="btn btn-outline expert-show__cta">Créer un compte étudiant</a>
                <?php else: ?>
                <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="btn expert-show__cta expert-show__cta--prof">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Rejoindre GLOBALO (étudiant)
                </a>
                <?php endif; ?>

                <div class="expert-show__guarantees">
                    <div class="expert-show__guarantee"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Sessions de cours personnalisées</div>
                    <div class="expert-show__guarantee"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Correction d'exercices incluse</div>
                </div>
            </div>
        </aside>
    </div>
</section>
