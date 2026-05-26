<?php
$csrfField = \App\Core\Security::getCsrfField();
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$data      = $data ?? [];
$errors    = $errors ?? [];
$competences = $competences ?? [];
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero cl-page__hero--narrow">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client/demandes" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Mes demandes
            </a>
            <h1 class="cl-page__title">Nouvelle demande d'assistance</h1>
            <p class="cl-page__sub">Décrivez votre besoin : un expert qualifié vous répondra rapidement.</p>
        </div>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $baseUrl ?>/client/demandes/nouvelle" class="cl-form" enctype="multipart/form-data">
        <?= $csrfField ?>

        <!-- Section 1 : Description -->
        <div class="cl-card cl-form__section">
            <div class="cl-form__section-head">
                <div class="cl-form__section-num">1</div>
                <div>
                    <h2 class="cl-form__section-title">Description du besoin</h2>
                    <p class="cl-form__section-sub">Soyez précis pour attirer les meilleurs experts.</p>
                </div>
            </div>

            <div class="cl-form__field">
                <label for="titre" class="cl-form__label">
                    Titre de la demande <span class="cl-form__required">*</span>
                </label>
                <input type="text" id="titre" name="titre" required maxlength="200"
                       value="<?= $e($data['titre'] ?? '') ?>"
                       placeholder="Ex. Correction bug sur mon application Flutter"
                       class="cl-form__input">
                <span class="cl-form__hint">Court et descriptif · max. 200 caractères</span>
            </div>

            <div class="cl-form__field">
                <label for="description" class="cl-form__label">
                    Description détaillée <span class="cl-form__required">*</span>
                </label>
                <textarea id="description" name="description" rows="6" required
                          placeholder="Décrivez le contexte, ce que vous avez déjà essayé, et ce dont vous avez besoin..."
                          class="cl-form__textarea"><?= $e($data['description'] ?? '') ?></textarea>
            </div>

            <div class="cl-form__field">
                <label for="competence_id" class="cl-form__label">Compétence requise</label>
                <div class="cl-form__select-wrap">
                    <select id="competence_id" name="competence_id" class="cl-form__select">
                        <option value="">— Sélectionner une compétence —</option>
                        <?php foreach ($competences as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($data['competence_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= $e($c['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="cl-form__select-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
        </div>

        <!-- Section 2 : Durée & urgence -->
        <div class="cl-card cl-form__section">
            <div class="cl-form__section-head">
                <div class="cl-form__section-num">2</div>
                <div>
                    <h2 class="cl-form__section-title">Durée & urgence</h2>
                    <p class="cl-form__section-sub">Aidez l'expert à préparer sa réponse.</p>
                </div>
            </div>

            <div class="cl-form__row">
                <div class="cl-form__field">
                    <label for="duree_estimee_heures" class="cl-form__label">Durée estimée (heures)</label>
                    <div class="cl-form__input-group">
                        <input type="number" id="duree_estimee_heures" name="duree_estimee_heures"
                               min="0.5" max="8" step="0.5"
                               value="<?= $e($data['duree_estimee_heures'] ?? '1') ?>"
                               class="cl-form__input cl-form__input--short">
                        <span class="cl-form__input-suffix">h</span>
                    </div>
                    <span class="cl-form__hint">Entre 0,5 h et 8 h</span>
                </div>

                <div class="cl-form__field">
                    <label class="cl-form__label">Niveau d'urgence</label>
                    <div class="cl-urgence-picker">
                        <label class="cl-urgence-option <?= ($data['urgence'] ?? 'normale') === 'normale' ? 'cl-urgence-option--selected' : '' ?>">
                            <input type="radio" name="urgence" value="normale" class="cl-urgence-radio"
                                   <?= ($data['urgence'] ?? 'normale') === 'normale' ? 'checked' : '' ?>>
                            <span class="cl-urgence-dot cl-urgence-dot--gray"></span>
                            Normale
                        </label>
                        <label class="cl-urgence-option <?= ($data['urgence'] ?? '') === 'urgent' ? 'cl-urgence-option--selected' : '' ?>">
                            <input type="radio" name="urgence" value="urgent" class="cl-urgence-radio"
                                   <?= ($data['urgence'] ?? '') === 'urgent' ? 'checked' : '' ?>>
                            <span class="cl-urgence-dot cl-urgence-dot--orange"></span>
                            Urgent
                        </label>
                        <label class="cl-urgence-option <?= ($data['urgence'] ?? '') === 'tres_urgent' ? 'cl-urgence-option--selected' : '' ?>">
                            <input type="radio" name="urgence" value="tres_urgent" class="cl-urgence-radio"
                                   <?= ($data['urgence'] ?? '') === 'tres_urgent' ? 'checked' : '' ?>>
                            <span class="cl-urgence-dot cl-urgence-dot--red"></span>
                            Très urgent
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3 : Pièces jointes & vidéo -->
        <div class="cl-card cl-form__section">
            <div class="cl-form__section-head">
                <div class="cl-form__section-num">3</div>
                <div>
                    <h2 class="cl-form__section-title">Pièces jointes &amp; vidéo <span class="cl-form__section-opt">(optionnel)</span></h2>
                    <p class="cl-form__section-sub">Joignez un document ou partagez un lien vidéo pour mieux illustrer votre besoin.</p>
                </div>
            </div>

            <!-- Upload document -->
            <div class="cl-form__field">
                <label for="fichier" class="cl-form__label">Document joint</label>
                <div class="cl-upload-zone" id="cl-upload-zone">
                    <input type="file" id="fichier" name="fichier"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mdb,.accdb,.odt,.ods,.odp,.txt,.zip,.jpg,.jpeg,.png,.gif,.webp"
                           class="cl-upload-input" aria-describedby="fichier-hint">
                    <div class="cl-upload-zone__body" id="cl-upload-body">
                        <div class="cl-upload-zone__icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="12" y1="18" x2="12" y2="12"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <p class="cl-upload-zone__label">
                            <span class="cl-upload-zone__cta">Choisir un fichier</span>
                            <span class="cl-upload-zone__or"> ou glisser-déposer ici</span>
                        </p>
                        <p class="cl-upload-zone__meta" id="fichier-hint">PDF · Word · Excel · Access · PowerPoint · Images · ZIP · Max. 10 Mo</p>
                    </div>
                    <div class="cl-upload-zone__preview" id="cl-upload-preview" hidden>
                        <span id="cl-upload-icon" class="cl-upload-zone__file-icon" aria-hidden="true"></span>
                        <span id="cl-upload-filename" class="cl-upload-zone__filename"></span>
                        <button type="button" class="cl-upload-zone__remove" id="cl-upload-remove" aria-label="Supprimer le fichier">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lien vidéo externe -->
            <div class="cl-form__field" style="margin-top:1.25rem;">
                <label for="lien_video" class="cl-form__label">
                    Lien vidéo explicative
                    <span class="cl-form__section-opt" style="font-weight:400">(WeTransfer, Google Drive, YouTube, Dropbox…)</span>
                </label>
                <div class="cl-video-link-wrap">
                    <span class="cl-video-link-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                    </span>
                    <input type="url" id="lien_video" name="lien_video"
                           value="<?= $e($data['lien_video'] ?? '') ?>"
                           placeholder="https://we.tl/... ou https://drive.google.com/..."
                           class="cl-form__input cl-video-link-input"
                           aria-describedby="lien-video-hint">
                </div>
                <span class="cl-form__hint" id="lien-video-hint">
                    Les vidéos sont trop lourdes pour être envoyées directement. Utilisez un service gratuit :
                    <a href="https://wetransfer.com" target="_blank" rel="noopener" class="cl-link">WeTransfer</a>,
                    <a href="https://drive.google.com" target="_blank" rel="noopener" class="cl-link">Google Drive</a>,
                    <a href="https://www.dropbox.com" target="_blank" rel="noopener" class="cl-link">Dropbox</a>,
                    <a href="https://youtube.com" target="_blank" rel="noopener" class="cl-link">YouTube</a>
                    — puis collez le lien ici.
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="cl-form__footer">
            <a href="<?= $baseUrl ?>/client/demandes" class="cl-btn cl-btn--outline">Annuler</a>
            <button type="submit" class="cl-btn cl-btn--amber">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                Publier la demande
            </button>
        </div>
    </form>

</div>

<script>
(function () {
    /* ---- Urgence picker ---- */
    var radios = document.querySelectorAll('.cl-urgence-radio');
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('.cl-urgence-option').forEach(function (o) { o.classList.remove('cl-urgence-option--selected'); });
            if (r.checked) r.closest('.cl-urgence-option').classList.add('cl-urgence-option--selected');
        });
    });

    /* ---- Upload document (multi-format) ---- */
    var input   = document.getElementById('fichier');
    var zone    = document.getElementById('cl-upload-zone');
    var body    = document.getElementById('cl-upload-body');
    var preview = document.getElementById('cl-upload-preview');
    var fname   = document.getElementById('cl-upload-filename');
    var ficon   = document.getElementById('cl-upload-icon');
    var rmBtn   = document.getElementById('cl-upload-remove');

    var allowedExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','mdb','accdb','odt','ods','odp','txt','zip','jpg','jpeg','png','gif','webp'];

    var extMeta = {
        pdf:  { label:'PDF',        color:'#dc2626' },
        doc:  { label:'Word',       color:'#2563eb' },
        docx: { label:'Word',       color:'#2563eb' },
        xls:  { label:'Excel',      color:'#16a34a' },
        xlsx: { label:'Excel',      color:'#16a34a' },
        ppt:  { label:'PowerPoint', color:'#ea580c' },
        pptx: { label:'PowerPoint', color:'#ea580c' },
        mdb:  { label:'Access',     color:'#7c3aed' },
        accdb:{ label:'Access',     color:'#7c3aed' },
        odt:  { label:'Writer',     color:'#0891b2' },
        ods:  { label:'Calc',       color:'#0d9488' },
        odp:  { label:'Impress',    color:'#d97706' },
        txt:  { label:'TXT',        color:'#6b7280' },
        zip:  { label:'ZIP',        color:'#92400e' },
        jpg:  { label:'Image',      color:'#db2777' },
        jpeg: { label:'Image',      color:'#db2777' },
        png:  { label:'Image',      color:'#db2777' },
        gif:  { label:'Image',      color:'#db2777' },
        webp: { label:'Image',      color:'#db2777' },
    };

    function getExt(name) { return (name.split('.').pop() || '').toLowerCase(); }

    function fileBadgeSvg(color, label) {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
             + '<span style="font-size:.72rem;font-weight:700;color:' + color + ';letter-spacing:.03em">' + label + '</span>';
    }

    function showFile(file) {
        if (!file) return;
        var ext  = getExt(file.name);
        var meta = extMeta[ext] || { label: ext.toUpperCase(), color: '#64748b' };
        body.hidden    = true;
        preview.hidden = false;
        ficon.innerHTML = fileBadgeSvg(meta.color, meta.label);
        fname.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' Mo)';
        zone.classList.add('cl-upload-zone--filled');
    }

    function clearFile() {
        input.value     = '';
        body.hidden     = false;
        preview.hidden  = true;
        fname.textContent = '';
        ficon.innerHTML = '';
        zone.classList.remove('cl-upload-zone--filled', 'cl-upload-zone--drag');
    }

    function isAllowed(file) {
        var ext = getExt(file.name);
        return allowedExts.indexOf(ext) !== -1;
    }

    input.addEventListener('change', function () {
        if (input.files && input.files[0]) showFile(input.files[0]);
        else clearFile();
    });

    rmBtn.addEventListener('click', clearFile);

    /* Drag & drop */
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('cl-upload-zone--drag'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('cl-upload-zone--drag'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('cl-upload-zone--drag');
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files[0]) {
            var f = dt.files[0];
            if (isAllowed(f)) {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(f);
                input.files = dataTransfer.files;
                showFile(f);
            } else {
                alert('Format non autorisé. Formats acceptés : PDF, Word, Excel, Access, PowerPoint, images, ZIP, TXT.\nPour une vidéo, utilisez le champ lien vidéo ci-dessous.');
            }
        }
    });
})();
</script>
