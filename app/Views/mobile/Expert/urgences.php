<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$missions = $missions ?? [];
?>

<!-- En-tête urgences -->
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem;text-align:center">
    <div style="font-size:2rem;margin-bottom:0.25rem">🚨</div>
    <h1 style="margin:0 0 0.2rem;font-size:1.1rem;font-weight:800;color:#dc2626">Missions urgentes</h1>
    <p style="margin:0;font-size:0.8rem;color:#dc2626;opacity:0.8">Le premier expert qui accepte obtient la mission</p>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem;font-size:0.85rem;color:#dc2626">
    <?= $e($_SESSION['flash_error']) ?>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if (empty($missions)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <div style="font-size:3rem;margin-bottom:0.75rem">✅</div>
    <p class="mobile-empty-hint">Aucune mission urgente en ce moment.</p>
    <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.5rem">Restez connecté pour être le premier à être notifié.</p>
    <a href="<?= $baseUrl ?>/expert" class="btn-mobile btn-outline" style="display:inline-flex;margin-top:0.75rem">← Tableau de bord</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($missions as $m): ?>
    <div style="background:var(--card-bg);border:2px solid #fca5a5;border-radius:var(--radius);padding:1rem;box-shadow:0 2px 8px rgba(220,38,38,0.1)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.6rem">
            <p style="margin:0;font-weight:700;font-size:0.95rem;color:var(--primary);flex:1;min-width:0"><?= $e($m['titre'] ?? '') ?></p>
            <?php if (!empty($m['competence_nom'])): ?>
            <span style="flex-shrink:0;font-size:0.72rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:#fef2f2;color:#dc2626"><?= $e($m['competence_nom']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($m['description'])): ?>
        <p style="margin:0 0 0.75rem;font-size:0.82rem;color:var(--text-muted);line-height:1.4">
            <?= $e(mb_substr($m['description'], 0, 120)) ?><?= mb_strlen($m['description']) > 120 ? '…' : '' ?>
        </p>
        <?php endif; ?>
        <form method="post" action="<?= $baseUrl ?>/expert/urgence-accept/<?= (int)$m['demande_id'] ?>">
            <?= \App\Core\Security::getCsrfField() ?>
            <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;background:#dc2626;border-color:#dc2626">
                ✅ J'accepte cette mission urgente
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
