<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$prixClient = (int) ($prix_client_xof ?? 1000);
$prixExpert = (int) ($prix_expert_xof ?? 1500);
$prixEtudiant = (int) ($prix_etudiant_xof ?? 500);
$prixProfesseur = (int) ($prix_professeur_xof ?? 1000);
$formatFcfa = fn(int $n) => $n > 0 ? number_format($n, 0, ',', ' ') . ' Fcfa/mois' : '';
$hspFallback = htmlspecialchars(json_encode([
    'Que cherchez-vous ? Ex. comptabilité, Excel, rédaction',
    'Une matière ou un domaine : statistiques, droit, maths…',
    'Ex. développement web, design, traduction anglais',
], JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$homeSmartSearchPlaceholders = $home_smart_search_placeholders ?? $hspFallback;
?>
<div class="page-home">

    <!-- ═══════════════════════════════════════════════════════════ HERO ═══ -->
    <section class="hero-desktop hero-home" aria-labelledby="hero-title">
        <div class="hero-home-backdrop" aria-hidden="true"></div>
        <div class="hero-content">
            <span class="hero-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                Mali · Côte d'Ivoire · Sénégal · Bénin · Niger
            </span>
            <h1 id="hero-title">L'assistance professionnelle &amp; académique <span class="hero-title-accent">en quelques clics</span></h1>
            <p class="hero-lead">Clients, experts, étudiants et professeurs d'université sur une seule plateforme. Débloquez vos tâches urgentes ou vos exercices universitaires en 1 à 3 heures.</p>

            <div id="smart-search-home" class="hero-smart-search hero-smart-search--in-hero" data-smart-search data-smart-search-api="<?= $baseUrl ?>/api/search/suggest" data-smart-search-app="0" data-smart-search-placeholders="<?= $homeSmartSearchPlaceholders ?>">
                <form class="hero-smart-search__form js-smart-search-form" method="get" action="<?= $baseUrl ?>/experts" role="search" autocomplete="off">
                    <div class="hero-smart-search__field-wrap">
                        <label for="hero-smart-search-q" class="visually-hidden">Rechercher un expert, une compétence ou une matière</label>
                        <input type="search" id="hero-smart-search-q" name="q" class="hero-smart-search__input js-smart-search-input" placeholder="Que cherchez-vous ? Par exemple : applications mobiles" autocomplete="off" aria-autocomplete="list" aria-expanded="false">
                        <button type="submit" class="hero-smart-search__submit" aria-label="Lancer la recherche">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </button>
                    </div>
                    <div class="hero-smart-search__dropdown smart-search-dropdown js-smart-search-results" hidden role="listbox" aria-label="Suggestions"></div>
                </form>
                <ul class="hero-smart-search__trust">
                    <li>
                        <span class="hero-smart-search__trust-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                        <span>Experts vérifiés sur la plateforme</span>
                    </li>
                    <li>
                        <span class="hero-smart-search__trust-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        <span>Assistance 24h/24 et 7j/7</span>
                    </li>
                    <li>
                        <span class="hero-smart-search__trust-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
                        <span>Paiement sécurisé</span>
                    </li>
                </ul>
            </div>

            <div class="hero-actions">
                <?php if (isset($user) && $user): ?>
                    <?php
                    $role = $user['role'] ?? '';
                    if ($role === 'etudiant') {
                        echo '<a href="' . $baseUrl . '/etudiant/exercices/nouveau" class="btn btn-primary btn-lg btn-hero">Soumettre un exercice</a>';
                    } elseif ($role === 'expert') {
                        echo '<a href="' . $baseUrl . '/expert" class="btn btn-primary btn-lg btn-hero">Mon espace expert</a>';
                    } else {
                        echo '<a href="' . $baseUrl . '/client/demandes/nouvelle" class="btn btn-primary btn-lg btn-hero">Nouvelle demande</a>';
                    }
                    ?>
                    <a href="<?= $baseUrl ?>/experts" class="btn btn-outline btn-lg btn-hero-outline">Voir les experts</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary btn-lg btn-hero">Créer un compte gratuit</a>
                    <a href="<?= $baseUrl ?>/experts" class="btn btn-outline btn-lg btn-hero-outline">Voir les experts</a>
                <?php endif; ?>
            </div>
            <!-- Chiffres clés -->
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat__num">5</span>
                    <span class="hero-stat__lbl">Pays éligibles</span>
                </div>
                <div class="hero-stat-sep" aria-hidden="true"></div>
                <div class="hero-stat">
                    <span class="hero-stat__num">50+</span>
                    <span class="hero-stat__lbl">Matières universitaires</span>
                </div>
                <div class="hero-stat-sep" aria-hidden="true"></div>
                <div class="hero-stat">
                    <span class="hero-stat__num">1–3h</span>
                    <span class="hero-stat__lbl">Session courte</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════ AUDIENCE ═══ -->
    <section class="section-desktop section-audience" aria-labelledby="section-audience-title">
        <span class="section-badge">Pour qui ?</span>
        <h2 id="section-audience-title">Une plateforme, quatre profils</h2>
        <p class="section-subtitle">Client, expert, étudiant ou professeur d'université — GLOBALO s'adapte à votre besoin.</p>
        <div class="audience-grid audience-grid--4">

            <article class="card-desktop audience-card audience-card--client">
                <div class="audience-card__icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h3>Client</h3>
                <p class="audience-card__abo">Abonnement : <?= \App\Core\Security::escape($formatFcfa($prixClient)) ?></p>
                <p>Freelance, entreprise, chef de projet : publiez une demande, choisissez un expert et obtenez de l'aide rapidement.</p>
                <ul class="audience-card__features">
                    <li>Demandes urgentes 24/7</li>
                    <li>Paiement sécurisé via Jɛmɛnipay</li>
                    <li>Mode urgence en 1 clic</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="btn btn-primary">Démarrer en tant que client</a>
            </article>

            <article class="card-desktop audience-card audience-card--expert">
                <div class="audience-card__icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <span class="audience-card__popular">Populaire</span>
                <h3>Expert</h3>
                <p class="audience-card__abo">Abonnement : <?= \App\Core\Security::escape($formatFcfa($prixExpert)) ?></p>
                <p>Valorisez vos compétences en Excel, comptabilité, dev web, design ou traduction. Acceptez les missions qui vous conviennent.</p>
                <ul class="audience-card__features">
                    <li>Tarif horaire libre</li>
                    <li>Missions en XOF</li>
                    <li>Retrait via Mobile Money (Orange Money, Moov Africa)</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription?role=expert" class="btn btn-primary">Devenir expert</a>
            </article>

            <article class="card-desktop audience-card audience-card--etudiant">
                <div class="audience-card__icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <span class="audience-card__new">Nouveau</span>
                <h3>Étudiant</h3>
                <p class="audience-card__abo">Abonnement : <?= \App\Core\Security::escape($formatFcfa($prixEtudiant)) ?></p>
                <p>Soumettez vos exercices et devoirs universitaires par matière. Obtenez l'aide d'un tuteur expert depuis tout le Sahel.</p>
                <ul class="audience-card__features">
                    <li>50+ matières universitaires</li>
                    <li>Suivi par matière</li>
                    <li>Notes et corrections</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="btn btn-outline">S'inscrire en tant qu'étudiant</a>
            </article>

            <article class="card-desktop audience-card audience-card--professeur">
                <div class="audience-card__icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M12 11v6M9 14h6"/></svg>
                </div>
                <span class="audience-card__badge-violet">Professeur</span>
                <h3>Professeur d'université</h3>
                <p class="audience-card__abo">Abonnement : <?= \App\Core\Security::escape($formatFcfa($prixProfesseur)) ?></p>
                <p>Enseignant à l'université ? Rejoignez GLOBALO, proposez votre expertise par matière et accompagnez les étudiants sur la plateforme.</p>
                <ul class="audience-card__features">
                    <li>Expertise par matière</li>
                    <li>Tutorat étudiant(e)s</li>
                    <li>Abonnement dédié</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription?role=professeur" class="btn btn-outline audience-card__btn-violet">Devenir professeur</a>
            </article>

        </div>
    </section>

    <!-- ══════════════════════════════════════════ DEMANDES RÉCENTES ═══ -->
    <?php if (!empty($demandes_recentes)): ?>
    <section class="section-desktop section-demandes-recentes" aria-labelledby="section-demandes-title" style="background:#f8fafc;padding:4rem 0;">
        <div style="max-width:1100px;margin:0 auto;padding:0 2rem;">
            <span class="section-badge" style="background:#dcfce7;color:#166534;">Demandes ouvertes</span>
            <h2 id="section-demandes-title" style="margin-top:0.75rem;margin-bottom:0.5rem;">Missions en attente d'un expert</h2>
            <p class="section-subtitle" style="margin-bottom:2rem;">Des clients attendent votre expertise. Postulez directement ou <a href="<?= $baseUrl ?>/demandes" style="color:#16a34a;font-weight:600;">voir toutes les demandes →</a></p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;">
            <?php
            $urgenceLb = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
            $urgenceColors = ['urgent' => '#f59e0b', 'tres_urgent' => '#ef4444'];
            foreach ($demandes_recentes as $dr):
                $titre   = \App\Core\Security::escape($dr['titre'] ?? '');
                $comp    = \App\Core\Security::escape($dr['competence_nom'] ?? '');
                $urgence = $dr['urgence'] ?? 'normale';
                $date    = isset($dr['created_at']) ? date('d/m/Y', strtotime($dr['created_at'])) : '';

                $cp   = trim((string) ($dr['client_prenom'] ?? ''));
                $cn   = trim((string) ($dr['client_nom'] ?? ''));
                $initials = strtoupper(mb_substr($cp, 0, 1) . mb_substr($cn, 0, 1));
                if ($initials === '') { $initials = '?'; }
                $colors   = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#d97706', '#dc2626'];
                $avatarBg = $colors[abs(crc32($cp . $cn)) % count($colors)];
                $avatarUrl = \App\Helpers\PublicUserPresentation::publicAvatarUrl($dr['client_avatar'] ?? null, $baseUrl);
                $hasPhoto  = \App\Helpers\PublicUserPresentation::hasUploadedAvatar($dr['client_avatar'] ?? null);
                $flag      = \App\Helpers\PublicUserPresentation::countryFlagEmoji($dr['client_pays'] ?? null);
                $flagLabel = \App\Helpers\PublicUserPresentation::countryLabel($dr['client_pays'] ?? null);
                $clientLabel = $cp !== '' ? ($cn !== '' ? $cp . ' ' . mb_substr($cn, 0, 1) . '.' : $cp) : 'Client';
            ?>
            <article style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.75rem;transition:box-shadow 0.18s;" onmouseover="this.style.boxShadow='0 4px 18px rgba(0,0,0,0.09)'" onmouseout="this.style.boxShadow='none'">

                <?php if ($urgence !== 'normale'): ?>
                <span style="display:inline-block;background:<?= $urgenceColors[$urgence] ?? '#f59e0b' ?>;color:#fff;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:20px;align-self:flex-start;letter-spacing:0.5px;text-transform:uppercase;"><?= \App\Core\Security::escape($urgenceLb[$urgence] ?? $urgence) ?></span>
                <?php endif; ?>

                <!-- Titre de la demande -->
                <p style="margin:0;font-weight:700;font-size:0.98rem;color:#1e293b;line-height:1.4;"><?= $titre ?></p>

                <!-- Tags compétence + date -->
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                    <?php if ($comp): ?><span style="background:#f0fdf4;color:#15803d;font-size:0.75rem;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid #bbf7d0;"><?= $comp ?></span><?php endif; ?>
                    <span style="color:#94a3b8;font-size:0.75rem;"><?= $date ?></span>
                </div>

                <!-- Profil du demandeur -->
                <div style="display:flex;align-items:center;gap:0.6rem;padding-top:0.25rem;border-top:1px solid #f1f5f9;margin-top:0.1rem;">
                    <?php if ($hasPhoto): ?>
                    <img src="<?= \App\Core\Security::escape($avatarUrl) ?>" alt="<?= \App\Core\Security::escape($clientLabel) ?>"
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e2e8f0;">
                    <?php else: ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:<?= $avatarBg ?>;color:#fff;font-size:0.75rem;font-weight:700;flex-shrink:0;border:2px solid <?= $avatarBg ?>20;">
                        <?= \App\Core\Security::escape($initials) ?>
                    </span>
                    <?php endif; ?>
                    <div style="display:flex;flex-direction:column;min-width:0;gap:1px;">
                        <span style="font-size:0.8rem;font-weight:600;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= \App\Core\Security::escape($clientLabel) ?></span>
                        <?php if ($flag !== ''): ?>
                        <span style="font-size:0.78rem;color:#64748b;" title="<?= \App\Core\Security::escape($flagLabel) ?>">
                            <?= $flag ?> <?= \App\Core\Security::escape($flagLabel) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CTA -->
                <?php if (isset($userData) && $userData && ($userData['role'] ?? '') === 'expert'): ?>
                <a href="<?= $baseUrl ?>/expert/demandes" style="margin-top:auto;display:inline-block;background:#16a34a;color:#fff;font-size:0.82rem;font-weight:600;padding:7px 16px;border-radius:8px;text-decoration:none;text-align:center;transition:background 0.15s;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">Proposer mes services</a>
                <?php else: ?>
                <a href="<?= $baseUrl ?>/auth/inscription?role=expert" style="margin-top:auto;display:inline-block;background:#f0fdf4;color:#16a34a;font-size:0.82rem;font-weight:600;padding:7px 16px;border-radius:8px;text-decoration:none;text-align:center;border:1.5px solid #bbf7d0;transition:background 0.15s;">Répondre à cette demande</a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:2rem;">
                <a href="<?= $baseUrl ?>/demandes" class="btn btn-outline" style="font-weight:600;">Voir toutes les demandes ouvertes</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════ ÉTAPES ═══ -->
    <section class="section-desktop section-steps" aria-labelledby="section-steps-title">
        <span class="section-badge"><?= __("home.steps.badge") ?></span>
        <h2 id="section-steps-title"><?= __("home.steps.title") ?></h2>
        <p class="section-subtitle"><?= __("home.steps.subtitle") ?></p>
        <div class="grid-3 steps-grid">
            <article class="card-desktop card-step">
                <span class="card-number" aria-hidden="true">1</span>
                <h3><?= __("home.step1.title") ?></h3>
                <p><?= __("home.step1.desc") ?></p>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-step">Créer un compte</a>
            </article>
            <article class="card-desktop card-step">
                <span class="card-number" aria-hidden="true">2</span>
                <h3><?= __("home.step2.title") ?></h3>
                <p><?= __("home.step2.desc") ?></p>
                <a href="<?= $baseUrl ?>/experts" class="btn btn-step btn-step--outline">Voir les experts</a>
            </article>
            <article class="card-desktop card-step">
                <span class="card-number" aria-hidden="true">3</span>
                <h3><?= __("home.step3.title") ?></h3>
                <p><?= __("home.step3.desc") ?></p>
                <a href="<?= $baseUrl ?>/auth/connexion" class="btn btn-step btn-step--outline">Accéder à mon espace</a>
            </article>
        </div>
        <style>
        .card-step { display: flex; flex-direction: column; align-items: stretch; }
        .card-step .btn-step { margin-top: auto; padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.95rem; text-align: center; transition: transform 0.15s, box-shadow 0.15s; }
        .card-step .btn-step:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .card-step .btn-step { background: var(--accent, #2563eb); color: #fff; border: none; }
        .card-step .btn-step--outline { background: transparent; color: var(--accent, #2563eb); border: 2px solid var(--accent, #2563eb); }
        </style>
    </section>

    <!-- ═══════════════════════════════════════════════════ DOMAINES ═══ -->
    <section class="section-desktop section-examples" aria-labelledby="section-examples-title">
        <span class="section-badge"><?= __("home.examples.badge") ?></span>
        <h2 id="section-examples-title">Expertise professionnelle &amp; académique</h2>
        <p class="section-subtitle">Des compétences bureautiques aux matières universitaires de l'Afrique de l'Ouest.</p>
        <ul class="tag-list">
            <li><?= __("home.tag.reports") ?></li>
            <li><?= __("home.tag.excel") ?></li>
            <li><?= __("home.tag.presentations") ?></li>
            <li><?= __("home.tag.web_dev") ?></li>
            <li><?= __("home.tag.graphic_design") ?></li>
            <li><?= __("home.tag.accounting") ?></li>
            <li><?= __("home.tag.translation") ?></li>
            <li><?= __("home.tag.writing") ?></li>
            <li>Mathématiques</li>
            <li>Économie &amp; Gestion</li>
            <li>Droit des affaires</li>
            <li>Comptabilité générale</li>
            <li>Algorithmique</li>
            <li>Agronomie</li>
        </ul>
    </section>

    <!-- ══════════════════════════════════════ BANNER ÉTUDIANT ═══ -->
    <div class="home-etudiant-banner" role="complementary">
        <div class="home-etudiant-banner__inner">
            <div class="home-etudiant-banner__icon" aria-hidden="true">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div class="home-etudiant-banner__text">
                <h2>Étudiant en Afrique de l'Ouest ?</h2>
                <p>Soumettez vos exercices par matière — Mathématiques, Droit, Économie, Informatique… — et recevez une correction ou une explication d'un tuteur expert. <strong>Abonnement : <?= \App\Core\Security::escape($formatFcfa($prixEtudiant)) ?></strong>. Disponible depuis Dakar, Abidjan, Bamako, Cotonou et partout dans la région.</p>
                <div class="home-etudiant-banner__countries">
                    <span>🇲🇱 Mali</span>
                    <span>🇨🇮 Côte d'Ivoire</span>
                    <span>🇸🇳 Sénégal</span>
                    <span>🇧🇯 Bénin</span>
                    <span>🇳🇪 Niger</span>
                    <span>+ 10 pays</span>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="home-etudiant-banner__btn">
                S'inscrire étudiant
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════ BANNER PROFESSEURS ═══ -->
    <div class="home-professeur-banner" role="complementary">
        <div class="home-professeur-banner__inner">
            <div class="home-professeur-banner__icon" aria-hidden="true">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M12 11v6M9 14h6"/></svg>
            </div>
            <div class="home-professeur-banner__text">
                <h2>Professeur(e) d'université ?</h2>
                <p>Rejoignez GLOBALO avec un abonnement dédié à <strong><?= \App\Core\Security::escape($formatFcfa($prixProfesseur)) ?></strong>. Proposez votre expertise par matière (Mathématiques, Économie, Droit, Informatique…), accompagnez les étudiant(e)s et valorisez votre enseignement. Plateforme disponible au Mali, Côte d'Ivoire, Sénégal, Bénin et Niger.</p>
                <ul class="home-professeur-banner__features">
                    <li>Expertise par matière universitaire</li>
                    <li>Tutorat et corrections d'exercices</li>
                    <li>Abonnement mensuel simple</li>
                </ul>
            </div>
            <a href="<?= $baseUrl ?>/auth/inscription?role=professeur" class="home-professeur-banner__btn">
                Devenir professeur
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>
    </div>

    <!-- Section paiement Mobile Money -->
    <section class="section-desktop section-jemeni-payment" aria-labelledby="intouch-payment-title">
        <div class="jemeni-pay-inner">

            <div class="jemeni-pay-left">
                <span class="section-badge jemeni-pay-badge">
                    Mobile Money
                </span>
                <h2 id="intouch-payment-title">Payez en toute sécurité<br>
                    <span class="jemeni-pay-accent">Mobile Money</span>
                </h2>
                <p class="jemeni-pay-desc">
                    GLOBALO utilise une passerelle de paiement sécurisée pour les abonnements et les recharges de portefeuille : redirection vers une page de paiement hébergée,
                    orchestration Mobile Money (Orange Money, Moov… selon Pays), montants en <strong>XOF</strong>.
                </p>

                <ul class="jemeni-pay-features">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Initiation depuis le site ou l’application : vous validez sur votre téléphone
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Abonnements, dépôts portefeuille et confirmation sécurisée via Mobile Money
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Clés API configurées côté serveur uniquement — vos données sensibles ne transitent jamais par le site
                    </li>
                </ul>

                <div class="jemeni-pay-actions">
                    <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary">Créer un compte</a>
                    <a href="<?= $baseUrl ?>/abonnement" class="btn btn-outline jemeni-pay-link">Voir les abonnements</a>
                </div>
            </div>

            <div class="jemeni-pay-right">
                <div class="jemeni-pay-hero-logo" aria-hidden="true" style="font-size:3rem;font-weight:800;color:#0f766e;line-height:1;text-align:center;padding:1rem;">
                    MM
                    <div class="jemeni-pay-ring jemeni-pay-ring--1"></div>
                    <div class="jemeni-pay-ring jemeni-pay-ring--2"></div>
                </div>

                <div class="jemeni-pay-operators jemeni-pay-operators--logos">
                    <?php
                    $mm_logo_size = 'lg';
                    $mm_logo_wrap_class = 'jemeni-pay-operators-inner mm-operator-logos';
                    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:1rem;width:100%;';
                    require APP_PATH . '/Views/partials/mm_operator_logos.php';
                    ?>
                </div>

                <div class="jemeni-pay-stats">
                    <div class="jemeni-pay-stat">
                        <span class="jemeni-pay-stat__num">XOF</span>
                        <span class="jemeni-pay-stat__lbl">Monnaie</span>
                    </div>
                    <div class="jemeni-pay-stat">
                        <span class="jemeni-pay-stat__num">24/7</span>
                        <span class="jemeni-pay-stat__lbl">Disponibilité</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ════════════════════════════════════════════════ CTA EXPERTS ═══ -->
    <div class="home-cta-banner" role="complementary">
        <div class="home-cta-banner__deco" aria-hidden="true"></div>
        <div class="home-cta-banner__content">
            <div class="home-cta-banner__icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="home-cta-banner__text">
                <h2 class="home-cta-banner__title"><?= __("home.cta.title") ?></h2>
                <p class="home-cta-banner__subtitle"><?= __("home.cta.subtitle") ?></p>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/experts" class="home-cta-banner__btn">
            <?= __("home.cta.button") ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════ TÉMOIGNAGES ═══ -->
    <div class="testi-wrap" role="region" aria-labelledby="section-testimonials-title">
        <!-- déco de fond -->
        <div class="testi-wrap__deco" aria-hidden="true"></div>

        <div class="testi-wrap__header">
            <div class="testi-wrap__header-left">
                <span class="testi-wrap__badge"><?= __("home.testimonials.badge") ?></span>
                <h2 id="section-testimonials-title" class="testi-wrap__title"><?= __("home.testimonials.title") ?></h2>
                <p class="testi-wrap__sub">Ils utilisent GLOBALO depuis Dakar, Abidjan, Bamako et bien d'autres villes.</p>
            </div>
            <div class="testi-wrap__nav" aria-label="Navigation carrousel">
                <button class="testi-wrap__nav-btn" id="testi-prev" aria-label="Témoignage précédent">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button class="testi-wrap__nav-btn" id="testi-next" aria-label="Témoignage suivant">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
        </div>

        <div class="testi-carousel" id="testimonials-carousel" aria-live="polite">
            <div class="testi-carousel__track" id="testi-track">
                <?php
                $testimonialsData = [
                    [
                        'quote' => "J'avais un rapport de stage à rendre pour le lendemain matin. En deux heures, un expert m'a aidée à structurer les données et rédiger les conclusions. Sauvée !",
                        'name'  => 'Aminata D.',
                        'role'  => 'Chef de projet · Dakar, Sénégal',
                        'flag'  => '🇸🇳',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Lupita_Nyong%27o_2019_by_Glenn_Francis.jpg/320px-Lupita_Nyong%27o_2019_by_Glenn_Francis.jpg',
                    ],
                    [
                        'quote' => "En tant qu'étudiant en L2 Économie à Abidjan, je soumets mes exercices sur GLOBALO. Les corrections sont claires, rapides. Ça m'a vraiment aidé pour mes partiels.",
                        'name'  => 'Kofi A.',
                        'role'  => 'Étudiant L2 Économie · Abidjan, Côte d\'Ivoire',
                        'flag'  => '🇨🇮',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2d/Didier_Drogba.jpg/320px-Didier_Drogba.jpg',
                    ],
                    [
                        'quote' => "Plateforme simple, experts réactifs. J'ai trouvé quelqu'un pour corriger mon tableau de bord Excel en moins d'une heure. Je recommande sans hésiter.",
                        'name'  => 'Moussa T.',
                        'role'  => 'Responsable administratif · Bamako, Mali',
                        'flag'  => '🇲🇱',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Trevor_Noah_%2843278702844%29_%28cropped%29.jpg/320px-Trevor_Noah_%2843278702844%29_%28cropped%29.jpg',
                    ],
                    [
                        'quote' => "Pour une présentation PowerPoint urgente, GLOBALO m'a permis de trouver un pro du design graphique. Résultat professionnel, délai respecté.",
                        'name'  => 'Fatoumata B.',
                        'role'  => 'Directrice commerciale · Cotonou, Bénin',
                        'flag'  => '🇧🇯',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Ang%C3%A9lique_Kidjo_2014.jpg/320px-Ang%C3%A9lique_Kidjo_2014.jpg',
                    ],
                    [
                        'quote' => "Service sérieux et réactif. En tant qu'expert comptable, j'ai pu aider plusieurs étudiants et clients depuis mon domicile à Niamey. Les paiements en XOF via Jɛmɛnipay (Orange Money, Moov Africa), c'est parfait.",
                        'name'  => 'Moussa I.',
                        'role'  => 'Expert-comptable · Niamey, Niger',
                        'flag'  => '🇳🇪',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Youssou_N%27Dour_2011_Shankbone.JPG/320px-Youssou_N%27Dour_2011_Shankbone.JPG',
                    ],
                    [
                        'quote' => "J'enseigne la statistique à l'université et j'utilise GLOBALO pour organiser des séances de tutorat ciblées. Les étudiants progressent vite et la communication est fluide.",
                        'name'  => 'Pr. Mariam K.',
                        'role'  => 'Professeure universitaire · Bamako, Mali',
                        'flag'  => '🇲🇱',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/93/Wangari_Maathai.jpg/320px-Wangari_Maathai.jpg',
                    ],
                    [
                        'quote' => "Je gère une petite startup e-commerce. Entre les urgences Excel, les visuels et les corrections de textes, GLOBALO nous fait gagner un temps énorme chaque semaine.",
                        'name'  => 'Serge N.',
                        'role'  => 'Fondateur startup · Abidjan, Côte d\'Ivoire',
                        'flag'  => '🇨🇮',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Chimamanda_Ngozi_Adichie_2015.jpg/320px-Chimamanda_Ngozi_Adichie_2015.jpg',
                    ],
                    [
                        'quote' => "Mon fils en première année de droit avait du mal avec la méthodologie. Avec les séances sur GLOBALO, il est devenu plus autonome et ses notes se sont améliorées.",
                        'name'  => 'Awa S.',
                        'role'  => 'Parent d\'étudiant · Dakar, Sénégal',
                        'flag'  => '🇸🇳',
                        'avatar'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Oumou_Sangar%C3%A9_at_Festival_des_arts_n%C3%A8gres_2010.jpg/320px-Oumou_Sangar%C3%A9_at_Festival_des_arts_n%C3%A8gres_2010.jpg',
                    ],
                ];
                foreach ($testimonialsData as $i => $t):
                    $initiale = mb_strtoupper(mb_substr($t['name'], 0, 1));
                ?>
                <article class="testi-card" role="group" aria-label="Témoignage <?= $i+1 ?>">
                    <div class="testi-card__quote-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,0.5)"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                    </div>
                    <blockquote class="testi-card__quote"><?= \App\Core\Security::escape($t['quote']) ?></blockquote>
                    <div class="testi-card__stars" aria-label="5 étoiles sur 5">
                        <?php for ($s = 0; $s < 5; $s++): ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?php endfor; ?>
                    </div>
                    <footer class="testi-card__footer">
                        <div class="testi-card__avatar-wrap">
                            <img
                                class="testi-card__avatar-img"
                                src="<?= \App\Core\Security::escape($t['avatar'] ?? '') ?>"
                                alt="Avatar de <?= \App\Core\Security::escape($t['name']) ?>"
                                loading="lazy"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                            >
                            <span class="testi-card__avatar-fallback"><?= \App\Core\Security::escape($initiale) ?></span>
                        </div>
                        <div class="testi-card__footer-text">
                            <cite class="testi-card__name"><?= \App\Core\Security::escape($t['name']) ?></cite>
                            <span class="testi-card__role"><?= \App\Core\Security::escape($t['role']) ?></span>
                        </div>
                        <span class="testi-card__flag"><?= $t['flag'] ?></span>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="testi-dots" id="testi-dots" role="tablist" aria-label="Pages du carrousel"></div>
    </div>

    <script>
    (function () {
        var track     = document.getElementById('testi-track');
        var dotsWrap  = document.getElementById('testi-dots');
        var btnPrev   = document.getElementById('testi-prev');
        var btnNext   = document.getElementById('testi-next');
        if (!track) return;

        var cards     = Array.from(track.querySelectorAll('.testi-card'));
        var total     = cards.length;
        var perView   = window.innerWidth <= 680 ? 1 : 2;
        var pages     = Math.ceil(total / perView);
        var current   = 0;
        var autoTimer = null;
        var GAP       = 16;

        function getCardWidth() {
            var cw = track.parentElement.offsetWidth;
            return perView === 1 ? cw : (cw - GAP) / 2;
        }

        function goTo(idx) {
            current = (idx + pages) % pages;
            var offset = current * (getCardWidth() + GAP) * perView;
            track.style.transform = 'translateX(-' + offset + 'px)';
            Array.from(dotsWrap.querySelectorAll('.testi-dot')).forEach(function (d, i) {
                d.classList.toggle('is-active', i === current);
                d.setAttribute('aria-selected', i === current ? 'true' : 'false');
            });
        }

        function buildDots() {
            dotsWrap.innerHTML = '';
            for (var p = 0; p < pages; p++) {
                var dot = document.createElement('button');
                dot.className = 'testi-dot' + (p === 0 ? ' is-active' : '');
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', 'Page ' + (p + 1));
                dot.setAttribute('aria-selected', p === 0 ? 'true' : 'false');
                (function (pi) { dot.addEventListener('click', function () { goTo(pi); resetAuto(); }); })(p);
                dotsWrap.appendChild(dot);
            }
        }

        function startAuto() { autoTimer = setInterval(function () { goTo(current + 1); }, 4500); }
        function resetAuto() { clearInterval(autoTimer); startAuto(); }

        function setCardSizes() {
            perView = window.innerWidth <= 680 ? 1 : 2;
            pages   = Math.ceil(total / perView);
            var w   = getCardWidth();
            cards.forEach(function (c) { c.style.width = w + 'px'; c.style.flexShrink = '0'; });
            buildDots();
            goTo(0);
        }

        btnPrev.addEventListener('click', function () { goTo(current - 1); resetAuto(); });
        btnNext.addEventListener('click', function () { goTo(current + 1); resetAuto(); });

        var carousel = document.getElementById('testimonials-carousel');
        carousel.addEventListener('mouseenter', function () { clearInterval(autoTimer); });
        carousel.addEventListener('mouseleave', startAuto);

        var touchStartX = 0;
        carousel.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
        carousel.addEventListener('touchend', function (e) {
            var dx = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(dx) > 40) { goTo(current + (dx > 0 ? 1 : -1)); resetAuto(); }
        });

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(setCardSizes, 150);
        });

        setCardSizes();
        startAuto();
    })();
    </script>

<style>
/* ── Section Jɛmɛnipay Payment ────────────────────────────────── */
.section-jemeni-payment {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
    border-top: none; border-bottom: none;
    position: relative;
    overflow: hidden;
}
.section-jemeni-payment::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 600px 400px at 80% 50%, rgba(233,69,96,.12), transparent),
                radial-gradient(ellipse 400px 300px at 20% 80%, rgba(245,166,35,.08), transparent);
    pointer-events: none;
}
.jemeni-pay-inner {
    display: flex; align-items: center; gap: 3.5rem; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.jemeni-pay-left  { flex: 1; min-width: 300px; }
.jemeni-pay-right { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 1.5rem; }

.jemeni-pay-badge {
    background: rgba(233,69,96,.15) !important;
    color: #f87171 !important;
    border: 1px solid rgba(233,69,96,.25);
    display: inline-flex; align-items: center; gap: .35rem;
}
.jemeni-pay-accent { color: #e94560; }
.section-jemeni-payment h2 {
    color: #fff; font-size: 2rem; font-weight: 800; line-height: 1.25; margin: .75rem 0 1rem;
}
.jemeni-pay-desc {
    color: #94a3b8; font-size: .97rem; line-height: 1.7; max-width: 440px;
}
.jemeni-pay-features {
    list-style: none; padding: 0; margin: 1.25rem 0 0; display: flex; flex-direction: column; gap: .55rem;
}
.jemeni-pay-features li {
    display: flex; align-items: center; gap: .5rem; font-size: .9rem; color: #cbd5e1;
}
.jemeni-pay-actions {
    display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1.75rem; align-items: center;
}
.jemeni-pay-link {
    display: inline-flex; align-items: center; gap: .4rem;
    border-color: rgba(255,255,255,.2); color: #e2e8f0;
}
.jemeni-pay-link:hover { border-color: #e94560; color: #e94560; }

/* Hero logo avec anneaux */
.jemeni-pay-hero-logo {
    position: relative; width: 160px; height: 160px;
    display: flex; align-items: center; justify-content: center;
}
.jemeni-pay-hero-logo img {
    border-radius: 28px; box-shadow: 0 0 60px rgba(233,69,96,.35);
    position: relative; z-index: 1;
}
.jemeni-pay-ring {
    position: absolute; border-radius: 50%;
    border: 2px solid rgba(233,69,96,.2); animation: jemeni-ring-pulse 2.5s ease-in-out infinite;
}
.jemeni-pay-ring--1 { width: 170px; height: 170px; animation-delay: 0s; }
.jemeni-pay-ring--2 { width: 210px; height: 210px; animation-delay: .9s; border-color: rgba(245,166,35,.15); }
@keyframes jemeni-ring-pulse {
    0%, 100% { transform: scale(1); opacity: .7; }
    50%       { transform: scale(1.06); opacity: .2; }
}

/* Grille opérateurs */
.jemeni-pay-operators {
    display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; width: 300px;
}
.jemeni-pay-op {
    background: rgba(255,255,255,.07);
    border: 1.5px solid rgba(255,255,255,.12);
    border-radius: 14px;
    padding: 1rem .75rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .25s; cursor: default;
    min-height: 70px;
}
.jemeni-pay-op:hover {
    background: rgba(255,255,255,.14);
    border-color: rgba(233,69,96,.5);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.3);
}
.jemeni-pay-op img { height: 44px; width: auto; border-radius: 6px; }

/* Bandeau 3 opérateurs (partial mm_operator_logos) */
.jemeni-pay-operators--logos {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 400px;
}
.jemeni-pay-operators--logos .jemeni-pay-operators-inner.mm-operator-logos img {
    box-shadow: 0 4px 14px rgba(0,0,0,.25);
}

/* Stats */
.jemeni-pay-stats { display: flex; gap: 1.25rem; }
.jemeni-pay-stat  { text-align: center; }
.jemeni-pay-stat__num { display: block; font-size: 1.35rem; font-weight: 800; color: #e94560; }
.jemeni-pay-stat__lbl { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }

@media (max-width: 700px) {
    .jemeni-pay-inner { flex-direction: column; text-align: center; }
    .jemeni-pay-features li { justify-content: center; }
    .jemeni-pay-right { width: 100%; }
    .jemeni-pay-actions { justify-content: center; }
    .jemeni-pay-desc { margin: 0 auto; }
}

/* ── Section Audience (4 profils) ─────────────────────────────── */
.section-audience { background: #f8fafc; }

.audience-grid { gap: 1.5rem; display: grid; }
.audience-grid--4 { grid-template-columns: repeat(2, 1fr); }
@media (min-width: 900px) { .audience-grid--4 { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 600px) { .audience-grid--4 { grid-template-columns: 1fr; } }

.audience-card__abo {
    font-size: .8125rem; font-weight: 700; color: #374151;
    margin: 0 0 .25rem; padding: .2rem 0;
    flex: 0 0 auto;
}

.audience-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    padding: 2rem 1.75rem;
    border-radius: 16px;
    border: 2px solid transparent;
    transition: border-color .2s, box-shadow .2s, transform .2s;
}
.audience-card:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(0,0,0,.1); }

.audience-card--client     { border-color: #e5e7eb; }
.audience-card--expert     { border-color: #16a34a; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
.audience-card--etudiant   { border-color: #dbeafe; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }
.audience-card--professeur { border-color: #c4b5fd; background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); }

.audience-card__icon {
    width: 54px; height: 54px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.7);
    margin-bottom: .25rem;
}
.audience-card--expert     .audience-card__icon { background: rgba(22,163,74,.12); color: #15803d; }
.audience-card--etudiant   .audience-card__icon { background: rgba(37,99,235,.1); color: #2563eb; }
.audience-card--client     .audience-card__icon { background: #f3f4f6; color: #374151; }
.audience-card--professeur .audience-card__icon { background: rgba(124,58,237,.12); color: #6d28d9; }

.audience-card h3 { font-size: 1.25rem; font-weight: 800; margin: 0; }
.audience-card p  { font-size: .9rem; color: #4b5563; line-height: 1.6; margin: 0; flex: 1; }

.audience-card__features {
    list-style: none; padding: 0; margin: .25rem 0 .5rem;
    display: flex; flex-direction: column; gap: .3rem;
}
.audience-card__features li {
    display: flex; align-items: center; gap: .5rem;
    font-size: .8125rem; color: #374151;
}
.audience-card__features li::before {
    content: '✓'; color: #16a34a; font-weight: 700; flex-shrink: 0;
}

.audience-card__popular, .audience-card__new, .audience-card__badge-violet {
    position: absolute; top: 1rem; right: 1rem;
    padding: .2rem .6rem; border-radius: 20px;
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
}
.audience-card__popular       { background: #16a34a; color: #fff; }
.audience-card__new          { background: #2563eb; color: #fff; }
.audience-card__badge-violet { background: #7c3aed; color: #fff; }

.audience-card__btn-violet {
    color: #6d28d9 !important; border-color: #7c3aed !important;
}
.audience-card__btn-violet:hover { background: #f5f3ff !important; color: #5b21b6 !important; }

.audience-card--professeur .audience-card__features li::before { color: #7c3aed; }

/* ── Banner Étudiant ──────────────────────────────────────────── */
.home-etudiant-banner {
    margin: 0;
    padding: 2.5rem 0;
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #0d9488 100%);
}
.home-etudiant-banner__inner {
    max-width: 1100px; margin: 0 auto;
    padding: 0 2rem;
    display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
}
.home-etudiant-banner__icon {
    width: 72px; height: 72px;
    background: rgba(255,255,255,.15);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}
.home-etudiant-banner__text { flex: 1; min-width: 260px; }
.home-etudiant-banner__text h2 { color: #fff; font-size: 1.375rem; font-weight: 800; margin: 0 0 .5rem; }
.home-etudiant-banner__text p  { color: rgba(255,255,255,.85); font-size: .9rem; line-height: 1.6; margin: 0 0 .875rem; }

.home-etudiant-banner__countries {
    display: flex; flex-wrap: wrap; gap: .5rem;
}
.home-etudiant-banner__countries span {
    background: rgba(255,255,255,.15);
    color: #fff; padding: .2rem .6rem;
    border-radius: 20px; font-size: .75rem; font-weight: 600;
}

.home-etudiant-banner__btn {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fff; color: #1d4ed8;
    padding: .8rem 1.5rem; border-radius: 10px;
    font-weight: 700; font-size: .9375rem;
    text-decoration: none; white-space: nowrap;
    transition: background .15s, transform .15s;
    flex-shrink: 0;
}
.home-etudiant-banner__btn:hover { background: #eff6ff; transform: translateY(-1px); }

/* ── Banner Professeurs ───────────────────────────────────────── */
.home-professeur-banner {
    margin: 0;
    padding: 2.5rem 0;
    background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #7c3aed 100%);
}
.home-professeur-banner__inner {
    max-width: 1100px; margin: 0 auto;
    padding: 0 2rem;
    display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
}
.home-professeur-banner__icon {
    width: 72px; height: 72px;
    background: rgba(255,255,255,.15);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}
.home-professeur-banner__text { flex: 1; min-width: 260px; }
.home-professeur-banner__text h2 { color: #fff; font-size: 1.375rem; font-weight: 800; margin: 0 0 .5rem; }
.home-professeur-banner__text p  { color: rgba(255,255,255,.9); font-size: .9rem; line-height: 1.6; margin: 0 0 .75rem; }
.home-professeur-banner__text p strong { color: #fff; }

.home-professeur-banner__features {
    list-style: none; padding: 0; margin: 0 0 1rem;
    display: flex; flex-direction: column; gap: .35rem;
}
.home-professeur-banner__features li {
    font-size: .875rem; color: rgba(255,255,255,.9);
    display: flex; align-items: center; gap: .5rem;
}
.home-professeur-banner__features li::before {
    content: '✓'; color: #c4b5fd; font-weight: 700;
}

.home-professeur-banner__btn {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fff; color: #6d28d9;
    padding: .8rem 1.5rem; border-radius: 10px;
    font-weight: 700; font-size: .9375rem;
    text-decoration: none; white-space: nowrap;
    transition: background .15s, transform .15s;
    flex-shrink: 0;
}
.home-professeur-banner__btn:hover { background: #f5f3ff; transform: translateY(-1px); }

/* Hero stats */
.hero-stats {
    display: flex; align-items: center; gap: 1.5rem;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255,255,255,.2);
}
.hero-stat { display: flex; flex-direction: column; gap: .1rem; }
.hero-stat__num { font-size: 1.375rem; font-weight: 800; color: #fff; }
.hero-stat__lbl { font-size: .75rem; color: rgba(255,255,255,.75); }
.hero-stat-sep  { width: 1px; height: 32px; background: rgba(255,255,255,.25); flex-shrink: 0; }

/* Hero title accent */
.hero-title-accent { color: #86efac; }

/* ── Wrapper Témoignages (style CTA) ──────────────────────────── */
.testi-wrap {
    position: relative;
    background: linear-gradient(135deg, #0f4c2a 0%, #16a34a 55%, #22c55e 100%);
    border-radius: 20px;
    padding: 2.25rem 2.5rem 2rem;
    margin: 0 0 3rem;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(22,163,74,0.30);
}
.testi-wrap__deco {
    position: absolute; inset: 0; pointer-events: none;
    background-image:
        radial-gradient(circle at 8% 50%,  rgba(255,255,255,.07) 0%, transparent 55%),
        radial-gradient(circle at 92% 15%, rgba(255,255,255,.05) 0%, transparent 45%);
}

/* Header */
.testi-wrap__header {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 1rem; margin-bottom: 1.75rem; position: relative; z-index: 1;
}
.testi-wrap__badge {
    display: inline-block;
    background: rgba(255,255,255,.18);
    color: #fff;
    font-size: .6875rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    padding: .25rem .75rem; border-radius: 20px;
    margin-bottom: .6rem;
}
.testi-wrap__title {
    font-size: 1.375rem; font-weight: 800;
    color: #fff; margin: 0 0 .3rem; letter-spacing: -.02em;
}
.testi-wrap__sub { font-size: .875rem; color: rgba(255,255,255,.78); margin: 0; }

/* Nav buttons */
.testi-wrap__nav { display: flex; gap: .5rem; flex-shrink: 0; }
.testi-wrap__nav-btn {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
    color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background .18s, transform .18s;
    backdrop-filter: blur(4px);
}
.testi-wrap__nav-btn:hover { background: rgba(255,255,255,.32); transform: scale(1.08); }

/* Carousel */
.testi-carousel { overflow: hidden; position: relative; z-index: 1; }
.testi-carousel__track {
    display: flex; gap: 16px;
    transition: transform .45s cubic-bezier(.4,0,.2,1);
}

/* Cards */
.testi-card {
    background: linear-gradient(160deg, rgba(255,255,255,.20), rgba(255,255,255,.12));
    border: 1px solid rgba(255,255,255,.26);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    padding: 1.5rem;
    display: flex; flex-direction: column; gap: .875rem;
    flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
    transition: background .18s, transform .18s, box-shadow .18s;
}
.testi-card:hover {
    background: linear-gradient(160deg, rgba(255,255,255,.26), rgba(255,255,255,.16));
    transform: translateY(-2px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 8px 20px rgba(12, 74, 36, .24);
}

.testi-card__quote-icon { flex-shrink: 0; line-height: 1; }
.testi-card__quote {
    font-size: .9375rem; line-height: 1.65;
    color: rgba(255,255,255,.95);
    font-style: italic; margin: 0; flex: 1;
    min-height: 92px;
}
.testi-card__stars { display: flex; gap: 2px; }

.testi-card__footer {
    display: flex; align-items: center; gap: .75rem;
    padding-top: .75rem;
    border-top: 1px solid rgba(255,255,255,.15);
}
.testi-card__avatar-wrap {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    background: rgba(255,255,255,.25);
    border: 2px solid rgba(255,255,255,.45);
    box-shadow: 0 2px 8px rgba(15, 23, 42, .22);
}
.testi-card__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}
.testi-card__avatar-fallback {
    display: none;
    width: 100%;
    height: 100%;
    color: #fff;
    font-weight: 800;
    font-size: .875rem;
    align-items: center;
    justify-content: center;
}
.testi-card__footer-text { flex: 1; }
.testi-card__name  { font-weight: 700; font-size: .875rem; color: #fff; font-style: normal; display: block; }
.testi-card__role  { font-size: .78rem; color: rgba(255,255,255,.7); }
.testi-card__flag  { font-size: 1.375rem; margin-left: auto; flex-shrink: 0; }

/* Dots */
.testi-dots {
    display: flex; justify-content: center; gap: .5rem;
    margin-top: 1.5rem; position: relative; z-index: 1;
}
.testi-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: rgba(255,255,255,.35); border: none; cursor: pointer; padding: 0;
    transition: background .2s, transform .2s;
}
.testi-dot.is-active { background: #fff; transform: scale(1.3); }

@media (max-width: 768px) {
    .home-etudiant-banner__inner,
    .home-professeur-banner__inner { flex-direction: column; align-items: flex-start; }
    .home-etudiant-banner__btn,
    .home-professeur-banner__btn { width: 100%; justify-content: center; }
    .hero-stats { flex-wrap: wrap; gap: 1rem; }
    .audience-grid { grid-template-columns: 1fr !important; }
    .testi-wrap { padding: 1.75rem 1.25rem 1.5rem; border-radius: 14px; }
    .testi-wrap__header { flex-direction: column; align-items: flex-start; gap: .75rem; }
    .testi-wrap__nav { align-self: flex-end; }
}
</style>
