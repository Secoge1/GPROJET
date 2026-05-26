<?php
use App\Helpers\PublicUserPresentation;

$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$expert       = $expert ?? [];
$competences  = $competences ?? [];
$avis         = $avis ?? [];
$demandeId    = (int)($demandeId ?? $_GET['demande_id'] ?? 0);
$profileUrl   = $profileUrl ?? $baseUrl . '/experts/show/' . (int)($expert['id'] ?? 0);
$isLoggedIn   = !empty($user);
$role         = $user['role'] ?? '';

$nomComplet   = trim(($expert['prenom'] ?? '') . ' ' . ($expert['nom'] ?? ''));
$initiales    = strtoupper(mb_substr($expert['prenom'] ?? '', 0, 1) . mb_substr($expert['nom'] ?? '', 0, 1));
if ($initiales === '') $initiales = strtoupper(mb_substr($expert['titre'] ?? 'E', 0, 1));
$avatarUrl    = PublicUserPresentation::publicAvatarUrl($expert['avatar'] ?? null, $baseUrl);
$hasUpload    = PublicUserPresentation::hasUploadedAvatar($expert['avatar'] ?? null);
$pays         = $expert['pays'] ?? null;
$flag         = PublicUserPresentation::countryFlagEmoji($pays);
$flagTitle    = PublicUserPresentation::countryLabel($pays);
$verifie      = !empty($expert['valide_par_admin']);
$note         = $expert['note_moyenne'] !== null ? (float)$expert['note_moyenne'] : null;
$nbAvis       = (int)($expert['nombre_avis'] ?? 0);
$tarif        = number_format((float)($expert['tarif_horaire'] ?? 0), 0, ',', ' ');

$shareTitle   = $e(($expert['titre'] ?? 'Expert') . ' sur GLOBALO');
$shareText    = $e(($expert['titre'] ?? '') . ' — Réservez une session avec cet expert.');

// Couleur avatar
$colors = ['#2563eb','#16a34a','#7c3aed','#0d9488','#dc2626','#d97706'];
$avatarColor = $colors[abs(crc32($initiales)) % count($colors)];
?>
<section class="section-desktop expert-show-page" data-growth-track="view_expert_profile" data-expert-id="<?= (int)($expert['id'] ?? 0) ?>">

    <!-- Retour -->
    <a href="<?= $baseUrl ?>/experts" class="page-expert__back expert-show__back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour aux experts
    </a>

    <div class="expert-show__layout">

        <!-- ═══ Colonne principale ═══ -->
        <div class="expert-show__main">

            <!-- Hero card -->
            <div class="expert-show__hero-card">
                <!-- Avatar -->
                <div class="expert-show__avatar-wrap expert-show__avatar-wrap--stack">
                    <div class="expert-show__avatar-fallback" style="background:<?= $e($avatarColor) ?>;"><?= $e($initiales) ?></div>
                    <img src="<?= $e($avatarUrl) ?>" alt="<?= $e($nomComplet) ?>" class="expert-show__avatar-img<?= $hasUpload ? '' : ' public-user-thumb__img--overlay' ?>" loading="lazy" decoding="async" width="100" height="100"
                         <?php if ($hasUpload): ?>onerror="this.style.display='none';"<?php endif; ?>>
                    <?php if ($flag !== ''): ?>
                    <span class="public-user-thumb__flag" title="<?= $e($flagTitle) ?>"><?= $flag ?></span>
                    <?php endif; ?>
                    <?php if ($verifie): ?>
                    <div class="expert-show__verified-ring" title="Expert vérifié">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Identité -->
                <div class="expert-show__identity">
                    <div class="expert-show__identity-top">
                        <div>
                            <h1 class="expert-show__titre"><?= $e($expert['titre'] ?? '') ?></h1>
                            <p class="expert-show__nom"><?= $e($nomComplet) ?></p>
                        </div>
                        <?php if ($verifie): ?>
                        <span class="expert-show__badge-verified">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Vérifié
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Stats ligne -->
                    <div class="expert-show__stats">
                        <!-- Note -->
                        <div class="expert-show__stat">
                            <?php if ($note !== null && $nbAvis > 0): ?>
                            <span class="expert-show__stars" aria-label="Note <?= number_format($note, 1) ?>/5">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $i <= round($note) ? '#f59e0b' : 'none' ?>" stroke="#f59e0b" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <?php endfor; ?>
                            </span>
                            <span class="expert-show__note-val"><?= number_format($note, 1) ?></span>
                            <span class="expert-show__avis-count"><?= $nbAvis ?> avis</span>
                            <?php else: ?>
                            <span class="expert-show__no-avis">Pas encore d'avis</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($expert['niveau_experience'])): ?>
                        <div class="expert-show__stat expert-show__stat--level">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="20" width="4" height="4"/><rect x="9" y="14" width="4" height="10"/><rect x="16" y="8" width="4" height="16"/></svg>
                            <?= $e(ucfirst($expert['niveau_experience'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Types de professions -->
            <?php if (!empty($competences)): ?>
            <div class="expert-show__section expert-show__section--professions">
                <h2 class="expert-show__section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Types de professions
                </h2>
                <?php require APP_PATH . '/Views/partials/expert_types_professions.php'; ?>
            </div>
            <?php endif; ?>

            <!-- Description -->
            <?php if (!empty($expert['description'])): ?>
            <div class="expert-show__section">
                <h2 class="expert-show__section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    À propos
                </h2>
                <div class="expert-show__description"><?= nl2br($e($expert['description'])) ?></div>
            </div>
            <?php endif; ?>

            <!-- Partage -->
            <div class="expert-show__section expert-show__share">
                <span class="expert-show__share-label">Partager ce profil</span>
                <div class="expert-show__share-btns">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($profileUrl) ?>" target="_blank" rel="noopener noreferrer" class="expert-show__share-btn expert-show__share-btn--fb" aria-label="Facebook">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($profileUrl) ?>&text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener noreferrer" class="expert-show__share-btn expert-show__share-btn--x" aria-label="X (Twitter)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($profileUrl) ?>" target="_blank" rel="noopener noreferrer" class="expert-show__share-btn expert-show__share-btn--li" aria-label="LinkedIn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <button type="button" class="expert-show__share-btn expert-show__share-btn--copy" id="expert-copy-link" data-url="<?= $e($profileUrl) ?>" aria-label="Copier le lien">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>
            </div>

        </div>

        <!-- ═══ Sidebar droite ═══ -->
        <aside class="expert-show__sidebar">

            <!-- Tarif card -->
            <div class="expert-show__tarif-card">
                <div class="expert-show__tarif-top">
                    <div class="expert-show__tarif-amount">
                        <span class="expert-show__tarif-val"><?= $e($tarif) ?></span>
                        <span class="expert-show__tarif-devise"><?= $e(devise()) ?></span>
                        <span class="expert-show__tarif-unit">/ heure</span>
                    </div>
                    <?php if ($note !== null && $nbAvis > 0): ?>
                    <div class="expert-show__tarif-note">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?= number_format($note, 1) ?> <span>(<?= $nbAvis ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CTA principal -->
                <?php if ($demandeId > 0 && $isLoggedIn && $role === 'client'): ?>
                <a href="<?= $baseUrl ?>/client/reserver/<?= $demandeId ?>?expert=<?= (int)($expert['id'] ?? 0) ?>" class="btn btn-primary expert-show__cta">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Réserver une session
                </a>
                <?php elseif ($isLoggedIn && $role === 'client'): ?>
                <a href="<?= $baseUrl ?>/client/demandes" class="btn btn-primary expert-show__cta">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Créer une demande
                </a>
                <p class="expert-show__cta-hint">Créez d'abord une demande, puis réservez cet expert.</p>
                <?php elseif ($isLoggedIn): ?>
                <p class="expert-show__cta-note">Pour réserver, connectez-vous avec un compte <strong>client</strong>.</p>
                <a href="<?= $baseUrl ?>/auth/deconnexion" class="btn btn-outline expert-show__cta">Changer de compte</a>
                <?php else: ?>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary expert-show__cta">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Rejoindre GLOBALO
                </a>
                <?php endif; ?>

                <div class="expert-show__guarantees">
                    <div class="expert-show__guarantee">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Paiement sécurisé via escrow
                    </div>
                    <div class="expert-show__guarantee">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Satisfaction garantie
                    </div>
                    <div class="expert-show__guarantee">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Support GLOBALO inclus
                    </div>
                </div>
            </div>

        </aside>
    </div>

    <!-- Avis clients -->
    <div class="expert-show__reviews" id="avis">
        <h2 class="expert-show__reviews-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Avis clients
            <?php if ($nbAvis > 0): ?>
            <span class="expert-show__reviews-count"><?= $nbAvis ?> avis</span>
            <?php endif; ?>
        </h2>

        <?php if (!empty($avis)): ?>
        <div class="expert-show__reviews-grid">
            <?php foreach ($avis as $a):
                $auteur = trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''));
                $noteA  = (int)$a['note'];
            ?>
            <div class="expert-review-item">
                <div class="expert-review-item__header">
                    <div class="expert-review-item__avatar"><?= $e(strtoupper(substr($auteur ?: 'C', 0, 1))) ?></div>
                    <div>
                        <div class="expert-review-item__author"><?= $e($auteur ?: 'Client') ?></div>
                        <div class="expert-review-item__stars" aria-label="Note <?= $noteA ?>/5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="<?= $i <= $noteA ? '#f59e0b' : 'none' ?>" stroke="#f59e0b" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty(trim($a['commentaire'] ?? ''))): ?>
                <p class="expert-review-item__text"><?= nl2br($e($a['commentaire'])) ?></p>
                <?php else: ?>
                <p class="expert-review-item__text expert-review-item__text--note-only">Note : <?= $noteA ?>/5</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="expert-show__reviews-empty">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <p>Aucun avis pour le moment. Réservez une session pour laisser le premier avis.</p>
        </div>
        <?php endif; ?>
    </div>

</section>

<script>
(function() {
    var btn = document.getElementById('expert-copy-link');
    if (btn) {
        btn.addEventListener('click', function() {
            var url = btn.getAttribute('data-url') || '';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
                    btn.style.background = '#dcfce7';
                    btn.style.color = '#16a34a';
                    setTimeout(function() {
                        btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                });
            }
        });
    }
})();
</script>
