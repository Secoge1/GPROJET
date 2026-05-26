<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$posts = $posts ?? [];
$categories = $categories ?? [];
$filtre_category = $filtre_category ?? null;
?>
<section class="section-desktop">
    <h1>Blog GLOBALO</h1>
    <p class="text-muted">Conseils, actualités et bonnes pratiques.</p>
    <?php if (!empty($categories)): ?>
        <p>
            <a href="<?= $baseUrl ?>/blog" class="btn btn-outline btn-sm">Tous</a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= $baseUrl ?>/blog?category=<?= (int)$cat['id'] ?>" class="btn btn-outline btn-sm"><?= \App\Core\Security::escape($cat['name']) ?></a>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>
    <?php if (empty($posts)): ?>
        <p>Aucun article pour le moment.</p>
    <?php else: ?>
        <ul class="blog-list" style="list-style:none;padding:0;">
            <?php foreach ($posts as $p): ?>
                <li class="card-desktop" style="margin-bottom:1.5rem;">
                    <h2><a href="<?= $baseUrl ?>/blog/<?= \App\Core\Security::escape($p['slug']) ?>"><?= \App\Core\Security::escape($p['title']) ?></a></h2>
                    <?php if (!empty($p['category_name'])): ?>
                        <p class="text-muted"><?= \App\Core\Security::escape($p['category_name']) ?> · <?= $p['published_at'] ? date('d/m/Y', strtotime($p['published_at'])) : '' ?></p>
                    <?php endif; ?>
                    <?php if (!empty($p['meta_description'])): ?>
                        <p><?= \App\Core\Security::escape(mb_substr($p['meta_description'], 0, 200)) ?></p>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/blog/<?= \App\Core\Security::escape($p['slug']) ?>" class="btn btn-outline btn-sm">Lire la suite</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
