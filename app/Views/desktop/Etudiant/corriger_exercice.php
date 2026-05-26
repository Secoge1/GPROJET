<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$ex        = $exercice ?? [];
$errors    = $errors ?? [];
$form      = $form ?? ['solution' => '', 'commentaire_expert' => '', 'note_finale' => ''];

$typeLabel = ['devoir' => 'Devoir', 'examen' => 'Examen', 'tp' => 'TP/TD', 'projet' => 'Projet',
              'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Oral', 'autre' => 'Autre'];
$diffLabel = ['facile' => 'Facile', 'moyen' => 'Moyen', 'difficile' => 'Difficile', 'tres_difficile' => 'Très difficile'];

$etudiantNom = trim(($ex['etudiant_prenom'] ?? '') . ' ' . ($ex['etudiant_nom'] ?? ''));
$bp = $base_path ?? '/professeur';
$listUrl = ($bp === '/app') ? $baseUrl . '/app/exercices-disponibles' : $baseUrl . '/professeur/exercices-disponibles';
?>

<div class="etd-page prof-corriger">
    <header class="prof-corriger__hero">
        <div class="prof-corriger__hero-main">
            <a href="<?= $e($listUrl) ?>" class="prof-corriger__back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Retour aux exercices
            </a>
            <h1 class="prof-corriger__title"><?= $e($ex['titre'] ?? 'Exercice') ?></h1>
            <p class="prof-corriger__meta">
                <?php if ($etudiantNom !== ''): ?>
                <span>Étudiant : <?= $e($etudiantNom) ?></span>
                <?php endif; ?>
                <?php if (!empty($ex['matiere_nom'])): ?>
                <span>· <?= $e($ex['matiere_nom']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <span class="prof-corriger__badge">
            <span class="prof-corriger__badge-dot" aria-hidden="true"></span>
            En cours de correction
        </span>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="prof-corriger__alert" role="alert">
        <?php foreach ($errors as $err): ?>
        <p>⚠️ <?= $e($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="prof-corriger__steps" aria-hidden="true">
        <div class="prof-corriger__step prof-corriger__step--active">
            <span class="prof-corriger__step-num">Étape 1</span>
            Lire l'énoncé
        </div>
        <div class="prof-corriger__step prof-corriger__step--active">
            <span class="prof-corriger__step-num">Étape 2</span>
            Rédiger la correction
        </div>
        <div class="prof-corriger__step">
            <span class="prof-corriger__step-num">Étape 3</span>
            Soumettre à l'étudiant
        </div>
    </div>

    <div class="prof-corriger__layout">
        <div class="prof-corriger__main">
            <form method="post" action="<?= ($bp === '/app') ? $baseUrl.'/app/corriger/'.(int)($ex['id']??0) : $baseUrl.'/professeur/corriger/'.(int)($ex['id']??0) ?>" class="prof-corriger__panel">
                <?= $csrfField ?>

                <div class="prof-corriger__panel-head">
                    <h2 class="prof-corriger__panel-title">Votre correction</h2>
                    <p class="prof-corriger__panel-sub">Rédigez une solution claire. L'étudiant sera notifié ; il confirmera ensuite que sa demande est résolue.</p>
                </div>

                <div class="prof-corriger__panel-body">
                    <div class="prof-corriger__field">
                        <label class="prof-corriger__label" for="solution">
                            <span>Solution détaillée <span class="prof-corriger__req">*</span></span>
                            <span class="prof-corriger__label-hint">Méthode, étapes, réponse finale</span>
                        </label>
                        <textarea name="solution" id="solution" required rows="14"
                                  class="prof-corriger__textarea prof-corriger__textarea--lg"
                                  placeholder="Expliquez la démarche, les calculs ou raisonnements, puis donnez la réponse attendue…"><?= $e($form['solution']) ?></textarea>
                    </div>

                    <div class="prof-corriger__field">
                        <label class="prof-corriger__label" for="commentaire_expert">
                            <span>Commentaire pédagogique</span>
                            <span class="prof-corriger__label-hint">Optionnel</span>
                        </label>
                        <textarea name="commentaire_expert" id="commentaire_expert" rows="4"
                                  class="prof-corriger__textarea"
                                  placeholder="Points forts, axes d'amélioration, encouragements…"><?= $e($form['commentaire_expert']) ?></textarea>
                    </div>

                    <div class="prof-corriger__field">
                        <label class="prof-corriger__label" for="note_finale">
                            <span>Note finale</span>
                            <span class="prof-corriger__label-hint">Optionnel</span>
                        </label>
                        <div class="prof-corriger__note-wrap">
                            <input type="number" name="note_finale" id="note_finale"
                                   class="prof-corriger__input prof-corriger__input--note"
                                   min="0" max="20" step="0.5" placeholder="—"
                                   value="<?= $e($form['note_finale']) ?>">
                            <span class="prof-corriger__note-scale">sur 20</span>
                        </div>
                    </div>

                    <div class="prof-corriger__actions">
                        <a href="<?= $e($listUrl) ?>" class="prof-corriger__cancel">Annuler</a>
                        <button type="submit" class="prof-corriger__submit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                            Envoyer la correction
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <aside class="prof-corriger__side">
            <div class="prof-corriger__enonce">
                <h3 class="prof-corriger__enonce-title">Énoncé</h3>
                <div class="prof-corriger__tags">
                    <?php if (!empty($ex['matiere_nom'])): ?>
                    <span class="prof-corriger__tag prof-corriger__tag--matiere"><?= $e($ex['matiere_nom']) ?></span>
                    <?php endif; ?>
                    <span class="prof-corriger__tag"><?= $e($typeLabel[$ex['type_exercice'] ?? ''] ?? 'Exercice') ?></span>
                    <span class="prof-corriger__tag"><?= $e($diffLabel[$ex['niveau_difficulte'] ?? 'moyen'] ?? '') ?></span>
                    <?php if (!empty($ex['date_limite'])): ?>
                    <span class="prof-corriger__tag prof-corriger__tag--deadline">⏰ <?= date('d/m/Y H:i', strtotime($ex['date_limite'])) ?></span>
                    <?php endif; ?>
                </div>
                <div class="prof-corriger__enonce-text"><?= $e($ex['description'] ?? '') ?></div>
                <?php if (!empty($ex['fichier'])): ?>
                <a href="<?= $baseUrl ?>/uploads/<?= $e($ex['fichier']) ?>" target="_blank" rel="noopener" class="prof-corriger__attach">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    Pièce jointe
                </a>
                <?php endif; ?>
                <?php if (!empty($ex['lien_ressource'])): ?>
                <a href="<?= $e($ex['lien_ressource']) ?>" target="_blank" rel="noopener noreferrer" class="prof-corriger__attach">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ressource externe
                </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
