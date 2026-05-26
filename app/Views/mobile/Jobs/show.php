<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$job            = $job ?? [];
$relatedExperts = $relatedExperts ?? [];
$pageUrl        = $pageUrl ?? $baseUrl . '/jobs/' . ($job['slug'] ?? '');
$shareText      = $e(($job['titre'] ?? '') . ' — Trouvez un expert pour cette mission.');
$user           = $user ?? null;
?>

<!-- Navigation retour -->
<div style="margin-bottom:1rem">
    <a href="<?= $baseUrl ?>/app" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.83rem;font-weight:600;color:var(--accent);text-decoration:none">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Accueil
    </a>
</div>

<!-- Détail mission -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem" data-growth-track="view_job_page">
    <div style="padding:1.25rem 1rem">
        <?php if (!empty($job['competence_nom'])): ?>
        <div style="margin-bottom:0.5rem">
            <span style="font-size:0.7rem;font-weight:700;padding:0.15rem 0.55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
                <?= $e($job['competence_nom']) ?>
            </span>
        </div>
        <?php endif; ?>

        <h1 style="font-size:1.15rem;font-weight:700;margin:0 0 1rem;color:var(--primary);line-height:1.3">
            <?= $e($job['titre'] ?? 'Mission') ?>
        </h1>

        <?php if (!empty($job['description'])): ?>
        <div style="font-size:0.88rem;line-height:1.6;color:var(--text);margin-bottom:1.25rem">
            <?= nl2br($e($job['description'])) ?>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <?php if ($user && $user['role'] === 'client'): ?>
            <a href="<?= $baseUrl ?>/client/demandes" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.5rem">
                Voir mes demandes
            </a>
            <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
                Créer une demande
            </a>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/auth/inscription" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
                Rejoindre GLOBALO pour postuler
            </a>
        <?php endif; ?>
    </div>

    <!-- Partage -->
    <div style="border-top:1px solid var(--border);padding:0.85rem 1rem">
        <p style="margin:0 0 0.6rem;font-size:0.8rem;font-weight:600;color:var(--text-muted)">Partager cette mission :</p>
        <div style="display:flex;gap:0.5rem">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener noreferrer"
               class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">X</a>
            <button type="button" id="job-copy-link" data-url="<?= $e($pageUrl) ?>"
                    class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">Copier</button>
        </div>
    </div>
</div>

<!-- Experts liés -->
<?php if (!empty($relatedExperts)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Experts disponibles pour cette mission</h2>
    </div>
    <?php foreach ($relatedExperts as $expert): ?>
    <?php $slug = $expert['slug'] ?? ('expert-' . (int)$expert['id']); ?>
    <a href="<?= $baseUrl ?>/expert/<?= $e($slug) ?>"
       style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid var(--border);text-decoration:none;color:inherit">
        <span style="font-size:0.88rem;font-weight:600;color:var(--primary)"><?= $e($expert['titre'] ?? 'Expert') ?></span>
        <?php if (isset($expert['tarif_horaire'])): ?>
        <span style="font-size:0.8rem;font-weight:700;color:var(--accent)"><?= number_format((float)$expert['tarif_horaire'], 0, ',', ' ') ?> <?= $e(devise()) ?>/h</span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function(){
    var btn = document.getElementById('job-copy-link');
    if (btn) btn.addEventListener('click', function(){
        navigator.clipboard && navigator.clipboard.writeText(btn.dataset.url || '');
        btn.textContent = '✓ Copié';
        setTimeout(function(){ btn.textContent = 'Copier'; }, 2000);
    });
})();
</script>
