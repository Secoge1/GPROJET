<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$contactEmail = (new \App\Models\ParametreModel())->get('plateforme_email', 'contact@secogesarl.com') ?: 'contact@secogesarl.com';
?>
<div class="page-legal page-confidentialite">
    <header class="legal-hero">
        <span class="section-badge">Legal</span>
        <h1>Politique de confidentialité</h1>
        <p class="legal-hero-lead">Dernière mise à jour : <?= date('d/m/Y') ?>. GLOBALO s'engage à protéger la vie privée des utilisateurs de sa plateforme.</p>
    </header>

    <div class="legal-content">
        <section class="legal-section">
            <h2>1. Responsable du traitement</h2>
            <p>Les données personnelles collectées sur la plateforme GLOBALO sont traitées par l'éditeur du site (coordonnées disponibles dans les <a href="<?= $baseUrl ?>/home/contact">mentions de contact</a>). Vous pouvez nous contacter à tout moment à l'adresse <a href="mailto:<?= $e($contactEmail) ?>"><?= $e($contactEmail) ?></a> pour toute question relative à vos données.</p>
        </section>

        <section class="legal-section">
            <h2>2. Données collectées</h2>
            <p>Nous collectons les informations que vous nous fournissez lors de l'inscription et de l'utilisation du service : identité (nom, prénom), adresse email, numéro de téléphone le cas échéant, rôle (client, expert, étudiant, professeur), et les données liées à votre activité (demandes, réservations, messages, avis). Les données de paiement sont gérées par notre prestataire Jɛmɛnipay (agrégateur Orange Money et Moov Africa) conformément à leurs propres politiques.</p>
        </section>

        <section class="legal-section">
            <h2>3. Finalités et bases légales</h2>
            <p>Les données sont utilisées pour : créer et gérer votre compte, mettre en relation clients et experts, traiter les réservations et paiements, assurer le support et la modération, améliorer nos services et respecter nos obligations légales. Le traitement repose sur l'exécution du contrat (utilisation de la plateforme), votre consentement (newsletters, cookies si applicable) et nos intérêts légitimes (sécurité, statistiques).</p>
        </section>

        <section class="legal-section">
            <h2>4. Durée de conservation</h2>
            <p>Les données de compte sont conservées tant que votre compte est actif. Après clôture, nous pouvons conserver certaines données pour nos obligations légales et comptables (durées définies par la réglementation en vigueur). Les logs techniques et données de sécurité sont conservés selon nos besoins opérationnels.</p>
        </section>

        <section class="legal-section">
            <h2>5. Destinataires et transferts</h2>
            <p>Vos données sont accessibles aux équipes autorisées de GLOBALO et, le cas échéant, à nos sous-traitants (hébergement, paiement, envoi d'emails). Nous ne vendons pas vos données. Les serveurs peuvent être situés dans l'Union européenne ou en Afrique de l'Ouest ; nous veillons à des garanties appropriées en cas de transfert hors UE.</p>
        </section>

        <section class="legal-section">
            <h2>6. Vos droits</h2>
            <p>Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation du traitement et, le cas échéant, de portabilité de vos données. Vous pouvez exercer ces droits en nous contactant à <a href="mailto:<?= $e($contactEmail) ?>"><?= $e($contactEmail) ?></a>. Vous avez également le droit d'introduire une réclamation auprès de l'autorité de contrôle compétente.</p>
        </section>

        <section class="legal-section">
            <h2>7. Sécurité</h2>
            <p>Nous mettons en œuvre des mesures techniques et organisationnelles pour protéger vos données contre l'accès non autorisé, la perte ou l'altération (accès sécurisé, chiffrement des mots de passe, sécurisation des échanges).</p>
        </section>

        <section class="legal-section">
            <h2>8. Modifications</h2>
            <p>Cette politique peut être mise à jour. La date de dernière mise à jour sera indiquée en tête de page. Nous vous invitons à la consulter régulièrement.</p>
        </section>
    </div>

    <div class="legal-footer-links">
        <a href="<?= $baseUrl ?>/home/donnees">Politique de gestion des données</a>
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
.legal-footer-links { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.legal-footer-links a { font-size: .9rem; font-weight: 600; color: var(--color-primary, #16a34a); text-decoration: none; }
.legal-footer-links a:hover { text-decoration: underline; }
</style>
