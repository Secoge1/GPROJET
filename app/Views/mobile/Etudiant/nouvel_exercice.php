<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$errors    = $errors ?? [];
$data      = $data ?? [];
$matieres  = $matieres ?? [];
$csrfField = \App\Core\Security::getCsrfField();

$typesExercice = ['devoir' => 'Devoir maison', 'examen' => 'Examen / DS', 'tp' => 'TP / TD', 'projet' => 'Projet',
                  'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Préparation oral', 'autre' => 'Autre'];
$niveauxDiff   = ['facile' => 'Facile', 'moyen' => 'Moyen', 'difficile' => 'Difficile', 'tres_difficile' => 'Très difficile'];
$urgences      = ['normale' => 'Normale', 'urgent' => 'Urgent (48h)', 'tres_urgent' => 'Très urgent (24h)'];
?>
<style>
.nex-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem}
.nex-back{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f1f5f9;color:#64748b;text-decoration:none;flex-shrink:0;transition:background .15s}
.nex-back:active{background:#e2e8f0}
.nex-title{margin:0;font-size:1.15rem;font-weight:700;color:#0f172a}
.nex-subtitle{margin:.15rem 0 0;font-size:.8rem;color:#64748b}
.nex-errors{background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem}
.nex-errors p{margin:0 0 .2rem;font-size:.85rem;color:#dc2626}
.nex-section{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:1rem}
.nex-section-head{display:flex;align-items:center;gap:.6rem;padding:.85rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.nex-step{width:22px;height:22px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0}
.nex-section-head span{font-size:.85rem;font-weight:700;color:#0f172a}
.nex-body{padding:1rem}
.nex-field{margin-bottom:.9rem}
.nex-field:last-child{margin-bottom:0}
.nex-label{display:block;font-size:.82rem;font-weight:600;color:#1e293b;margin-bottom:.35rem}
.nex-req{color:#dc2626;margin-left:.1rem}
.nex-input,.nex-select,.nex-textarea{display:block;width:100%;padding:.75rem 1rem;font-size:16px;font-family:var(--font,'Plus Jakarta Sans',sans-serif);border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#1e293b;transition:border-color .15s,box-shadow .15s;outline:none}
.nex-input:focus,.nex-select:focus,.nex-textarea:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.nex-textarea{resize:vertical;min-height:130px;line-height:1.55}
.nex-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.nex-hint{font-size:.73rem;color:#94a3b8;margin:.3rem 0 0}
.nex-chips{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.5rem}
.nex-chip{padding:.35rem .8rem;border-radius:999px;font-size:.8rem;font-weight:600;cursor:pointer;border:1.5px solid #e2e8f0;background:transparent;color:#64748b;transition:all .15s}
.nex-chip.active{border-color:#16a34a;background:#dcfce7;color:#16a34a}
.nex-file-wrap{border:2px dashed #e2e8f0;border-radius:10px;padding:1rem;text-align:center;cursor:pointer;transition:border-color .15s,background .15s}
.nex-file-wrap:focus-within{border-color:#16a34a;background:#f0fdf4}
.nex-file-input{display:block;width:100%;font-size:15px;cursor:pointer;opacity:0;position:absolute;inset:0}
.nex-file-btn{display:flex;flex-direction:column;align-items:center;gap:.35rem;pointer-events:none}
.nex-urgence-pills{display:flex;flex-direction:column;gap:.45rem}
.nex-urgence-pill{display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .15s}
.nex-urgence-pill.active-normale{border-color:#16a34a;background:#f0fdf4}
.nex-urgence-pill.active-urgent{border-color:#f59e0b;background:#fffbeb}
.nex-urgence-pill.active-tres_urgent{border-color:#dc2626;background:#fef2f2}
.nex-urgence-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.nex-submit{display:flex;align-items:center;justify-content:center;gap:.55rem;width:100%;padding:.9rem 1.5rem;background:#16a34a;color:#fff;font-size:1rem;font-weight:700;font-family:var(--font,'Plus Jakarta Sans',sans-serif);border:none;border-radius:12px;cursor:pointer;margin-bottom:.75rem;transition:background .15s,transform .1s,box-shadow .15s;box-shadow:0 4px 12px rgba(22,163,74,.25);letter-spacing:.01em}
.nex-submit:active{background:#15803d;transform:scale(.975);box-shadow:0 2px 6px rgba(22,163,74,.2)}
.nex-cancel{display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;padding:.8rem 1.5rem;border:1.5px solid #e2e8f0;border-radius:12px;background:transparent;color:#64748b;font-size:.9rem;font-weight:600;font-family:var(--font,'Plus Jakarta Sans',sans-serif);cursor:pointer;text-decoration:none;transition:background .15s,border-color .15s}
.nex-cancel:active{background:#f1f5f9;border-color:#cbd5e1}
</style>

<!-- En-tête -->
<div class="nex-header">
    <a href="<?= $baseUrl ?>/etudiant/exercices" class="nex-back" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div>
        <h1 class="nex-title">Soumettre un exercice</h1>
        <p class="nex-subtitle">Décrivez l'énoncé et choisissez la matière</p>
    </div>
</div>

<!-- Erreurs -->
<?php if (!empty($errors)): ?>
<div class="nex-errors">
    <?php foreach ($errors as $err): ?>
    <p>• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= $baseUrl ?>/etudiant/exercices/nouveau" enctype="multipart/form-data" novalidate id="nex-form">
    <?= $csrfField ?>

    <!-- Section 1 : Identification -->
    <div class="nex-section">
        <div class="nex-section-head">
            <span class="nex-step">1</span>
            <span>Identification</span>
        </div>
        <div class="nex-body">
            <div class="nex-field">
                <label class="nex-label">Titre de l'exercice<span class="nex-req">*</span></label>
                <input type="text" name="titre" required maxlength="250" class="nex-input"
                       value="<?= $e($data['titre'] ?? '') ?>"
                       placeholder="Ex : Intégrales — Chapitre 5">
            </div>
            <div class="nex-field">
                <label class="nex-label">Matière</label>
                <select name="matiere_id" class="nex-select">
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
        </div>
    </div>

    <!-- Section 2 : Type & Difficulté -->
    <div class="nex-section">
        <div class="nex-section-head">
            <span class="nex-step">2</span>
            <span>Type &amp; Difficulté</span>
        </div>
        <div class="nex-body">
            <div class="nex-field">
                <label class="nex-label">Type d'exercice</label>
                <div class="nex-chips" id="type-chips">
                    <?php foreach ($typesExercice as $val => $lbl): ?>
                    <button type="button" class="nex-chip <?= ($data['type_exercice'] ?? 'devoir') === $val ? 'active' : '' ?>"
                            data-val="<?= $val ?>" data-group="type_exercice"
                            onclick="pickChip(this,'type_exercice','type-chips')">
                        <?= $e($lbl) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="type_exercice" id="type_exercice" value="<?= $e($data['type_exercice'] ?? 'devoir') ?>">
            </div>
            <div class="nex-field">
                <label class="nex-label">Niveau de difficulté</label>
                <div class="nex-chips" id="diff-chips">
                    <?php
                    $diffColors = ['facile'=>'#16a34a','moyen'=>'#2563eb','difficile'=>'#f59e0b','tres_difficile'=>'#dc2626'];
                    foreach ($niveauxDiff as $val => $lbl):
                    ?>
                    <button type="button" class="nex-chip <?= ($data['niveau_difficulte'] ?? 'moyen') === $val ? 'active' : '' ?>"
                            data-val="<?= $val ?>" data-group="niveau_difficulte"
                            onclick="pickChip(this,'niveau_difficulte','diff-chips')">
                        <?= $e($lbl) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="niveau_difficulte" id="niveau_difficulte" value="<?= $e($data['niveau_difficulte'] ?? 'moyen') ?>">
            </div>
        </div>
    </div>

    <!-- Section 3 : Énoncé -->
    <div class="nex-section">
        <div class="nex-section-head">
            <span class="nex-step">3</span>
            <span>Énoncé</span>
        </div>
        <div class="nex-body">
            <div class="nex-field">
                <label class="nex-label">Description complète<span class="nex-req">*</span></label>
                <textarea name="description" class="nex-textarea" required maxlength="8000"
                          placeholder="Copiez-collez l'énoncé complet ici…"><?= $e($data['description'] ?? '') ?></textarea>
                <p class="nex-hint">Max 8 000 caractères</p>
            </div>
        </div>
    </div>

    <!-- Section 4 : Délai & Urgence -->
    <div class="nex-section">
        <div class="nex-section-head">
            <span class="nex-step">4</span>
            <span>Délai &amp; Urgence</span>
        </div>
        <div class="nex-body">
            <div class="nex-field">
                <label class="nex-label">Date limite (optionnel)</label>
                <input type="date" name="date_limite" class="nex-input"
                       value="<?= $e($data['date_limite'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="nex-field">
                <label class="nex-label">Niveau d'urgence</label>
                <div class="nex-urgence-pills">
                    <?php
                    $urgConf = [
                        'normale'     => ['dot'=>'#16a34a','label'=>'Normale','desc'=>'Pas de délai critique'],
                        'urgent'      => ['dot'=>'#f59e0b','label'=>'Urgent','desc'=>'Réponse souhaitée sous 48h'],
                        'tres_urgent' => ['dot'=>'#dc2626','label'=>'Très urgent','desc'=>'Réponse souhaitée sous 24h'],
                    ];
                    $selectedUrgence = $data['urgence'] ?? 'normale';
                    foreach ($urgConf as $val => $conf):
                    ?>
                    <label class="nex-urgence-pill <?= $selectedUrgence === $val ? 'active-' . $val : '' ?>" id="urg-pill-<?= $val ?>">
                        <input type="radio" name="urgence" value="<?= $val ?>"
                               <?= $selectedUrgence === $val ? 'checked' : '' ?>
                               style="display:none"
                               onchange="updateUrgencePills('<?= $val ?>')">
                        <span class="nex-urgence-dot" style="background:<?= $conf['dot'] ?>"></span>
                        <div>
                            <div style="font-size:.85rem;font-weight:600;color:#1e293b"><?= $conf['label'] ?></div>
                            <div style="font-size:.75rem;color:#64748b"><?= $conf['desc'] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 5 : Pièces jointes -->
    <div class="nex-section">
        <div class="nex-section-head">
            <span class="nex-step">5</span>
            <span>Pièces jointes</span>
        </div>
        <div class="nex-body">
            <div class="nex-field">
                <label class="nex-label">Fichier (optionnel)</label>
                <div class="nex-file-wrap" style="position:relative">
                    <input type="file" name="fichier" class="nex-file-input"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.zip"
                           onchange="showFileName(this,'nex-file-name')">
                    <div class="nex-file-btn">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        <span id="nex-file-name" style="font-size:.82rem;color:#94a3b8;font-weight:500">Toucher pour choisir un fichier</span>
                        <span style="font-size:.72rem;color:#cbd5e1">PDF, Word, Excel, Image, ZIP · Max 10 Mo</span>
                    </div>
                </div>
            </div>
            <div class="nex-field">
                <label class="nex-label">Lien ressource (optionnel)</label>
                <div style="position:relative">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <input type="url" name="lien_ressource" class="nex-input"
                           style="padding-left:2.4rem"
                           value="<?= $e($data['lien_ressource'] ?? '') ?>"
                           placeholder="https://drive.google.com/…">
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <button type="submit" class="nex-submit">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        Soumettre l'exercice
    </button>
    <a href="<?= $baseUrl ?>/etudiant/exercices" class="nex-cancel">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Annuler
    </a>
</form>

<script>
function pickChip(el, hiddenId, chipsId) {
    document.querySelectorAll('#' + chipsId + ' .nex-chip').forEach(function(c){ c.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById(hiddenId).value = el.dataset.val;
}
function updateUrgencePills(selected) {
    var levels = ['normale','urgent','tres_urgent'];
    levels.forEach(function(v){
        var pill = document.getElementById('urg-pill-' + v);
        if (!pill) return;
        pill.className = 'nex-urgence-pill';
        if (v === selected) pill.classList.add('active-' + v);
    });
}
function showFileName(input, spanId) {
    var span = document.getElementById(spanId);
    if (span && input.files && input.files[0]) {
        span.textContent = input.files[0].name;
        span.style.color = '#16a34a';
    }
}
// Init urgence pills click
document.querySelectorAll('.nex-urgence-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
        var radio = this.querySelector('input[type=radio]');
        if (radio) { radio.checked = true; updateUrgencePills(radio.value); }
    });
});
</script>
