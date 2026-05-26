<?php
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$e                = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$abonnement       = $abonnement ?? null;
$type             = $type ?? 'client';
$prixXof          = (float) ($prix_xof ?? 0);
$planGratuitActif = (bool)  ($plan_gratuit_actif ?? false);
$modeAbonnement   = (bool)  ($mode_abonnement ?? true);
$dureeJours       = (int)   ($duree_jours ?? 30);
$provider         = $provider ?? 'intouch';
$aboMmCheckout    = in_array($provider, ['intouch', 'paytech'], true);
$paytechConfigured = !empty($paytech_configured);
$aboPaytechUi     = ($provider === 'paytech') || $paytechConfigured;
$csrfField        = \App\Core\Security::getCsrfField();

// Lien retour selon le rôle
if ($type === 'expert')      { $backUrl = $baseUrl . '/expert';   $backLabel = 'Tableau de bord expert'; }
elseif ($type === 'etudiant')  { $backUrl = $baseUrl . '/etudiant'; $backLabel = 'Tableau de bord étudiant'; }
elseif ($type === 'professeur'){ $backUrl = $baseUrl . '/professeur'; $backLabel = 'Tableau de bord professeur'; }
else                           { $backUrl = $baseUrl . '/client';   $backLabel = 'Tableau de bord client'; }

$isExpert     = $type === 'expert';
$isEtudiant   = $type === 'etudiant';
$isProfesseur = $type === 'professeur';
$dureeLabel   = $dureeJours >= 365 ? 'an' : ($dureeJours >= 30 ? 'mois' : $dureeJours . ' jours');

// Jours restants (si abonnement actif)
$joursRestants = null;
if ($abonnement && !empty($abonnement['date_fin'])) {
    $joursRestants = (int) ceil((strtotime($abonnement['date_fin']) - time()) / 86400);
}
$expireBientot = $joursRestants !== null && $joursRestants <= 7;

// Label du type d'abonnement
$typeLabels = ['expert'=>'Expert','client'=>'Client','etudiant'=>'Étudiant','professeur'=>'Professeur'];
$typeLabel  = $typeLabels[$type] ?? ucfirst($type);

$avantages = $isExpert ? [
    ['icon'=>'zap',      'text'=>'Accès à toutes les demandes clients en temps réel'],
    ['icon'=>'bolt',     'text'=>'Accepter des missions urgentes (premier à répondre)'],
    ['icon'=>'calendar', 'text'=>'Gérer vos réservations et prestations'],
    ['icon'=>'wallet',   'text'=>'Portefeuille intégré et demandes de retrait'],
    ['icon'=>'eye',      'text'=>'Profil public visible par les clients'],
    ['icon'=>'message',  'text'=>'Messagerie et sessions de travail'],
    ['icon'=>'percent',  'text'=>'Commission 0% sur vos prestations'],
] : ($isEtudiant ? [
    ['icon'=>'edit',     'text'=>'Soumettre des exercices et problèmes universitaires'],
    ['icon'=>'users',    'text'=>'Accès aux tuteurs et experts académiques'],
    ['icon'=>'message',  'text'=>'Messagerie avec les tuteurs'],
    ['icon'=>'calendar', 'text'=>'Sessions de travail planifiées'],
    ['icon'=>'star',     'text'=>'Suivi de progression par matière'],
    ['icon'=>'shield',   'text'=>'Ressources pédagogiques illimitées'],
] : ($isProfesseur ? [
    ['icon'=>'edit',     'text'=>'Publier des corrections et ressources pédagogiques'],
    ['icon'=>'users',    'text'=>'Accompagner les étudiants de la plateforme'],
    ['icon'=>'message',  'text'=>'Messagerie professionnelle intégrée'],
    ['icon'=>'calendar', 'text'=>'Sessions de tutorat planifiées'],
    ['icon'=>'star',     'text'=>'Profil expert en enseignement universitaire'],
    ['icon'=>'eye',      'text'=>'Visibilité sur l\'annuaire des professeurs'],
] : [
    ['icon'=>'users',    'text'=>'Accès à tous les experts disponibles'],
    ['icon'=>'edit',     'text'=>'Publier des demandes d\'assistance'],
    ['icon'=>'calendar', 'text'=>'Faire des réservations planifiées'],
    ['icon'=>'zap',      'text'=>'Mode urgence : expert en quelques minutes'],
    ['icon'=>'message',  'text'=>'Messagerie et sessions de travail'],
    ['icon'=>'shield',   'text'=>'Portefeuille sécurisé (paiement escrow)'],
    ['icon'=>'star',     'text'=>'Notes et avis sur les experts'],
]));

$iconSvgs = [
    'zap'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'bolt'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
    'calendar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'wallet'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/><path d="M18 12h3v5h-3a2.5 2.5 0 0 1 0-5z"/></svg>',
    'eye'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'message'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'percent'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
    'users'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'edit'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
    'shield'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    'star'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'check'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'refresh'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
];
?>

<section class="section-desktop abo-page">

    <a href="<?= $backUrl ?>" class="abo-page__back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        <?= $e($backLabel) ?>
    </a>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="abo-alert abo-alert--success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="abo-alert abo-alert--error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <?php if ($abonnement): ?>
    <!-- ===== ABONNEMENT ACTIF ===== -->
    <div class="abo-hero abo-hero--actif">
        <div class="abo-hero__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="abo-hero__text">
            <h1 class="abo-hero__title">Abonnement actif</h1>
            <p class="abo-hero__subtitle">
                <?php if ($isExpert): ?>Vous avez accès à toutes les missions disponibles.
                <?php elseif ($isEtudiant): ?>Vous avez accès à tous les tuteurs et ressources pédagogiques.
                <?php elseif ($isProfesseur): ?>Vous accompagnez les étudiants sur la plateforme.
                <?php else: ?>Vous avez accès à tous les experts de la plateforme.<?php endif; ?>
            </p>
        </div>
        <span class="abo-badge abo-badge--actif">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="12"/></svg>
            Actif
        </span>
    </div>

    <?php if ($expireBientot): ?>
    <div class="abo-alert abo-alert--warn" style="display:flex;align-items:center;gap:.6rem;background:#fffbeb;border:1.5px solid #fbbf24;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.25rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span style="color:#92400e;font-weight:600;">Votre abonnement expire dans <?= $joursRestants ?> jour<?= $joursRestants > 1 ? 's' : '' ?>. Renouvelez dès maintenant.</span>
    </div>
    <?php endif; ?>

    <div class="abo-grid">
        <div class="abo-card abo-card--details">
            <h2 class="abo-card__title">Détails du plan</h2>
            <div class="abo-details">
                <div class="abo-detail-row">
                    <span class="abo-detail-row__label">Plan</span>
                    <span class="abo-detail-row__value abo-detail-row__value--pill"><?= $e(ucfirst($abonnement['plan'] ?? 'premium')) ?></span>
                </div>
                <div class="abo-detail-row">
                    <span class="abo-detail-row__label">Type</span>
                    <span class="abo-detail-row__value"><?= $e($typeLabel) ?></span>
                </div>
                <div class="abo-detail-row">
                    <span class="abo-detail-row__label">Début</span>
                    <span class="abo-detail-row__value"><?= $e(isset($abonnement['date_debut']) ? date('d/m/Y', strtotime($abonnement['date_debut'])) : '—') ?></span>
                </div>
                <div class="abo-detail-row">
                    <span class="abo-detail-row__label">Expire le</span>
                    <span class="abo-detail-row__value abo-detail-row__value--date <?= $expireBientot ? 'abo-detail-row__value--warn' : '' ?>">
                        <?= $e(isset($abonnement['date_fin']) ? date('d/m/Y', strtotime($abonnement['date_fin'])) : '—') ?>
                        <?php if ($joursRestants !== null): ?>
                        <span style="font-size:.78rem;margin-left:.35rem;<?= $expireBientot ? 'color:#d97706;font-weight:700' : 'color:#64748b' ?>">
                            (<?= $joursRestants > 0 ? $joursRestants . 'j restant' . ($joursRestants > 1 ? 's' : '') : 'Expiré' ?>)
                        </span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($abonnement['payment_provider']) && $abonnement['payment_provider'] !== 'gratuit'): ?>
                <div class="abo-detail-row">
                    <span class="abo-detail-row__label">Via</span>
                    <span class="abo-detail-row__value"><?= $e(ucfirst($abonnement['payment_provider'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Renouvellement -->
            <?php if ($prixXof > 0): ?>
            <div class="abo-renewal">
                <?= $iconSvgs['refresh'] ?>
                Renouvellement : <strong><?= number_format($prixXof, 0, ',', ' ') ?> FCFA / <?= $e($dureeLabel) ?></strong>
            </div>
            <?php endif; ?>

            <!-- ✅ Bouton RENOUVELER (toujours visible quand abonnement actif) -->
            <div class="abo-renew-block" style="margin-top:1.25rem;padding-top:1.1rem;border-top:1px solid var(--border,#e2e8f0);">
                <p style="font-size:.84rem;color:#64748b;margin:0 0 .8rem;">
                    Renouvelez votre abonnement avant l'expiration pour ne pas perdre votre accès.
                </p>
                <?php if ($planGratuitActif || $prixXof <= 0): ?>
                <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
                    <?= $csrfField ?>
                    <button type="submit" class="abo-btn abo-btn--primary" style="width:100%">
                        <?= $iconSvgs['refresh'] ?>
                        Renouveler l'accès gratuit
                    </button>
                </form>
                <?php elseif ($aboMmCheckout): ?>
                <?php
                $mm_logo_size = 'sm';
                $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem;margin:0 0 .75rem;';
                require APP_PATH . '/Views/partials/mm_operator_logos.php';
                ?>
                <a href="<?= $baseUrl ?><?= $e($intouch_payment_path ?? ('/paytech/checkout/' . rawurlencode($type))) ?>"
                   class="abo-btn abo-btn--primary" style="width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;box-sizing:border-box;">
                    Renouveler — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA <?php if ($aboPaytechUi): ?>(Paiement mobile)<?php elseif ($aboMmCheckout): ?>(Mobile Money)<?php endif; ?>
                </a>
                <p class="abo-hint"><?= $aboPaytechUi ? 'Paiement Mobile Money sécurisé via notre service de paiement.' : 'Paiement Mobile Money sécurisé.' ?></p>
                <?php else: ?>
                <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
                    <?= $csrfField ?>
                    <button type="submit" class="abo-btn abo-btn--primary" style="width:100%">
                        <?= $iconSvgs['refresh'] ?>
                        Renouveler — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="abo-card abo-card--avantages">
            <h2 class="abo-card__title">Inclus dans votre abonnement</h2>
            <ul class="abo-features">
                <?php foreach ($avantages as $av): ?>
                <li class="abo-feature">
                    <span class="abo-feature__icon abo-feature__icon--check"><?= $iconSvgs['check'] ?></span>
                    <span><?= htmlspecialchars($av['text'], ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== PAS D'ABONNEMENT ===== -->
    <div class="abo-hero abo-hero--inactive">
        <div class="abo-hero__text">
            <h1 class="abo-hero__title">Abonnement <?= $e($typeLabel) ?></h1>
            <p class="abo-hero__subtitle">
                <?php if ($isExpert): ?>Accédez aux missions et gérez vos prestations sans commission.
                <?php elseif ($isEtudiant): ?>Accédez aux tuteurs et soumettez vos exercices universitaires.
                <?php elseif ($isProfesseur): ?>Accompagnez les étudiants et gérez vos ressources pédagogiques.
                <?php else: ?>Réservez des experts et gérez vos missions en toute simplicité.<?php endif; ?>
            </p>
        </div>
    </div>

    <div class="abo-pricing-layout">
        <div class="abo-pricing-card">
            <div class="abo-pricing-card__header">
                <div class="abo-pricing-card__tag"><?= $e($typeLabel) ?></div>
                <div class="abo-pricing-card__price">
                    <?php if ($prixXof > 0): ?>
                        <span class="abo-pricing-card__amount"><?= number_format($prixXof, 0, ',', ' ') ?></span>
                        <span class="abo-pricing-card__currency">FCFA</span>
                        <span class="abo-pricing-card__period">/ <?= $e($dureeLabel) ?></span>
                    <?php else: ?>
                        <span class="abo-pricing-card__amount">Gratuit</span>
                    <?php endif; ?>
                </div>
                <?php if ($prixXof > 0): ?>
                <p class="abo-pricing-card__desc">Accès complet pendant <?= $dureeJours ?> jours</p>
                <?php endif; ?>
            </div>

            <ul class="abo-features abo-features--pricing">
                <?php foreach ($avantages as $av): ?>
                <li class="abo-feature">
                    <span class="abo-feature__icon"><?= $iconSvgs[$av['icon']] ?? $iconSvgs['check'] ?></span>
                    <span><?= htmlspecialchars($av['text'], ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="abo-pricing-card__footer">
                <?php if ($planGratuitActif || $prixXof <= 0): ?>
                    <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
                        <?= $csrfField ?>
                        <button type="submit" class="abo-btn abo-btn--primary">
                            <?= $iconSvgs['check'] ?>
                            Activer l'accès gratuit
                        </button>
                    </form>

                <?php elseif ($aboMmCheckout): ?>
                <?php
                $mm_logo_size = 'md';
                $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;margin:0 0 1rem;';
                require APP_PATH . '/Views/partials/mm_operator_logos.php';
                ?>
                <div class="abo-payment-badge" style="display:flex;align-items:center;gap:.5rem;">
                    Paiement sécurisé via <strong><?= $aboPaytechUi ? 'Paiement mobile' : 'Mobile Money' ?></strong>
                </div>
                <a href="<?= $baseUrl ?><?= $e($intouch_payment_path ?? ('/paytech/checkout/' . rawurlencode($type))) ?>"
                   class="abo-btn abo-btn--primary" style="display:flex;align-items:center;justify-content:center;gap:.6rem;width:100%;text-decoration:none;box-sizing:border-box;">
                    Payer <?= number_format($prixXof, 0, ',', ' ') ?> FCFA <?php if ($aboPaytechUi): ?>(Paiement mobile)<?php elseif ($aboMmCheckout): ?>(Mobile Money)<?php endif; ?>
                </a>
                <p class="abo-hint">
                    Validez la demande sur votre téléphone Mobile Money. L’abonnement s’active après confirmation.
                </p>

                <?php else: ?>
                    <form method="post" action="<?= $baseUrl ?>/abonnement/souscrire">
                        <?= $csrfField ?>
                        <button type="submit" class="abo-btn abo-btn--primary">
                            Souscrire — <?= number_format($prixXof, 0, ',', ' ') ?> FCFA
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="abo-trust">
            <div class="abo-trust__item">
                <div class="abo-trust__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div><strong>Paiement sécurisé</strong><p>Vos transactions sont protégées par le système escrow de Globalo<?php if ($aboMmCheckout): ?> ; l’abonnement se règle via <strong><?= $aboPaytechUi ? 'Paiement mobile' : 'Mobile Money' ?></strong><?php endif; ?>.</p></div>
            </div>
            <div class="abo-trust__item">
                <div class="abo-trust__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></div>
                <div><strong>Renouvellement simple</strong><p>Revenez sur cette page avant expiration pour renouveler en un clic.</p></div>
            </div>
            <div class="abo-trust__item">
                <div class="abo-trust__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.4 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 6.55 6.55l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                <div><strong>Support disponible</strong><p>Une question ? Notre équipe vous répond rapidement.</p></div>
            </div>
            <?php if ($prixXof > 0 && $aboMmCheckout): ?>
            <div class="abo-trust__wave-info" style="font-size:.85rem;color:#64748b;">
                Utilisez le numéro Mobile Money de l’opérateur choisi (Orange, Moov ou Wave) pour valider le paiement.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</section>
