<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$referralLink = $referral_link ?? '';
$demandes    = $demandes ?? [];
$reservations = $reservations ?? [];
$avisRecus   = $avis_recus ?? [];
$user        = $user ?? [];
$prenom      = $e($user['prenom'] ?? $user['nom'] ?? 'Utilisateur');
$solde       = (float)($solde_portefeuille ?? 0);

// Stats rapides
$nbDemandes    = count($demandes);
$nbReservations = isset($nb_reservations_total) ? (int) $nb_reservations_total : count($reservations);
$nbAvis        = count($avisRecus);
$pending_payment_reservations = $pending_payment_reservations ?? [];

// Statut → badge
if (!function_exists('clientStatutBadge')) { function clientStatutBadge(string $s): string {
    $map = [
        'ouverte'    => ['cl-badge--green',  'Ouverte'],
        'en_cours'   => ['cl-badge--blue',   'En cours'],
        'terminee'   => ['cl-badge--gray',   'Terminée'],
        'annulee'    => ['cl-badge--red',    'Annulée'],
        'acceptee'   => ['cl-badge--amber',  'Acceptée'],
        'en_attente' => ['cl-badge--orange', 'En attente'],
        'payee'      => ['cl-badge--purple', 'Payée'],
    ];
    [$cls, $label] = $map[$s] ?? ['cl-badge--gray', ucfirst(str_replace('_', ' ', htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')))];
    return '<span class="cl-badge ' . $cls . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</span>';
} }
?>
<div class="cl-dashboard">

    <!-- En-tête de bienvenue -->
    <div class="cl-hero">
        <div class="cl-hero__left">
            <p class="cl-hero__label">Bonjour,</p>
            <h1 class="cl-hero__title"><?= $prenom ?> <span class="cl-hero__wave">👋</span></h1>
            <p class="cl-hero__sub">Gérez vos demandes et suivez vos missions en un clin d'œil.</p>
        </div>
        <div class="cl-hero__actions">
            <a href="<?= $baseUrl ?>/client/urgence" class="cl-btn cl-btn--urgence">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Besoin d'aide maintenant
            </a>
            <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-btn cl-btn--outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouvelle demande
            </a>
        </div>
    </div>

    <?php if (!empty($pending_payment_reservations)): ?>
    <?php
    $nbPay = count($pending_payment_reservations);
    $firstPay = $pending_payment_reservations[0];
    $payBannerHref = $nbPay === 1
        ? $baseUrl . '/client/payer/' . (int) ($firstPay['id'] ?? 0)
        : $baseUrl . '/client/reservations';
    ?>
    <div class="cl-payment-alert" role="alert">
        <div class="cl-payment-alert__icon" aria-hidden="true">💳</div>
        <div class="cl-payment-alert__body">
            <strong class="cl-payment-alert__title">Paiement requis</strong>
            <p class="cl-payment-alert__text"><?= $nbPay === 1
                ? 'L’expert a accepté votre mission. Sans paiement depuis votre portefeuille, la session ne peut pas démarrer.'
                : 'Vous avez ' . (int) $nbPay . ' réservation(s) en attente de paiement. Réglez chaque montant pour lancer les missions.' ?></p>
        </div>
        <a href="<?= $payBannerHref ?>" class="cl-btn cl-btn--amber cl-payment-alert__cta"><?= $nbPay === 1 ? 'Payer maintenant' : 'Voir les réservations' ?></a>
    </div>
    <?php endif; ?>

    <!-- Cartes stats -->
    <div class="cl-stats">
        <a href="<?= $baseUrl ?>/client/demandes" class="cl-stat-card">
            <div class="cl-stat-card__icon cl-stat-card__icon--amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div class="cl-stat-card__body">
                <span class="cl-stat-card__num"><?= $nbDemandes ?></span>
                <span class="cl-stat-card__lbl">Demandes</span>
            </div>
            <svg class="cl-stat-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $baseUrl ?>/client/reservations" class="cl-stat-card">
            <div class="cl-stat-card__icon cl-stat-card__icon--green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="cl-stat-card__body">
                <span class="cl-stat-card__num"><?= $nbReservations ?></span>
                <span class="cl-stat-card__lbl">Réservations</span>
            </div>
            <svg class="cl-stat-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $baseUrl ?>/experts" class="cl-stat-card">
            <div class="cl-stat-card__icon cl-stat-card__icon--blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="cl-stat-card__body">
                <span class="cl-stat-card__num">∞</span>
                <span class="cl-stat-card__lbl">Experts disponibles</span>
            </div>
            <svg class="cl-stat-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $baseUrl ?>/client/portefeuille" class="cl-stat-card">
            <div class="cl-stat-card__icon cl-stat-card__icon--purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
            </div>
            <div class="cl-stat-card__body">
                <span class="cl-stat-card__num" style="font-size:1rem"><?= number_format($solde, 0, ',', ' ') ?></span>
                <span class="cl-stat-card__lbl">Portefeuille (<?= $e((new \App\Models\ParametreModel())->get('devise_plateforme','XOF')) ?>)</span>
            </div>
            <svg class="cl-stat-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="cl-content-grid">

        <!-- Dernières demandes -->
        <div class="cl-card">
            <div class="cl-card__head">
                <h2 class="cl-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Dernières demandes
                </h2>
                <a href="<?= $baseUrl ?>/client/demandes" class="cl-card__link">Tout voir →</a>
            </div>
            <?php if (empty($demandes)): ?>
            <div class="cl-empty">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <p>Aucune demande pour le moment</p>
                <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-btn-sm cl-btn-sm--amber">Créer une demande</a>
            </div>
            <?php else: ?>
            <ul class="cl-list">
                <?php foreach (array_slice($demandes, 0, 5) as $d): ?>
                <li class="cl-list__item">
                    <div class="cl-list__left">
                        <span class="cl-list__title"><?= $e($d['titre']) ?></span>
                        <span class="cl-list__meta">
                            <?= (int)($d['duree_estimee_heures'] ?? 0) ?>h
                            <?php if (!empty($d['competence_nom'])): ?> · <?= $e($d['competence_nom']) ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="cl-list__right">
                        <?= clientStatutBadge($d['statut'] ?? '') ?>
                        <?php if (($d['statut'] ?? '') === 'ouverte'): ?>
                        <a href="<?= $baseUrl ?>/client/reserver/<?= (int)$d['id'] ?>" class="cl-btn-sm cl-btn-sm--outline">Réserver</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Dernières réservations -->
        <div class="cl-card">
            <div class="cl-card__head">
                <h2 class="cl-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Dernières réservations
                </h2>
                <a href="<?= $baseUrl ?>/client/reservations" class="cl-card__link">Tout voir →</a>
            </div>
            <?php if (empty($reservations)): ?>
            <div class="cl-empty">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <p>Aucune réservation</p>
                <a href="<?= $baseUrl ?>/experts" class="cl-btn-sm cl-btn-sm--amber">Voir les experts</a>
            </div>
            <?php else: ?>
            <ul class="cl-list">
                <?php foreach (array_slice($reservations, 0, 5) as $r): ?>
                <li class="cl-list__item">
                    <div class="cl-list__left">
                        <span class="cl-list__title"><?= $e($r['expert_titre'] ?? $r['demande_titre'] ?? 'Réservation') ?></span>
                        <span class="cl-list__meta"><?= number_format((float)($r['montant_total'] ?? 0), 0, ',', ' ') ?> <?= $e(devise()) ?></span>
                    </div>
                    <div class="cl-list__right">
                        <?= clientStatutBadge($r['statut'] ?? '') ?>
                        <a href="<?= $baseUrl ?>/client/reservations/<?= (int)$r['id'] ?>" class="cl-btn-sm cl-btn-sm--outline">Détail</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Avis reçus -->
        <?php if (!empty($avisRecus)): ?>
        <div class="cl-card">
            <div class="cl-card__head">
                <h2 class="cl-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Avis des experts
                </h2>
            </div>
            <ul class="cl-list cl-list--avis">
                <?php foreach (array_slice($avisRecus, 0, 4) as $a): ?>
                <li class="cl-list__item cl-list__item--avis">
                    <div class="cl-avis-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="cl-star <?= $i <= (int)$a['note'] ? 'cl-star--on' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="cl-list__left">
                        <span class="cl-list__title"><?= $e(trim(($a['expert_prenom'] ?? '') . ' ' . ($a['expert_nom'] ?? ''))) ?></span>
                        <?php if (!empty(trim($a['commentaire'] ?? ''))): ?>
                        <span class="cl-list__meta">«&nbsp;<?= $e(mb_substr($a['commentaire'], 0, 100)) ?><?= mb_strlen($a['commentaire']) > 100 ? '…' : '' ?>&nbsp;»</span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Parrainage -->
        <?php if ($referralLink): ?>
        <div class="cl-card cl-card--referral">
            <div class="cl-card__head">
                <h2 class="cl-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Programme de parrainage
                </h2>
            </div>
            <p class="cl-referral__desc">Invitez vos amis et profitez d'avantages exclusifs dès leur inscription.</p>
            <div class="cl-referral__copy">
                <input type="text" readonly id="referral-link-input" class="cl-referral__input" value="<?= $e($referralLink) ?>" aria-label="Lien de parrainage">
                <button type="button" class="cl-btn cl-btn--amber" data-copy-target="referral-link-input">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span class="copy-label">Copier</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-copy-target'));
            if (!input) return;
            input.select();
            input.setSelectionRange(0, 99999);
            try {
                navigator.clipboard && navigator.clipboard.writeText(input.value);
                var lbl = this.querySelector('.copy-label');
                if (lbl) { var t = lbl.textContent; lbl.textContent = 'Copié !'; setTimeout(function () { lbl.textContent = t; }, 2000); }
            } catch (e) {}
        });
    });
})();
</script>
