<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$professeurs   = $professeurs ?? [];
$matieres      = $matieres ?? [];
$filtreMatiere = (int)($filtre_matiere ?? 0);
$recherche     = $recherche ?? '';
$count         = count($professeurs);
$isLoggedIn    = !empty($user);
$role          = $user['role'] ?? '';

$dashboardUrl  = $isLoggedIn
    ? $baseUrl . ($role === 'professeur' ? '/professeur' : ($role === 'etudiant' ? '/etudiant' : '/client'))
    : $baseUrl . '/';

$isEtudiant    = $role === 'etudiant';
$hasFilters    = $filtreMatiere || $recherche !== '';
?>
<style>
.professeurs-listing__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.125rem}
.professeur-listing-card{background:var(--surface,#fff);border:1.5px solid var(--border,#e2e8f0);border-radius:var(--radius,12px);padding:1.5rem 1.25rem 1.25rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:.625rem;position:relative;box-shadow:0 1px 3px rgba(15,23,42,.05);transition:transform .15s,box-shadow .18s}
.professeur-listing-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(124,58,237,.12);border-color:#c4b5fd}
.professeur-listing-card__verified{position:absolute;top:.875rem;right:.875rem;display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:700;color:#15803d;background:#dcfce7;border:1px solid #86efac;padding:.2rem .55rem;border-radius:50px}
.professeur-listing-card__avatar-wrap{margin-bottom:.25rem}
.professeur-listing-card__avatar-stack{position:relative;width:72px;height:72px;margin:0 auto;flex-shrink:0}
.professeur-listing-card__avatar-stack .professeur-listing-card__avatar-fallback{position:absolute;inset:0;width:100%!important;height:100%!important;margin:0;display:flex!important;align-items:center;justify-content:center;font-size:1.375rem;font-weight:800;color:#fff;border-radius:50%!important;border:3px solid rgba(255,255,255,.8);box-shadow:0 2px 8px rgba(0,0,0,.12)}
.professeur-listing-card__avatar-stack .professeur-listing-card__avatar-img{position:absolute;inset:0;width:100%!important;height:100%!important;z-index:1;object-fit:cover;border-radius:50%!important;border:3px solid rgba(255,255,255,.8);box-shadow:0 2px 8px rgba(0,0,0,.12);background:#f1f5f6}
.professeur-listing-card__titre{margin:0 0 .2rem;font-size:.9375rem;font-weight:700;color:var(--text,#0f172a);line-height:1.3}
.professeur-listing-card__nom{margin:0;font-size:.8rem;color:var(--text-muted,#64748b)}
.professeur-listing-card__rating{display:flex;align-items:center;justify-content:center;gap:.3rem;font-size:.8125rem}
.professeur-listing-card__tarif{display:flex;align-items:baseline;justify-content:center;gap:.25rem}
.professeur-listing-card__tarif-val{font-size:1.25rem;font-weight:800;color:#7c3aed}
.professeur-listing-card__tarif-unit{font-size:.75rem;color:var(--text-muted,#64748b)}
.professeur-listing-card__matieres{display:flex;flex-wrap:wrap;gap:.3rem;justify-content:center;width:100%}
.professeur-listing-card__mat-tag{background:#f5f3ff;border:1px solid #e9d5ff;border-radius:6px;padding:.2rem .55rem;font-size:.72rem;color:#6b21a8}
.professeur-listing-card__btn{display:inline-flex;align-items:center;gap:.4rem;margin-top:.25rem;padding:.55rem 1.25rem;background:#7c3aed;color:#fff!important;border:none;border-radius:8px;font-size:.875rem;font-weight:600;text-decoration:none;cursor:pointer;transition:background .15s}
.professeur-listing-card__btn:hover{background:#6d28d9}
@media(max-width:560px){.professeurs-listing__grid{grid-template-columns:1fr}}
</style>
<section class="section-desktop professeurs-listing">

    <div class="missions-header">
        <div class="missions-header__left">
            <?php if ($isLoggedIn): ?>
            <a href="<?= $dashboardUrl ?>" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <?= $isEtudiant ? 'Espace étudiant' : ($role === 'professeur' ? 'Espace professeur' : 'Tableau de bord') ?>
            </a>
            <?php endif; ?>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon professeurs-listing__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Professeurs disponibles</h1>
                    <p class="missions-header__sub">
                        <?= $isEtudiant
                            ? 'Trouvez un professeur pour des sessions de cours, tutorat ou correction d\'exercices.'
                            : 'Parcourez les professeurs d\'université et réservez une session.' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php if ($count > 0): ?>
        <span class="missions-header__count"><?= $count ?> professeur<?= $count > 1 ? 's' : '' ?> disponible<?= $count > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <?php if ($isEtudiant): ?>
    <div class="experts-etudiant-tip" role="note" style="background:#f5f3ff;border-color:#c4b5fd;color:#5b21b6">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
        <div>
            <strong>Conseil :</strong>
            Filtrez par matière (Mathématiques, Économie, Droit…) pour trouver un professeur spécialisé. Vous pouvez réserver une session ou soumettre un exercice à corriger.
        </div>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="experts-etudiant-tip__btn" style="background:#ede9fe;border-color:#c4b5fd;color:#5b21b6">Soumettre un exercice →</a>
    </div>
    <?php endif; ?>

    <form method="get" action="<?= $baseUrl ?>/professeurs" class="experts-filter-bar" role="search">
        <div class="experts-filter-bar__inner">
            <div class="experts-filter-bar__group">
                <label for="matiere" class="experts-filter-bar__label">Matière</label>
                <div class="experts-filter-bar__select-wrap">
                    <select name="matiere" id="matiere" class="experts-filter-bar__select">
                        <option value="">Toutes les matières</option>
                        <?php foreach ($matieres as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= $filtreMatiere === (int)$m['id'] ? 'selected' : '' ?>><?= $e($m['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="experts-filter-bar__select-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
            <div class="experts-filter-bar__group experts-filter-bar__group--search">
                <label for="q" class="experts-filter-bar__label">Recherche</label>
                <div class="experts-filter-bar__input-wrap">
                    <svg class="experts-filter-bar__input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" id="q" value="<?= $e($recherche) ?>" placeholder="Nom, matière…" class="experts-filter-bar__input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary experts-filter-bar__btn">Rechercher</button>
            <?php if ($hasFilters): ?>
            <a href="<?= $baseUrl ?>/professeurs" class="btn btn-outline experts-filter-bar__reset">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($professeurs)): ?>
    <div class="missions-empty">
        <div class="missions-empty__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
        </div>
        <h2 class="missions-empty__title">Aucun professeur trouvé</h2>
        <p class="missions-empty__text">
            <?= $hasFilters ? 'Aucun professeur ne correspond à ces critères.' : 'Aucun professeur disponible pour le moment.' ?>
        </p>
        <?php if ($hasFilters): ?>
        <a href="<?= $baseUrl ?>/professeurs" class="btn btn-primary missions-empty__btn">Voir tous les professeurs</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="professeurs-listing__grid" role="list">
        <?php foreach ($professeurs as $prof):
            $initiales = strtoupper(mb_substr($prof['prenom'] ?? '', 0, 1) . mb_substr($prof['nom'] ?? '', 0, 1));
            if ($initiales === '') $initiales = strtoupper(mb_substr($prof['titre'] ?? 'P', 0, 1));
            $note = $prof['note_moyenne'] !== null ? (float)$prof['note_moyenne'] : null;
            $nbAvis = (int)($prof['nombre_avis'] ?? 0);
            $tarif = number_format((float)($prof['tarif_horaire'] ?? 0), 0, ',', ' ');
            $verifie = !empty($prof['valide_par_admin']);
            $matieresProf = $matieres; // On ne charge pas les matières par professeur dans la liste pour simplifier
            $colors = ['#7c3aed','#6d28d9','#5b21b6','#4c1d95'];
            $avatarColor = $colors[abs(crc32($initiales)) % count($colors)];
        ?>
        <article class="professeur-listing-card" role="listitem">
            <?php if ($verifie): ?>
            <div class="professeur-listing-card__verified" title="Professeur vérifié">Vérifié</div>
            <?php endif; ?>
            <div class="professeur-listing-card__avatar-wrap professeur-listing-card__avatar-stack" aria-hidden="true">
                <?php
                $initials     = $initiales;
                $avatarBg     = $avatarColor;
                $avatarColumn = $prof['avatar'] ?? null;
                $pays         = $prof['pays'] ?? null;
                $alt          = '';
                $size         = 'lg';
                require APP_PATH . '/Views/partials/public_user_thumb.php';
                ?>
            </div>
            <div class="professeur-listing-card__identity">
                <h2 class="professeur-listing-card__titre"><?= $e($prof['titre'] ?? '') ?></h2>
                <p class="professeur-listing-card__nom"><?= $e(trim(($prof['prenom'] ?? '') . ' ' . ($prof['nom'] ?? ''))) ?></p>
            </div>
            <div class="professeur-listing-card__rating">
                <?php if ($note !== null && $nbAvis > 0): ?>
                <span>⭐ <?= number_format($note, 1) ?></span> <span>(<?= $nbAvis ?> avis)</span>
                <?php else: ?>
                <span>Aucun avis</span>
                <?php endif; ?>
            </div>
            <div class="professeur-listing-card__tarif">
                <span class="professeur-listing-card__tarif-val"><?= $e($tarif) ?></span>
                <span class="professeur-listing-card__tarif-unit"><?= $e(devise()) ?>/h</span>
            </div>
            <a href="<?= $baseUrl ?>/professeurs/show/<?= (int)$prof['id'] ?>" class="professeur-listing-card__btn">
                Voir le profil <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>
