<?php
/**
 * GLOBALO — Page Maintenance
 * Activée automatiquement si le fichier .maintenance existe à la racine du projet.
 *
 * Contenu optionnel JSON :
 * {
 *   "action":   "deploy" | "migration" | "pays" | "patch" | "backup" | "config",
 *   "message":  "Texte libre affiché sous le titre",
 *   "eta":      "2026-06-01 14:00",
 *   "progress": 60,
 *   "contact":  "admin@globalo.secogesarl.com"
 * }
 */
http_response_code(503);
header('Retry-After: 3600');
header('Content-Type: text/html; charset=UTF-8');

// ── Lecture du fichier .maintenance ──────────────────────────────────────────
$maintenanceFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.maintenance';
$meta = [
    'action'   => 'deploy',
    'message'  => '',
    'eta'      => '',
    'contact'  => 'admin@globalo.secogesarl.com',
    'progress' => 0,
];
if (is_file($maintenanceFile)) {
    $raw = @file_get_contents($maintenanceFile);
    if ($raw) {
        $decoded = @json_decode($raw, true);
        if (is_array($decoded)) {
            $meta = array_merge($meta, $decoded);
        }
    }
}

$etaTs        = $meta['eta'] ? strtotime((string) $meta['eta']) : 0;
$etaFormatted = ($etaTs && $etaTs > time()) ? date('d/m/Y à H:i', $etaTs) : '';
$progress     = min(100, max(0, (int) $meta['progress']));
$action       = (string) $meta['action'];

// ── Configuration par type d'action ──────────────────────────────────────────
$actions = [
    'deploy' => [
        'badge' => 'Déploiement en cours',
        'title' => 'Mise à jour en cours',
        'icon'  => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
        'steps' => [
            ['done',   'Sauvegarde fichiers',               'Copie sécurisée effectuée'],
            ['active', 'Déploiement des nouveaux fichiers', 'Upload en cours via FTP…'],
            ['wait',   'Vérification de l\'intégrité',      'Contrôle des fichiers'],
            ['wait',   'Remise en ligne',                   'Suppression du flag maintenance'],
        ],
    ],
    'migration' => [
        'badge' => 'Migration base de données',
        'title' => 'Migration en cours',
        'icon'  => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'steps' => [
            ['done',   'Sauvegarde base de données',     'Dump SQL sécurisé'],
            ['active', 'Exécution des migrations',       'Modification des tables…'],
            ['wait',   'Vérification des données',       'Contrôle d\'intégrité'],
            ['wait',   'Remise en ligne',                'Redémarrage des services'],
        ],
    ],
    'pays' => [
        'badge' => 'Mise à jour des pays',
        'title' => 'Changement de zone géographique',
        'icon'  => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'steps' => [
            ['done',   'Mise à jour du code source',     'Références pays modifiées'],
            ['active', 'Déploiement en production',      'Upload des fichiers en cours…'],
            ['wait',   'Test paiements',                 'Vérification PayTech / Wave'],
            ['wait',   'Remise en ligne',                'Validation finale'],
        ],
    ],
    'patch' => [
        'badge' => 'Correctif urgent',
        'title' => 'Correctif appliqué',
        'icon'  => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'steps' => [
            ['done',   'Identification du bug',          'Correctif développé'],
            ['active', 'Application du patch',           'Déploiement en cours…'],
            ['wait',   'Tests de régression',            'Vérification du fix'],
            ['wait',   'Remise en ligne',                'Surveillance post-déploiement'],
        ],
    ],
    'backup' => [
        'badge' => 'Sauvegarde & maintenance',
        'title' => 'Maintenance préventive',
        'icon'  => '<polyline points="20 21 20 8 12 2 4 8 4 21"/><polyline points="9 21 9 12 15 12 15 21"/><line x1="12" y1="2" x2="12" y2="12"/>',
        'steps' => [
            ['active', 'Sauvegarde complète',            'Base de données + fichiers…'],
            ['wait',   'Optimisation des tables',        'OPTIMIZE TABLE en cours'],
            ['wait',   'Nettoyage des logs',             'Rotation des fichiers log'],
            ['wait',   'Remise en ligne',                'Vérification finale'],
        ],
    ],
    'config' => [
        'badge' => 'Mise à jour configuration',
        'title' => 'Reconfiguration du serveur',
        'icon'  => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>',
        'steps' => [
            ['done',   'Arrêt des services',             'Mode maintenance activé'],
            ['active', 'Application configuration',     'Mise à jour des paramètres…'],
            ['wait',   'Rechargement des services',      'Nginx / PHP-FPM reload'],
            ['wait',   'Tests de connexion',             'Vérification complète'],
        ],
    ],
];

$cfg   = $actions[$action] ?? $actions['deploy'];
$steps = $cfg['steps'];

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="60">
    <title>GLOBALO — Maintenance en cours</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:   #16a34a;
            --green-l: #dcfce7;
            --blue:    #0ea5e9;
            --slate:   #1e293b;
            --muted:   #64748b;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow: hidden;
            color: #fff;
        }

        /* Particules animées */
        .bg-particles { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .particle {
            position: absolute; border-radius: 50%; opacity: 0.06;
            animation: float linear infinite;
        }
        .particle:nth-child(1) { width:280px;height:280px;background:var(--green);top:-5%;left:10%;animation-duration:20s; }
        .particle:nth-child(2) { width:180px;height:180px;background:var(--blue);top:60%;left:-5%;animation-duration:25s;animation-delay:-8s; }
        .particle:nth-child(3) { width:350px;height:350px;background:var(--green);top:70%;right:-8%;animation-duration:30s;animation-delay:-15s; }
        .particle:nth-child(4) { width:120px;height:120px;background:var(--blue);top:20%;right:15%;animation-duration:18s;animation-delay:-5s; }

        @keyframes float {
            0%   { transform: translateY(0) rotate(0deg); }
            50%  { transform: translateY(-40px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }

        /* Carte */
        .card {
            position: relative; z-index: 10;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 3rem 3.5rem;
            max-width: 620px; width: 100%;
            box-shadow: 0 32px 80px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            text-align: center;
            animation: cardIn .6s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes cardIn {
            from { opacity:0; transform:translateY(30px) scale(.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }

        /* Logo GLOBALO */
        .logo {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: 1.1rem; font-weight: 800; letter-spacing: -.02em;
            color: #fff; text-decoration: none; margin-bottom: 1.75rem;
        }
        .logo-dot { color: #4ade80; }

        /* Icône animée */
        .icon-wrap {
            display: inline-flex; align-items: center; justify-content: center;
            width: 80px; height: 80px; border-radius: 20px;
            background: linear-gradient(135deg, #16a34a, #0ea5e9);
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(22,163,74,.35);
            animation: pulse 2.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { box-shadow: 0 8px 32px rgba(22,163,74,.35); }
            50%     { box-shadow: 0 8px 48px rgba(22,163,74,.6), 0 0 0 12px rgba(22,163,74,.08); }
        }
        .icon-wrap svg { filter: drop-shadow(0 2px 4px rgba(0,0,0,.3)); }

        /* Badge statut */
        .badge {
            display: inline-flex; align-items: center; gap: .4rem;
            background: rgba(22,163,74,.15); color: #4ade80;
            border: 1px solid rgba(22,163,74,.3);
            border-radius: 999px; padding: .3rem .9rem;
            font-size: .75rem; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase;
            margin-bottom: 1.25rem;
        }
        .badge-dot {
            width: 7px; height: 7px; border-radius: 50%; background: #4ade80;
            animation: blink 1.2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{ opacity:1; } 50%{ opacity:.2; } }

        h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800; letter-spacing: -.02em;
            color: #fff; margin-bottom: .75rem; line-height: 1.2;
        }
        h1 span { color: #4ade80; }

        .subtitle {
            color: #94a3b8; font-size: .9375rem; line-height: 1.65;
            margin-bottom: 1.75rem; max-width: 460px;
            margin-left: auto; margin-right: auto;
        }

        /* Barre de progression */
        .progress-wrap { margin-bottom: 1.75rem; }
        .progress-label {
            display: flex; justify-content: space-between; align-items: center;
            font-size: .8125rem; color: #64748b; margin-bottom: .5rem;
        }
        .progress-label strong { color: #4ade80; }
        .progress-bar {
            height: 8px; background: rgba(255,255,255,.07);
            border-radius: 999px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; width: <?= $progress ?>%;
            border-radius: 999px;
            background: linear-gradient(90deg, #16a34a, #4ade80, #0ea5e9);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
        }
        @keyframes shimmer { 0%{ background-position:200% 0; } 100%{ background-position:-200% 0; } }

        /* Étapes */
        .steps {
            display: flex; flex-direction: column; gap: .5rem;
            margin-bottom: 1.75rem; text-align: left;
        }
        .step {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem .9rem; border-radius: 10px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.05);
            font-size: .875rem; transition: border-color .3s;
        }
        .step--done   { border-color: rgba(22,163,74,.25); }
        .step--active { border-color: rgba(14,165,233,.35); background: rgba(14,165,233,.06); }

        .step-icon {
            flex-shrink: 0; width: 24px; height: 24px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-size: .8rem; font-weight: 700;
        }
        .step-icon--done   { background: rgba(22,163,74,.2);  color: #4ade80; }
        .step-icon--active { background: rgba(14,165,233,.2); color: #38bdf8; animation: spin 1.5s linear infinite; }
        .step-icon--wait   { background: rgba(255,255,255,.06); color: #475569; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .step-body { flex: 1; }
        .step-name { color: #e2e8f0; font-weight: 600; }
        .step-sub  { font-size: .75rem; color: #64748b; margin-top: .1rem; }

        /* Compteur */
        .countdown { display: flex; justify-content: center; gap: 1rem; margin-bottom: 1.75rem; }
        .countdown-unit { text-align: center; min-width: 64px; }
        .countdown-num {
            display: block; font-size: 1.9rem; font-weight: 800; color: #fff;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.09);
            border-radius: 12px; padding: .4rem .7rem;
            letter-spacing: -.02em; font-variant-numeric: tabular-nums;
        }
        .countdown-label { font-size: .68rem; color: #64748b; margin-top: .35rem; text-transform: uppercase; letter-spacing: .06em; }

        /* ETA */
        .eta-box {
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .7rem 1.25rem; border-radius: 12px;
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            font-size: .875rem; color: #94a3b8; margin-bottom: 1.5rem;
        }
        .eta-box strong { color: #f1f5f9; }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,.07); margin: 1.25rem 0; }

        /* Liens sociaux */
        .social { display: flex; align-items: center; justify-content: center; gap: .75rem; margin-top: 1.25rem; }
        .social-link {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
            color: #94a3b8; text-decoration: none;
            transition: background .2s, color .2s, transform .2s;
        }
        .social-link:hover { background: rgba(255,255,255,.13); color: #fff; transform: translateY(-2px); }

        /* Auto-refresh notice */
        .refresh-notice {
            font-size: .75rem; color: #475569; margin-top: 1rem;
            display: flex; align-items: center; justify-content: center; gap: .35rem;
        }

        @media (max-width: 560px) {
            .card { padding: 2rem 1.25rem; }
            .countdown-num { font-size: 1.4rem; padding: .3rem .5rem; }
            .countdown { gap: .5rem; }
        }
    </style>
</head>
<body>
<div class="bg-particles">
    <div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
</div>

<div class="card" role="main">

    <!-- Logo -->
    <div class="logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
        GLOB<span class="logo-dot">A</span>LO
    </div>

    <!-- Icône animée -->
    <div class="icon-wrap" aria-hidden="true">
        <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <?= $cfg['icon'] ?>
        </svg>
    </div>

    <!-- Statut -->
    <div class="badge"><span class="badge-dot"></span><?= esc($cfg['badge']) ?></div>

    <h1>GLOBALO — <span><?= esc($cfg['title']) ?></span></h1>

    <p class="subtitle">
        <?= $meta['message']
            ? esc((string) $meta['message'])
            : 'Nous améliorons la plateforme pour vous offrir une meilleure expérience. La page se rafraîchit automatiquement.' ?>
    </p>

    <!-- Progression -->
    <?php if ($progress > 0): ?>
    <div class="progress-wrap">
        <div class="progress-label">
            <span>Progression de la mise à jour</span>
            <strong><?= $progress ?>%</strong>
        </div>
        <div class="progress-bar" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-fill"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Étapes dynamiques -->
    <div class="steps" aria-label="Étapes de la mise à jour">
        <?php foreach ($steps as [$state, $name, $sub]): ?>
        <div class="step step--<?= $state ?>">
            <span class="step-icon step-icon--<?= $state ?>" aria-label="<?= $state === 'done' ? 'Terminé' : ($state === 'active' ? 'En cours' : 'En attente') ?>">
                <?= $state === 'done' ? '✓' : ($state === 'active' ? '↻' : '○') ?>
            </span>
            <div class="step-body">
                <div class="step-name"><?= esc($name) ?></div>
                <div class="step-sub"><?= esc($sub) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Compteur si ETA défini -->
    <?php if ($etaTs > time()): ?>
    <div class="countdown" id="countdown" aria-label="Temps restant estimé">
        <div class="countdown-unit">
            <span class="countdown-num" id="cd-h">--</span>
            <span class="countdown-label">Heures</span>
        </div>
        <div class="countdown-unit">
            <span class="countdown-num" id="cd-m">--</span>
            <span class="countdown-label">Minutes</span>
        </div>
        <div class="countdown-unit">
            <span class="countdown-num" id="cd-s">--</span>
            <span class="countdown-label">Secondes</span>
        </div>
    </div>
    <script>
    (function(){
        var target = <?= $etaTs ?> * 1000;
        function pad(n){ return n < 10 ? '0' + n : '' + n; }
        function tick(){
            var diff = target - Date.now();
            if (diff <= 0) { document.getElementById('countdown').style.display='none'; return; }
            document.getElementById('cd-h').textContent = pad(Math.floor(diff / 3600000));
            document.getElementById('cd-m').textContent = pad(Math.floor((diff % 3600000) / 60000));
            document.getElementById('cd-s').textContent = pad(Math.floor((diff % 60000) / 1000));
        }
        tick(); setInterval(tick, 1000);
    })();
    </script>
    <?php endif; ?>

    <!-- ETA texte -->
    <?php if ($etaFormatted): ?>
    <div class="eta-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Retour estimé : <strong><?= esc($etaFormatted) ?></strong>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Contact -->
    <p style="font-size:.875rem;color:#64748b;">
        Une urgence ?
        <a href="mailto:<?= esc((string) $meta['contact']) ?>" style="color:#4ade80;font-weight:600;text-decoration:none;">
            <?= esc((string) $meta['contact']) ?>
        </a>
    </p>

    <!-- Liens sociaux -->
    <div class="social" aria-label="GLOBALO">
        <a href="https://globalo.secogesarl.com" class="social-link" title="Site principal" aria-label="Site principal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
        </a>
        <a href="mailto:<?= esc((string) $meta['contact']) ?>" class="social-link" title="Email" aria-label="Email">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
        </a>
        <a href="https://wa.me/22394035456" class="social-link" title="WhatsApp" aria-label="WhatsApp">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
    </div>

    <!-- Notice refresh automatique -->
    <p class="refresh-notice" aria-live="polite">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Cette page se rafraîchit automatiquement toutes les 60 secondes
    </p>

</div>
</body>
</html>
