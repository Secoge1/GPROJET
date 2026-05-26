<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
?>
<style>
/* ── Styles experts (filet de sécurité cache) ── */
.experts-listing__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.125rem}
.expert-listing-card{background:var(--surface,#fff);border:1.5px solid var(--border,#e2e8f0);border-radius:var(--radius,12px);padding:1.5rem 1.25rem 1.25rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:.625rem;position:relative;box-shadow:0 1px 3px rgba(15,23,42,.05);transition:transform .15s,box-shadow .18s}
.expert-listing-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(22,163,74,.12);border-color:#86efac}
.expert-listing-card__verified{position:absolute;top:.875rem;right:.875rem;display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:700;color:#16a34a;background:#dcfce7;border:1px solid #bbf7d0;padding:.2rem .55rem;border-radius:50px}
.expert-listing-card__avatar-wrap{position:relative;width:72px;height:72px;margin:0 auto .25rem;flex-shrink:0}
.expert-listing-card__avatar-img,.expert-listing-card__avatar-fallback{width:72px!important;height:72px!important;border-radius:50%!important;border:3px solid rgba(255,255,255,.8);box-shadow:0 2px 8px rgba(0,0,0,.12)}
.expert-listing-card__avatar-fallback{position:absolute;inset:0;display:flex!important;align-items:center;justify-content:center;font-size:1.375rem;font-weight:800;color:#fff;z-index:0;margin:0}
.expert-listing-card__avatar-img{object-fit:cover;position:relative;z-index:1;display:block;background:#f1f5f9}
.expert-listing-card__avatar-fallback.is-hidden{display:none!important}
.expert-listing-card__titre{margin:0 0 .2rem;font-size:.9375rem;font-weight:700;color:var(--text,#0f172a);line-height:1.3}
.expert-listing-card__nom{margin:0;font-size:.8rem;color:var(--text-muted,#64748b)}
.expert-listing-card__rating{display:flex;align-items:center;justify-content:center;gap:.3rem;font-size:.8125rem}
.expert-listing-card__stars{display:inline-flex;gap:1px}
.expert-listing-card__tarif{display:flex;align-items:baseline;justify-content:center;gap:.25rem}
.expert-listing-card__tarif-val{font-size:1.25rem;font-weight:800;color:#16a34a}
.expert-listing-card__tarif-unit{font-size:.75rem;color:var(--text-muted,#64748b)}
.expert-listing-card__competences{display:flex;flex-wrap:wrap;gap:.3rem;justify-content:center;width:100%}
.expert-listing-card__comp-tag{background:var(--surface-2,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:6px;padding:.2rem .55rem;font-size:.72rem;color:var(--text-muted,#64748b)}
.expert-listing-card__btn{display:inline-flex;align-items:center;gap:.4rem;margin-top:.25rem;padding:.55rem 1.25rem;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;text-decoration:none;cursor:pointer;transition:background .15s}
.expert-listing-card__btn:hover{background:#15803d}
@media(max-width:560px){.experts-listing__grid{grid-template-columns:1fr}}
</style>
<?php
$experts      = $experts ?? [];
$competences  = $competences ?? [];
$filtreComp   = (int)($filtre_competence ?? 0);
$recherche    = $recherche ?? '';
$count        = count($experts);
$isLoggedIn   = !empty($user);
$role         = $user['role'] ?? '';

$dashboardUrl = $isLoggedIn
    ? $baseUrl . ($role === 'expert' ? '/expert' : ($role === 'etudiant' ? '/etudiant' : '/client'))
    : $baseUrl . '/';

$isEtudiant   = $role === 'etudiant';
$hasFilters   = $filtreComp || $recherche !== '';
?>
<section class="section-desktop experts-listing">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <?php if ($isLoggedIn): ?>
            <a href="<?= $dashboardUrl ?>" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <?= $isEtudiant ? 'Espace étudiant' : 'Tableau de bord' ?>
            </a>
            <?php endif; ?>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon experts-listing__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">
                        <?= $isEtudiant ? 'Trouver un tuteur / expert' : 'Experts disponibles' ?>
                    </h1>
                    <p class="missions-header__sub">
                        <?= $isEtudiant
                            ? 'Trouvez un expert pour vous aider sur vos exercices et devoirs universitaires.'
                            : 'Trouvez l\'expert qu\'il vous faut et réservez une session.' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php if ($count > 0): ?>
        <span class="missions-header__count experts-listing__count">
            <?= $count ?> expert<?= $count > 1 ? 's' : '' ?> disponible<?= $count > 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Bandeau étudiant -->
    <?php if ($isEtudiant): ?>
    <div class="experts-etudiant-tip" role="note">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        <div>
            <strong>Conseil étudiant :</strong>
            Utilisez le filtre <em>Compétence</em> pour trouver un tuteur dans votre matière (Mathématiques, Économie, Droit, Informatique…). Après contact, vous pourrez soumettre votre exercice directement depuis votre tableau de bord.
        </div>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="experts-etudiant-tip__btn">Soumettre un exercice →</a>
    </div>
    <?php endif; ?>

    <!-- Barre de filtres modernisée -->
    <form method="get" action="<?= $baseUrl ?>/experts" class="experts-filter-bar" role="search" aria-label="Filtrer les experts">
        <div class="experts-filter-bar__inner">

            <div class="experts-filter-bar__group">
                <label for="competence" class="experts-filter-bar__label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <?= $isEtudiant ? 'Matière / Compétence' : 'Compétence' ?>
                </label>
                <div class="experts-filter-bar__select-wrap">
                    <select name="competence" id="competence" class="experts-filter-bar__select">
                        <option value=""><?= $isEtudiant ? 'Toutes les matières' : 'Toutes les compétences' ?></option>
                        <?php foreach ($competences as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $filtreComp === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= $e($c['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="experts-filter-bar__select-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>

            <div class="experts-filter-bar__group experts-filter-bar__group--search">
                <label for="q" class="experts-filter-bar__label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Recherche
                </label>
                <div class="experts-filter-bar__input-wrap">
                    <svg class="experts-filter-bar__input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" id="q" value="<?= $e($recherche) ?>"
                           placeholder="<?= $isEtudiant ? 'Nom, spécialité, matière…' : 'Nom, titre, compétence…' ?>"
                           class="experts-filter-bar__input">
                    <?php if ($recherche): ?>
                    <a href="<?= $baseUrl ?>/experts<?= $filtreComp ? '?competence=' . $filtreComp : '' ?>" class="experts-filter-bar__clear" title="Effacer" aria-label="Effacer">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary experts-filter-bar__btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Rechercher
            </button>

            <?php if ($hasFilters): ?>
            <a href="<?= $baseUrl ?>/experts" class="btn btn-outline experts-filter-bar__reset">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Réinitialiser
            </a>
            <?php endif; ?>
        </div>

        <?php if ($hasFilters): ?>
        <div class="experts-filter-active">
            <?php if ($filtreComp): ?>
            <span class="experts-filter-chip">
                <?php foreach ($competences as $c): if ((int)$c['id'] === $filtreComp): echo $e($c['nom']); break; endif; endforeach; ?>
                <a href="<?= $baseUrl ?>/experts<?= $recherche ? '?q=' . urlencode($recherche) : '' ?>">×</a>
            </span>
            <?php endif; ?>
            <?php if ($recherche): ?>
            <span class="experts-filter-chip">
                "<?= $e($recherche) ?>"
                <a href="<?= $baseUrl ?>/experts<?= $filtreComp ? '?competence=' . $filtreComp : '' ?>">×</a>
            </span>
            <?php endif; ?>
            <span class="experts-filter-result"><?= $count ?> résultat<?= $count > 1 ? 's' : '' ?></span>
        </div>
        <?php endif; ?>
    </form>

    <!-- État vide -->
    <?php if (empty($experts)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <h2 class="missions-empty__title">Aucun expert trouvé</h2>
        <p class="missions-empty__text">
            <?= $hasFilters
                ? 'Aucun expert ne correspond à ces critères. Essayez une autre compétence ou effacez la recherche.'
                : 'Aucun expert disponible pour le moment. Revenez bientôt !' ?>
        </p>
        <?php if ($hasFilters): ?>
        <a href="<?= $baseUrl ?>/experts" class="btn btn-primary missions-empty__btn">Voir tous les experts</a>
        <?php endif; ?>
        <?php if ($isEtudiant): ?>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="btn btn-outline missions-empty__btn">Soumettre un exercice quand même</a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- Grille des experts -->
    <div class="experts-listing__grid" role="list" aria-label="Liste des experts disponibles">
        <?php foreach ($experts as $exp):
            $initiales = strtoupper(
                mb_substr($exp['prenom'] ?? '', 0, 1) .
                mb_substr($exp['nom'] ?? '', 0, 1)
            );
            if ($initiales === '') $initiales = strtoupper(mb_substr($exp['titre'] ?? 'E', 0, 1));
            $note      = $exp['note_moyenne'] !== null ? (float)$exp['note_moyenne'] : null;
            $nbAvis    = (int)($exp['nombre_avis'] ?? 0);
            $tarif     = number_format((float)($exp['tarif_horaire'] ?? 0), 0, ',', ' ');
            $verifie   = !empty($exp['valide_par_admin']);
            $slug      = $exp['slug'] ?? ('expert-' . (int)$exp['id']);

            $colors    = ['#2563eb','#16a34a','#7c3aed','#0d9488','#dc2626','#d97706','#db2777'];
            $colorIdx  = abs(crc32($initiales)) % count($colors);
            $avatarColor = $colors[$colorIdx];
        ?>
        <article class="expert-listing-card" role="listitem">

            <?php if ($verifie): ?>
            <div class="expert-listing-card__verified" title="Expert vérifié par GLOBALO">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                Vérifié
            </div>
            <?php endif; ?>

            <div class="expert-listing-card__avatar-wrap" aria-hidden="true">
                <?php
                $initials     = $initiales;
                $avatarBg     = $avatarColor;
                $avatarColumn = $exp['avatar'] ?? null;
                $pays         = $exp['pays'] ?? null;
                $alt          = '';
                $size         = 'lg';
                require APP_PATH . '/Views/partials/public_user_thumb.php';
                ?>
            </div>

            <div class="expert-listing-card__identity">
                <h2 class="expert-listing-card__titre"><?= $e($exp['titre'] ?? '') ?></h2>
                <p class="expert-listing-card__nom"><?= $e(trim(($exp['prenom'] ?? '') . ' ' . ($exp['nom'] ?? ''))) ?></p>
            </div>

            <div class="expert-listing-card__rating">
                <?php if ($note !== null && $nbAvis > 0): ?>
                <span class="expert-listing-card__stars" aria-label="Note <?= number_format($note, 1) ?> sur 5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="<?= $i <= round($note) ? '#f59e0b' : 'none' ?>" stroke="#f59e0b" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                </span>
                <span class="expert-listing-card__note-val"><?= number_format($note, 1) ?></span>
                <span class="expert-listing-card__avis">(<?= $nbAvis ?> avis)</span>
                <?php else: ?>
                <span class="expert-listing-card__no-avis">Aucun avis</span>
                <?php endif; ?>
            </div>

            <div class="expert-listing-card__tarif">
                <span class="expert-listing-card__tarif-val"><?= $e($tarif) ?></span>
                <span class="expert-listing-card__tarif-unit"><?= $e(devise()) ?>/h</span>
            </div>

            <a href="<?= $baseUrl ?>/expert/<?= $e($slug) ?>" class="btn btn-primary expert-listing-card__cta">
                <?= $isEtudiant ? 'Voir le tuteur' : 'Voir le profil' ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>

        </article>
        <?php endforeach; ?>
    </div>

    <!-- Si étudiant : CTA bas de page -->
    <?php if ($isEtudiant && $count > 0): ?>
    <div class="experts-etudiant-footer">
        <p>Vous avez trouvé un tuteur qui vous convient ? Contactez-le via la messagerie ou soumettez directement votre exercice.</p>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Soumettre un exercice
        </a>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</section>

<style>
/* ── Bandeau étudiant ─────────────────────────────────────────────────────── */
.experts-etudiant-tip {
    display: flex; align-items: flex-start; gap: 1rem;
    background: #eff6ff; border: 1.5px solid #bfdbfe;
    border-radius: 12px; padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    font-size: .875rem; color: #1e40af;
}
.experts-etudiant-tip svg { flex-shrink: 0; margin-top: .1rem; }
.experts-etudiant-tip__btn {
    margin-left: auto; white-space: nowrap;
    font-size: .8125rem; font-weight: 700;
    color: #1d4ed8; text-decoration: none;
    padding: .35rem .85rem; border-radius: 8px;
    background: #dbeafe; border: 1px solid #93c5fd;
    transition: background .15s;
    flex-shrink: 0;
}
.experts-etudiant-tip__btn:hover { background: #bfdbfe; }

/* ── Filtres actifs ───────────────────────────────────────────────────────── */
.experts-filter-active {
    display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
    padding: .65rem 0 0;
    border-top: 1px solid #e5e7eb;
    margin-top: .65rem;
    font-size: .8125rem;
}
.experts-filter-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    background: #e0f2fe; color: #0369a1;
    padding: .25rem .65rem; border-radius: 20px;
    font-weight: 600;
}
.experts-filter-chip a { color: inherit; text-decoration: none; font-size: 1rem; line-height: 1; }
.experts-result { color: #6b7280; }
.experts-filter-result { color: #6b7280; margin-left: auto; }

/* ── Footer étudiant ──────────────────────────────────────────────────────── */
.experts-etudiant-footer {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-radius: 12px; padding: 1.25rem 1.5rem;
    margin-top: 2rem;
    font-size: .875rem; color: #1e40af;
}
.experts-etudiant-footer p { margin: 0; }
</style>
