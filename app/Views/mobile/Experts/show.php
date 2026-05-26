<?php
use App\Helpers\PublicUserPresentation;

$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$expert      = $expert ?? [];
$competences = $competences ?? [];
$avis        = $avis ?? [];
$demandeId   = (int)($demandeId ?? $_GET['demande_id'] ?? 0);
$user        = $user ?? null;
$role        = isset($user['role']) ? trim((string) $user['role']) : '';
$isClient    = !empty($isClient) || ($role !== '' && strtolower($role) === 'client');

$nomComplet  = trim(($expert['prenom'] ?? '') . ' ' . ($expert['nom'] ?? ''));
$initiales   = strtoupper(mb_substr($expert['prenom'] ?? '', 0, 1) . mb_substr($expert['nom'] ?? '', 0, 1));
if ($initiales === '') $initiales = strtoupper(mb_substr($expert['titre'] ?? 'E', 0, 1));
$avatarUrl   = PublicUserPresentation::publicAvatarUrl($expert['avatar'] ?? null, $baseUrl);
$hasUpload   = PublicUserPresentation::hasUploadedAvatar($expert['avatar'] ?? null);
$paysExp     = $expert['pays'] ?? null;
$flag        = PublicUserPresentation::countryFlagEmoji($paysExp);
$flagTitle   = PublicUserPresentation::countryLabel($paysExp);
$verifie     = !empty($expert['valide_par_admin']);
$note        = isset($expert['note_moyenne']) && $expert['note_moyenne'] !== null ? (float)$expert['note_moyenne'] : null;
$nbAvis      = (int)($expert['nombre_avis'] ?? count($avis));
$tarif       = number_format((float)($expert['tarif_horaire'] ?? 0), 0, ',', ' ');
$disponible  = !empty($expert['disponible']) || (string)($expert['disponible'] ?? '') === '1';

$colors      = ['#2563eb','#16a34a','#7c3aed','#0d9488','#dc2626','#d97706'];
$avatarColor = $colors[abs(crc32($initiales)) % count($colors)];

// Calcul note étoiles
$noteEntiere = $note !== null ? round($note) : 0;
?>

<div class="mob-expert-profile">

    <!-- Breadcrumb retour -->
    <div class="mob-expert-profile__nav">
        <a href="<?= $baseUrl ?>/experts" class="mob-expert-profile__back" aria-label="Retour aux experts">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <span class="mob-expert-profile__breadcrumb">Experts disponibles</span>
    </div>

    <!-- Hero card -->
    <div class="mob-expert-profile__hero">
        <!-- Avatar + badge disponibilité -->
        <div class="mob-expert-profile__avatar-wrap mob-expert-profile__avatar-wrap--stack">
            <div class="mob-expert-profile__avatar-initials"
                 style="background:<?= $avatarColor ?>"><?= $initiales ?></div>
            <img src="<?= $e($avatarUrl) ?>" alt="<?= $e($nomComplet) ?>"
                 class="mob-expert-profile__avatar-img" loading="lazy" decoding="async" width="88" height="88"
                 <?php if ($hasUpload): ?>onerror="this.style.display='none';"<?php endif; ?>>
            <?php if ($flag !== ''): ?>
            <span class="public-user-thumb__flag" title="<?= $e($flagTitle) ?>"><?= $flag ?></span>
            <?php endif; ?>
            <span class="mob-expert-profile__dispo-dot mob-expert-profile__dispo-dot--<?= $disponible ? 'on' : 'off' ?>"
                  title="<?= $disponible ? 'Disponible' : 'Indisponible' ?>"></span>
        </div>

        <!-- Identité -->
        <div class="mob-expert-profile__identity">
            <div class="mob-expert-profile__name-row">
                <h1 class="mob-expert-profile__name"><?= $e($expert['titre'] ?? '') ?></h1>
                <?php if ($verifie): ?>
                <span class="mob-expert-profile__verified" title="Expert vérifié par GLOBALO">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($nomComplet): ?>
            <p class="mob-expert-profile__fullname"><?= $e($nomComplet) ?></p>
            <?php endif; ?>

            <!-- Badge disponibilité -->
            <span class="mob-expert-profile__dispo-badge mob-expert-profile__dispo-badge--<?= $disponible ? 'on' : 'off' ?>">
                <?= $disponible ? '● Disponible maintenant' : '○ Indisponible' ?>
            </span>
        </div>

        <!-- Stats (tarif, note, missions) -->
        <div class="mob-expert-profile__stats">
            <div class="mob-expert-profile__stat">
                <span class="mob-expert-profile__stat-value"><?= $tarif ?> <?= $e(devise()) ?></span>
                <span class="mob-expert-profile__stat-label">par heure</span>
            </div>
            <?php if ($note !== null && $nbAvis > 0): ?>
            <div class="mob-expert-profile__stat-divider"></div>
            <div class="mob-expert-profile__stat">
                <span class="mob-expert-profile__stat-value mob-expert-profile__stat-value--star">
                    ★ <?= number_format($note, 1) ?>
                </span>
                <span class="mob-expert-profile__stat-label"><?= $nbAvis ?> avis</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($expert['nb_missions_terminees'])): ?>
            <div class="mob-expert-profile__stat-divider"></div>
            <div class="mob-expert-profile__stat">
                <span class="mob-expert-profile__stat-value"><?= (int)$expert['nb_missions_terminees'] ?></span>
                <span class="mob-expert-profile__stat-label">missions</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CTA Réservation (client connecté) -->
    <?php if ($isClient && $disponible): ?>
    <div class="mob-expert-profile__cta-wrap" style="visibility:visible;display:block">
        <?php if ($demandeId): ?>
        <a href="<?= $baseUrl ?>/app/reserver/<?= $demandeId ?>?expert=<?= (int)($expert['id'] ?? 0) ?>"
           class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
            Réserver cet expert
        </a>
        <?php else: ?>
        <a href="<?= $baseUrl ?>/app/nouvelle"
           class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Créer une demande puis réserver
        </a>
        <?php endif; ?>
    </div>
    <?php elseif ($isClient && !$disponible): ?>
    <div class="mob-expert-profile__unavail">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Cet expert n'est pas disponible pour le moment. <a href="<?= $baseUrl ?>/app/experts" style="color:var(--accent);font-weight:600">Voir d'autres experts</a>
    </div>
    <?php elseif (!$user): ?>
    <div class="mob-expert-profile__cta-wrap">
        <a href="<?= $baseUrl ?>/auth/connexion"
           class="mob-expert-profile__cta-btn mob-expert-profile__cta-btn--primary">
            Se connecter pour réserver
        </a>
    </div>
    <?php elseif (!$disponible): ?>
    <div class="mob-expert-profile__unavail">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Cet expert n'est pas disponible pour le moment
    </div>
    <?php endif; ?>

    <!-- Description -->
    <?php if (!empty($expert['description'])): ?>
    <div class="mob-expert-profile__section">
        <div class="mob-expert-profile__section-header">
            <span class="mob-expert-profile__section-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <h2 class="mob-expert-profile__section-title">À propos</h2>
        </div>
        <p class="mob-expert-profile__description">
            <?= nl2br($e($expert['description'])) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Types de professions -->
    <?php if (!empty($competences)): ?>
    <div class="mob-expert-profile__section">
        <div class="mob-expert-profile__section-header">
            <span class="mob-expert-profile__section-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
            <h2 class="mob-expert-profile__section-title">Types de professions</h2>
        </div>
        <?php require APP_PATH . '/Views/partials/expert_types_professions.php'; ?>
    </div>
    <?php endif; ?>

    <!-- Avis clients -->
    <?php if (!empty($avis)): ?>
    <div class="mob-expert-profile__section">
        <div class="mob-expert-profile__section-header">
            <span class="mob-expert-profile__section-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <h2 class="mob-expert-profile__section-title">Avis clients</h2>
            <span class="mob-expert-profile__avis-count"><?= count($avis) ?></span>
        </div>
        <div class="mob-expert-profile__avis-list">
            <?php foreach (array_slice($avis, 0, 5) as $av): ?>
            <div class="mob-expert-profile__avis-item">
                <div class="mob-expert-profile__avis-top">
                    <span class="mob-expert-profile__avis-author">
                        <?= $e(trim(($av['client_prenom'] ?? '') . ' ' . mb_substr($av['client_nom'] ?? '', 0, 1))) ?>.
                    </span>
                    <span class="mob-expert-profile__avis-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="<?= $i <= (int)($av['note'] ?? 0) ? '#f59e0b' : '#e2e8f0' ?>" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?php endfor; ?>
                    </span>
                </div>
                <?php if (!empty($av['commentaire'])): ?>
                <p class="mob-expert-profile__avis-text"><?= $e($av['commentaire']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Aucun avis -->
    <div class="mob-expert-profile__no-avis">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p>Aucun avis pour le moment.</p>
    </div>
    <?php endif; ?>

</div>
