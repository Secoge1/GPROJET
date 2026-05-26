<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$role      = $user['role'] ?? 'etudiant';
$basePath  = $base_path ?? ($role === 'professeur' ? '/professeur' : '/etudiant');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$profil    = $profil ?? [];
$stats     = $stats ?? [];
$recents   = $recents ?? [];
$matieres  = $matieres ?? [];
$prenom    = $e($profil['prenom'] ?? $profil['nom'] ?? ($role === 'professeur' ? 'Professeur' : 'Étudiant'));

$statutLabel = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'correction_livree' => 'À valider', 'resolu' => 'Résolu', 'annule' => 'Annulé'];
$statutColor = ['ouvert' => '#16a34a', 'en_cours' => '#2563eb', 'correction_livree' => '#d97706', 'resolu' => '#6b7280', 'annule' => '#dc2626'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success">
    <?= $e($_SESSION['flash_success']) ?>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- Salutation -->
<div class="mobile-greeting" style="margin-bottom:1.5rem">
    <div>
        <h2 style="font-size:1.25rem;margin:0 0 0.2rem">Bonjour, <?= $prenom ?> 🎓</h2>
        <p style="margin:0;font-size:0.85rem;color:var(--text-muted)">
            <?= !empty($profil['universite']) ? $e($profil['universite']) : ($role === 'professeur' ? 'Espace Professeur' : 'Espace Étudiant') ?>
        </p>
    </div>
    <a href="<?= $baseUrl . $basePath ?>/compte" class="icon-avatar" aria-label="Mon compte">
        <?= mb_strtoupper(mb_substr($prenom, 0, 1)) ?>
    </a>
</div>

<?php if ($role === 'etudiant'): ?>
<!-- Stats rapides (étudiants uniquement) -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.25rem">
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#f59e0b"><?= (int)($stats['total'] ?? 0) ?></span>
        <span class="mobile-stat-card__lbl">Total exercices</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#16a34a"><?= (int)($stats['resolus'] ?? 0) ?></span>
        <span class="mobile-stat-card__lbl">Résolus</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#2563eb"><?= (int)($stats['en_cours'] ?? 0) ?></span>
        <span class="mobile-stat-card__lbl">En cours</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#7c3aed">
            <?= !empty($stats['moyenne_notes']) ? number_format((float)$stats['moyenne_notes'], 1) . '/20' : '—' ?>
        </span>
        <span class="mobile-stat-card__lbl">Moyenne</span>
    </div>
</div>
<?php endif; ?>

<!-- Actions principales -->
<?php if ($role === 'professeur'): ?>
<a href="<?= ($role === 'professeur' && $basePath === '/app') ? $baseUrl.'/app/exercices-disponibles' : $baseUrl.$basePath.'/exercices-disponibles' ?>" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    Exercices à corriger
</a>
<a href="<?= $baseUrl . $basePath ?>/retrait-choix" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:1.5rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    Retrait Mobile Money
</a>
<?php else: ?>
<a href="<?= $baseUrl . $basePath ?>/exercices/nouveau" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Soumettre un exercice
</a>
<a href="<?= $baseUrl . $basePath ?>/exercices" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:1.5rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Mes exercices
</a>
<?php endif; ?>

<!-- Exercices récents / En cours de correction -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1rem 0.75rem;border-bottom:1px solid var(--border)">
        <h3 style="margin:0;font-size:0.95rem;font-weight:600;color:var(--primary)"><?= $role === 'professeur' ? 'Exercices en cours de correction' : 'Exercices récents' ?></h3>
        <a href="<?= $role === 'professeur' ? $baseUrl . $basePath . '/exercices-disponibles' : $baseUrl . $basePath . '/exercices' ?>" style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:500">Tout voir →</a>
    </div>
    <?php if (empty($recents)): ?>
    <div style="padding:1.5rem 1rem;text-align:center">
        <?php if ($role === 'professeur'): ?>
        <p class="mobile-empty-hint">Aucun exercice en cours de correction.</p>
        <a href="<?= $baseUrl . $basePath ?>/exercices-disponibles" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;margin-top:0.5rem">Voir les exercices à corriger</a>
        <?php else: ?>
        <p class="mobile-empty-hint">Aucun exercice soumis pour le moment.</p>
        <a href="<?= $baseUrl . $basePath ?>/exercices/nouveau" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;margin-top:0.5rem">Soumettre</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <ul class="mobile-list" style="margin:0">
        <?php foreach ($recents as $ex): ?>
        <li style="padding:0.85rem 1rem;border-bottom:1px solid var(--border);list-style:none">
            <a href="<?= $role === 'professeur' ? $baseUrl . $basePath . '/corriger/' . (int)$ex['id'] : $baseUrl . $basePath . '/exercices/' . (int)$ex['id'] ?>" style="text-decoration:none;display:block">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem">
                    <div style="flex:1;min-width:0">
                        <p style="margin:0 0 0.25rem;font-weight:600;font-size:0.9rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= $e($ex['titre']) ?>
                        </p>
                        <p style="margin:0;font-size:0.78rem;color:var(--text-muted)">
                            <?= !empty($ex['matiere_nom']) ? $e($ex['matiere_nom']) . ' · ' : '' ?><?= $e(ucfirst(str_replace('_', ' ', $ex['type_exercice'] ?? ''))) ?>
                        </p>
                    </div>
                    <span style="flex-shrink:0;font-size:0.72rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;background:<?= $statutColor[$ex['statut'] ?? 'ouvert'] ?? '#6b7280' ?>22;color:<?= $statutColor[$ex['statut'] ?? 'ouvert'] ?? '#6b7280' ?>">
                        <?= $statutLabel[$ex['statut'] ?? 'ouvert'] ?? 'Ouvert' ?>
                    </span>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<!-- Matières maîtrisées -->
<?php if (!empty($matieres)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
        <h3 style="margin:0;font-size:0.95rem;font-weight:600;color:var(--primary)">Matières maîtrisées</h3>
        <a href="<?= $baseUrl . $basePath ?>/profil" style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:500">Gérer →</a>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
        <?php foreach (array_slice($matieres, 0, 6) as $m): ?>
        <span style="font-size:0.78rem;font-weight:500;padding:0.25rem 0.65rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
            <?= $e($m['matiere_nom']) ?>
        </span>
        <?php endforeach; ?>
        <?php if (count($matieres) > 6): ?>
        <a href="<?= $baseUrl . $basePath ?>/profil" style="font-size:0.78rem;font-weight:500;padding:0.25rem 0.65rem;border-radius:999px;background:var(--border);color:var(--text-muted);text-decoration:none">
            +<?= count($matieres) - 6 ?> autres
        </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem;text-align:center">
    <p class="mobile-empty-hint" style="margin:0 0 0.75rem"><?= $role === 'professeur' ? 'Ajoutez les matières que vous corrigez.' : 'Ajoutez vos matières maîtrisées' ?></p>
    <a href="<?= $baseUrl . $basePath ?>/profil" class="btn-mobile btn-outline btn-sm" style="display:inline-flex">Configurer</a>
</div>
<?php endif; ?>
