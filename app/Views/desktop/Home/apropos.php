<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$prixEtudiant = (int) ($prix_etudiant_xof ?? 500);
$prixProfesseur = (int) ($prix_professeur_xof ?? 1000);
$formatFcfa = fn(int $n) => $n > 0 ? number_format($n, 0, ',', ' ') . ' Fcfa/mois' : '';
?>
<div class="page-about">

    <!-- En-tête -->
    <header class="about-intro">
        <span class="section-badge"><?= __("nav.about") ?></span>
        <h1><?= __("about.title") ?></h1>
        <p class="about-intro-lead"><?= __("about.lead") ?></p>
    </header>

    <!-- Mission -->
    <section class="about-section about-section--mission">
        <div class="about-mission-grid">
            <div class="about-mission-text">
                <h2><?= __("about.mission.title") ?></h2>
                <p class="about-text">GLOBALO met en relation des <strong>professionnels débordés</strong> et des <strong>étudiants en difficulté</strong> avec des <strong>experts disponibles</strong> pour les assister sur des tâches urgentes ou académiques — typiquement <strong>1 à 3 heures</strong>, sans engagement, en XOF.</p>
                <p class="about-text">Plus besoin de recruter sur la durée ou d'attendre des semaines : vous décrivez le besoin, vous choisissez votre expert ou tuteur, vous réservez une session et vous avancez. Les règlements en ligne (abonnements, portefeuille, certaines missions) sont traités via <strong>Service de paiement</strong> (service de paiement) : page hébergée sécurisée, Mobile Money et carte selon les pays.</p>
            </div>
            <div class="about-mission-stats">
                <div class="about-stat">
                    <span class="about-stat__num">5</span>
                    <span class="about-stat__lbl">Pays éligibles</span>
                </div>
                <div class="about-stat">
                    <span class="about-stat__num">50+</span>
                    <span class="about-stat__lbl">Matières universitaires</span>
                </div>
                <div class="about-stat">
                    <span class="about-stat__num">1–3h</span>
                    <span class="about-stat__lbl">Durée typique d'une session</span>
                </div>
                <div class="about-stat">
                    <span class="about-stat__num">XOF</span>
                    <span class="about-stat__lbl">Devise locale (Franc CFA)</span>
                </div>
            </div>
        </div>
    </section>

    <!-- PayTech -->
    <section class="about-section about-section--paytech" aria-labelledby="about-paytech-heading">
        <div class="about-paytech">
            <div class="about-paytech__grid">
                <div class="about-paytech__copy">
                    <span class="about-paytech__badge"><?= __("about.paytech.badge") ?></span>
                    <h2 id="about-paytech-heading"><?= __("about.paytech.title") ?></h2>
                    <p class="about-paytech__lead"><?= __("about.paytech.lead") ?></p>
                    <ul class="about-paytech__list">
                        <li><?= __("about.paytech.f1") ?></li>
                        <li><?= __("about.paytech.f2") ?></li>
                        <li><?= __("about.paytech.f3") ?></li>
                        <li><?= __("about.paytech.f4") ?></li>
                        <li><?= __("about.paytech.f5") ?></li>
                    </ul>
                    <p class="about-paytech__note"><?= __("about.paytech.note") ?></p>
                    <span class="about-paytech__link"><?= __("about.paytech.link") ?></span>
                </div>
                <div class="about-paytech__visual" aria-hidden="true">
                    <div class="about-paytech__visual-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M7 15h4"/></svg>
                    </div>
                    <ul class="about-paytech__chips">
                        <li>HTTPS</li>
                        <li>XOF</li>
                        <li>Mobile Money</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Pour qui ? -->
    <section class="about-section">
        <h2><?= __("about.who.title") ?></h2>
        <p class="about-text about-text-muted">Quatre profils, une seule plateforme — choisissez celui qui vous correspond.</p>
        <div class="about-cards about-cards--4">

            <?php
            $iconClient    = icon_illustration('client');
            $iconClientImg = \App\Helpers\IconsIllustrations::hasIllustration('client')
                ? \App\Helpers\IconsIllustrations::illustrationUrl('client', $baseUrl) : null;
            ?>
            <div class="about-card about-card-client">
                <div class="about-card-icon">
                    <?php if ($iconClientImg): ?>
                        <img src="<?= \App\Core\Security::escape($iconClientImg) ?>" alt="" width="48" height="48" aria-hidden="true">
                    <?php else: ?>
                        <?= $iconClient['emoji_fallback'] ?>
                    <?php endif; ?>
                </div>
                <h3><?= __("about.who.client.title") ?></h3>
                <p>Freelance, entreprise, chef de projet ou particulier : quand une deadline approche et qu'il manque une compétence ou du temps, publiez une demande. Choisissez un expert selon ses compétences, son tarif et les avis, puis travaillez ensemble.</p>
                <ul class="about-card__features">
                    <li>Demandes urgentes 24/7</li>
                    <li>Mode urgence (premier expert disponible)</li>
                    <li><?= __("about.who.client.feature_paytech") ?></li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary"><?= __("about.who.client.button") ?></a>
            </div>

            <?php
            $iconExpert    = icon_illustration('expert');
            $iconExpertImg = \App\Helpers\IconsIllustrations::hasIllustration('expert')
                ? \App\Helpers\IconsIllustrations::illustrationUrl('expert', $baseUrl) : null;
            ?>
            <div class="about-card about-card-expert about-card--featured">
                <span class="about-card__badge">Populaire</span>
                <div class="about-card-icon">
                    <?php if ($iconExpertImg): ?>
                        <img src="<?= \App\Core\Security::escape($iconExpertImg) ?>" alt="" width="48" height="48" aria-hidden="true">
                    <?php else: ?>
                        <?= $iconExpert['emoji_fallback'] ?>
                    <?php endif; ?>
                </div>
                <h3><?= __("about.who.expert.title") ?></h3>
                <p><?= __("about.who.expert.text") ?></p>
                <ul class="about-card__features">
                    <li>Tarif horaire libre (en XOF)</li>
                    <li>Missions pour clients et étudiants</li>
                    <li>Certification admin disponible</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-outline"><?= __("about.who.expert.button") ?></a>
            </div>

            <!-- Étudiant(e) -->
            <div class="about-card about-card-etudiant">
                <span class="about-card__badge about-card__badge--blue">Abonnement</span>
                <div class="about-card-icon about-card-icon--etudiant">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <h3><?= __("about.who.etudiant.title") ?></h3>
                <p><?= __("about.who.etudiant.text") ?></p>
                <ul class="about-card__features">
                    <li>Abonnement <?= $e($formatFcfa($prixEtudiant)) ?></li>
                    <li>50+ matières universitaires</li>
                    <li>Suivi par matière (notes, niveau)</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-outline btn-outline--blue"><?= __("about.who.etudiant.button") ?></a>
            </div>

            <!-- Professeur(e) d'université -->
            <div class="about-card about-card-professeur">
                <span class="about-card__badge about-card__badge--violet">Abonnement</span>
                <div class="about-card-icon about-card-icon--professeur">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M12 11v6M9 14h6"/></svg>
                </div>
                <h3><?= __("about.who.professeur.title") ?></h3>
                <p><?= __("about.who.professeur.text") ?></p>
                <ul class="about-card__features">
                    <li>Abonnement <?= $e($formatFcfa($prixProfesseur)) ?></li>
                    <li>Expertise par matière universitaire</li>
                    <li>Accompagnement des étudiant(e)s</li>
                </ul>
                <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-outline btn-outline--violet"><?= __("about.who.professeur.button") ?></a>
            </div>

        </div>
    </section>

    <!-- Domaines d'assistance -->
    <section class="about-section">
        <h2><?= __("about.domains.title") ?></h2>
        <p class="about-text about-text-muted"><?= __("about.domains.text") ?></p>

        <div class="about-domains-split">
            <div class="about-domain-col">
                <h3 class="about-domain-col__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Assistance professionnelle
                </h3>
                <ul class="about-tags">
                    <li><?= __("about.tag.reports_analyses") ?></li>
                    <li><?= __("home.tag.excel") ?></li>
                    <li><?= __("home.tag.presentations") ?></li>
                    <li><?= __("home.tag.web_dev") ?></li>
                    <li><?= __("home.tag.graphic_design") ?></li>
                    <li><?= __("home.tag.accounting") ?></li>
                    <li><?= __("home.tag.translation") ?></li>
                    <li><?= __("home.tag.writing") ?></li>
                </ul>
            </div>
            <div class="about-domain-col">
                <h3 class="about-domain-col__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    Matières universitaires (UEMOA)
                </h3>
                <ul class="about-tags about-tags--blue">
                    <li>Mathématiques &amp; Statistiques</li>
                    <li>Économie &amp; Gestion</li>
                    <li>Comptabilité générale</li>
                    <li>Droit civil &amp; des affaires</li>
                    <li>Informatique &amp; Algorithmique</li>
                    <li>Physique &amp; Chimie</li>
                    <li>Sciences politiques</li>
                    <li>Agronomie &amp; Zootechnie</li>
                    <li>Santé publique &amp; Médecine</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Pays couverts -->
    <section class="about-section about-section--pays">
        <h2>Pays éligibles</h2>
        <p class="about-text about-text-muted">GLOBALO est disponible dans 5 pays. Paiement en XOF (Franc CFA), connectivité optimisée pour les réseaux mobiles.</p>
        <div class="about-pays-grid">
            <span>🇲🇱 Mali</span><span>🇨🇮 Côte d'Ivoire</span>
            <span>🇸🇳 Sénégal</span><span>🇧🇯 Bénin</span>
            <span>🇳🇪 Niger</span>
        </div>
    </section>

    <!-- CTA final -->
    <section class="about-section about-cta">
        <h2><?= __("about.cta.title") ?></h2>
        <p class="about-text"><?= __("about.cta.lead") ?></p>
        <div class="about-cta-buttons">
            <a href="<?= $baseUrl ?>/experts" class="btn btn-outline btn-lg"><?= __("about.cta.experts") ?></a>
            <a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary btn-lg"><?= __("about.cta.signup") ?></a>
            <a href="<?= $baseUrl ?>/home/contact" class="btn btn-outline btn-lg"><?= __("about.cta.contact") ?></a>
        </div>
    </section>

</div>

<style>
/* ── About spécifique ─────────────────────────────────────────────────────── */
.about-cards--3 { grid-template-columns: repeat(3, 1fr); display: grid; gap: 1.5rem; }
.about-cards--4 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }

.about-card {
    position: relative;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 2rem 1.75rem;
    display: flex; flex-direction: column; gap: .75rem;
    transition: box-shadow .2s, transform .2s;
}
.about-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.08); transform: translateY(-2px); }
.about-card--featured { border-color: #16a34a; background: linear-gradient(135deg, #f0fdf4 0%, #fff 60%); }
.about-card-etudiant  { border-color: #bfdbfe; background: linear-gradient(135deg, #eff6ff 0%, #fff 60%); }
.about-card-professeur { border-color: #c4b5fd; background: linear-gradient(135deg, #f5f3ff 0%, #fff 60%); }

.about-card__badge {
    position: absolute; top: 1rem; right: 1rem;
    background: #16a34a; color: #fff;
    padding: .2rem .6rem; border-radius: 20px;
    font-size: .7rem; font-weight: 700; text-transform: uppercase;
}
.about-card__badge--blue { background: #2563eb; }
.about-card__badge--violet { background: #7c3aed; }

.about-card-icon { width: 60px; height: 60px; border-radius: 14px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
.about-card-icon--etudiant { background: #dbeafe; color: #2563eb; }
.about-card-icon--professeur { background: #ede9fe; color: #7c3aed; }

.about-card h3 { font-size: 1.1875rem; font-weight: 800; margin: 0; }
.about-card p  { font-size: .875rem; color: #4b5563; line-height: 1.6; margin: 0; flex: 1; }

.about-card__features {
    list-style: none; padding: 0; margin: .25rem 0 .5rem;
    display: flex; flex-direction: column; gap: .3rem;
}
.about-card__features li {
    display: flex; align-items: center; gap: .5rem;
    font-size: .8rem; color: #374151;
}
.about-card__features li::before { content: '✓'; color: #16a34a; font-weight: 700; }
.about-card-etudiant .about-card__features li::before { color: #2563eb; }
.about-card-professeur .about-card__features li::before { color: #7c3aed; }

.btn-outline--blue { color: #2563eb !important; border-color: #2563eb !important; }
.btn-outline--blue:hover { background: #eff6ff !important; }
.btn-outline--violet { color: #7c3aed !important; border-color: #7c3aed !important; }
.btn-outline--violet:hover { background: #f5f3ff !important; }

/* Mission stats */
.about-section--mission { background: #f8fafc; border-radius: 16px; }
.about-mission-grid { display: grid; grid-template-columns: 1fr auto; gap: 2.5rem; align-items: start; }
.about-mission-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; min-width: 260px; }
.about-stat { background: #fff; border-radius: 12px; padding: 1.25rem; text-align: center; border: 1px solid #e5e7eb; }
.about-stat__num { display: block; font-size: 1.75rem; font-weight: 800; color: #16a34a; }
.about-stat__lbl { display: block; font-size: .75rem; color: #6b7280; margin-top: .2rem; }

/* Domains split */
.about-domains-split { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.25rem; }
.about-domain-col__title { font-size: .9375rem; font-weight: 700; margin: 0 0 .75rem; display: flex; align-items: center; gap: .4rem; color: #1f2937; }
.about-tags--blue li { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }

/* Pays */
.about-section--pays { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%); border-radius: 16px; }
.about-section--pays h2 { color: #fff; }
.about-section--pays .about-text-muted { color: rgba(255,255,255,.8); }
.about-pays-grid { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
.about-pays-grid span { background: rgba(255,255,255,.15); color: #fff; padding: .3rem .75rem; border-radius: 20px; font-size: .8125rem; font-weight: 600; }

/* About intro badge */
.about-intro .section-badge { display: inline-block; margin-bottom: .75rem; }

@media (max-width: 900px) {
    .about-cards--3, .about-cards--4, .about-domains-split, .about-mission-grid { grid-template-columns: 1fr !important; }
    .about-mission-stats { grid-template-columns: repeat(2, 1fr); min-width: 0; }
}
@media (max-width: 600px) {
    .about-mission-stats { grid-template-columns: 1fr 1fr; }
}
</style>
