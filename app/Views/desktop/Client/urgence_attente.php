<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$demande    = $demande    ?? null;
$reservation= $reservation ?? null;
$mission    = $mission    ?? null;
$e = fn($s) => \App\Core\Security::escape((string)($s ?? ''));

$expertAccepte = $reservation !== null;
$enAttente     = !$expertAccepte && $mission && ($mission['statut'] ?? '') === 'en_attente';
?>
<div class="urgence-page">

    <!-- Hero banner -->
    <div class="urgence-hero<?= $expertAccepte ? ' urgence-hero--success' : '' ?>">
        <div class="urgence-hero__deco" aria-hidden="true"></div>
        <div class="urgence-hero__inner">
            <a href="<?= $baseUrl ?>/client" class="urgence-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="urgence-hero__content">
                <div class="urgence-hero__icon-wrap" aria-hidden="true">
                    <?php if ($expertAccepte): ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>
                        <span class="urgence-pulse"></span>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($expertAccepte): ?>
                        <h1 class="urgence-hero__title">Un expert a accepté !</h1>
                        <p class="urgence-hero__subtitle">Votre mission est prête. Procédez au paiement pour démarrer.</p>
                    <?php elseif ($enAttente): ?>
                        <h1 class="urgence-hero__title">En attente d'un expert…</h1>
                        <p class="urgence-hero__subtitle">Votre demande a été diffusée aux experts disponibles. <strong>Le premier qui accepte vous sera assigné.</strong></p>
                    <?php else: ?>
                        <h1 class="urgence-hero__title">Demande expirée</h1>
                        <p class="urgence-hero__subtitle">Cette mission n'est plus en attente.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu central -->
    <div class="urgence-attente-wrap">

        <?php if ($expertAccepte): ?>
        <!-- ── Expert accepté ── -->
        <div class="urgence-attente-card urgence-attente-card--success">
            <div class="urgence-attente-card__icon" style="background:#dcfce7;color:#16a34a;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="urgence-attente-card__body">
                <h2 class="urgence-attente-card__title">Mission acceptée</h2>
                <p class="urgence-attente-card__text">
                    Un expert a pris en charge votre demande
                    <?php if (!empty($demande['titre'])): ?>
                    « <strong><?= $e($demande['titre']) ?></strong> »
                    <?php endif; ?>.
                </p>
                <?php if (!empty($reservation['montant_total'])): ?>
                <p class="urgence-attente-card__montant">
                    Montant estimé :
                    <strong><?= number_format((float)$reservation['montant_total'], 0, ',', ' ') ?> FCFA</strong>
                </p>
                <?php endif; ?>
                <a href="<?= $baseUrl ?>/client/reservations/<?= (int)($reservation['id'] ?? 0) ?>"
                   class="urgence-form__submit" style="display:inline-flex;margin-top:1.25rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Voir la réservation et payer
                </a>
            </div>
        </div>

        <?php elseif ($enAttente): ?>
        <!-- ── En attente ── -->
        <div class="urgence-attente-card">

            <!-- Info mission -->
            <div class="urgence-attente-card__mission">
                <span class="urgence-attente-card__mission-label">Votre demande</span>
                <p class="urgence-attente-card__mission-titre"><?= $e($demande['titre'] ?? '—') ?></p>
                <?php if (!empty($demande['description'])): ?>
                <p class="urgence-attente-card__mission-desc"><?= $e(mb_substr($demande['description'], 0, 200)) ?></p>
                <?php endif; ?>
            </div>

            <!-- Indicateur live -->
            <div class="urgence-attente-live" id="live-status">
                <div class="urgence-attente-live__dot"></div>
                <div class="urgence-attente-live__text">
                    <strong>Recherche d'expert en cours…</strong>
                    <span id="live-chrono" class="urgence-attente-live__chrono"></span>
                </div>
            </div>

            <!-- Barre de progression animée -->
            <div class="urgence-attente-progress" aria-hidden="true">
                <div class="urgence-attente-progress__bar" id="progress-bar"></div>
            </div>

            <!-- Étapes -->
            <ol class="urgence-attente-steps">
                <li class="urgence-attente-step urgence-attente-step--done">
                    <div class="urgence-attente-step__dot"></div>
                    <span>Demande publiée</span>
                </li>
                <li class="urgence-attente-step urgence-attente-step--active">
                    <div class="urgence-attente-step__dot"></div>
                    <span>Notification envoyée aux experts disponibles</span>
                </li>
                <li class="urgence-attente-step">
                    <div class="urgence-attente-step__dot"></div>
                    <span>Expert assigné</span>
                </li>
                <li class="urgence-attente-step">
                    <div class="urgence-attente-step__dot"></div>
                    <span>Paiement &amp; démarrage</span>
                </li>
            </ol>

            <!-- Actions -->
            <div class="urgence-attente-actions">
                <a href="<?= $baseUrl ?>/client/urgence/attente/<?= (int)($demande['id'] ?? 0) ?>"
                   class="btn btn-primary" id="btn-refresh">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Actualiser maintenant
                </a>
                <a href="<?= $baseUrl ?>/client" class="btn btn-outline">
                    ← Tableau de bord
                </a>
            </div>

        </div>

        <?php else: ?>
        <!-- ── Expirée / annulée ── -->
        <div class="urgence-attente-card" style="text-align:center;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:1rem;"><circle cx="12" cy="12" r="10"/><line x1="8" y1="8" x2="16" y2="16"/><line x1="16" y1="8" x2="8" y2="16"/></svg>
            <h2 style="color:#475569;margin:0 0 .5rem;">Demande non disponible</h2>
            <p style="color:#94a3b8;margin:0 0 1.5rem;">Cette mission n'est plus en attente ou a expiré.</p>
            <a href="<?= $baseUrl ?>/client/urgence" class="urgence-form__submit" style="display:inline-flex;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Nouvelle demande urgente
            </a>
        </div>
        <?php endif; ?>

    </div><!-- /.urgence-attente-wrap -->

</div><!-- /.urgence-page -->

<?php if ($enAttente): ?>
<style>
.urgence-attente-wrap{max-width:640px;margin:1.5rem auto;padding:0 1rem;}
.urgence-attente-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:2rem;box-shadow:0 2px 12px rgba(0,0,0,.06);}
.urgence-attente-card--success{border-color:#bbf7d0;}
.urgence-attente-card__icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;}
.urgence-attente-card__title{font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 .5rem;}
.urgence-attente-card__text{color:#475569;margin:0 0 .5rem;}
.urgence-attente-card__montant{color:#0f172a;font-size:1rem;margin:.5rem 0 0;}
.urgence-attente-card__mission{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;}
.urgence-attente-card__mission-label{font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.06em;}
.urgence-attente-card__mission-titre{font-size:1.0625rem;font-weight:700;color:#0f172a;margin:.25rem 0 0;}
.urgence-attente-card__mission-desc{font-size:.875rem;color:#64748b;margin:.4rem 0 0;}
.urgence-attente-live{display:flex;align-items:center;gap:.875rem;margin-bottom:1rem;}
.urgence-attente-live__dot{width:12px;height:12px;border-radius:50%;background:#ef4444;flex-shrink:0;animation:livePulse 1.2s ease-in-out infinite;}
@keyframes livePulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.4);}}
.urgence-attente-live__text{display:flex;flex-direction:column;gap:.1rem;}
.urgence-attente-live__chrono{font-size:.8rem;color:#94a3b8;}
.urgence-attente-progress{background:#f1f5f9;border-radius:8px;height:6px;overflow:hidden;margin-bottom:1.75rem;}
.urgence-attente-progress__bar{height:100%;width:30%;background:linear-gradient(90deg,#f97316,#ef4444);border-radius:8px;animation:progressLoop 3s ease-in-out infinite;}
@keyframes progressLoop{0%{width:10%;}50%{width:85%;}100%{width:10%;}}
.urgence-attente-steps{list-style:none;padding:0;margin:0 0 1.75rem;display:flex;flex-direction:column;gap:.875rem;}
.urgence-attente-step{display:flex;align-items:center;gap:.75rem;font-size:.875rem;color:#94a3b8;}
.urgence-attente-step--done{color:#16a34a;}
.urgence-attente-step--active{color:#0f172a;font-weight:600;}
.urgence-attente-step__dot{width:10px;height:10px;border-radius:50%;background:#e2e8f0;flex-shrink:0;}
.urgence-attente-step--done .urgence-attente-step__dot{background:#16a34a;}
.urgence-attente-step--active .urgence-attente-step__dot{background:#f97316;animation:livePulse 1.2s ease-in-out infinite;}
.urgence-attente-actions{display:flex;gap:.75rem;flex-wrap:wrap;}
</style>

<script>
(function () {
    var demandeId  = <?= (int)($demande['id'] ?? 0) ?>;
    var baseUrl    = <?= json_encode(rtrim(BASE_URL ?? '', '/')) ?>;
    var startTime  = Date.now();

    // Chrono
    function updateChrono() {
        var s = Math.floor((Date.now() - startTime) / 1000);
        var m = Math.floor(s / 60); s = s % 60;
        var el = document.getElementById('live-chrono');
        if (el) el.textContent = 'Depuis ' + (m > 0 ? m + ' min ' : '') + s + ' s';
    }
    setInterval(updateChrono, 1000);

    // Auto-refresh toutes les 12 secondes
    var countdown = 12;
    function tick() {
        countdown--;
        if (countdown <= 0) {
            window.location.href = baseUrl + '/client/urgence/attente/' + demandeId;
        }
    }
    setInterval(tick, 1000);

    // Mise à jour visuelle du bouton actualiser
    var btn = document.getElementById('btn-refresh');
    if (btn) {
        setInterval(function () {
            var remaining = Math.max(0, countdown);
            btn.querySelector('svg').nextSibling
               ? null
               : null;
        }, 1000);
    }
})();
</script>
<?php endif; ?>
