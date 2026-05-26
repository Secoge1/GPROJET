<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$errors   = $errors ?? [];
$data     = $data ?? [];
$matieres = $matieres ?? [];
$csrfField = \App\Core\Security::getCsrfField();

$typesExercice = [
    'devoir'       => 'Devoir maison',
    'examen'       => 'Examen / DS',
    'tp'           => 'TP / TD',
    'projet'       => 'Projet',
    'dissertation' => 'Dissertation',
    'qcm'          => 'QCM',
    'oral'         => 'Préparation oral',
    'autre'        => 'Autre',
];
$niveauxDiff = [
    'facile'        => 'Facile',
    'moyen'         => 'Moyen',
    'difficile'     => 'Difficile',
    'tres_difficile'=> 'Très difficile',
];
$urgences = [
    'normale'     => 'Normale',
    'urgent'      => 'Urgent (48h)',
    'tres_urgent' => 'Très urgent (24h)',
];
?>
<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <a href="<?= $baseUrl ?>/etudiant/exercices" class="etd-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Retour
            </a>
            <h1 class="etd-page__title">Soumettre un exercice</h1>
            <p class="etd-page__sub">Décrivez l'énoncé et choisissez la matière concernée</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="etd-alert etd-alert--error">
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $baseUrl ?>/etudiant/exercices/nouveau" enctype="multipart/form-data" class="etd-form" novalidate>
        <?= $csrfField ?>

        <div class="etd-form-grid">

            <!-- Colonne principale -->
            <div class="etd-form-col etd-form-col--main">

                <div class="etd-form-block">
                    <h2 class="etd-form-block__title">Énoncé de l'exercice</h2>

                    <div class="form-group">
                        <label for="titre">Titre de l'exercice <span class="req">*</span></label>
                        <input type="text" id="titre" name="titre" required maxlength="250"
                               value="<?= $e($data['titre'] ?? '') ?>"
                               placeholder="Ex: Exercice sur les intégrales — Chap.5">
                    </div>

                    <div class="form-group">
                        <label for="description">Énoncé / Description <span class="req">*</span></label>
                        <textarea id="description" name="description" rows="8" required maxlength="8000"
                                  placeholder="Copiez-collez l'énoncé complet ici. Plus vous êtes précis, meilleure sera l'aide."><?= $e($data['description'] ?? '') ?></textarea>
                        <span class="form-hint">Maximum 8000 caractères. Vous pouvez aussi joindre un fichier ci-dessous.</span>
                    </div>

                    <div class="form-group">
                        <label for="fichier">Pièce jointe (PDF, Word, image…)</label>
                        <input type="file" id="fichier" name="fichier" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.zip">
                        <span class="form-hint">Formats : PDF, Word, Excel, images, ZIP. Max 10 Mo.</span>
                    </div>

                    <div class="form-group">
                        <label for="lien_ressource">Lien ressource (Google Drive, Moodle, Dropbox…)</label>
                        <input type="url" id="lien_ressource" name="lien_ressource"
                               value="<?= $e($data['lien_ressource'] ?? '') ?>"
                               placeholder="https://drive.google.com/...">
                    </div>
                </div>

            </div>

            <!-- Colonne latérale -->
            <div class="etd-form-col etd-form-col--side">

                <div class="etd-form-block">
                    <h2 class="etd-form-block__title">Matière &amp; Type</h2>

                    <div class="form-group">
                        <label for="matiere_id">Matière universitaire</label>
                        <select id="matiere_id" name="matiere_id" class="etd-select">
                            <option value="">— Choisir une matière —</option>
                            <?php
                            $currentCat = '';
                            foreach ($matieres as $mat):
                                if ($mat['categorie'] !== $currentCat):
                                    if ($currentCat !== '') echo '</optgroup>';
                                    $currentCat = $mat['categorie'];
                                    echo '<optgroup label="' . \App\Core\Security::escape($mat['categorie']) . '">';
                                endif;
                            ?>
                            <option value="<?= (int)$mat['id'] ?>" <?= (int)($data['matiere_id'] ?? 0) === (int)$mat['id'] ? 'selected' : '' ?>>
                                <?= $e($mat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if ($currentCat !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type_exercice">Type d'exercice</label>
                        <select id="type_exercice" name="type_exercice" class="etd-select">
                            <?php foreach ($typesExercice as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($data['type_exercice'] ?? 'devoir') === $val ? 'selected' : '' ?>><?= $e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau_difficulte">Niveau de difficulté</label>
                        <select id="niveau_difficulte" name="niveau_difficulte" class="etd-select">
                            <?php foreach ($niveauxDiff as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($data['niveau_difficulte'] ?? 'moyen') === $val ? 'selected' : '' ?>><?= $e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="etd-form-block">
                    <h2 class="etd-form-block__title">Délai &amp; Urgence</h2>

                    <div class="form-group">
                        <label for="date_limite">Date limite de rendu</label>
                        <input type="date" id="date_limite" name="date_limite"
                               value="<?= $e($data['date_limite'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>Urgence</label>
                        <div class="etd-urgence-btns">
                            <?php foreach ($urgences as $val => $lbl): ?>
                            <label class="etd-urgence-btn <?= ($data['urgence'] ?? 'normale') === $val ? 'active' : '' ?> etd-urgence-btn--<?= $val ?>">
                                <input type="radio" name="urgence" value="<?= $val ?>" <?= ($data['urgence'] ?? 'normale') === $val ? 'checked' : '' ?>>
                                <?= $e($lbl) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="etd-form-actions">
                    <button type="submit" class="etd-btn etd-btn--primary etd-btn--block">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Soumettre l'exercice
                    </button>
                    <a href="<?= $baseUrl ?>/etudiant/exercices" class="etd-btn etd-btn--ghost etd-btn--block">Annuler</a>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
(function(){
    var urgenceBtns = document.querySelectorAll('.etd-urgence-btn');
    urgenceBtns.forEach(function(btn){
        btn.addEventListener('click', function(){
            urgenceBtns.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
        });
    });
})();
</script>
