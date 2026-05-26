<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$basePath     = ($user['role'] ?? '') === 'professeur' ? '/professeur' : '/etudiant';
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$profil       = $profil ?? [];
$matieres     = $matieres ?? [];
$mesMatiereIds = $mes_matiere_ids ?? [];
$mesMatieres   = $mes_matieres ?? [];
$errors        = $errors ?? [];
$csrfField     = \App\Core\Security::getCsrfField();

$niveauxEtude = ['Licence 1','Licence 2','Licence 3','Master 1','Master 2','Doctorat','BTS','DUT','Autre'];
$paysAO = ['Mali',"Côte d'Ivoire",'Sénégal','Bénin','Niger','Autre'];
$niveauxMaitrise = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé','expert'=>'Expert'];
$profilProfesseur = $profil_professeur ?? [];
$isProfesseur = ($user['role'] ?? '') === 'professeur';
$profValide = $isProfesseur && !empty($profilProfesseur['valide_par_admin']);
$profDispo = $isProfesseur && !empty($profilProfesseur['disponible']);

$mesMatiereNiveaux = [];
$mesMatiereNotes   = [];
foreach ($mesMatieres as $mm) {
    $mesMatiereNiveaux[$mm['matiere_id']] = $mm['niveau_maitrise'];
    $mesMatiereNotes[$mm['matiere_id']]   = $mm['note_obtenue'];
}
?>
<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <h1 class="etd-page__title"><?= ($user['role'] ?? '') === 'professeur' ? 'Mon profil professeur' : 'Mon profil étudiant' ?></h1>
            <p class="etd-page__sub">Informations universitaires et matières maîtrisées</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="etd-alert etd-alert--error">
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="etd-flash etd-flash--success">
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <form method="post" action="<?= $baseUrl . $basePath ?>/profil" class="etd-form">
        <?= $csrfField ?>

        <div class="etd-profil-grid">

            <!-- Infos académiques -->
            <div class="etd-form-block etd-form-block--academic">
                <h2 class="etd-form-block__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    Informations universitaires
                </h2>

                <div class="etd-form-row">
                    <div class="form-group">
                        <label for="universite">Université / École</label>
                        <input type="text" id="universite" name="universite" maxlength="200"
                               value="<?= $e($profil['universite'] ?? '') ?>"
                               placeholder="Ex: Université Cheikh Anta Diop de Dakar">
                    </div>
                    <div class="form-group">
                        <label for="pays">Pays</label>
                        <select id="pays" name="pays" class="etd-select">
                            <option value="">— Choisir —</option>
                            <?php foreach ($paysAO as $p): ?>
                            <option value="<?= $e($p) ?>" <?= ($profil['pays'] ?? '') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="etd-form-row">
                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" maxlength="100"
                               value="<?= $e($profil['ville'] ?? '') ?>"
                               placeholder="Ex: Dakar, Abidjan, Bamako…">
                    </div>
                    <div class="form-group">
                        <label for="filiere">Filière / Département</label>
                        <input type="text" id="filiere" name="filiere" maxlength="150"
                               value="<?= $e($profil['filiere'] ?? '') ?>"
                               placeholder="Ex: Mathématiques, Gestion, Informatique…">
                    </div>
                </div>

                <div class="form-group">
                    <label for="niveau_etude">Niveau d'étude</label>
                    <div class="etd-niveau-pills" role="group">
                        <?php foreach ($niveauxEtude as $niv): ?>
                        <label class="etd-niveau-pill <?= ($profil['niveau_etude'] ?? 'Licence 1') === $niv ? 'active' : '' ?>">
                            <input type="radio" name="niveau_etude" value="<?= $e($niv) ?>"
                                   <?= ($profil['niveau_etude'] ?? 'Licence 1') === $niv ? 'checked' : '' ?>>
                            <?= $e($niv) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio">Bio / Description</label>
                    <textarea id="bio" name="bio" rows="3" maxlength="1000"
                              placeholder="Décrivez brièvement votre parcours académique, vos centres d'intérêt…"><?= $e($profil['bio'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if ($isProfesseur): ?>
            <!-- Tarif horaire professeur -->
            <div class="etd-form-block etd-form-block--tarif" style="grid-column:1/-1">
                <h2 class="etd-form-block__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Mon tarif horaire
                </h2>
                <p style="margin:0 0 1rem;font-size:.85rem;color:#64748b">
                    Définissez votre tarif en <strong>FCFA par heure</strong>. Ce montant sera visible par les étudiants et utilisé pour le calcul du coût des sessions.
                </p>
                <div class="etd-form-row" style="max-width:320px">
                    <div class="form-group">
                        <label for="tarif_horaire">Tarif horaire (FCFA)</label>
                        <div style="position:relative;display:flex;align-items:center">
                            <input type="number" id="tarif_horaire" name="tarif_horaire"
                                   min="0" max="999999" step="100"
                                   value="<?= number_format((float)($profilProfesseur['tarif_horaire'] ?? 0), 0, '.', '') ?>"
                                   placeholder="Ex : 5000"
                                   style="padding-right:3.5rem">
                            <span style="position:absolute;right:.85rem;font-size:.82rem;font-weight:600;color:#64748b;pointer-events:none">FCFA</span>
                        </div>
                        <?php if (empty($profilProfesseur['tarif_horaire']) || (float)($profilProfesseur['tarif_horaire'] ?? 0) == 0): ?>
                        <p style="margin:.4rem 0 0;font-size:.8rem;color:#f59e0b;font-weight:500">
                            ⚠ Votre tarif est à 0 FCFA — les étudiants ne pourront pas vous réserver tant qu'il n'est pas défini.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="etd-form-block etd-form-block--disponibilite" style="grid-column:1/-1">
                <h2 class="etd-form-block__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Disponibilité
                </h2>
                <?php if (!$profValide): ?>
                <p style="margin:0;font-size:.85rem;color:#64748b;line-height:1.5">
                    Votre profil doit être <strong>validé par l’administrateur</strong> avant de pouvoir apparaître comme disponible sur le site et l’app.
                </p>
                <?php else: ?>
                <div class="profil-form__disponibilite">
                    <label class="profil-form__toggle-label">
                        <input type="checkbox" name="disponible" value="1"
                            <?= $profDispo ? 'checked' : '' ?>
                            class="profil-form__toggle-input">
                        <span class="profil-form__toggle-track">
                            <span class="profil-form__toggle-thumb"></span>
                        </span>
                        <span class="profil-form__toggle-text">
                            <strong><?= $profDispo ? 'En ligne — disponible pour les réservations' : 'Hors ligne' ?></strong>
                            <span><?= $profDispo
                                ? 'Votre fiche est visible dans la liste des professeurs.'
                                : 'Vous n’apparaissez pas dans les recherches publiques.' ?></span>
                        </span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Matières maîtrisées -->
            <div class="etd-form-block etd-form-block--matieres">
                <h2 class="etd-form-block__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Matières maîtrisées
                </h2>
                <p class="etd-form-block__desc">
                    Cochez les matières que vous maîtrisez et indiquez votre niveau. Cela permet un suivi personnalisé de vos exercices.
                </p>

                <?php foreach ($matieres as $categorie => $mats): ?>
                <details class="etd-matiere-group" <?= array_filter($mats, fn($m) => in_array((int)$m['id'], $mesMatiereIds, true)) ? 'open' : '' ?>>
                    <summary class="etd-matiere-group__title">
                        <?php
                        $icons = ['Sciences exactes'=>'📐','Sciences de la vie'=>'🧬','Sciences humaines'=>'🌍',
                                  'Sciences juridiques'=>'⚖️','Sciences économiques'=>'📊','Informatique & Numérique'=>'💻',
                                  'Lettres & Langues'=>'📚','Santé & Médecine'=>'🏥','Agriculture & Environnement'=>'🌱',
                                  'Architecture & BTP'=>'🏗️','Autres'=>'📦'];
                        echo ($icons[$categorie] ?? '📖') . ' ' . $e($categorie);
                        $countMine = count(array_filter($mats, fn($m) => in_array((int)$m['id'], $mesMatiereIds, true)));
                        if ($countMine > 0): ?>
                        <span class="etd-matiere-group__badge"><?= $countMine ?> sélectionnée<?= $countMine > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="etd-matiere-checkboxes">
                        <?php foreach ($mats as $mat): ?>
                        <?php $isMine = in_array((int)$mat['id'], $mesMatiereIds, true); ?>
                        <div class="etd-matiere-row <?= $isMine ? 'etd-matiere-row--active' : '' ?>" id="matrow-<?= (int)$mat['id'] ?>">
                            <label class="etd-matiere-check">
                                <input type="checkbox" name="matieres[]" value="<?= (int)$mat['id'] ?>"
                                       <?= $isMine ? 'checked' : '' ?>
                                       onchange="toggleMatiereOptions(<?= (int)$mat['id'] ?>, this.checked)">
                                <span class="etd-matiere-check__name"><?= $e($mat['nom']) ?></span>
                                <span class="etd-matiere-check__code"><?= $e($mat['code'] ?? '') ?></span>
                            </label>
                            <div class="etd-matiere-options" id="matopts-<?= (int)$mat['id'] ?>" style="<?= !$isMine ? 'display:none' : '' ?>">
                                <select name="niveau_<?= (int)$mat['id'] ?>" class="etd-select etd-select--sm">
                                    <?php foreach ($niveauxMaitrise as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= ($mesMatiereNiveaux[(int)$mat['id']] ?? 'intermediaire') === $val ? 'selected' : '' ?>><?= $e($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="etd-note-input">
                                    <input type="number" name="note_<?= (int)$mat['id'] ?>"
                                           min="0" max="20" step="0.25"
                                           value="<?= $mesMatiereNotes[(int)$mat['id']] !== null ? number_format((float)($mesMatiereNotes[(int)$mat['id']] ?? 0), 2, '.', '') : '' ?>"
                                           placeholder="/20"
                                           class="etd-input etd-input--sm">
                                    <span class="etd-note-unit">/20</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="etd-form-actions etd-form-actions--bottom">
            <button type="submit" class="etd-btn etd-btn--primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer le profil
            </button>
            <a href="<?= $baseUrl ?>/etudiant" class="etd-btn etd-btn--ghost">Retour au tableau de bord</a>
        </div>
    </form>
</div>

<script>
function toggleMatiereOptions(id, checked) {
    var opts = document.getElementById('matopts-' + id);
    var row  = document.getElementById('matrow-' + id);
    if (opts) opts.style.display = checked ? '' : 'none';
    if (row)  row.classList.toggle('etd-matiere-row--active', checked);
}

(function(){
    var pills = document.querySelectorAll('.etd-niveau-pill');
    pills.forEach(function(p){
        p.addEventListener('click', function(){
            pills.forEach(function(pp){ pp.classList.remove('active'); });
            p.classList.add('active');
        });
    });
})();
</script>
