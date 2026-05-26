<?php
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$e               = fn($s) => \App\Core\Security::escape($s ?? '');
$exercices       = $exercices ?? [];
$matieres        = $matieres ?? [];
$matiere_id      = (int)($matiere_id ?? 0);
$matiereCourante = $matiere_courante ?? null;

$statutLabel = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'correction_livree' => 'À valider', 'resolu' => 'Résolu', 'annule' => 'Annulé'];
$statutColor = ['ouvert' => '#16a34a', 'en_cours' => '#2563eb', 'correction_livree' => '#d97706', 'resolu' => '#6b7280', 'annule' => '#dc2626'];
$diffLabel   = ['facile' => 'Facile', 'moyen' => 'Moyen', 'difficile' => 'Difficile', 'tres_difficile' => 'Très difficile'];
$typeLabel   = ['devoir' => 'Devoir', 'examen' => 'Examen', 'tp' => 'TP', 'projet' => 'Projet', 'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Oral', 'autre' => 'Autre'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- En-tête -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">
        <?= $matiereCourante ? $e($matiereCourante['nom']) : 'Mes exercices' ?>
    </h1>
    <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouveau
    </a>
</div>

<!-- Filtres par matière (scroll horizontal) -->
<?php if (!empty($matieres)): ?>
<div style="display:flex;gap:0.5rem;overflow-x:auto;padding-bottom:0.5rem;margin-bottom:1rem;-webkit-overflow-scrolling:touch">
    <a href="<?= $baseUrl ?>/etudiant/exercices"
       style="flex-shrink:0;padding:0.4rem 0.9rem;border-radius:999px;font-size:0.8rem;font-weight:600;text-decoration:none;border:1.5px solid <?= !$matiere_id ? 'var(--accent)' : 'var(--border)' ?>;background:<?= !$matiere_id ? 'var(--accent)' : 'transparent' ?>;color:<?= !$matiere_id ? '#fff' : 'var(--text-muted)' ?>">
        Toutes
    </a>
    <?php foreach ($matieres as $mat): ?>
    <a href="<?= $baseUrl ?>/etudiant/exercices?matiere=<?= (int)$mat['id'] ?>"
       style="flex-shrink:0;padding:0.4rem 0.9rem;border-radius:999px;font-size:0.8rem;font-weight:600;text-decoration:none;border:1.5px solid <?= $matiere_id === (int)$mat['id'] ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $matiere_id === (int)$mat['id'] ? 'var(--accent)' : 'transparent' ?>;color:<?= $matiere_id === (int)$mat['id'] ? '#fff' : 'var(--text-muted)' ?>">
        <?= $e($mat['nom']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Liste exercices -->
<?php if (empty($exercices)): ?>
<div style="text-align:center;padding:3rem 1rem">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1" style="margin-bottom:1rem"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p class="mobile-empty-hint">Aucun exercice<?= $matiereCourante ? ' pour ' . $e($matiereCourante['nom']) : '' ?>.</p>
    <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="btn-mobile btn-primary" style="display:inline-flex;margin-top:0.75rem">Soumettre un exercice</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($exercices as $ex): ?>
    <a href="<?= $baseUrl ?>/etudiant/exercices/<?= (int)$ex['id'] ?>"
       style="display:block;text-decoration:none;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;box-shadow:var(--shadow)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.5rem">
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap">
                <?php if (!empty($ex['matiere_nom'])): ?>
                <span style="font-size:0.72rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)"><?= $e($ex['matiere_nom']) ?></span>
                <?php endif; ?>
                <span style="font-size:0.72rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:#f1f5f9;color:#475569"><?= $e($typeLabel[$ex['type_exercice'] ?? ''] ?? '') ?></span>
            </div>
            <span style="flex-shrink:0;font-size:0.72rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:<?= ($statutColor[$ex['statut'] ?? 'ouvert'] ?? '#6b7280') ?>22;color:<?= $statutColor[$ex['statut'] ?? 'ouvert'] ?? '#6b7280' ?>">
                <?= $statutLabel[$ex['statut'] ?? 'ouvert'] ?? 'Ouvert' ?>
            </span>
        </div>
        <p style="margin:0 0 0.5rem;font-weight:600;font-size:0.95rem;color:var(--primary)"><?= $e($ex['titre']) ?></p>
        <?php if (!empty($ex['description'])): ?>
        <p style="margin:0 0 0.5rem;font-size:0.82rem;color:var(--text-muted);line-height:1.4">
            <?= $e(mb_substr($ex['description'], 0, 90)) ?><?= mb_strlen($ex['description']) > 90 ? '…' : '' ?>
        </p>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:0.75rem;color:var(--text-muted)"><?= $e($diffLabel[$ex['niveau_difficulte'] ?? 'moyen'] ?? '') ?></span>
            <?php if (!empty($ex['date_limite'])): ?>
            <span style="font-size:0.75rem;color:#f59e0b;font-weight:500">⏰ <?= date('d/m/Y', strtotime($ex['date_limite'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($ex['note_finale'])): ?>
            <span style="font-size:0.78rem;font-weight:600;color:#16a34a"><?= number_format((float)$ex['note_finale'], 1) ?>/20</span>
            <?php endif; ?>
        </div>
        <?php if (($ex['urgence'] ?? '') === 'tres_urgent'): ?>
        <div style="margin-top:0.5rem;font-size:0.72rem;font-weight:700;color:#dc2626">🔴 TRÈS URGENT</div>
        <?php elseif (($ex['urgence'] ?? '') === 'urgent'): ?>
        <div style="margin-top:0.5rem;font-size:0.72rem;font-weight:700;color:#f59e0b">🟡 URGENT</div>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
