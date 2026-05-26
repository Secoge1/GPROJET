<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$prestations = $prestations ?? [];
?>

<div style="margin-bottom:1.25rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">Mes prestations</h1>
    <p style="margin:0.2rem 0 0;font-size:0.82rem;color:var(--text-muted)"><?= count($prestations) ?> prestation<?= count($prestations) > 1 ? 's' : '' ?></p>
</div>

<?php if (empty($prestations)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1" style="margin-bottom:1rem"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    <p class="mobile-empty-hint">Aucune prestation pour le moment.</p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($prestations as $p): ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;box-shadow:var(--shadow)">
        <p style="margin:0 0 0.3rem;font-weight:700;font-size:0.9rem;color:var(--primary)"><?= $e($p['demande_titre'] ?? '') ?></p>
        <p style="margin:0 0 0.5rem;font-size:0.78rem;color:var(--text-muted)">
            Client : <?= $e(trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''))) ?>
            <?php if (!empty($p['duree_heures'])): ?> · <?= $p['duree_heures'] ?>h<?php endif; ?>
        </p>
        <?php if (!empty($p['montant_net_expert'])): ?>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:0.78rem;color:var(--text-muted)"><?= !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '' ?></span>
            <span style="font-weight:700;font-size:0.9rem;color:#16a34a"><?= number_format((float)$p['montant_net_expert'], 0, ',', ' ') ?> <?= $e(devise()) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
