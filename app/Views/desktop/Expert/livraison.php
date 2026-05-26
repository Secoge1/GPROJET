<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$csrfField   = \App\Core\Security::getCsrfField();
$e           = fn($s) => \App\Core\Security::escape((string)($s ?? ''));
$r           = $reservation ?? [];
$livraisons  = $livraisons  ?? [];
$errors      = $errors      ?? [];
$flashOk     = $flashOk     ?? null;

$extLabel = 'PDF, Word (.doc/.docx), Excel (.xls/.xlsx), Access (.mdb/.accdb), PowerPoint, OpenDocument, ZIP…';
$services = [
    ['nom' => 'WeTransfer',  'url' => 'https://wetransfer.com',      'desc' => '2 Go gratuit'],
    ['nom' => 'Smash',       'url' => 'https://fromsmash.com',       'desc' => 'Illimité gratuit'],
    ['nom' => 'Filemail',    'url' => 'https://www.filemail.com',    'desc' => '5 Go gratuit'],
    ['nom' => 'Google Drive','url' => 'https://drive.google.com',    'desc' => '15 Go gratuit'],
    ['nom' => 'Dropbox',     'url' => 'https://www.dropbox.com',     'desc' => '2 Go gratuit'],
];

function ext_icon(string $ext): string {
    if (in_array($ext, ['doc','docx','odt','rtf'], true))  return '📄';
    if (in_array($ext, ['xls','xlsx','csv','ods'], true))  return '📊';
    if (in_array($ext, ['ppt','pptx','odp'], true))        return '📑';
    if (in_array($ext, ['mdb','accdb'], true))             return '🗄️';
    if ($ext === 'pdf')                                    return '📕';
    if (in_array($ext, ['zip','rar'], true))               return '🗜️';
    return '📎';
}
?>
<section class="section-desktop page-expert page-expert-livraison">

    <!-- Hero -->
    <div class="livraison-hero">
        <a href="<?= $baseUrl ?>/expert/reservations" class="page-expert__back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Mes réservations
        </a>
        <div class="livraison-hero__content">
            <div class="livraison-hero__icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div>
                <h1 class="livraison-hero__title">Livrer le travail</h1>
                <p class="livraison-hero__sub">Mission : <strong><?= $e($r['demande_titre'] ?? 'Réservation #' . (int)($r['id'] ?? 0)) ?></strong></p>
            </div>
        </div>
    </div>

    <!-- Flash -->
    <?php if ($flashOk): ?>
    <div class="livraison-alert livraison-alert--success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= $e($flashOk) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="livraison-alert livraison-alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul style="margin:0;padding-left:1.1rem;">
            <?php foreach ($errors as $err): ?>
            <li><?= $e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="livraison-grid">

        <!-- ── COLONNE GAUCHE : Formulaire ── -->
        <div class="livraison-col">

            <!-- Onglets type -->
            <div class="livraison-tabs" id="tabs">
                <button type="button" class="livraison-tab livraison-tab--active" id="tab-fichier" onclick="switchTab('fichier')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Fichier(s) Office
                </button>
                <button type="button" class="livraison-tab" id="tab-video" onclick="switchTab('video')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                    Vidéo / Lien externe
                </button>
            </div>

            <!-- Formulaire fichier -->
            <form method="post" enctype="multipart/form-data"
                  action="<?= $baseUrl ?>/expert/livrer/<?= (int)($r['id'] ?? 0) ?>"
                  id="form-fichier" class="livraison-form">
                <?= $csrfField ?>
                <input type="hidden" name="type" value="fichier">

                <div class="livraison-card">
                    <!-- Zone de dépôt -->
                    <div class="livraison-dropzone" id="dropzone"
                         onclick="document.getElementById('fichiers-input').click()">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p class="livraison-dropzone__title">Cliquez ou glissez vos fichiers ici</p>
                        <p class="livraison-dropzone__hint"><?= htmlspecialchars($extLabel) ?></p>
                        <p class="livraison-dropzone__hint">Max 20 Mo par fichier · 5 fichiers maximum</p>
                        <input type="file" id="fichiers-input" name="fichiers[]" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mdb,.accdb,.odt,.ods,.odp,.rtf,.txt,.csv,.zip,.rar"
                               style="display:none;" onchange="previewFichiers(this)">
                    </div>

                    <!-- Prévisualisation noms de fichiers -->
                    <div id="file-preview" style="display:none;margin-top:.875rem;">
                        <ul id="file-list" class="livraison-file-list"></ul>
                    </div>

                    <!-- Commentaire -->
                    <div class="livraison-field">
                        <label class="livraison-label">
                            Message au client
                            <span class="livraison-optional">optionnel</span>
                        </label>
                        <textarea name="commentaire" rows="3" class="livraison-textarea"
                                  placeholder="Ex: Voici le tableau Excel demandé avec les formules. N'hésitez pas à me contacter si…"></textarea>
                    </div>

                    <button type="submit" class="livraison-submit" id="submit-fichier">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Livrer les fichiers
                    </button>
                </div>
            </form>

            <!-- Formulaire vidéo lien externe -->
            <form method="post"
                  action="<?= $baseUrl ?>/expert/livrer/<?= (int)($r['id'] ?? 0) ?>"
                  id="form-video" class="livraison-form" style="display:none;">
                <?= $csrfField ?>
                <input type="hidden" name="type" value="video">

                <div class="livraison-card">
                    <!-- Services suggérés -->
                    <div class="livraison-services">
                        <p class="livraison-services__title">Services gratuits recommandés :</p>
                        <div class="livraison-services__list">
                            <?php foreach ($services as $svc): ?>
                            <a href="<?= htmlspecialchars($svc['url']) ?>" target="_blank" rel="noopener"
                               class="livraison-service-pill">
                                <strong><?= $e($svc['nom']) ?></strong>
                                <span><?= $e($svc['desc']) ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <ol class="livraison-services__steps">
                            <li>Uploadez votre vidéo sur l'un de ces services</li>
                            <li>Copiez le lien de partage généré</li>
                            <li>Collez-le ci-dessous et livrez</li>
                        </ol>
                    </div>

                    <div class="livraison-field">
                        <label class="livraison-label" for="lien_externe">
                            Lien de partage (WeTransfer, Smash, Drive…)
                            <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="url" id="lien_externe" name="lien_externe" required
                               class="livraison-input"
                               placeholder="https://wetransfer.com/downloads/…">
                    </div>

                    <div class="livraison-field">
                        <label class="livraison-label">
                            Message au client
                            <span class="livraison-optional">optionnel</span>
                        </label>
                        <textarea name="commentaire" rows="3" class="livraison-textarea"
                                  placeholder="Ex: Voici la vidéo de démonstration. Le lien expire dans 7 jours."></textarea>
                    </div>

                    <button type="submit" class="livraison-submit livraison-submit--video">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        Livrer le lien vidéo
                    </button>
                </div>
            </form>
        </div>

        <!-- ── COLONNE DROITE : Historique ── -->
        <div class="livraison-col">
            <div class="livraison-card">
                <h2 class="livraison-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Livraisons envoyées
                    <?php if (!empty($livraisons)): ?>
                    <span class="livraison-count"><?= count($livraisons) ?></span>
                    <?php endif; ?>
                </h2>

                <?php if (empty($livraisons)): ?>
                <div class="livraison-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p>Aucune livraison pour cette mission.</p>
                </div>
                <?php else: ?>
                <ul class="livraison-history">
                    <?php foreach ($livraisons as $lv): ?>
                    <?php
                        $ext  = strtolower(pathinfo($lv['nom_fichier'] ?? '', PATHINFO_EXTENSION));
                        $icon = $lv['type'] === 'video' ? '🎬' : ext_icon($ext);
                        $date = !empty($lv['created_at']) ? date('d/m/Y à H:i', strtotime($lv['created_at'])) : '';
                    ?>
                    <li class="livraison-history__item">
                        <div class="livraison-history__icon"><?= $icon ?></div>
                        <div class="livraison-history__body">
                            <?php if ($lv['type'] === 'video'): ?>
                            <a href="<?= $e($lv['lien_externe'] ?? '#') ?>" target="_blank" rel="noopener"
                               class="livraison-history__name">
                                Vidéo / Lien externe
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                            <?php else: ?>
                            <a href="<?= $baseUrl ?>/fichier/livraison/<?= (int)$lv['id'] ?>"
                               class="livraison-history__name" download>
                                <?= $e($lv['nom_fichier'] ?? 'Fichier') ?>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </a>
                            <?php if (!empty($lv['taille'])): ?>
                            <span class="livraison-history__size"><?= number_format($lv['taille'] / 1024, 0, ',', ' ') ?> Ko</span>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($lv['commentaire'])): ?>
                            <p class="livraison-history__comment">«&nbsp;<?= $e(mb_substr($lv['commentaire'], 0, 120)) ?>&nbsp;»</p>
                            <?php endif; ?>
                            <?php if ($date): ?>
                            <span class="livraison-history__date"><?= $e($date) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.livraison-grid -->

</section>

<style>
.page-expert-livraison{max-width:980px;margin:0 auto;padding:1.25rem 1rem 3rem;}
.livraison-hero{padding:1.25rem 0 1.5rem;}
.livraison-hero__content{display:flex;align-items:center;gap:1rem;margin-top:.75rem;}
.livraison-hero__icon{width:52px;height:52px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;}
.livraison-hero__title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;}
.livraison-hero__sub{color:#64748b;margin:.2rem 0 0;font-size:.9rem;}

.livraison-alert{display:flex;align-items:flex-start;gap:.75rem;padding:.875rem 1rem;border-radius:10px;margin-bottom:1.25rem;font-size:.875rem;}
.livraison-alert--success{background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;}
.livraison-alert--error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}

.livraison-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
@media(max-width:700px){.livraison-grid{grid-template-columns:1fr;}}

.livraison-tabs{display:flex;gap:.5rem;margin-bottom:.875rem;}
.livraison-tab{display:flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border:1.5px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#64748b;font-size:.875rem;font-weight:500;cursor:pointer;transition:all .15s;}
.livraison-tab--active{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8;}
.livraison-tab:hover:not(.livraison-tab--active){background:#f1f5f9;color:#334155;}

.livraison-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.5rem;box-shadow:0 1px 8px rgba(0,0,0,.05);}
.livraison-card__title{display:flex;align-items:center;gap:.5rem;font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1.25rem;}
.livraison-count{background:#eff6ff;color:#1d4ed8;border-radius:20px;font-size:.75rem;font-weight:700;padding:.1rem .55rem;margin-left:.25rem;}

.livraison-dropzone{border:2px dashed #cbd5e1;border-radius:10px;padding:2.5rem 1.5rem;text-align:center;cursor:pointer;transition:border-color .15s,background .15s;}
.livraison-dropzone:hover{border-color:#3b82f6;background:#eff6ff;}
.livraison-dropzone__title{font-size:.9375rem;font-weight:600;color:#334155;margin:.875rem 0 .25rem;}
.livraison-dropzone__hint{font-size:.78rem;color:#94a3b8;margin:.15rem 0;}

.livraison-file-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.4rem;}
.livraison-file-list li{display:flex;align-items:center;gap:.5rem;font-size:.8125rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.4rem .7rem;color:#334155;}

.livraison-field{margin-top:1rem;}
.livraison-label{display:block;font-size:.875rem;font-weight:600;color:#374151;margin-bottom:.4rem;}
.livraison-optional{font-size:.75rem;font-weight:400;color:#94a3b8;margin-left:.3rem;}
.livraison-input,.livraison-textarea{width:100%;box-sizing:border-box;border:1.5px solid #d1d5db;border-radius:8px;padding:.6rem .875rem;font-size:.875rem;color:#0f172a;outline:none;transition:border-color .15s;}
.livraison-input:focus,.livraison-textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);}
.livraison-textarea{resize:vertical;min-height:80px;}

.livraison-submit{display:inline-flex;align-items:center;gap:.5rem;margin-top:1.25rem;padding:.7rem 1.5rem;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s;}
.livraison-submit:hover{background:#1d4ed8;}
.livraison-submit--video{background:#7c3aed;}
.livraison-submit--video:hover{background:#6d28d9;}

.livraison-services{background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:1rem 1.125rem;margin-bottom:.875rem;}
.livraison-services__title{font-size:.8125rem;font-weight:600;color:#6d28d9;margin:0 0 .625rem;}
.livraison-services__list{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.75rem;}
.livraison-service-pill{display:flex;flex-direction:column;background:#fff;border:1px solid #ddd6fe;border-radius:8px;padding:.35rem .75rem;font-size:.75rem;text-decoration:none;color:#4c1d95;line-height:1.3;transition:background .15s;}
.livraison-service-pill:hover{background:#f5f3ff;}
.livraison-service-pill span{color:#8b5cf6;font-size:.7rem;}
.livraison-services__steps{margin:.5rem 0 0;padding-left:1.2rem;font-size:.8rem;color:#6d28d9;line-height:1.7;}

.livraison-empty{text-align:center;padding:2rem 1rem;color:#94a3b8;}
.livraison-empty p{margin:.75rem 0 0;font-size:.875rem;}

.livraison-history{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.875rem;}
.livraison-history__item{display:flex;gap:.75rem;align-items:flex-start;}
.livraison-history__icon{font-size:1.375rem;flex-shrink:0;margin-top:.1rem;}
.livraison-history__body{flex:1;min-width:0;}
.livraison-history__name{display:inline-flex;align-items:center;gap:.3rem;font-size:.875rem;font-weight:600;color:#1d4ed8;text-decoration:none;word-break:break-all;}
.livraison-history__name:hover{text-decoration:underline;}
.livraison-history__size{font-size:.72rem;color:#94a3b8;margin-left:.35rem;}
.livraison-history__comment{font-size:.8rem;color:#64748b;margin:.25rem 0 0;font-style:italic;}
.livraison-history__date{font-size:.72rem;color:#94a3b8;display:block;margin-top:.2rem;}
</style>

<script>
function switchTab(type) {
    var isFile = type === 'fichier';
    document.getElementById('tab-fichier').classList.toggle('livraison-tab--active', isFile);
    document.getElementById('tab-video').classList.toggle('livraison-tab--active', !isFile);
    document.getElementById('form-fichier').style.display = isFile  ? '' : 'none';
    document.getElementById('form-video').style.display   = !isFile ? '' : 'none';
}

function previewFichiers(input) {
    var preview = document.getElementById('file-preview');
    var list    = document.getElementById('file-list');
    list.innerHTML = '';
    if (!input.files || !input.files.length) { preview.style.display = 'none'; return; }
    var icons = {pdf:'📕', doc:'📄', docx:'📄', xls:'📊', xlsx:'📊', ppt:'📑', pptx:'📑', mdb:'🗄️', accdb:'🗄️', zip:'🗜️', rar:'🗜️'};
    Array.from(input.files).forEach(function(f) {
        var ext = f.name.split('.').pop().toLowerCase();
        var ic  = icons[ext] || '📎';
        var kb  = (f.size / 1024).toFixed(0);
        var li  = document.createElement('li');
        li.textContent = ic + '  ' + f.name + '  (' + kb + ' Ko)';
        list.appendChild(li);
    });
    preview.style.display = 'block';
}

// Drag & drop
(function() {
    var dz = document.getElementById('dropzone');
    if (!dz) return;
    dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.style.borderColor='#3b82f6'; dz.style.background='#eff6ff'; });
    dz.addEventListener('dragleave', function()   { dz.style.borderColor=''; dz.style.background=''; });
    dz.addEventListener('drop', function(e) {
        e.preventDefault();
        dz.style.borderColor=''; dz.style.background='';
        var inp = document.getElementById('fichiers-input');
        inp.files = e.dataTransfer.files;
        previewFichiers(inp);
    });
})();
</script>
