<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$a = $achievement ?? [];
$shareTitle = $shareTitle ?? '';
$pageUrl = $pageUrl ?? '';
$stars = $stars ?? '';
$prenom = $a['expert_prenom'] ?? 'Expert';
$titre = $a['titre_session'] ?? $a['expert_titre'] ?? 'Session';
?>
<section class="section-desktop">
    <div class="card-desktop" style="max-width:500px;margin:2rem auto;text-align:center;">
        <p class="text-muted">Session terminée sur GLOBALO</p>
        <h1><?= \App\Core\Security::escape($prenom) ?> a terminé une session</h1>
        <p><strong><?= \App\Core\Security::escape($titre) ?></strong></p>
        <?php if ($stars !== ''): ?>
            <p style="font-size:1.5rem;"><?= $stars ?></p>
        <?php endif; ?>
        <p><a href="<?= $baseUrl ?>/experts" class="btn btn-primary">Trouver un expert</a></p>
        <div class="share-buttons" style="margin-top:1rem;">
            <span class="text-muted">Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareTitle) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">LinkedIn</a>
        </div>
    </div>
</section>
