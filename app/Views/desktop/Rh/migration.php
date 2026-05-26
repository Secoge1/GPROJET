<?php
/**
 * Page de migration RH — Accessible uniquement aux admins
 * URL : /rh/migration
 */
$baseUrl = rtrim(BASE_URL ?? '', '/');
$result  = $migrationResult ?? null;
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">
<div class="rh-page">
    <div class="rh-header">
        <div class="rh-header__left">
            <div class="rh-header__badge"><span class="rh-pulse"></span>Migration BDD</div>
            <h1 class="rh-header__title">Migration — Tables RH</h1>
            <p class="rh-header__sub">Crée les tables nécessaires au module RH avec IA</p>
        </div>
        <a href="<?= $baseUrl ?>/rh" class="rh-btn rh-btn--ghost">← Retour</a>
    </div>

    <?php if ($result !== null): ?>
    <div class="rh-alerte rh-alerte--<?= $result['success'] ? 'info' : 'error' ?>" style="margin-bottom:16px;padding:16px;font-size:.9rem;">
        <?php if ($result['success']): ?>
        ✅ Migration terminée : <?= (int)$result['ok'] ?> table(s) créée(s)/vérifiée(s).
        <?php else: ?>
        ❌ Erreurs : <?= \App\Core\Security::escape(implode('<br>', $result['errors'])) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="rh-guide" style="max-width:600px;">
        <h3 class="rh-guide__title">Tables qui seront créées</h3>
        <ul style="color:var(--rh-text-muted);font-size:.875rem;line-height:2;">
            <li><code>rh_ia_logs</code> — Historique des conversations avec les agents IA</li>
            <li><code>rh_ia_analyses</code> — Analyses générées par l'IA</li>
            <li><code>rh_notes</code> — Notes RH manuelles sur les utilisateurs</li>
            <li><code>rh_marketing_recommandations</code> — Recommandations marketing IA</li>
        </ul>
        <form method="POST" action="<?= $baseUrl ?>/rh/migration" style="margin-top:20px;">
            <?= \App\Core\Security::getCsrfField() ?>
            <button type="submit" class="rh-btn rh-btn--primary" style="font-size:.9rem;padding:12px 24px;">
                🚀 Exécuter la migration
            </button>
        </form>
    </div>
</div>
