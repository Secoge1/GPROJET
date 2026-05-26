<?php
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$e               = fn($s) => \App\Core\Security::escape($s ?? '');
$posts           = $posts ?? [];
$categories      = $categories ?? [];
$filtre_category = $filtre_category ?? null;
?>

<!-- En-tête -->
<div style="margin-bottom:1.25rem">
    <h1 style="font-size:1.25rem;font-weight:700;margin:0 0 0.25rem;color:var(--primary)">Blog GLOBALO</h1>
    <p style="margin:0;font-size:0.83rem;color:var(--text-muted)">Conseils, actualités et bonnes pratiques.</p>
</div>

<!-- Filtres catégories -->
<?php if (!empty($categories)): ?>
<div style="display:flex;gap:0.45rem;overflow-x:auto;padding-bottom:0.5rem;margin-bottom:1rem;scrollbar-width:none">
    <a href="<?= $baseUrl ?>/blog"
       style="flex-shrink:0;font-size:0.78rem;font-weight:600;padding:0.35rem 0.8rem;border-radius:999px;border:1.5px solid <?= $filtre_category === null ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $filtre_category === null ? 'var(--accent-soft)' : 'transparent' ?>;color:<?= $filtre_category === null ? 'var(--accent)' : 'var(--text-muted)' ?>;text-decoration:none;white-space:nowrap">
        Tous
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= $baseUrl ?>/blog?category=<?= (int)$cat['id'] ?>"
       style="flex-shrink:0;font-size:0.78rem;font-weight:600;padding:0.35rem 0.8rem;border-radius:999px;border:1.5px solid <?= (int)$filtre_category === (int)$cat['id'] ? 'var(--accent)' : 'var(--border)' ?>;background:<?= (int)$filtre_category === (int)$cat['id'] ? 'var(--accent-soft)' : 'transparent' ?>;color:<?= (int)$filtre_category === (int)$cat['id'] ? 'var(--accent)' : 'var(--text-muted)' ?>;text-decoration:none;white-space:nowrap">
        <?= $e($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Articles -->
<?php if (empty($posts)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5" style="margin-bottom:0.75rem"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p style="margin:0;color:var(--text-muted);font-size:0.88rem">Aucun article pour le moment.</p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.85rem">
    <?php foreach ($posts as $p): ?>
    <a href="<?= $baseUrl ?>/blog/<?= $e($p['slug']) ?>"
       style="display:block;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;text-decoration:none;color:inherit">
        <?php if (!empty($p['category_name'])): ?>
        <div style="margin-bottom:0.4rem">
            <span style="font-size:0.7rem;font-weight:700;padding:0.15rem 0.55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
                <?= $e($p['category_name']) ?>
            </span>
        </div>
        <?php endif; ?>
        <h2 style="margin:0 0 0.4rem;font-size:0.95rem;font-weight:700;color:var(--primary);line-height:1.3">
            <?= $e($p['title']) ?>
        </h2>
        <?php if (!empty($p['meta_description'])): ?>
        <p style="margin:0 0 0.65rem;font-size:0.82rem;color:var(--text-muted);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= $e(mb_substr($p['meta_description'], 0, 150)) ?>
        </p>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:0.75rem;color:var(--text-muted)">
                <?= !empty($p['published_at']) ? date('d/m/Y', strtotime($p['published_at'])) : '' ?>
            </span>
            <span style="font-size:0.78rem;font-weight:600;color:var(--accent)">Lire →</span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
