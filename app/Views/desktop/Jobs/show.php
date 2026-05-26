<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$job = $job ?? [];
$pageUrl = $pageUrl ?? $baseUrl . '/jobs/' . ($job['slug'] ?? '');
$relatedExperts = $relatedExperts ?? [];
$shareTitle = \App\Core\Security::escape($job['titre'] ?? 'Mission sur GLOBALO');
$shareText = \App\Core\Security::escape(($job['titre'] ?? '') . ' — Trouvez un expert pour cette mission.');
?>
<section class="section-desktop" data-growth-track="view_job_page">
    <p><a href="<?= $baseUrl ?>/" class="btn btn-outline">← Accueil</a></p>
    <div class="card-desktop job-detail">
        <h1><?= \App\Core\Security::escape($job['titre'] ?? 'Mission') ?></h1>
        <?php if (!empty($job['competence_nom'])): ?>
            <p class="job-category"><strong>Catégorie :</strong> <?= \App\Core\Security::escape($job['competence_nom']) ?></p>
        <?php endif; ?>
        <?php if (!empty($job['description'])): ?>
            <div class="job-description"><?= nl2br(\App\Core\Security::escape($job['description'])) ?></div>
        <?php endif; ?>

        <div class="job-share" style="margin-top:1rem;">
            <span class="text-muted">Partager cette mission :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" aria-label="Partager sur Facebook">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" aria-label="Partager sur X">X (Twitter)</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" aria-label="Partager sur LinkedIn">LinkedIn</a>
            <button type="button" class="btn btn-outline btn-sm" id="job-copy-link" data-url="<?= \App\Core\Security::escape($pageUrl) ?>" aria-label="Copier le lien">Copier le lien</button>
        </div>

        <?php if (isset($user) && $user && $user['role'] === 'client'): ?>
            <p style="margin-top:1.5rem;"><a href="<?= $baseUrl ?>/client/demandes" class="btn btn-primary btn-lg">Voir mes demandes</a> ou <a href="<?= $baseUrl ?>/client/nouvelle_demande" class="btn btn-primary btn-lg">Créer une demande</a></p>
        <?php else: ?>
            <p style="margin-top:1.5rem;"><a href="<?= $baseUrl ?>/auth/inscription" class="btn btn-primary btn-lg">Rejoindre GLOBALO pour postuler</a></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($relatedExperts)): ?>
        <h2>Experts pour cette mission</h2>
        <ul class="experts-list" style="list-style:none;padding:0;">
            <?php foreach ($relatedExperts as $expert): ?>
                <?php $expertSlug = $expert['slug'] ?? ('expert-' . (int)$expert['id']); ?>
                <li class="card-desktop" style="margin-bottom:1rem;">
                    <a href="<?= $baseUrl ?>/expert/<?= \App\Core\Security::escape($expertSlug) ?>">
                        <strong><?= \App\Core\Security::escape($expert['titre'] ?? 'Expert') ?></strong>
                        <?php if (isset($expert['tarif_horaire'])): ?> — <?= number_format((float)$expert['tarif_horaire'], 2, ',', ' ') ?> <?= \App\Core\Security::escape(devise()) ?>/h<?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<script>
(function(){
    var btn = document.getElementById('job-copy-link');
    if (btn) btn.addEventListener('click', function(){ navigator.clipboard && navigator.clipboard.writeText(btn.getAttribute('data-url') || ''); });
})();
</script>
