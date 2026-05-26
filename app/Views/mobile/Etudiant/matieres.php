<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$grouped       = $matieres ?? [];
$mesMatiereIds = $mes_matiere_ids ?? [];

$icons = [
    'Sciences exactes'           => '📐',
    'Sciences de la vie'         => '🧬',
    'Sciences humaines'          => '🌍',
    'Sciences juridiques'        => '⚖️',
    'Sciences économiques'       => '📊',
    'Informatique & Numérique'   => '💻',
    'Lettres & Langues'          => '📚',
    'Santé & Médecine'           => '🏥',
    'Agriculture & Environnement'=> '🌱',
    'Architecture & BTP'         => '🏗️',
    'Autres'                     => '📦',
];
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Matières universitaires</h1>
    <a href="<?= $baseUrl ?>/etudiant/profil" class="btn-mobile btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mes matières
    </a>
</div>

<p style="margin:0 0 1.25rem;font-size:0.82rem;color:var(--text-muted)">
    Catalogue — Mali, Côte d'Ivoire, Sénégal, Bénin, Niger
    <?php if (!empty($mesMatiereIds)): ?>
    · <span style="color:var(--accent);font-weight:600"><?= count($mesMatiereIds) ?> matière<?= count($mesMatiereIds) > 1 ? 's' : '' ?> dans mon profil</span>
    <?php endif; ?>
</p>

<!-- Catégories -->
<div style="display:flex;flex-direction:column;gap:0.75rem">
    <?php foreach ($grouped as $categorie => $mats): ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
        <div style="padding:0.75rem 1rem;background:#f8fafc;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:0.9rem;font-weight:700;color:var(--primary)">
                <?= ($icons[$categorie] ?? '📖') . ' ' . $e($categorie) ?>
            </span>
            <span style="font-size:0.72rem;color:var(--text-muted)"><?= count($mats) ?> matière<?= count($mats) > 1 ? 's' : '' ?></span>
        </div>
        <div style="padding:0.75rem;display:flex;flex-wrap:wrap;gap:0.4rem">
            <?php foreach ($mats as $mat): ?>
            <?php $isMine = in_array((int)$mat['id'], $mesMatiereIds, true); ?>
            <a href="<?= $baseUrl ?>/etudiant/exercices?matiere=<?= (int)$mat['id'] ?>"
               style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.3rem 0.7rem;border-radius:999px;font-size:0.78rem;font-weight:<?= $isMine ? '700' : '500' ?>;text-decoration:none;border:1.5px solid <?= $isMine ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $isMine ? 'var(--accent-soft)' : 'transparent' ?>;color:<?= $isMine ? 'var(--accent)' : 'var(--text-muted)' ?>">
                <?= $e($mat['nom']) ?>
                <?php if ($isMine): ?>
                <span aria-label="Dans mes matières" style="font-size:0.65rem">✓</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div style="margin-top:1.5rem;text-align:center;padding:1rem;background:var(--accent-soft);border-radius:var(--radius)">
    <p style="margin:0 0 0.75rem;font-size:0.82rem;color:var(--text)">Les matières <strong style="color:var(--accent)">✓</strong> font partie de votre profil.</p>
    <a href="<?= $baseUrl ?>/etudiant/profil" class="btn-mobile btn-primary btn-sm" style="display:inline-flex">Gérer mes matières</a>
</div>
