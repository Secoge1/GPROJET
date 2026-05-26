<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$agentInfo      = $agentInfo ?? [];
$welcomeAnalysis = $welcomeAnalysis ?? '';
$ia_active      = $ia_active ?? false;
$agentType      = 'profils';
$profilsExperts = $profilsExperts ?? [];
$profilsClients = $profilsClients ?? [];
$scoresMoyens   = $scoresMoyens ?? ['experts'=>0,'clients'=>0];
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
            <span class="rh-agent-header__emoji"><?= $e($agentInfo['emoji'] ?? '👤') ?></span>
            <div>
                <h1 class="rh-agent-header__name"><?= $e($agentInfo['nom'] ?? 'PROFIA') ?></h1>
                <p class="rh-agent-header__titre"><?= $e($agentInfo['titre'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="rh-agent-layout">

        <div class="rh-agent-data">

            <!-- Scores globaux -->
            <div class="rh-score-overview">
                <div class="rh-score-card">
                    <div class="rh-score-ring" style="--score:<?= $scoresMoyens['experts'] ?>">
                        <svg viewBox="0 0 36 36" class="rh-score-svg">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="<?= $e($agentInfo['couleur'] ?? '#0ea5e9') ?>" stroke-width="3" stroke-dasharray="<?= $scoresMoyens['experts'] ?>, 100" stroke-linecap="round"/>
                        </svg>
                        <span class="rh-score-ring__val"><?= $scoresMoyens['experts'] ?>%</span>
                    </div>
                    <div class="rh-score-card__label">Score moyen<br><strong>Experts</strong></div>
                </div>
                <div class="rh-score-card">
                    <div class="rh-score-ring" style="--score:<?= $scoresMoyens['clients'] ?>">
                        <svg viewBox="0 0 36 36" class="rh-score-svg">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#f59e0b" stroke-width="3" stroke-dasharray="<?= $scoresMoyens['clients'] ?>, 100" stroke-linecap="round"/>
                        </svg>
                        <span class="rh-score-ring__val"><?= $scoresMoyens['clients'] ?>%</span>
                    </div>
                    <div class="rh-score-card__label">Score moyen<br><strong>Clients</strong></div>
                </div>
                <div class="rh-score-summary">
                    <p>📊 <strong><?= count($profilsExperts) ?></strong> profils experts analysés</p>
                    <p>💼 <strong><?= count($profilsClients) ?></strong> profils clients analysés</p>
                    <?php
                    $expertsBas = count(array_filter($profilsExperts, fn($p) => ($p['score_profil'] ?? 0) < 60));
                    ?>
                    <?php if ($expertsBas > 0): ?>
                    <p class="rh-score-warn">⚠️ <strong><?= $expertsBas ?></strong> expert(s) avec score < 60%</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Onglets -->
            <div class="rh-tabs">
                <button class="rh-tab rh-tab--active" onclick="rhTab('experts', this)">⚡ Experts (<?= count($profilsExperts) ?>)</button>
                <button class="rh-tab" onclick="rhTab('clients', this)">💼 Clients (<?= count($profilsClients) ?>)</button>
            </div>

            <!-- Table Experts -->
            <div class="rh-tab-content" id="rh-tab-experts">
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr><th>Expert</th><th>Spécialité</th><th>Tarif/h</th><th>Score Profil</th><th>Statut</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($profilsExperts)): ?>
                            <tr><td colspan="6" class="rh-table-empty">Aucun expert trouvé</td></tr>
                            <?php else: foreach ($profilsExperts as $ex): $score = (int)($ex['score_profil'] ?? 0); ?>
                            <tr>
                                <td>
                                    <div class="rh-user-cell">
                                        <?php
                                        $nomEx  = trim($ex['nom'] ?? '');
                                        $initEx = strtoupper(substr($nomEx ?: '?', 0, 1));
                                        ?>
                                        <?php if (!empty($ex['photo'])): ?>
                                        <img src="<?= $e($baseUrl . '/uploads/' . $ex['photo']) ?>"
                                             alt="<?= $e($nomEx) ?>"
                                             class="rh-avatar-sm"
                                             onerror="this.outerHTML='<span class=\'rh-avatar-sm rh-avatar-placeholder\' style=\'background:<?= $e($agentInfo['couleur'] ?? '#0ea5e9') ?>\'><?= $initEx ?></span>'">
                                        <?php else: ?>
                                        <span class="rh-avatar-sm rh-avatar-placeholder" style="background:<?= $e($agentInfo['couleur'] ?? '#0ea5e9') ?>"><?= $initEx ?></span>
                                        <?php endif; ?>
                                        <div class="rh-user-cell__info">
                                            <span class="rh-user-cell__name"><?= $e($nomEx ?: 'Inconnu') ?></span>
                                            <span class="rh-user-cell__role"><?= $e($ex['pays'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="rh-pays-cell"><?= $e($ex['specialite'] ?? '—') ?></span></td>
                                <td><span class="rh-date-cell"><?= $ex['tarif_heure'] ? number_format((float)$ex['tarif_heure']) . ' FCFA' : '—' ?></span></td>
                                <td>
                                    <div class="rh-score-bar">
                                        <div class="rh-score-bar__fill <?= $score >= 80 ? 'rh-score-bar__fill--ok' : ($score >= 60 ? 'rh-score-bar__fill--mid' : 'rh-score-bar__fill--low') ?>" style="width:<?= $score ?>%"></div>
                                    </div>
                                    <small><?= $score ?>%</small>
                                </td>
                                <td><span class="rh-badge <?= $ex['valide'] ? 'rh-badge--ok' : 'rh-badge--warn' ?>"><?= $ex['valide'] ? 'Validé' : 'En attente' ?></span></td>
                                <td>
                                    <button class="rh-btn-sm rh-btn-sm--ai" onclick="rhAnalyseProfil('expert', <?= (int)$ex['id'] ?>, '<?= $e($ex['nom'] ?? '') ?>', <?= $score ?>)">🤖 Analyser</button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table Clients -->
            <div class="rh-tab-content" id="rh-tab-clients" style="display:none">
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr><th>Client</th><th>Pays</th><th>Score Profil</th><th>Inscrit le</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($profilsClients)): ?>
                            <tr><td colspan="5" class="rh-table-empty">Aucun client trouvé</td></tr>
                            <?php else: foreach ($profilsClients as $cl): $score = (int)($cl['score_profil'] ?? 0); ?>
                            <tr>
                                <td>
                                    <div class="rh-user-cell">
                                        <?php
                                        $nomCl  = trim($cl['nom'] ?? '');
                                        $initCl = strtoupper(substr($nomCl ?: '?', 0, 1));
                                        ?>
                                        <span class="rh-avatar-sm rh-avatar-placeholder" style="background:#f59e0b"><?= $initCl ?></span>
                                        <div class="rh-user-cell__info">
                                            <span class="rh-user-cell__name"><?= $e($nomCl ?: 'Inconnu') ?></span>
                                            <span class="rh-user-cell__role"><?= $e($cl['email'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="rh-pays-cell"><?= $e($cl['pays'] ?? '—') ?></span></td>
                                <td>
                                    <div class="rh-score-bar">
                                        <div class="rh-score-bar__fill <?= $score >= 80 ? 'rh-score-bar__fill--ok' : ($score >= 60 ? 'rh-score-bar__fill--mid' : 'rh-score-bar__fill--low') ?>" style="width:<?= $score ?>%"></div>
                                    </div>
                                    <small><?= $score ?>%</small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($cl['created_at'] ?? 'now')) ?></td>
                                <td><button class="rh-btn-sm rh-btn-sm--ai" onclick="rhAnalyseProfil('client', <?= (int)$cl['id'] ?>, '<?= $e($cl['nom'] ?? '') ?>', <?= $score ?>)">🤖 Analyser</button></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Chat IA -->
        <div class="rh-agent-chat">
            <?php include __DIR__ . '/_chat_widget.php'; ?>
        </div>
    </div>
</div>

<script>
function rhTab(id, btn) {
    document.querySelectorAll('.rh-tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.rh-tab').forEach(t => t.classList.remove('rh-tab--active'));
    document.getElementById('rh-tab-' + id).style.display = 'block';
    btn.classList.add('rh-tab--active');
}
function rhAnalyseProfil(role, id, nom, score) {
    window.rhChatSend(`Analyse le profil ${role} de "${nom}" (ID: ${id}, score actuel: ${score}%). Quelles améliorations suggères-tu ?`);
}
</script>
