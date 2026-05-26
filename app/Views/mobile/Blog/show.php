<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$post       = $post ?? [];
$tags       = $tags ?? [];
$related    = $related ?? [];
$pageUrl    = $pageUrl ?? $baseUrl . '/blog/' . ($post['slug'] ?? '');
$shareTitle = $e($post['title'] ?? 'Article GLOBALO');
$shareText  = $e($post['meta_description'] ?? $post['title'] ?? '');
?>

<!-- Navigation retour -->
<div style="margin-bottom:1rem">
    <a href="<?= $baseUrl ?>/blog" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.83rem;font-weight:600;color:var(--accent);text-decoration:none">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Retour au blog
    </a>
</div>

<!-- Article -->
<article style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div style="padding:1.25rem 1rem">
        <?php if (!empty($post['category_name'])): ?>
        <div style="margin-bottom:0.6rem">
            <span style="font-size:0.7rem;font-weight:700;padding:0.15rem 0.55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
                <?= $e($post['category_name']) ?>
            </span>
            <?php if (!empty($post['published_at'])): ?>
            <span style="font-size:0.73rem;color:var(--text-muted);margin-left:0.5rem">
                <?= date('d/m/Y', strtotime($post['published_at'])) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <h1 style="font-size:1.2rem;font-weight:700;margin:0 0 0.85rem;color:var(--primary);line-height:1.3">
            <?= $e($post['title'] ?? '') ?>
        </h1>

        <?php if (!empty($tags)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-bottom:1rem">
            <?php foreach ($tags as $t): ?>
            <a href="<?= $baseUrl ?>/blog?tag=<?= $e($t['slug']) ?>"
               style="font-size:0.72rem;padding:0.2rem 0.55rem;border-radius:999px;border:1px solid var(--border);color:var(--text-muted);text-decoration:none">
                #<?= $e($t['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="font-size:0.9rem;line-height:1.65;color:var(--text)">
            <?= $post['body'] ?? '' ?>
        </div>
    </div>

    <!-- Partage -->
    <div style="border-top:1px solid var(--border);padding:0.85rem 1rem">
        <p style="margin:0 0 0.6rem;font-size:0.8rem;font-weight:600;color:var(--text-muted)">Partager cet article :</p>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex;min-width:80px">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareTitle) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex;min-width:80px">X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex;min-width:80px">LinkedIn</a>
        </div>
    </div>
</article>

<!-- Articles liés -->
<?php if (!empty($related)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Articles liés</h2>
    </div>
    <?php foreach ($related as $r): ?>
    <a href="<?= $baseUrl ?>/blog/<?= $e($r['slug']) ?>"
       style="display:block;padding:0.75rem 1rem;border-bottom:1px solid var(--border);text-decoration:none;color:var(--primary);font-size:0.88rem;font-weight:600">
        <?= $e($r['title']) ?> <span style="color:var(--accent)">→</span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
