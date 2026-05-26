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

<div class="prof-corriger prof-corriger--mobile">
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.85rem">
        <a href="<?= $e($listUrl) ?>" class="prof-corriger__back" style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <span style="font-size:.78rem;font-weight:600;color:var(--text-muted)">Correction en cours</span>
    </div>

    <header class="prof-corriger__mobile-hero">
        <h1><?= $e($ex['titre'] ?? 'Exercice') ?></h1>
        <p>
            <?php if ($etudiantNom !== ''): ?>Étudiant : <?= $e($etudiantNom) ?><?php endif; ?>
            <?php if (!empty($ex['matiere_nom'])): ?><?= $etudiantNom !== '' ? ' · ' : '' ?><?= $e($ex['matiere_nom']) ?><?php endif; ?>
        </p>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="prof-corriger__alert" role="alert" style="margin-bottom:1rem">
        <?php foreach ($errors as $err): ?>
        <p>⚠️ <?= $e($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Énoncé -->
    <section class="prof-corriger__mobile-card" aria-labelledby="enonce-title">
        <h2 id="enonce-title" style="margin:0 0 .65rem;font-size:.82rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:.04em">Énoncé</h2>
        <div class="prof-corriger__tags" style="margin-bottom:.75rem">
            <?php if (!empty($ex['matiere_nom'])): ?>
            <span class="prof-corriger__tag prof-corriger__tag--matiere"><?= $e($ex['matiere_nom']) ?></span>
            <?php endif; ?>
            <span class="prof-corriger__tag"><?= $e($typeLabel[$ex['type_exercice'] ?? 'autre'] ?? '') ?></span>
            <span class="prof-corriger__tag"><?= $e($diffLabel[$ex['niveau_difficulte'] ?? 'moyen'] ?? '') ?></span>
        </div>
        <p style="margin:0;font-size:.84rem;line-height:1.6;color:var(--text);white-space:pre-wrap"><?= $e($ex['description'] ?? '') ?></p>
        <?php if (!empty($ex['fichier'])): ?>
        <a href="<?= $baseUrl ?>/uploads/<?= $e($ex['fichier']) ?>" target="_blank" rel="noopener" class="prof-corriger__attach">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66"/></svg>
            Pièce jointe
        </a>
        <?php endif; ?>
        <?php if (!empty($ex['lien_ressource'])): ?>
        <a href="<?= $e($ex['lien_ressource']) ?>" target="_blank" rel="noopener noreferrer" class="prof-corriger__attach">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Ressource
        </a>
        <?php endif; ?>
    </section>

    <form method="post" action="<?= ($bp === '/app') ? $baseUrl.'/app/corriger/'.(int)($ex['id']??0) : $baseUrl.'/professeur/corriger/'.(int)($ex['id']??0) ?>">
        <?= $csrfField ?>

        <section class="prof-corriger__mobile-card">
            <h2 style="margin:0 0 1rem;font-size:.95rem;font-weight:800;color:var(--primary)">Votre correction</h2>
            <p style="margin:-0.5rem 0 1rem;font-size:.78rem;color:var(--text-muted);line-height:1.45">L'étudiant confirmera ensuite que sa demande est résolue.</p>

            <div class="prof-corriger__field">
                <label class="prof-corriger__label" for="solution">
                    <span>Solution <span class="prof-corriger__req">*</span></span>
                </label>
                <textarea name="solution" id="solution" required rows="10"
                          class="prof-corriger__textarea"
                          placeholder="Correction détaillée…"><?= $e($form['solution']) ?></textarea>
            </div>

            <div class="prof-corriger__field">
                <label class="prof-corriger__label" for="commentaire_expert">
                    <span>Commentaire</span>
                    <span class="prof-corriger__label-hint">Optionnel</span>
                </label>
                <textarea name="commentaire_expert" id="commentaire_expert" rows="4"
                          class="prof-corriger__textarea"
                          placeholder="Conseils pour l'étudiant…"><?= $e($form['commentaire_expert']) ?></textarea>
            </div>

            <div class="prof-corriger__field" style="margin-bottom:0">
                <label class="prof-corriger__label" for="note_finale">
                    <span>Note /20</span>
                    <span class="prof-corriger__label-hint">Optionnel</span>
                </label>
                <input type="number" name="note_finale" id="note_finale"
                       class="prof-corriger__input prof-corriger__input--note"
                       min="0" max="20" step="0.5" placeholder="14.5"
                       value="<?= $e($form['note_finale']) ?>">
            </div>
        </section>

        <!-- Espace pour la barre fixe -->
        <div style="height:1rem" aria-hidden="true"></div>

        <div class="prof-corriger__sticky">
            <button type="submit" class="prof-corriger__submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
                Envoyer la correction
            </button>
        </div>
    </form>
</div>
