<?php
http_response_code(404);
$baseUrl = rtrim(BASE_URL ?? '', '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Page introuvable (404) — GLOBALO</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem;text-align:center}
        .wrap{max-width:480px}
        .code{font-size:6rem;font-weight:800;color:#e2e8f0;line-height:1;margin-bottom:1rem}
        h1{font-size:1.5rem;font-weight:700;margin-bottom:0.75rem}
        p{color:#64748b;line-height:1.6;margin-bottom:2rem}
        .links{display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap}
        a.btn{display:inline-block;padding:0.6rem 1.4rem;border-radius:8px;font-weight:600;font-size:0.9rem;text-decoration:none;transition:opacity .15s}
        a.btn-primary{background:#16a34a;color:#fff}
        a.btn-outline{background:transparent;color:#16a34a;border:2px solid #16a34a}
        a.btn:hover{opacity:.85}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="code">404</div>
        <h1>Page introuvable</h1>
        <p>Cette page n'existe pas ou a été déplacée. Retrouvez des experts disponibles au Mali, Côte d'Ivoire, Sénégal, Bénin et Niger.</p>
        <div class="links">
            <a href="<?= $baseUrl ?>/" class="btn btn-primary">Accueil</a>
            <a href="<?= $baseUrl ?>/experts" class="btn btn-outline">Voir les experts</a>
            <a href="<?= $baseUrl ?>/demandes" class="btn btn-outline">Voir les demandes</a>
        </div>
    </div>
</body>
</html>
