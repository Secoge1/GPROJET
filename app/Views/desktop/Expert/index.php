<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$profil       = $profil ?? null;
$utilisateur  = $utilisateur ?? [];
$missions     = $missions ?? [];
$referralLink = $referral_link ?? '';

$userId       = (int)($utilisateur['id'] ?? $user['id'] ?? 0);
$nomComplet   = trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''));
$hasAvatar    = $userId && !empty($utilisateur['avatar']);
$avatarUrl    = $hasAvatar ? $baseUrl . '/fichier/user-avatar/' . $userId . '?t=' . time() : null;
$disponible   = !empty($profil) && !empty($profil['disponible']);
$titre        = $profil['titre'] ?? '';
$tarif        = !empty($profil['tarif_horaire']) ? number_format((float)$profil['tarif_horaire'], 0, ',', ' ') . ' FCFA/h' : null;

$solde        = (float)($solde ?? 0);
$totalGains   = (float)($totalGains ?? 0);
$nbEnAttente  = (int)($nbEnAttente ?? 0);
$nbEnCours    = (int)($nbEnCours ?? 0);
$nbUrgences   = (int)($nbUrgences ?? 0);
$nbDemandes   = (int)($nbDemandes ?? 0);
$nbNotifs     = (int)($nbNotifications ?? 0);

$statutMissions = [
    'en_attente' => ['label' => 'En attente',  'color' => '#b45309', 'bg' => '#fffbeb', 'dot' => '#f59e0b'],
    'acceptee'   => ['label' => 'Acceptée',    'color' => '#1d4ed8', 'bg' => '#eff6ff', 'dot' => '#3b82f6'],
    'en_cours'   => ['label' => 'En cours',    'color' => '#0f766e', 'bg' => '#f0fdfa', 'dot' => '#14b8a6'],
    'terminee'   => ['label' => 'Terminée',    'color' => '#16a34a', 'bg' => '#dcfce7', 'dot' => '#22c55e'],
    'annulee'    => ['label' => 'Annulée',     'color' => '#6b7280', 'bg' => '#f9fafb', 'dot' => '#9ca3af'],
];

$initiales = strtoupper(
    substr($nomComplet ?: 'E', 0, 1) .
    (strpos($nomComplet, ' ') !== false ? substr(strrchr($nomComplet, ' '), 1, 1) : '')
);
?>
<section class="section-desktop expert-dashboard">

    <!-- ═══════════════════════════════════════
         Hero banner
    ═══════════════════════════════════════ -->
    <div class="expert-dashboard__hero">
        <!-- Avatar + identité -->
        <div class="expert-dashboard__identity">
            <div class="expert-dashboard__avatar-wrap">
                <?php if ($avatarUrl): ?>
                    <img src="<?= $e($avatarUrl) ?>" alt="Photo de profil" class="expert-dashboard__avatar-img">
                <?php else: ?>
                    <div class="expert-dashboard__avatar-placeholder"><?= $e($initiales ?: 'E') ?></div>
                <?php endif; ?>
                <span class="expert-dashboard__status-dot <?= $disponible ? 'is-available' : 'is-offline' ?>" title="<?= $disponible ? 'Disponible' : 'Hors ligne' ?>"></span>
            </div>
            <div class="expert-dashboard__identity-text">
                <h1 class="expert-dashboard__name">
                    Bonjour<?= $nomComplet ? ', ' . $e($nomComplet) : '' ?> 👋
                </h1>
                <?php if ($titre): ?>
                <p class="expert-dashboard__role"><?= $e($titre) ?></p>
                <?php endif; ?>
                <div class="expert-dashboard__meta">
                    <span class="expert-dashboard__status-badge <?= $disponible ? 'is-available' : 'is-offline' ?>">
                        <span class="expert-dashboard__status-dot-inline"></span>
                        <?= $disponible ? 'Disponible' : 'Hors ligne' ?>
                    </span>
                    <?php if ($tarif): ?>
                    <span class="expert-dashboard__tarif">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <?= $e($tarif) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($nbNotifs > 0): ?>
                    <a href="<?= $baseUrl ?>/expert/notifications" class="expert-dashboard__notif-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?= $nbNotifs ?> non lue<?= $nbNotifs > 1 ? 's' : '' ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="expert-dashboard__hero-actions">
            <a href="<?= $baseUrl ?>/expert/profil" class="btn btn-primary expert-dashboard__hero-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Mon profil
            </a>
            <a href="<?= $baseUrl ?>/expert/retrait-choix" class="btn btn-outline expert-dashboard__hero-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Retrait
            </a>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         KPI cards
    ═══════════════════════════════════════ -->
    <div class="expert-dashboard__kpi-grid">

        <!-- Solde disponible -->
        <div class="expert-kpi expert-kpi--green">
            <div class="expert-kpi__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div class="expert-kpi__body">
                <span class="expert-kpi__label">Solde disponible</span>
                <span class="expert-kpi__value"><?= number_format($solde, 0, ',', ' ') ?> <small>FCFA</small></span>
            </div>
            <a href="<?= $baseUrl ?>/expert/retrait-choix" class="expert-kpi__link" aria-label="Faire un retrait">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

        <!-- Total gains -->
        <div class="expert-kpi expert-kpi--blue">
            <div class="expert-kpi__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <div class="expert-kpi__body">
                <span class="expert-kpi__label">Total des gains</span>
                <span class="expert-kpi__value"><?= number_format($totalGains, 0, ',', ' ') ?> <small>FCFA</small></span>
            </div>
            <a href="<?= $baseUrl ?>/expert/revenus" class="expert-kpi__link" aria-label="Voir les revenus">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

        <!-- Réservations en attente -->
        <div class="expert-kpi expert-kpi--orange">
            <div class="expert-kpi__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="expert-kpi__body">
                <span class="expert-kpi__label">Réservations en attente</span>
                <span class="expert-kpi__value"><?= $nbEnAttente ?> <small>à traiter</small></span>
            </div>
            <a href="<?= $baseUrl ?>/expert/reservations?statut=en_attente" class="expert-kpi__link" aria-label="Voir les réservations en attente">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

        <!-- Missions en cours -->
        <div class="expert-kpi expert-kpi--teal">
            <div class="expert-kpi__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="expert-kpi__body">
                <span class="expert-kpi__label">Missions en cours</span>
                <span class="expert-kpi__value"><?= $nbEnCours ?> <small>active<?= $nbEnCours > 1 ? 's' : '' ?></small></span>
            </div>
            <a href="<?= $baseUrl ?>/expert/missions" class="expert-kpi__link" aria-label="Voir les missions">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

    </div>

    <!-- ═══════════════════════════════════════
         Accès rapides (raccourcis)
    ═══════════════════════════════════════ -->
    <div class="expert-dashboard__shortcuts">

        <a href="<?= $baseUrl ?>/expert/urgences" class="expert-shortcut expert-shortcut--red">
            <div class="expert-shortcut__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            </div>
            <div class="expert-shortcut__body">
                <span class="expert-shortcut__title">Missions urgentes</span>
                <span class="expert-shortcut__count"><?= $nbUrgences ?> disponible<?= $nbUrgences > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($nbUrgences > 0): ?>
            <span class="expert-shortcut__badge"><?= $nbUrgences ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $baseUrl ?>/expert/demandes" class="expert-shortcut expert-shortcut--purple">
            <div class="expert-shortcut__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="expert-shortcut__body">
                <span class="expert-shortcut__title">Demandes clients</span>
                <span class="expert-shortcut__count"><?= $nbDemandes ?> correspondante<?= $nbDemandes > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($nbDemandes > 0): ?>
            <span class="expert-shortcut__badge expert-shortcut__badge--purple"><?= $nbDemandes ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $baseUrl ?>/expert/reservations" class="expert-shortcut expert-shortcut--blue">
            <div class="expert-shortcut__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="expert-shortcut__body">
                <span class="expert-shortcut__title">Réservations</span>
                <span class="expert-shortcut__count">Gérer vos réservations</span>
            </div>
        </a>

        <a href="<?= $baseUrl ?>/messages" class="expert-shortcut expert-shortcut--indigo">
            <div class="expert-shortcut__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="expert-shortcut__body">
                <span class="expert-shortcut__title">Messages</span>
                <span class="expert-shortcut__count">Vos conversations</span>
            </div>
        </a>

    </div>

    <!-- ═══════════════════════════════════════
         Dernières missions
    ═══════════════════════════════════════ -->
    <div class="expert-dashboard__section">
        <div class="expert-dashboard__section-header">
            <h2 class="expert-dashboard__section-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Dernières missions
            </h2>
            <a href="<?= $baseUrl ?>/expert/missions" class="expert-dashboard__section-link">
                Voir tout
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

        <?php if (empty($missions)): ?>
        <div class="expert-dashboard__empty">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <p>Aucune mission pour le moment.</p>
            <div class="expert-dashboard__empty-actions">
                <a href="<?= $baseUrl ?>/expert/urgences" class="btn btn-primary btn-sm">Missions urgentes</a>
                <a href="<?= $baseUrl ?>/expert/demandes" class="btn btn-outline btn-sm">Voir les demandes</a>
            </div>
        </div>
        <?php else: ?>
        <ul class="expert-dashboard__mission-list">
            <?php foreach ($missions as $m):
                $statut  = $m['statut'] ?? 'en_attente';
                $stConf  = $statutMissions[$statut] ?? ['label' => ucfirst($statut), 'color' => '#64748b', 'bg' => '#f8fafc', 'dot' => '#94a3b8'];
                $montant = !empty($m['montant_total']) ? number_format((float)$m['montant_total'], 0, ',', ' ') . ' FCFA' : '—';
                $client  = trim(($m['client_prenom'] ?? '') . ' ' . ($m['client_nom'] ?? ''));
                $date    = !empty($m['date_prestation']) ? date('d/m/Y', strtotime($m['date_prestation'])) : '—';
            ?>
            <li class="expert-dashboard__mission-item">
                <div class="expert-dashboard__mission-stripe" style="background:<?= $stConf['dot'] ?>;"></div>
                <div class="expert-dashboard__mission-content">
                    <div class="expert-dashboard__mission-top">
                        <span class="expert-dashboard__mission-title"><?= $e($m['demande_titre'] ?? 'Mission #' . $m['id']) ?></span>
                        <span class="expert-dashboard__mission-badge" style="color:<?= $stConf['color'] ?>;background:<?= $stConf['bg'] ?>;">
                            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:<?= $stConf['dot'] ?>;margin-right:4px;"></span>
                            <?= $e($stConf['label']) ?>
                        </span>
                    </div>
                    <div class="expert-dashboard__mission-meta">
                        <?php if ($client): ?>
                        <span>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?= $e($client) ?>
                        </span>
                        <?php endif; ?>
                        <span>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= $e($date) ?>
                        </span>
                        <span class="expert-dashboard__mission-amount"><?= $e($montant) ?></span>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/messages" class="expert-dashboard__mission-action" title="Messages">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════
         Parrainage
    ═══════════════════════════════════════ -->
    <?php if ($referralLink): ?>
    <div class="expert-dashboard__referral">
        <div class="expert-dashboard__referral-left">
            <div class="expert-dashboard__referral-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h3 class="expert-dashboard__referral-title">Parrainage</h3>
                <p class="expert-dashboard__referral-desc">Partagez votre lien d'invitation pour faire connaître Globalo.</p>
            </div>
        </div>
        <div class="expert-dashboard__referral-copy">
            <input type="text" readonly id="expert-referral-input" class="expert-dashboard__referral-input" value="<?= $e($referralLink) ?>" aria-label="Lien de parrainage">
            <button type="button" class="btn btn-outline btn-sm" data-copy-target="expert-referral-input">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copier le lien
            </button>
        </div>
    </div>
    <?php endif; ?>

</section>

<script>
(function() {
    document.querySelectorAll('[data-copy-target]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var el = document.getElementById(this.getAttribute('data-copy-target'));
            if (!el) return;
            el.select();
            try {
                navigator.clipboard && navigator.clipboard.writeText(el.value);
                this.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Copié !';
                var b = this;
                setTimeout(function() {
                    b.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copier le lien';
                }, 2200);
            } catch(e) {}
        });
    });
})();
</script>
