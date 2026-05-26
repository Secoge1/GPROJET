<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$grouped       = $matieres ?? [];
$mesMatiereIds = $mes_matiere_ids ?? [];
?>
<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <h1 class="etd-page__title">Matières universitaires</h1>
            <p class="etd-page__sub">Catalogue des matières disponibles — Mali, Côte d'Ivoire, Sénégal, Bénin, Niger</p>
        </div>
        <a href="<?= $baseUrl ?>/etudiant/profil" class="etd-btn etd-btn--outline">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Gérer mes matières
        </a>
    </div>

    <div class="etd-matieres-categories">
        <?php foreach ($grouped as $categorie => $mats): ?>
        <section class="etd-matieres-cat">
            <h2 class="etd-matieres-cat__title">
                <?php
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
                echo ($icons[$categorie] ?? '📖') . ' ';
                ?>
                <?= $e($categorie) ?>
                <span class="etd-matieres-cat__count"><?= count($mats) ?> matière<?= count($mats) > 1 ? 's' : '' ?></span>
            </h2>
            <div class="etd-matieres-pills">
                <?php foreach ($mats as $mat): ?>
                <?php $isMine = in_array((int)$mat['id'], $mesMatiereIds, true); ?>
                <a href="<?= $baseUrl ?>/etudiant/exercices?matiere=<?= (int)$mat['id'] ?>"
                   class="etd-matiere-pill <?= $isMine ? 'etd-matiere-pill--mine' : '' ?>"
                   title="<?= $e($mat['filiere']) ?>">
                    <?= $e($mat['nom']) ?>
                    <?php if ($isMine): ?>
                    <span class="etd-matiere-pill__check" aria-label="Dans mes matières">✓</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <div class="etd-matieres-footer">
        <p>Les matières marquées <strong style="color:var(--etd-primary)">✓</strong> font partie de vos matières maîtrisées.</p>
        <a href="<?= $baseUrl ?>/etudiant/profil" class="etd-btn etd-btn--primary">Gérer mes matières maîtrisées</a>
    </div>
</div>
