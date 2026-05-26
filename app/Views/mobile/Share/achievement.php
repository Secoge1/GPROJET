<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$a          = $achievement ?? [];
$shareTitle = $shareTitle ?? '';
$pageUrl    = $pageUrl ?? '';
$stars      = $stars ?? '';
$prenom     = $e($a['expert_prenom'] ?? 'Expert');
$titre      = $e($a['titre_session'] ?? $a['expert_titre'] ?? 'Session');
?>

<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;padding:1rem">
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:2rem 1.5rem;text-align:center;width:100%;max-width:380px">

        <!-- Icône succès -->
        <div style="width:64px;height:64px;border-radius:50%;background:var(--accent-soft);display:inline-flex;align-items:center;justify-content:center;margin-bottom:1rem">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>

        <p style="margin:0 0 0.35rem;font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em">
            Session terminée sur GLOBALO
        </p>
        <h1 style="margin:0 0 0.5rem;font-size:1.25rem;font-weight:700;color:var(--primary)">
            <?= $prenom ?> a terminé une session
        </h1>
        <p style="margin:0 0 0.75rem;font-size:0.92rem;font-weight:600;color:var(--text)">
            <?= $titre ?>
        </p>

        <?php if ($stars !== ''): ?>
        <p style="font-size:1.75rem;margin:0 0 1.25rem"><?= $stars ?></p>
        <?php else: ?>
        <div style="margin-bottom:1.25rem"></div>
        <?php endif; ?>

        <a href="<?= $baseUrl ?>/app/experts" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:1rem">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Trouver un expert
        </a>

        <?php if ($pageUrl): ?>
        <div style="border-top:1px solid var(--border);padding-top:1rem">
            <p style="margin:0 0 0.6rem;font-size:0.8rem;font-weight:600;color:var(--text-muted)">Partager :</p>
            <div style="display:flex;gap:0.5rem;justify-content:center">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($shareTitle) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">X</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-mobile btn-outline btn-sm" style="flex:1;justify-content:center;display:flex">LinkedIn</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
