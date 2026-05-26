<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$contactEmail = (new \App\Models\ParametreModel())->get('plateforme_email', 'contact@secogesarl.com') ?: 'contact@secogesarl.com';
?>
<div class="page-legal page-donnees">
    <header class="legal-hero">
        <span class="section-badge">Données personnelles</span>
        <h1>Politique de gestion des données</h1>
        <p class="legal-hero-lead">Cette page décrit comment GLOBALO collecte, utilise et protège vos données personnelles, dans le respect des bonnes pratiques et des réglementations applicables.</p>
    </header>

    <div class="legal-content">
        <section class="legal-section">
            <h2>1. Principes</h2>
            <p>GLOBALO traite les données personnelles de manière licite, loyale et transparente. Nous ne collectons que les données nécessaires à la fourniture du service et à nos obligations. Nous les conservons pendant des durées limitées et assurons leur sécurité.</p>
        </section>

        <section class="legal-section">
            <h2>2. Types de données traitées</h2>
            <ul class="legal-list">
                <li><strong>Données d'identification :</strong> nom, prénom, email, téléphone (optionnel), pays.</li>
                <li><strong>Données de compte :</strong> rôle (client, expert, étudiant, professeur), mot de passe (stocké de manière sécurisée), date d'inscription, statut du compte.</li>
                <li><strong>Données d'activité :</strong> demandes d'assistance, réservations, messages échangés sur la plateforme, avis et notations.</li>
                <li><strong>Données de paiement et abonnement :</strong> historique des transactions et abonnements (les détails de carte ou mobile money sont gérés par nos prestataires de paiement).</li>
                <li><strong>Données techniques :</strong> adresse IP, type de navigateur, pages vues (pour le fonctionnement du site et la sécurité).</li>
            </ul>
        </section>

        <section class="legal-section">
            <h2>3. Utilisation des données</h2>
            <p>Vos données permettent de : créer et sécuriser votre compte, afficher votre profil (experts) ou vos demandes (clients), mettre en relation les parties, gérer les réservations et les paiements, envoyer les notifications utiles au service, assurer la modération et le support, produire des statistiques agrégées et anonymisées pour améliorer la plateforme.</p>
        </section>

        <section class="legal-section">
            <h2>4. Partage des données</h2>
            <p>Vos données ne sont pas vendues à des tiers. Elles peuvent être partagées avec : les autres utilisateurs dans le cadre du service (ex. nom et profil expert pour une réservation), nos sous-traitants (hébergement, envoi d'emails, traitement des paiements), les autorités en cas d'obligation légale.</p>
        </section>

        <section class="legal-section">
            <h2>5. Exercice de vos droits</h2>
            <p>Vous pouvez à tout moment : <strong>accéder</strong> à vos données (via votre compte ou sur demande), les <strong>rectifier</strong>, demander leur <strong>effacement</strong> (sous réserve des obligations légales), <strong>limiter</strong> certains traitements, vous <strong>opposer</strong> à des traitements fondés sur l'intérêt légitime. Pour exercer ces droits, contactez-nous à <a href="mailto:<?= $e($contactEmail) ?>"><?= $e($contactEmail) ?></a>. Vous pouvez également introduire une réclamation auprès de l'autorité de protection des données de votre pays.</p>
        </section>

        <section class="legal-section">
            <h2>6. Sécurité et conservation</h2>
            <p>Nous appliquons des mesures de sécurité adaptées (accès restreint, mots de passe hashés, échanges sécurisés). Les données sont conservées pendant la durée d'utilisation du compte puis, le cas échéant, pour les durées imposées par la loi (comptabilité, litiges).</p>
        </section>

        <section class="legal-section">
            <h2>7. Cookies et traceurs</h2>
            <p>Le site peut utiliser des cookies ou traceurs nécessaires au fonctionnement (session, préférences). Des cookies analytiques ou publicitaires peuvent être utilisés si vous y consentez ; vous pouvez gérer vos préférences via notre bandeau ou les paramètres de votre navigateur.</p>
        </section>
    </div>

    <div class="legal-footer-links">
        <a href="<?= $baseUrl ?>/home/confidentialite">Politique de confidentialité</a>
        <a href="<?= $baseUrl ?>/home/contact">Nous contacter</a>
    </div>
</div>

<style>
.page-legal { max-width: 800px; margin: 0 auto; padding: 2rem 1.5rem 3rem; }
.legal-hero { text-align: center; margin-bottom: 2.5rem; }
.legal-hero .section-badge { display: inline-block; margin-bottom: .75rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--color-primary, #16a34a); }
.legal-hero h1 { font-size: 1.75rem; font-weight: 800; color: #111827; margin: 0 0 .75rem; }
.legal-hero-lead { font-size: .9375rem; color: #6b7280; margin: 0; line-height: 1.6; }
.legal-content { margin-bottom: 2rem; }
.legal-section { margin-bottom: 2rem; }
.legal-section h2 { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 .75rem; }
.legal-section p { font-size: .9375rem; color: #374151; line-height: 1.7; margin: 0; }
.legal-section a { color: var(--color-primary, #16a34a); text-decoration: none; }
.legal-section a:hover { text-decoration: underline; }
.legal-list { margin: 0; padding-left: 1.25rem; font-size: .9375rem; color: #374151; line-height: 1.7; }
.legal-list li { margin-bottom: .5rem; }
.legal-footer-links { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.legal-footer-links a { font-size: .9rem; font-weight: 600; color: var(--color-primary, #16a34a); text-decoration: none; }
.legal-footer-links a:hover { text-decoration: underline; }
</style>
