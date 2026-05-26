<?php
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$agentInfo       = $agentInfo ?? [];
$welcomeAnalysis = $welcomeAnalysis ?? '';
$ia_active       = $ia_active ?? false;
$agentType       = 'manager';
$dashboard       = $dashboard ?? [];
$stats           = $dashboard['stats'] ?? [];
$alertes         = $dashboard['alertes'] ?? [];
$segments        = $dashboard['segments'] ?? [];
$e = fn($s) => \App\Core\Security::escape((string)($s ?? ''));

$totalUsers = ($stats['total_professeurs'] ?? 0) + ($stats['total_etudiants'] ?? 0)
            + ($stats['total_clients'] ?? 0) + ($stats['total_experts'] ?? 0);
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">

<div class="rh-page rh-page--agent rh-page--manager">

    <div class="rh-agent-header rh-agent-header--manager" style="--agent-gradient:<?= $e($agentInfo['gradient'] ?? '') ?>">
        <a href="<?= $baseUrl ?>/rh" class="rh-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Espace RH
        </a>
        <div class="rh-agent-header__info">
            <span class="rh-agent-header__emoji"><?= $e($agentInfo['emoji'] ?? '🎯') ?></span>
            <div>
                <h1 class="rh-agent-header__name"><?= $e($agentInfo['nom'] ?? 'MAIA') ?></h1>
                <p class="rh-agent-header__titre"><?= $e($agentInfo['titre'] ?? '') ?> — Vue 360° GLOBALO</p>
            </div>
        </div>
        <div class="rh-header-kpi">
            <div class="rh-kpi">
                <span class="rh-kpi__val"><?= number_format($totalUsers) ?></span>
                <span class="rh-kpi__label">Utilisateurs</span>
            </div>
            <div class="rh-kpi">
                <span class="rh-kpi__val">+<?= number_format($stats['inscrits_cette_semaine'] ?? 0) ?></span>
                <span class="rh-kpi__label">Cette semaine</span>
            </div>
            <div class="rh-kpi">
                <span class="rh-kpi__val"><?= number_format($stats['actifs_30j'] ?? 0) ?></span>
                <span class="rh-kpi__label">Actifs 30j</span>
            </div>
        </div>
    </div>

    <div class="rh-agent-layout">

        <div class="rh-agent-data">

            <!-- Alertes IA -->
            <?php if (!empty($alertes)): ?>
            <div class="rh-manager-alertes">
                <h3>🚨 Points d'attention</h3>
                <?php foreach ($alertes as $al): ?>
                <div class="rh-alerte rh-alerte--<?= $e($al['type'] ?? 'info') ?>">
                    <?= $e($al['message'] ?? '') ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="rh-manager-kpis">
                <div class="rh-manager-kpi rh-manager-kpi--purple">
                    <span class="rh-manager-kpi__icon">🎓</span>
                    <div>
                        <strong><?= number_format($stats['total_professeurs'] ?? 0) ?></strong>
                        <span>Professeurs</span>
                    </div>
                    <a href="<?= $baseUrl ?>/rh/inscriptions" class="rh-manager-kpi__link">Gérer →</a>
                </div>
                <div class="rh-manager-kpi rh-manager-kpi--indigo">
                    <span class="rh-manager-kpi__icon">📚</span>
                    <div>
                        <strong><?= number_format($stats['total_etudiants'] ?? 0) ?></strong>
                        <span>Étudiants</span>
                    </div>
                    <a href="<?= $baseUrl ?>/rh/inscriptions" class="rh-manager-kpi__link">Gérer →</a>
                </div>
                <div class="rh-manager-kpi rh-manager-kpi--cyan">
                    <span class="rh-manager-kpi__icon">💼</span>
                    <div>
                        <strong><?= number_format($stats['total_clients'] ?? 0) ?></strong>
                        <span>Clients</span>
                    </div>
                    <a href="<?= $baseUrl ?>/rh/profils" class="rh-manager-kpi__link">Profils →</a>
                </div>
                <div class="rh-manager-kpi rh-manager-kpi--green">
                    <span class="rh-manager-kpi__icon">⚡</span>
                    <div>
                        <strong><?= number_format($stats['total_experts'] ?? 0) ?></strong>
                        <span>Experts</span>
                    </div>
                    <a href="<?= $baseUrl ?>/rh/profils" class="rh-manager-kpi__link">Profils →</a>
                </div>
            </div>

            <!-- Accès rapide aux agents -->
            <div class="rh-manager-agents">
                <h3>🤖 Accès rapide aux agents</h3>
                <div class="rh-manager-agents__grid">
                    <?php
                    $agentLinks = [
                        ['key'=>'inscriptions','label'=>'ARIA — Inscriptions','url'=>'/rh/inscriptions','color'=>'#6366f1'],
                        ['key'=>'profils','label'=>'PROFIA — Profils','url'=>'/rh/profils','color'=>'#0ea5e9'],
                        ['key'=>'marketing','label'=>'MARKIA — Marketing','url'=>'/rh/marketing','color'=>'#f59e0b'],
                    ];
                    foreach ($agentLinks as $al):
                        $ai = \App\Services\RhAiService::getAgentInfo($al['key']);
                    ?>
                    <a href="<?= $baseUrl . $al['url'] ?>" class="rh-manager-agent-link" style="--c:<?= $al['color'] ?>">
                        <span><?= $ai['emoji'] ?></span>
                        <strong><?= $e($al['label']) ?></strong>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions rapides IA -->
            <div class="rh-manager-quick-actions">
                <h3>⚡ Demandes rapides à MAIA</h3>
                <div class="rh-quick-actions-grid">
                    <button onclick="window.rhChatSend('Génère un rapport hebdomadaire complet de la plateforme avec les points clés, les tendances et les recommandations prioritaires.')" class="rh-quick-action">
                        📋 Rapport hebdomadaire
                    </button>
                    <button onclick="window.rhChatSend('Quels sont les 3 risques opérationnels principaux de la plateforme en ce moment ?')" class="rh-quick-action">
                        🚨 Risques opérationnels
                    </button>
                    <button onclick="window.rhChatSend('Compare les performances par pays et identifie le marché avec le plus fort potentiel de croissance.')" class="rh-quick-action">
                        🌍 Analyse par pays
                    </button>
                    <button onclick="window.rhChatSend('Quelles sont les opportunités de croissance les plus importantes pour les 30 prochains jours ?')" class="rh-quick-action">
                        🚀 Opportunités de croissance
                    </button>
                    <button onclick="window.rhChatSend('Analyse la rétention utilisateurs et propose des actions pour réduire le churn.')" class="rh-quick-action">
                        💧 Analyse churn
                    </button>
                    <button onclick="window.rhChatSend('Donne-moi les KPIs les plus importants à surveiller cette semaine avec leurs seuils d\'alerte.')" class="rh-quick-action">
                        📊 KPIs à surveiller
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat IA -->
        <div class="rh-agent-chat">
            <?php include __DIR__ . '/_chat_widget.php'; ?>
        </div>
    </div>
</div>
