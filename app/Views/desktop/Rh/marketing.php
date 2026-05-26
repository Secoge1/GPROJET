<?php
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$agentInfo       = $agentInfo ?? [];
$welcomeAnalysis = $welcomeAnalysis ?? '';
$ia_active       = $ia_active ?? false;
$agentType       = 'marketing';
$segments        = $segments ?? [];
$recommandations = $recommandations ?? [];
$e = fn($s) => \App\Core\Security::escape((string)($s ?? ''));
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">

<div class="rh-page rh-page--agent">

    <div class="rh-agent-header" style="--agent-gradient:<?= $e($agentInfo['gradient'] ?? '') ?>">
        <a href="<?= $baseUrl ?>/rh" class="rh-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Espace RH
        </a>
        <div class="rh-agent-header__info">
            <span class="rh-agent-header__emoji"><?= $e($agentInfo['emoji'] ?? '📊') ?></span>
            <div>
                <h1 class="rh-agent-header__name"><?= $e($agentInfo['nom'] ?? 'MARKIA') ?></h1>
                <p class="rh-agent-header__titre"><?= $e($agentInfo['titre'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="rh-agent-layout">

        <div class="rh-agent-data">

            <!-- Segmentation -->
            <div class="rh-marketing-grids">

                <!-- Répartition par rôle -->
                <div class="rh-mkt-card">
                    <h3 class="rh-mkt-card__title">📊 Répartition par rôle</h3>
                    <div class="rh-mkt-bars">
                        <?php
                        $parRole = $segments['par_role'] ?? [];
                        $totalRole = max(1, array_sum(array_column($parRole, 'nb')));
                        $roleColors = ['client'=>'#0ea5e9','expert'=>'#10b981','etudiant'=>'#6366f1','professeur'=>'#f59e0b','admin'=>'#ef4444'];
                        foreach ($parRole as $r):
                            $pct = round(($r['nb'] / $totalRole) * 100);
                        ?>
                        <div class="rh-mkt-bar-row">
                            <span class="rh-mkt-bar-label"><?= $e($r['role']) ?></span>
                            <div class="rh-mkt-bar-track">
                                <div class="rh-mkt-bar-fill" style="width:<?= $pct ?>%;background:<?= $roleColors[$r['role']] ?? '#6b7280' ?>"></div>
                            </div>
                            <span class="rh-mkt-bar-val"><?= number_format((int)$r['nb']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($parRole)): ?><p class="rh-table-empty">Aucune donnée</p><?php endif; ?>
                    </div>
                </div>

                <!-- Top pays -->
                <div class="rh-mkt-card">
                    <h3 class="rh-mkt-card__title">🌍 Top pays</h3>
                    <div class="rh-mkt-country-list">
                        <?php
                        $flags = ['Mali'=>'🇲🇱','Senegal'=>'🇸🇳','Sénégal'=>'🇸🇳','Cote d\'Ivoire'=>'🇨🇮','Côte d\'Ivoire'=>'🇨🇮','Bénin'=>'🇧🇯','Benin'=>'🇧🇯','Niger'=>'🇳🇪','France'=>'🇫🇷','Guinee'=>'🇬🇳','Guinée'=>'🇬🇳'];
                        foreach ($segments['par_pays'] ?? [] as $pays):
                            $flag = $flags[$pays['pays']] ?? '🌍';
                        ?>
                        <div class="rh-mkt-country-row">
                            <span><?= $flag ?> <?= $e($pays['pays']) ?></span>
                            <strong><?= number_format((int)$pays['nb']) ?></strong>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($segments['par_pays'])): ?><p class="rh-table-empty">Aucune donnée pays</p><?php endif; ?>
                    </div>
                </div>

                <!-- Évolution -->
                <div class="rh-mkt-card rh-mkt-card--full">
                    <h3 class="rh-mkt-card__title">📈 Évolution inscriptions (6 mois)</h3>
                    <div class="rh-mkt-evolution">
                        <?php
                        $evolution = $segments['evolution'] ?? [];
                        $maxEv = max(1, ...array_column($evolution, 'nb'));
                        foreach ($evolution as $ev):
                            $h = round(($ev['nb'] / $maxEv) * 80);
                        ?>
                        <div class="rh-mkt-ev-bar">
                            <div class="rh-mkt-ev-bar__fill" style="height:<?= $h ?>px" title="<?= $e($ev['mois']) ?>: <?= (int)$ev['nb'] ?>"></div>
                            <span class="rh-mkt-ev-bar__label"><?= $e(substr($ev['mois'], 5)) ?></span>
                            <span class="rh-mkt-ev-bar__val"><?= (int)$ev['nb'] ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($evolution)): ?><p class="rh-table-empty">Aucune donnée</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recommandations sauvegardées -->
            <?php if (!empty($recommandations)): ?>
            <div class="rh-recommandations">
                <h3 class="rh-recommandations__title">💡 Recommandations en cours</h3>
                <div class="rh-reco-list">
                    <?php foreach (array_slice($recommandations, 0, 5) as $reco): ?>
                    <div class="rh-reco-item">
                        <div class="rh-reco-item__header">
                            <strong><?= $e($reco['titre']) ?></strong>
                            <span class="rh-badge rh-badge--<?= $reco['statut'] === 'approuvee' ? 'ok' : 'info' ?>"><?= $e($reco['statut']) ?></span>
                        </div>
                        <p><?= $e($reco['description']) ?></p>
                        <small>Segment : <?= $e($reco['segment']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bouton générer recommandation -->
            <button class="rh-btn rh-btn--primary rh-btn--full" onclick="window.rhChatSend('Génère 3 recommandations marketing prioritaires basées sur les données actuelles. Pour chaque recommandation, précise le segment cible, l\'action à mener et le résultat attendu.')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Générer des recommandations IA
            </button>
        </div>

        <!-- Chat IA -->
        <div class="rh-agent-chat">
            <?php include __DIR__ . '/_chat_widget.php'; ?>
        </div>
    </div>
</div>
