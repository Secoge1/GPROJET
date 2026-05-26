<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$post = $post ?? [];
$tags = $tags ?? [];
$related = $related ?? [];
$pageUrl = $pageUrl ?? $baseUrl . '/blog/' . ($post['slug'] ?? '');
$shareTitle = \App\Core\Security::escape($post['title'] ?? 'Article GLOBALO');
$shareText = \App\Core\Security::escape(($post['meta_description'] ?? $post['title'] ?? ''));
?>
<section class="section-desktop">
    <p><a href="<?= $baseUrl ?>/blog" class="btn btn-outline">← Retour au blog</a></p>
    <article class="card-desktop blog-article">
        <?php if (!empty($post['category_name'])): ?>
            <p class="text-muted"><?= \App\Core\Security::escape($post['category_name']) ?> · <?= !empty($post['published_at']) ? date('d/m/Y', strtotime($post['published_at'])) : '' ?></p>
        <?php endif; ?>
        <h1><?= \App\Core\Security::escape($post['title'] ?? '') ?></h1>
        <?php if (!empty($tags)): ?>
            <p>
                <?php foreach ($tags as $t): ?>
                    <a href="<?= $baseUrl ?>/blog?tag=<?= \App\Core\Security::escape($t['slug']) ?>" class="badge"><?= \App\Core\Security::escape($t['name']) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <div class="blog-body"><?= $post['body'] ?? '' ?></div>
        <div class="blog-share" style="margin-top:1.5rem;">
            <span class="text-muted">Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareTitle) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">LinkedIn</a>
        </div>
    </article>
    <?php if (!empty($related)): ?>
        <h2>Articles liés</h2>
        <ul style="list-style:none;padding:0;">
            <?php foreach ($related as $r): ?>
                <li><a href="<?= $baseUrl ?>/blog/<?= \App\Core\Security::escape($r['slug']) ?>"><?= \App\Core\Security::escape($r['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
