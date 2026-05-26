<?php
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$e                = fn($s) => \App\Core\Security::escape($s ?? '');
$abonnement       = $abonnement ?? null;
$type             = $type ?? 'client';
$prixXof          = (float)($prix_xof ?? 0);
$planGratuitActif = (bool)($plan_gratuit_actif ?? false);
$dureeJours       = (int)($duree_jours ?? 30);
$provider         = $provider ?? 'intouch';
$aboMmCheckout    = in_array($provider, ['intouch', 'paytech'], true);
$paytechConfigured = !empty($paytech_configured);
$aboPaytechUi     = ($provider === 'paytech') || $paytechConfigured;
$csrfField        = \App\Core\Security::getCsrfField();
$showMobMmSteps   = $aboMmCheckout && !$planGratuitActif && $prixXof > 0;

$isExpert     = $type === 'expert';
$isEtudiant   = $type === 'etudiant';
$isProfesseur = $type === 'professeur';

if ($isExpert)        { $backUrl = $baseUrl . '/expert';   $typeLabel = 'Expert'; }
elseif ($isEtudiant)  { $backUrl = $baseUrl . '/etudiant'; $typeLabel = 'Étudiant'; }
elseif ($isProfesseur){ $backUrl = $baseUrl . '/professeur'; $typeLabel = 'Professeur'; }
else                  { $backUrl = $baseUrl . '/client';   $typeLabel = 'Client'; }

$dureeLabel = $dureeJours >= 365 ? 'an' : ($dureeJours >= 30 ? 'mois' : $dureeJours . ' jours');

// Jours restants
$joursRestants = null;
if ($abonnement && !empty($abonnement['date_fin'])) {
    $joursRestants = (int) ceil((strtotime($abonnement['date_fin']) - time()) / 86400);
}
$expireBientot = $joursRestants !== null && $joursRestants <= 7;

$avantages = $isExpert ? [
    'Accès à toutes les demandes clients en temps réel',
    'Accepter des missions urgentes en priorité',
    'Gérer vos réservations et prestations',
    'Portefeuille intégré et demandes de retrait',
    'Profil public visible par les clients',
    'Messagerie et sessions de travail',
    'Commission 0% sur vos prestations',
] : ($isEtudiant ? [
    'Soumettre des exercices et problèmes universitaires',
    'Accès aux tuteurs et experts académiques',
    'Messagerie avec les tuteurs',
    'Sessions de travail planifiées',
    'Suivi de progression par matière',
    'Ressources pédagogiques illimitées',
] : ($isProfesseur ? [
    'Publier des corrections et ressources pédagogiques',
    'Accompagner les étudiants de la plateforme',
    'Messagerie professionnelle intégrée',
    'Sessions de tutorat planifiées',
    'Profil expert en enseignement universitaire',
    'Visibilité sur l\'annuaire des professeurs',
] : [
    'Accès à tous les experts disponibles',
    'Publier des demandes d\'assistance',
    'Réservations planifiées avec des experts',
    'Mode urgence : expert en quelques minutes',
    'Messagerie et sessions de travail',
    'Portefeuille sécurisé (paiement escrow)',
    'Notes et avis sur les experts',
]));
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-alert mobile-alert--success" style="margin-bottom:1rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <?= $e($_SESSION['flash_success']) ?>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="mobile-alert mobile-alert--error" style="margin-bottom:1rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= $e($_SESSION['flash_error']) ?>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<section class="abo-mob-page">

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem">
    <a href="<?= $backUrl ?>" style="color:var(--text-muted);display:flex;align-items:center">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <h1 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--primary)">
        Abonnement <?= $e($typeLabel) ?>
    </h1>
</div>

<?php if ($abonnement): ?>
<!-- ===== ABONNEMENT ACTIF ===== -->

<!-- Alerte expiration imminente -->
<?php if ($expireBientot): ?>
<div style="background:#fffbeb;border:1.5px solid #fbbf24;border-radius:10px;padding:.85rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span style="color:#92400e;font-weight:600;font-size:.83rem">
        Expire dans <?= $joursRestants ?> jour<?= $joursRestants > 1 ? 's' : '' ?>. Renouvelez maintenant !
    </span>
</div>
<?php endif; ?>

<div style="background:var(--accent-soft);border:1.5px solid #bbf7d0;border-radius:var(--radius);padding:1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:.85rem">
    <div style="width:40px;height:40px;border-radius:50%;background:#16a34a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
        <p style="margin:0 0 .15rem;font-weight:700;font-size:.95rem;color:#16a34a">Abonnement actif</p>
        <p style="margin:0;font-size:.8rem;color:var(--text-muted)">
            Expire le <?= $e(isset($abonnement['date_fin']) ? date('d/m/Y', strtotime($abonnement['date_fin'])) : '—') ?>
            <?php if ($joursRestants !== null && $joursRestants > 0): ?>
            <span style="font-weight:600;color:<?= $expireBientot ? '#d97706' : 'var(--accent)' ?>">
                · <?= $joursRestants ?>j restant<?= $joursRestants > 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </p>
    </div>
    <span style="margin-left:auto;font-size:.7rem;font-weight:700;padding:.2rem .6rem;background:#16a34a;color:#fff;border-radius:999px">ACTIF</span>
</div>

<!-- Avantages inclus -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <h2 style="margin:0 0 .85rem;font-size:.9rem;font-weight:700;color:var(--primary)">Inclus dans votre abonnement</h2>
    <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:.55rem">
        <?php foreach ($avantages as $av): ?>
        <li style="display:flex;align-items:flex-start;gap:.55rem;font-size:.85rem;color:var(--text)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink:0;margin-top:.1rem"><polyline points="20 6 9 17 4 12"/></svg>
            <?= htmlspecialchars($av, ENT_QUOTES, 'UTF-8') ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- ✅ BOUTON RENOUVELER -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <p style="margin:0 0 .75rem;font-size:.82rem;color:var(--text-muted)">
        Renouvelez votre abonnement pour maintenir votre accès sans interruption.
    </p>
    <?php if ($planGratuitActif || $prixXof <= 0): ?>
    <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
        <?= $csrfField ?>
        <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:.5rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Renouveler l'accès gratuit
        </button>
    </form>
    <?php elseif ($aboMmCheckout): ?>
    <?php if ($showMobMmSteps): ?>
    <ol class="abo-mob-mm-steps" aria-label="Étapes du paiement Mobile Money">
        <li><span class="abo-mob-mm-steps__n" aria-hidden="true">1</span><span class="abo-mob-mm-steps__txt">Vérifiez le montant et le type d’abonnement sur cette page.</span></li>
        <li><span class="abo-mob-mm-steps__n" aria-hidden="true">2</span><span class="abo-mob-mm-steps__txt">Sur l’écran suivant : saisissez le numéro Mobile Money (Orange, Moov ou Wave) puis continuez vers Service de paiement.</span></li>
        <li><span class="abo-mob-mm-steps__n" aria-hidden="true">3</span><span class="abo-mob-mm-steps__txt">Validez la demande sur votre téléphone (application ou code USSD de l’opérateur).</span></li>
    </ol>
    <?php endif; ?>
    <?php
    $mm_logo_size = 'sm';
    $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.45rem;margin:0 0 .65rem;';
    require APP_PATH . '/Views/partials/mm_operator_logos.php';
    ?>
    <div class="abo-mob-mm-trust" style="margin-bottom:0.5rem">
        <span class="abo-mob-mm-trust__label">Orange · Moov · Wave</span>
        <span>· <?= $aboPaytechUi ? 'Paiement mobile' : 'Mobile Money' ?></span>
    </div>
    <a href="<?= $baseUrl ?><?= $e($intouch_payment_path ?? ('/paytech/checkout/' . rawurlencode($type))) ?>"
       class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;box-sizing:border-box;">
        Renouveler — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA <?php if ($aboPaytechUi): ?>(Service de paiement)<?php else: ?>(Mobile Money)<?php endif; ?>
    </a>
    <p style="margin-top:.55rem;text-align:center;font-size:.75rem;color:var(--text-muted)"><?= $aboPaytechUi ? 'Passerelle Paiement mobile — Mobile Money.' : 'Paiement Mobile Money sécurisé.' ?></p>
    <?php else: ?>
    <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
        <?= $csrfField ?>
        <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:.4rem"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Renouveler — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA
        </button>
    </form>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ===== PAS D'ABONNEMENT ===== -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div class="abo-mob-pricing__hero">
        <p style="margin:0 0 .35rem;font-size:.75rem;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.05em">
            <?= $e($typeLabel) ?>
        </p>
        <?php if ($prixXof > 0): ?>
        <div style="color:#fff">
            <span style="font-size:2rem;font-weight:800"><?= number_format($prixXof, 0, ',', ' ') ?></span>
            <span style="font-size:1rem;font-weight:600"> FCFA</span>
            <span style="font-size:.85rem;opacity:.8"> / <?= $e($dureeLabel) ?></span>
        </div>
        <?php else: ?>
        <div style="font-size:2rem;font-weight:800;color:#fff">Gratuit</div>
        <?php endif; ?>
    </div>

    <div style="padding:1.25rem">
        <ul style="margin:0 0 1.25rem;padding:0;list-style:none;display:flex;flex-direction:column;gap:.6rem">
            <?php foreach ($avantages as $av): ?>
            <li style="display:flex;align-items:flex-start;gap:.55rem;font-size:.85rem;color:var(--text)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink:0;margin-top:.1rem"><polyline points="20 6 9 17 4 12"/></svg>
                <?= htmlspecialchars($av, ENT_QUOTES, 'UTF-8') ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($planGratuitActif || $prixXof <= 0): ?>
        <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
            <?= $csrfField ?>
            <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:.5rem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Activer l'accès gratuit
            </button>
        </form>

        <?php elseif ($aboMmCheckout): ?>
        <?php if ($showMobMmSteps): ?>
        <ol class="abo-mob-mm-steps" aria-label="Étapes du paiement Mobile Money">
            <li><span class="abo-mob-mm-steps__n" aria-hidden="true">1</span><span class="abo-mob-mm-steps__txt">Vérifiez le montant et le type d’abonnement ci-dessus.</span></li>
            <li><span class="abo-mob-mm-steps__n" aria-hidden="true">2</span><span class="abo-mob-mm-steps__txt">Écran suivant : numéro Mobile Money puis redirection sécurisée vers Service de paiement.</span></li>
            <li><span class="abo-mob-mm-steps__n" aria-hidden="true">3</span><span class="abo-mob-mm-steps__txt">Choisissez Orange Money, Moov ou Wave sur service de paiement et confirmez sur votre téléphone.</span></li>
        </ol>
        <?php endif; ?>
        <?php
        $mm_logo_size = 'sm';
        $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.45rem;margin:0 0 .65rem;';
        require APP_PATH . '/Views/partials/mm_operator_logos.php';
        ?>
        <div class="abo-mob-mm-trust">
            <span class="abo-mob-mm-trust__label">Orange · Moov · Wave</span>
            <span>· passerelle <strong><?= $aboPaytechUi ? 'Paiement mobile' : 'Mobile Money' ?></strong></span>
        </div>
        <a href="<?= $baseUrl ?><?= $e($intouch_payment_path ?? ('/paytech/checkout/' . rawurlencode($type))) ?>"
           class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;box-sizing:border-box;">
            Payer <?= number_format($prixXof, 0, ',', ' ') ?> FCFA
        </a>
        <p style="margin-top:.65rem;text-align:center;font-size:.78rem;color:var(--text-muted)">
            Validez la demande sur votre téléphone Mobile Money.
        </p>

        <?php else: ?>
        <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
            <?= $csrfField ?>
            <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center">
                Souscrire — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:.65rem">
    <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.82rem;color:var(--text-muted)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0;margin-top:.1rem"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>Paiement abonnement via <strong><?= $aboPaytechUi ? 'Paiement mobile' : 'Mobile Money' ?></strong> et sécurisation des prestations par l’escrow Globalo.</span>
    </div>
    <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.82rem;color:var(--text-muted)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0;margin-top:.1rem"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        <span>Abonnement activé immédiatement après confirmation.</span>
    </div>
</div>
<?php endif; ?>

</section>

