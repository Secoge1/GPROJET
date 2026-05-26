<?php
/**
 * Types de professions / compétences expert (fiche publique).
 * Variables : $competences (list), $expert (array profil), $e (callable escape)
 */
$competences = $competences ?? [];
$expert      = $expert ?? [];
$e           = $e ?? fn($s) => \App\Core\Security::escape($s ?? '');

if (empty($competences)) {
    return;
}

$niveauxLabels = [
    'debutant'      => 'Débutant',
    'intermediaire' => 'Intermédiaire',
    'avance'        => 'Avancé',
    'expert'        => 'Expert',
];
?>
<div class="expert-types-professions">
    <?php foreach ($competences as $c):
        if (!is_array($c)) {
            continue;
        }
        $nom = (string) ($c['nom'] ?? '');
        if ($nom === '') {
            continue;
        }
        $slug = strtolower((string) ($c['slug'] ?? ''));
        $isAutres = $slug === 'autres' || strtolower($nom) === 'autres';
        $label = $nom;
        if ($isAutres && !empty($expert['competences_autres'])) {
            $label = 'Autres : ' . $expert['competences_autres'];
        }
        $niveau = (string) ($c['niveau'] ?? '');
        $niveauLabel = $niveauxLabels[$niveau] ?? '';
        $categorie = trim((string) ($c['categorie'] ?? ''));
    ?>
    <span class="expert-types-professions__chip">
        <span class="expert-types-professions__label"><?= $e($label) ?></span>
        <?php if ($categorie !== '' && !$isAutres): ?>
        <span class="expert-types-professions__cat"><?= $e($categorie) ?></span>
        <?php endif; ?>
        <?php if ($niveauLabel !== ''): ?>
        <span class="expert-types-professions__niveau"><?= $e($niveauLabel) ?></span>
        <?php endif; ?>
    </span>
    <?php endforeach; ?>
</div>
