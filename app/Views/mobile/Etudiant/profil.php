<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$basePath      = $base_path ?? (($user['role'] ?? '') === 'professeur' ? '/professeur' : '/etudiant');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$profil        = $profil ?? [];
$matieres      = $matieres ?? [];
$mesMatiereIds = $mes_matiere_ids ?? [];
$mesMatieres   = $mes_matieres ?? [];
$errors        = $errors ?? [];
$csrfField     = \App\Core\Security::getCsrfField();

$niveauxEtude    = ['Licence 1','Licence 2','Licence 3','Master 1','Master 2','Doctorat','BTS','DUT','Autre'];
$paysAO          = ['Mali',"Côte d'Ivoire",'Sénégal','Bénin','Niger','Autre'];
$niveauxMaitrise = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé', 'expert' => 'Expert'];
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

$catIcons = [
    'Sciences exactes'=>'📐','Sciences de la vie'=>'🧬','Sciences humaines'=>'🌍',
    'Sciences juridiques'=>'⚖️','Sciences économiques'=>'📊','Informatique & Numérique'=>'💻',
    'Lettres & Langues'=>'📚','Santé & Médecine'=>'🏥','Agriculture & Environnement'=>'🌱',
    'Architecture & BTP'=>'🏗️','Autres'=>'📦',
];
?>
<style>
.ep-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem}
.ep-back{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f1f5f9;color:#64748b;text-decoration:none;flex-shrink:0;transition:background .15s}
.ep-back:active{background:#e2e8f0}
.ep-title{margin:0;font-size:1.15rem;font-weight:700;color:#0f172a}
.ep-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:1rem}
.ep-card-head{display:flex;align-items:center;gap:.6rem;padding:.85rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.ep-card-head span.ep-icon{font-size:1rem}
.ep-card-head h2{margin:0;font-size:.88rem;font-weight:700;color:#0f172a;text-transform:uppercase;letter-spacing:.04em}
.ep-card-body{padding:1rem}
.ep-field{margin-bottom:.9rem}
.ep-field:last-child{margin-bottom:0}
.ep-label{display:block;font-size:.82rem;font-weight:600;color:#1e293b;margin-bottom:.35rem}
.ep-input,.ep-select,.ep-textarea{display:block;width:100%;padding:.75rem 1rem;font-size:16px;font-family:var(--font,'Plus Jakarta Sans',sans-serif);border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#1e293b;transition:border-color .15s,box-shadow .15s;outline:none}
.ep-input:focus,.ep-select:focus,.ep-textarea:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.ep-textarea{resize:vertical;min-height:90px}
.ep-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.ep-pills{display:flex;flex-wrap:wrap;gap:.4rem}
.ep-pill{display:inline-flex;align-items:center;padding:.35rem .8rem;border-radius:999px;font-size:.8rem;font-weight:600;cursor:pointer;border:1.5px solid #e2e8f0;background:transparent;color:#64748b;transition:all .15s}
.ep-pill.sel{border-color:#16a34a;background:#dcfce7;color:#16a34a}
.ep-errors{background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem}
.ep-errors p{margin:0 0 .2rem;font-size:.85rem;color:#dc2626}
.ep-flash{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem;font-size:.88rem;color:#15803d;font-weight:500}
.ep-cat-details{border:1.5px solid #e2e8f0;border-radius:12px;margin-bottom:.5rem;overflow:hidden}
.ep-cat-summary{padding:.75rem 1rem;background:#f8fafc;font-size:.85rem;font-weight:600;color:#0f172a;cursor:pointer;display:flex;align-items:center;justify-content:space-between;list-style:none}
.ep-cat-summary::-webkit-details-marker{display:none}
.ep-mat-row{display:flex;flex-direction:column;margin-bottom:.5rem;padding:.5rem 0;border-bottom:1px solid #f1f5f9}
.ep-mat-row:last-child{margin-bottom:0;border-bottom:none}
.ep-mat-check{display:flex;align-items:center;gap:.6rem;cursor:pointer}
.ep-mat-opts{margin-top:.4rem;margin-left:1.9rem;display:flex;gap:.5rem;align-items:center}
.ep-mat-select{flex:1;padding:.4rem .6rem;font-size:14px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;font-family:var(--font,'Plus Jakarta Sans',sans-serif);outline:none}
.ep-mat-select:focus{border-color:#16a34a}
.ep-mat-note{width:66px;padding:.4rem .5rem;font-size:14px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;font-family:var(--font,'Plus Jakarta Sans',sans-serif);outline:none}
.ep-mat-note:focus{border-color:#16a34a}
.ep-submit{display:flex;align-items:center;justify-content:center;gap:.55rem;width:100%;padding:.9rem 1.5rem;background:#16a34a;color:#fff;font-size:1rem;font-weight:700;font-family:var(--font,'Plus Jakarta Sans',sans-serif);border:none;border-radius:12px;cursor:pointer;margin-bottom:.75rem;box-shadow:0 4px 12px rgba(22,163,74,.25);letter-spacing:.01em;transition:background .15s,transform .1s}
.ep-submit:active{background:#15803d;transform:scale(.975)}
</style>

<div class="ep-header">
    <a href="<?= $baseUrl . $basePath ?>" class="ep-back" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 class="ep-title"><?= ($user['role'] ?? '') === 'professeur' ? 'Mon profil professeur' : 'Mon profil étudiant' ?></h1>
</div>

<?php if (!empty($errors)): ?>
<div class="ep-errors">
    <?php foreach ($errors as $err): ?>
    <p>• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="ep-flash">✓ <?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<form method="post" action="<?= $baseUrl . $basePath ?>/profil">
    <?= $csrfField ?>

    <!-- Informations universitaires -->
    <div class="ep-card">
        <div class="ep-card-head">
            <span class="ep-icon">🎓</span>
            <h2>Informations universitaires</h2>
        </div>
        <div class="ep-card-body">
            <div class="ep-field">
                <label class="ep-label">Université / École</label>
                <input type="text" name="universite" maxlength="200" class="ep-input"
                       value="<?= $e($profil['universite'] ?? '') ?>"
                       placeholder="Ex : Université Cheikh Anta Diop de Dakar">
            </div>

            <div class="ep-field ep-grid">
                <div>
                    <label class="ep-label">Pays</label>
                    <select name="pays" class="ep-select">
                        <option value="">— Pays —</option>
                        <?php foreach ($paysAO as $p): ?>
                        <option value="<?= $e($p) ?>" <?= ($profil['pays'] ?? '') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="ep-label">Ville</label>
                    <input type="text" name="ville" maxlength="100" class="ep-input"
                           value="<?= $e($profil['ville'] ?? '') ?>" placeholder="Ex : Dakar">
                </div>
            </div>

            <div class="ep-field">
                <label class="ep-label">Filière / Département</label>
                <input type="text" name="filiere" maxlength="150" class="ep-input"
                       value="<?= $e($profil['filiere'] ?? '') ?>"
                       placeholder="Ex : Mathématiques, Informatique…">
            </div>

            <div class="ep-field">
                <label class="ep-label">Niveau d'étude</label>
                <div class="ep-pills" id="ep-niveaux-pills">
                    <?php foreach ($niveauxEtude as $niv): ?>
                    <label class="ep-pill <?= ($profil['niveau_etude'] ?? 'Licence 1') === $niv ? 'sel' : '' ?>" id="ep-niv-<?= $e(str_replace(' ', '_', $niv)) ?>">
                        <input type="radio" name="niveau_etude" value="<?= $e($niv) ?>"
                               <?= ($profil['niveau_etude'] ?? 'Licence 1') === $niv ? 'checked' : '' ?>
                               style="display:none">
                        <?= $e($niv) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ep-field">
                <label class="ep-label">Bio</label>
                <textarea name="bio" rows="3" maxlength="1000" class="ep-textarea"
                          placeholder="Décrivez votre parcours académique…"><?= $e($profil['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <?php if ($isProfesseur): ?>
    <!-- Tarif horaire professeur -->
    <div class="ep-card">
        <div class="ep-card-head">
            <span class="ep-icon">💵</span>
            <h2>Mon tarif horaire</h2>
        </div>
        <div class="ep-card-body">
            <p style="margin:0 0 .9rem;font-size:.82rem;color:#64748b">
                Votre tarif en <strong>FCFA/heure</strong>, visible par les étudiants et utilisé pour le calcul des sessions.
            </p>
            <div class="ep-field">
                <label class="ep-label">Tarif horaire (FCFA)</label>
                <div style="position:relative;display:flex;align-items:center">
                    <input type="number" name="tarif_horaire"
                           min="0" max="999999" step="100" class="ep-input"
                           value="<?= number_format((float)($profilProfesseur['tarif_horaire'] ?? 0), 0, '.', '') ?>"
                           placeholder="Ex : 5000"
                           style="padding-right:4rem">
                    <span style="position:absolute;right:.85rem;font-size:.82rem;font-weight:600;color:#64748b;pointer-events:none">FCFA</span>
                </div>
                <?php if (empty($profilProfesseur['tarif_horaire']) || (float)($profilProfesseur['tarif_horaire'] ?? 0) == 0): ?>
                <p style="margin:.45rem 0 0;font-size:.8rem;color:#f59e0b;font-weight:500">
                    ⚠ Tarif à 0 — les étudiants ne peuvent pas vous réserver.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ep-card">
        <div class="ep-card-head">
            <span class="ep-icon">🟢</span>
            <h2>Disponibilité</h2>
        </div>
        <div class="ep-card-body">
            <?php if (!$profValide): ?>
            <p style="margin:0;font-size:.82rem;color:#64748b;line-height:1.5">
                Votre profil doit être <strong>validé par l’administrateur</strong> avant de pouvoir apparaître comme disponible.
            </p>
            <?php else: ?>
            <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;gap:.75rem">
                <div>
                    <p style="margin:0 0 .1rem;font-weight:700;font-size:.92rem;color:var(--primary)">Statut public</p>
                    <p style="margin:0;font-size:.78rem;color:var(--text-muted)">Visible dans la liste des professeurs</p>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">
                    <span style="font-size:.82rem;font-weight:600;color:<?= $profDispo ? '#16a34a' : '#6b7280' ?>"><?= $profDispo ? 'En ligne' : 'Hors ligne' ?></span>
                    <input type="checkbox" name="disponible" value="1" <?= $profDispo ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent)">
                </div>
            </label>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Matières maîtrisées -->
    <div class="ep-card">
        <div class="ep-card-head">
            <span class="ep-icon">📚</span>
            <h2>Matières maîtrisées</h2>
        </div>
        <div class="ep-card-body" style="padding-bottom:1.25rem">
            <p style="margin:0 0 1rem;font-size:.82rem;color:#64748b">
                Cochez les matières que vous maîtrisez et indiquez votre niveau.
            </p>

            <?php foreach ($matieres as $categorie => $mats): ?>
            <?php $countMine = count(array_filter($mats, fn($m) => in_array((int)$m['id'], $mesMatiereIds, true))); ?>
            <details class="ep-cat-details" <?= $countMine > 0 ? 'open' : '' ?>>
                <summary class="ep-cat-summary">
                    <span><?= ($catIcons[$categorie] ?? '📖') . ' ' . $e($categorie) ?></span>
                    <?php if ($countMine > 0): ?>
                    <span style="font-size:.72rem;padding:.15rem .55rem;border-radius:999px;background:#dcfce7;color:#16a34a;font-weight:700"><?= $countMine ?> ✓</span>
                    <?php else: ?>
                    <span style="font-size:.72rem;color:#94a3b8"><?= count($mats) ?></span>
                    <?php endif; ?>
                </summary>
                <div style="padding:.5rem .75rem">
                    <?php foreach ($mats as $mat): ?>
                    <?php $isMine = in_array((int)$mat['id'], $mesMatiereIds, true); ?>
                    <div class="ep-mat-row">
                        <label class="ep-mat-check">
                            <input type="checkbox" name="matieres[]" value="<?= (int)$mat['id'] ?>"
                                   <?= $isMine ? 'checked' : '' ?>
                                   onchange="epToggleMat(<?= (int)$mat['id'] ?>, this.checked)"
                                   style="width:18px;height:18px;flex-shrink:0;accent-color:#16a34a;cursor:pointer">
                            <span style="font-size:.85rem;color:#1e293b"><?= $e($mat['nom']) ?></span>
                        </label>
                        <div id="ep-mat-opts-<?= (int)$mat['id'] ?>" class="ep-mat-opts" style="<?= !$isMine ? 'display:none' : '' ?>">
                            <select name="niveau_<?= (int)$mat['id'] ?>" class="ep-mat-select">
                                <?php foreach ($niveauxMaitrise as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($mesMatiereNiveaux[(int)$mat['id']] ?? 'intermediaire') === $val ? 'selected' : '' ?>><?= $e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="note_<?= (int)$mat['id'] ?>"
                                   min="0" max="20" step="0.25" class="ep-mat-note"
                                   value="<?= $mesMatiereNotes[(int)$mat['id']] !== null ? number_format((float)($mesMatiereNotes[(int)$mat['id']] ?? 0), 2, '.', '') : '' ?>"
                                   placeholder="/20">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit" class="ep-submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer le profil
    </button>
</form>

<?php if (($user['role'] ?? '') === 'professeur'): ?>
<!-- Liens rapides professeur (compte + déconnexion accessibles depuis le profil) -->
<div style="margin-top:1.5rem;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <a href="<?= $baseUrl . $basePath ?>/compte" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:var(--text);border-bottom:1px solid var(--border)">
        <span style="font-size:0.9rem">Mon compte</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $baseUrl ?>/auth/deconnexion" style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1rem;text-decoration:none;color:#dc2626">
        <span style="font-size:0.9rem">Déconnexion</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
</div>
<?php endif; ?>

<script>
function epToggleMat(id, checked) {
    var opts = document.getElementById('ep-mat-opts-' + id);
    if (opts) opts.style.display = checked ? 'flex' : 'none';
}
(function(){
    var pills = document.querySelectorAll('#ep-niveaux-pills .ep-pill');
    pills.forEach(function(p) {
        p.addEventListener('click', function() {
            pills.forEach(function(pp){ pp.classList.remove('sel'); });
            p.classList.add('sel');
        });
    });
})();
</script>
