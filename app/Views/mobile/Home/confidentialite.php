<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$contactEmail = (new \App\Models\ParametreModel())->get('plateforme_email', 'contact@secogesarl.com') ?: 'contact@secogesarl.com';
?>
<div class="mob-legal">
    <div class="mob-legal-hero">
        <span class="mob-legal-badge">Legal</span>
        <h1 class="mob-legal-title">Politique de confidentialité</h1>
        <p class="mob-legal-lead">GLOBALO s'engage à protéger la vie privée des utilisateurs. Dernière mise à jour : <?= date('d/m/Y') ?>.</p>
    </div>
    <div class="mob-legal-body">
        <section><h2>1. Responsable</h2><p>Les données sont traitées par l'éditeur de GLOBALO. Contact : <a href="mailto:<?= $e($contactEmail) ?>"><?= $e($contactEmail) ?></a>.</p></section>
        <section><h2>2. Données collectées</h2><p>Identité, email, rôle, activité (demandes, réservations, messages). Les paiements sont gérés par notre prestataire Jɛmɛnipay (Orange Money et Moov Africa).</p></section>
        <section><h2>3. Finalités</h2><p>Gestion du compte, mise en relation, réservations et paiements, support, amélioration des services et obligations légales.</p></section>
        <section><h2>4. Durée</h2><p>Données conservées tant que le compte est actif, puis selon obligations légales et comptables.</p></section>
        <section><h2>5. Destinataires</h2><p>Équipes GLOBALO et sous-traitants (hébergement, paiement, emails). Nous ne vendons pas vos données.</p></section>
        <section><h2>6. Vos droits</h2><p>Accès, rectification, effacement, limitation, portabilité. Contact : <a href="mailto:<?= $e($contactEmail) ?>"><?= $e($contactEmail) ?></a>. Droit de réclamation auprès de l'autorité compétente.</p></section>
        <section><h2>7. Sécurité</h2><p>Mesures techniques et organisationnelles pour protéger vos données.</p></section>
    </div>
    <div class="mob-legal-links">
        <a href="<?= $baseUrl ?>/home/donnees">Politique de gestion des données</a>
        <a href="<?= $baseUrl ?>/home/contact">Contact</a>
    </div>
</div>
<style>
.mob-legal { padding: 1.25rem 1.25rem 2rem; }
.mob-legal-hero { text-align: center; margin-bottom: 1.5rem; }
.mob-legal-badge { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--accent, #16a34a); }
.mob-legal-title { font-size: 1.25rem; font-weight: 800; color: #111; margin: .5rem 0 .5rem; }
.mob-legal-lead { font-size: .875rem; color: #6b7280; margin: 0; }
.mob-legal-body section { margin-bottom: 1.25rem; }
.mob-legal-body h2 { font-size: 1rem; font-weight: 700; margin: 0 0 .4rem; color: #111; }
.mob-legal-body p { font-size: .875rem; color: #374151; line-height: 1.6; margin: 0; }
.mob-legal-body a { color: var(--accent, #16a34a); text-decoration: none; }
.mob-legal-links { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.mob-legal-links a { font-size: .875rem; font-weight: 600; color: var(--accent, #16a34a); }
</style>
