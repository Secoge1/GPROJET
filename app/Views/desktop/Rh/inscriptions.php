<?php
$baseUrl               = rtrim(BASE_URL ?? '', '/');
$agentInfo             = $agentInfo ?? [];
$welcomeAnalysis       = $welcomeAnalysis ?? '';
$ia_active             = $ia_active ?? false;
$agentType             = 'inscriptions';
$inscriptionsProfs     = $inscriptionsProfs ?? [];
$inscriptionsEtudiants = $inscriptionsEtudiants ?? [];
$statsInscriptions     = $statsInscriptions ?? [];
$e = fn($s) => \App\Core\Security::escape((string)($s ?? ''));

// Couleurs d'avatar par initiale
$avatarColors = [
    'A'=>'#6366f1','B'=>'#8b5cf6','C'=>'#ec4899','D'=>'#f43f5e','E'=>'#f97316',
    'F'=>'#eab308','G'=>'#84cc16','H'=>'#10b981','I'=>'#06b6d4','J'=>'#3b82f6',
    'K'=>'#6366f1','L'=>'#a855f7','M'=>'#ec4899','N'=>'#14b8a6','O'=>'#f59e0b',
    'P'=>'#ef4444','Q'=>'#8b5cf6','R'=>'#0ea5e9','S'=>'#10b981','T'=>'#f97316',
    'U'=>'#6366f1','V'=>'#84cc16','W'=>'#ec4899','X'=>'#f43f5e','Y'=>'#a855f7','Z'=>'#06b6d4',
];
$getColor = function(string $nom) use ($avatarColors): string {
    $l = strtoupper(substr(trim($nom), 0, 1));
    return $avatarColors[$l] ?? '#475569';
};
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">

<div class="rh-page rh-page--agent">

    <!-- ══ HEADER AGENT ════════════════════════════════════════════════ -->
    <div class="rh-agent-header" style="--agent-gradient:<?= $e($agentInfo['gradient'] ?? '') ?>">
        <div class="rh-agent-header__left">
            <a href="<?= $baseUrl ?>/rh" class="rh-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Espace RH
            </a>
            <div class="rh-agent-header__info">
                <div class="rh-agent-header__avatar-wrap" style="background:<?= $e($agentInfo['gradient'] ?? '') ?>">
                    <span><?= $e($agentInfo['emoji'] ?? '🎓') ?></span>
                </div>
                <div>
                    <h1 class="rh-agent-header__name"><?= $e($agentInfo['nom'] ?? 'ARIA') ?></h1>
                    <p class="rh-agent-header__titre"><?= $e($agentInfo['titre'] ?? '') ?></p>
                </div>
            </div>
        </div>
        <div class="rh-agent-header__kpis">
            <div class="rh-agent-kpi">
                <span class="rh-agent-kpi__val"><?= count($inscriptionsProfs) + count($inscriptionsEtudiants) ?></span>
                <span class="rh-agent-kpi__label">Récents</span>
            </div>
            <div class="rh-agent-kpi rh-agent-kpi--warn">
                <span class="rh-agent-kpi__val"><?= (int)($statsInscriptions['profs_en_attente'] ?? 0) ?></span>
                <span class="rh-agent-kpi__label">En attente</span>
            </div>
            <div class="rh-agent-kpi rh-agent-kpi--ok">
                <span class="rh-agent-kpi__val"><?= (int)($statsInscriptions['profs_valides'] ?? 0) ?></span>
                <span class="rh-agent-kpi__label">Validés</span>
            </div>
        </div>
    </div>

    <div class="rh-agent-layout">

        <!-- ══ PANNEAU GAUCHE : Données ════════════════════════════════ -->
        <div class="rh-agent-data">

            <!-- Onglets -->
            <div class="rh-tabs">
                <button class="rh-tab rh-tab--active" onclick="rhTab('profs', this)">
                    🎓 Professeurs
                    <span class="rh-tab-count"><?= count($inscriptionsProfs) ?></span>
                </button>
                <button class="rh-tab" onclick="rhTab('etuds', this)">
                    📚 Étudiants
                    <span class="rh-tab-count"><?= count($inscriptionsEtudiants) ?></span>
                </button>
            </div>

            <!-- ── Table Professeurs ── -->
            <div class="rh-tab-content" id="rh-tab-profs">
                <?php if (empty($inscriptionsProfs)): ?>
                <div class="rh-empty-state">
                    <span class="rh-empty-state__icon">🎓</span>
                    <p>Aucune inscription professeur récente</p>
                </div>
                <?php else: ?>
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th style="width:34%">Professeur</th>
                                <th style="width:28%">Email</th>
                                <th style="width:10%">Pays</th>
                                <th style="width:10%">Statut</th>
                                <th style="width:10%">Inscrit le</th>
                                <th style="width:8%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscriptionsProfs as $p):
                                $fullName = trim($p['nom'] ?? '');
                                $initiale = strtoupper(substr($fullName ?: '?', 0, 1));
                                $color    = $getColor($fullName);
                            ?>
                            <tr>
                                <td>
                                    <div class="rh-user-cell">
                                        <?php if (!empty($p['photo'])): ?>
                                        <img src="<?= $e($baseUrl . '/uploads/' . $p['photo']) ?>"
                                             alt="<?= $e($fullName) ?>"
                                             class="rh-avatar-sm"
                                             onerror="this.outerHTML='<span class=\'rh-avatar-sm rh-avatar-placeholder\' style=\'background:<?= $color ?>\'>'+this.alt.charAt(0).toUpperCase()+'</span>'">
                                        <?php else: ?>
                                        <span class="rh-avatar-sm rh-avatar-placeholder" style="background:<?= $color ?>"><?= $initiale ?></span>
                                        <?php endif; ?>
                                        <div class="rh-user-cell__info">
                                            <span class="rh-user-cell__name"><?= $e($fullName ?: 'Inconnu') ?></span>
                                            <span class="rh-user-cell__role">Professeur</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="rh-email-cell" title="<?= $e($p['email'] ?? '') ?>">
                                        <?= $e($p['email'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="rh-pays-cell"><?= $e($p['pays'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <span class="rh-badge <?= $p['actif'] ? 'rh-badge--ok' : 'rh-badge--warn' ?>">
                                        <?= $p['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="rh-date-cell">
                                        <?php
                                        $ts = strtotime($p['created_at'] ?? '');
                                        echo $ts ? date('d/m/Y', $ts) : '—';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rh-actions-cell">
                                        <button class="rh-btn-icon rh-btn-icon--ai"
                                                onclick="rhAnalyseUser('professeur', <?= (int)$p['id'] ?>, '<?= $e($fullName) ?>')"
                                                title="Analyser avec l'IA">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.5 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-4.5M21 3l-9 9M15 3h6v6"/></svg>
                                        </button>
                                        <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$p['id'] ?>"
                                           class="rh-btn-icon"
                                           target="_blank"
                                           title="Voir le profil">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Table Étudiants ── -->
            <div class="rh-tab-content" id="rh-tab-etuds" style="display:none">
                <?php if (empty($inscriptionsEtudiants)): ?>
                <div class="rh-empty-state">
                    <span class="rh-empty-state__icon">📚</span>
                    <p>Aucune inscription étudiant récente</p>
                </div>
                <?php else: ?>
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th style="width:34%">Étudiant</th>
                                <th style="width:28%">Email</th>
                                <th style="width:10%">Pays</th>
                                <th style="width:10%">Statut</th>
                                <th style="width:10%">Inscrit le</th>
                                <th style="width:8%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscriptionsEtudiants as $et):
                                $fullName = trim($et['nom'] ?? '');
                                $initiale = strtoupper(substr($fullName ?: '?', 0, 1));
                                $color    = $getColor($fullName);
                            ?>
                            <tr>
                                <td>
                                    <div class="rh-user-cell">
                                        <span class="rh-avatar-sm rh-avatar-placeholder" style="background:<?= $color ?>"><?= $initiale ?></span>
                                        <div class="rh-user-cell__info">
                                            <span class="rh-user-cell__name"><?= $e($fullName ?: 'Inconnu') ?></span>
                                            <span class="rh-user-cell__role">Étudiant</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="rh-email-cell" title="<?= $e($et['email'] ?? '') ?>">
                                        <?= $e($et['email'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="rh-pays-cell"><?= $e($et['pays'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <span class="rh-badge <?= $et['actif'] ? 'rh-badge--ok' : 'rh-badge--warn' ?>">
                                        <?= $et['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="rh-date-cell">
                                        <?php
                                        $ts = strtotime($et['created_at'] ?? '');
                                        echo $ts ? date('d/m/Y', $ts) : '—';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rh-actions-cell">
                                        <button class="rh-btn-icon rh-btn-icon--ai"
                                                onclick="rhAnalyseUser('etudiant', <?= (int)$et['id'] ?>, '<?= $e($fullName) ?>')"
                                                title="Analyser avec l'IA">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.5 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-4.5M21 3l-9 9M15 3h6v6"/></svg>
                                        </button>
                                        <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$et['id'] ?>"
                                           class="rh-btn-icon"
                                           target="_blank"
                                           title="Voir le profil">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.rh-agent-data -->

        <!-- ══ PANNEAU DROIT : Chat IA ══════════════════════════════════ -->
        <div class="rh-agent-chat">
            <?php include __DIR__ . '/_chat_widget.php'; ?>
        </div>

    </div><!-- /.rh-agent-layout -->
</div>

<script>
function rhTab(id, btn) {
    document.querySelectorAll('.rh-tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.rh-tab').forEach(t => t.classList.remove('rh-tab--active'));
    document.getElementById('rh-tab-' + id).style.display = 'block';
    btn.classList.add('rh-tab--active');
}

function rhAnalyseUser(role, id, nom) {
    const msg = `Analyse le profil ${role} de "${nom}" (ID: ${id}). Évalue la complétude du profil, identifie les informations manquantes et donne-moi 3 recommandations concrètes.`;
    if (typeof window.rhChatSend === 'function') window.rhChatSend(msg);
}
</script>
