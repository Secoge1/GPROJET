<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$stats     = $stats ?? [];
$dashboard = $dashboard ?? [];
$agents    = $agents ?? [];
$ia_active = $ia_active ?? false;

$totalUsers = ($stats['total_professeurs'] ?? 0) + ($stats['total_etudiants'] ?? 0)
            + ($stats['total_clients'] ?? 0) + ($stats['total_experts'] ?? 0);
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">

<div class="rh-page">

    <!-- ══ HEADER ══════════════════════════════════════════════════ -->
    <div class="rh-header">
        <div class="rh-header__left">
            <div class="rh-header__badge">
                <span class="rh-pulse"></span>
                Espace RH · IA Active
            </div>
            <h1 class="rh-header__title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Gestion RH — GLOBALO
            </h1>
            <p class="rh-header__sub">4 agents IA spécialisés • <?= $totalUsers ?> utilisateurs actifs</p>
        </div>
        <div class="rh-header__actions">
            <?php if (!$ia_active): ?>
            <div class="rh-alert-ia">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                IA non configurée — Ajoutez une clé API dans <code>.env</code>
            </div>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/admin" class="rh-btn rh-btn--ghost">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Admin
            </a>
        </div>
    </div>

    <!-- ══ STATS GLOBALES ═══════════════════════════════════════════ -->
    <div class="rh-stats-grid">
        <div class="rh-stat-card rh-stat-card--purple">
            <div class="rh-stat-card__icon">🎓</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Professeurs</span>
                <span class="rh-stat-card__value"><?= number_format($stats['total_professeurs'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-stat-card rh-stat-card--blue">
            <div class="rh-stat-card__icon">📚</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Étudiants</span>
                <span class="rh-stat-card__value"><?= number_format($stats['total_etudiants'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-stat-card rh-stat-card--cyan">
            <div class="rh-stat-card__icon">💼</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Clients</span>
                <span class="rh-stat-card__value"><?= number_format($stats['total_clients'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-stat-card rh-stat-card--green">
            <div class="rh-stat-card__icon">⚡</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Experts</span>
                <span class="rh-stat-card__value"><?= number_format($stats['total_experts'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-stat-card rh-stat-card--amber">
            <div class="rh-stat-card__icon">📅</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Ce mois</span>
                <span class="rh-stat-card__value">+<?= number_format($stats['inscrits_ce_mois'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-stat-card rh-stat-card--orange">
            <div class="rh-stat-card__icon">🔥</div>
            <div class="rh-stat-card__body">
                <span class="rh-stat-card__label">Actifs 30j</span>
                <span class="rh-stat-card__value"><?= number_format($stats['actifs_30j'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- ══ ALERTES IA ═══════════════════════════════════════════════ -->
    <?php if (!empty($dashboard['alertes'])): ?>
    <div class="rh-alertes">
        <h3 class="rh-alertes__title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Points d'attention
        </h3>
        <div class="rh-alertes__list">
            <?php foreach ($dashboard['alertes'] as $alerte): ?>
            <div class="rh-alerte rh-alerte--<?= \App\Core\Security::escape($alerte['type'] ?? 'info') ?>">
                <?= \App\Core\Security::escape($alerte['message'] ?? '') ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ AGENTS IA ════════════════════════════════════════════════ -->
    <div class="rh-section">
        <h2 class="rh-section__title">Vos Agents IA</h2>
        <p class="rh-section__sub">Chaque agent est spécialisé dans un domaine RH. Cliquez pour démarrer une session.</p>
    </div>

    <div class="rh-agents-grid">
        <?php
        $agentRoutes = ['inscriptions' => '/rh/inscriptions', 'profils' => '/rh/profils', 'marketing' => '/rh/marketing', 'manager' => '/rh/manager'];
        $agentKeys   = ['inscriptions', 'profils', 'marketing', 'manager'];
        foreach ($agentKeys as $i => $key):
            $a = $agents[$i] ?? \App\Services\RhAiService::getAgentInfo($key);
        ?>
        <a href="<?= $baseUrl . $agentRoutes[$key] ?>" class="rh-agent-card" style="--agent-color:<?= \App\Core\Security::escape($a['couleur']) ?>; --agent-gradient:<?= \App\Core\Security::escape($a['gradient']) ?>">
            <div class="rh-agent-card__glow"></div>
            <div class="rh-agent-card__header">
                <div class="rh-agent-card__avatar">
                    <span class="rh-agent-card__emoji"><?= $a['emoji'] ?></span>
                </div>
                <div class="rh-agent-card__status">
                    <span class="rh-agent-card__dot <?= $ia_active ? 'rh-agent-card__dot--active' : '' ?>"></span>
                    <?= $ia_active ? 'En ligne' : 'Hors ligne' ?>
                </div>
            </div>
            <div class="rh-agent-card__body">
                <h3 class="rh-agent-card__name"><?= \App\Core\Security::escape($a['nom']) ?></h3>
                <p class="rh-agent-card__titre"><?= \App\Core\Security::escape($a['titre']) ?></p>
                <p class="rh-agent-card__desc"><?= \App\Core\Security::escape($a['description']) ?></p>
            </div>
            <div class="rh-agent-card__footer">
                <span class="rh-agent-card__btn">Ouvrir l'agent →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ══ GUIDE RAPIDE ═════════════════════════════════════════════ -->
    <div class="rh-guide">
        <h3 class="rh-guide__title">💡 Guide rapide</h3>
        <div class="rh-guide__grid">
            <div class="rh-guide__item">
                <span class="rh-guide__emoji">🎓</span>
                <div><strong>ARIA — Inscriptions</strong><br>Validez les profils Professeurs & Étudiants avec l'IA. Détectez les incomplets, obtenez des suggestions de relance.</div>
            </div>
            <div class="rh-guide__item">
                <span class="rh-guide__emoji">👤</span>
                <div><strong>PROFIA — Profils</strong><br>Analysez la qualité des profils Clients & Experts. Obtenez des scores et suggestions d'amélioration automatiques.</div>
            </div>
            <div class="rh-guide__item">
                <span class="rh-guide__emoji">📊</span>
                <div><strong>MARKIA — Marketing</strong><br>Segmentez vos utilisateurs et générez des recommandations de campagnes adaptées au marché africain.</div>
            </div>
            <div class="rh-guide__item">
                <span class="rh-guide__emoji">🎯</span>
                <div><strong>MAIA — Manager</strong><br>Vue 360° de la plateforme. Posez n'importe quelle question analytique et obtenez des insights stratégiques.</div>
            </div>
        </div>
    </div>

</div>
