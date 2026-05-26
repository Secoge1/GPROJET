<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$csrfField  = \App\Core\Security::getCsrfField();
$e          = fn($s) => \App\Core\Security::escape((string)($s ?? ''));
$r          = $reservation ?? [];
$livraisons = $livraisons ?? [];
$errors     = $errors ?? [];
$flashOk    = $flashOk ?? null;

$services = [
    ['nom' => 'WeTransfer',   'url' => 'https://wetransfer.com',   'desc' => '2 Go gratuit'],
    ['nom' => 'Smash',        'url' => 'https://fromsmash.com',    'desc' => 'Illimité'],
    ['nom' => 'Google Drive', 'url' => 'https://drive.google.com', 'desc' => '15 Go'],
];

function ext_icon_mobile(string $ext): string {
    if (in_array($ext, ['doc','docx','odt'], true))  return '📄';
    if (in_array($ext, ['xls','xlsx','csv'], true))  return '📊';
    if (in_array($ext, ['ppt','pptx'], true))        return '📑';
    if (in_array($ext, ['mdb','accdb'], true))       return '🗄️';
    if ($ext === 'pdf')                              return '📕';
    if (in_array($ext, ['zip','rar'], true))         return '🗜️';
    return '📎';
}
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl ?>/expert/reservations" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div>
        <h1 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--primary)">Livrer le travail</h1>
        <p style="margin:0;font-size:0.78rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Mission : <?= $e($r['demande_titre'] ?? 'Réservation #' . (int)($r['id'] ?? 0)) ?></p>
    </div>
</div>

<?php if ($flashOk): ?>
<div class="mobile-flash-success"><?= $e($flashOk) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?><p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Onglets -->
<div style="display:flex;gap:0.4rem;margin-bottom:1rem" id="livraison-tabs">
    <button type="button" id="tab-fichier" onclick="switchTabMobile('fichier')"
            style="flex:1;padding:0.6rem;border-radius:var(--radius);font-size:0.82rem;font-weight:600;cursor:pointer;border:1.5px solid var(--accent);background:var(--accent);color:#fff">
        📎 Fichier(s)
    </button>
    <button type="button" id="tab-video" onclick="switchTabMobile('video')"
            style="flex:1;padding:0.6rem;border-radius:var(--radius);font-size:0.82rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:transparent;color:var(--text-muted)">
        🔗 Lien vidéo
    </button>
</div>

<!-- Formulaire fichier -->
<form method="post" enctype="multipart/form-data"
      action="<?= $baseUrl ?>/expert/livrer/<?= (int)($r['id'] ?? 0) ?>"
      id="form-fichier-m" style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <?= $csrfField ?>
    <input type="hidden" name="type" value="fichier">

    <div style="border:2px dashed var(--border);border-radius:var(--radius);padding:1.5rem 1rem;text-align:center;margin-bottom:0.85rem;background:#f8fafc">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:0.5rem"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <p style="margin:0 0 0.5rem;font-size:0.87rem;font-weight:600;color:var(--text)">Joindre les fichiers</p>
        <p style="margin:0 0 0.75rem;font-size:0.75rem;color:var(--text-muted)">PDF, Word, Excel, Access, ZIP… · Max 20 Mo</p>
        <input type="file" name="fichiers[]" multiple
               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mdb,.accdb,.odt,.ods,.odp,.rtf,.txt,.csv,.zip,.rar"
               style="display:block;width:100%;font-size:14px;padding:0.35rem 0" onchange="previewMobile(this)">
    </div>
    <div id="file-preview-m" style="display:none;margin-bottom:0.75rem">
        <ul id="file-list-m" style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.3rem"></ul>
    </div>
    <div style="margin-bottom:0.85rem">
        <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Message au client (optionnel)</label>
        <textarea name="commentaire" rows="3" placeholder="Ex : Voici les fichiers demandés…"
                  style="display:block;width:100%;padding:0.7rem 0.9rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"></textarea>
    </div>
    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Livrer les fichiers
    </button>
</form>

<!-- Formulaire vidéo/lien -->
<form method="post"
      action="<?= $baseUrl ?>/expert/livrer/<?= (int)($r['id'] ?? 0) ?>"
      id="form-video-m" style="display:none;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <?= $csrfField ?>
    <input type="hidden" name="type" value="video">

    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:var(--radius);padding:0.85rem;margin-bottom:0.85rem">
        <p style="margin:0 0 0.5rem;font-size:0.78rem;font-weight:700;color:#6d28d9">Services gratuits recommandés :</p>
        <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-bottom:0.5rem">
            <?php foreach ($services as $svc): ?>
            <a href="<?= htmlspecialchars($svc['url']) ?>" target="_blank" rel="noopener"
               style="display:inline-flex;flex-direction:column;padding:0.3rem 0.65rem;background:#fff;border:1px solid #ddd6fe;border-radius:8px;font-size:0.72rem;text-decoration:none;color:#4c1d95">
                <strong><?= $e($svc['nom']) ?></strong>
                <span style="color:#8b5cf6;font-size:0.68rem"><?= $e($svc['desc']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <p style="margin:0;font-size:0.72rem;color:#6d28d9">Uploadez → Copiez le lien → Collez ci-dessous</p>
    </div>

    <div style="margin-bottom:0.85rem">
        <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Lien de partage <span style="color:#dc2626">*</span></label>
        <input type="url" name="lien_externe" required
               placeholder="https://wetransfer.com/…"
               style="display:block;width:100%;padding:0.7rem 0.9rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
    </div>
    <div style="margin-bottom:0.85rem">
        <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Message (optionnel)</label>
        <textarea name="commentaire" rows="3" placeholder="Ex : Le lien expire dans 7 jours…"
                  style="display:block;width:100%;padding:0.7rem 0.9rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"></textarea>
    </div>
    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;background:#7c3aed;border-color:#7c3aed">
        🔗 Livrer le lien
    </button>
</form>

<!-- Historique livraisons -->
<?php if (!empty($livraisons)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Livraisons envoyées (<?= count($livraisons) ?>)</h2>
    </div>
    <?php foreach ($livraisons as $lv): ?>
    <?php
    $ext  = strtolower(pathinfo($lv['nom_fichier'] ?? '', PATHINFO_EXTENSION));
    $icon = $lv['type'] === 'video' ? '🎬' : ext_icon_mobile($ext);
    $date = !empty($lv['created_at']) ? date('d/m/Y à H:i', strtotime($lv['created_at'])) : '';
    ?>
    <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <span style="font-size:1.5rem;flex-shrink:0"><?= $icon ?></span>
        <div style="flex:1;min-width:0">
            <?php if ($lv['type'] === 'video'): ?>
            <a href="<?= $e($lv['lien_externe'] ?? '#') ?>" target="_blank" rel="noopener"
               style="display:block;font-size:0.87rem;font-weight:600;color:#1d4ed8;word-break:break-all;text-decoration:none">Vidéo / Lien externe →</a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/fichier/livraison/<?= (int)$lv['id'] ?>" download
               style="display:block;font-size:0.87rem;font-weight:600;color:#1d4ed8;word-break:break-all;text-decoration:none"><?= $e($lv['nom_fichier'] ?? 'Fichier') ?> ↓</a>
            <?php endif; ?>
            <?php if (!empty($lv['commentaire'])): ?>
            <p style="margin:0.2rem 0 0;font-size:0.78rem;color:var(--text-muted);font-style:italic">«&nbsp;<?= $e(mb_substr($lv['commentaire'], 0, 80)) ?>&nbsp;»</p>
            <?php endif; ?>
            <?php if ($date): ?>
            <p style="margin:0.15rem 0 0;font-size:0.7rem;color:var(--text-muted)"><?= $e($date) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function switchTabMobile(type) {
    var isF = type === 'fichier';
    document.getElementById('tab-fichier').style.background   = isF  ? 'var(--accent)' : 'transparent';
    document.getElementById('tab-fichier').style.color        = isF  ? '#fff' : 'var(--text-muted)';
    document.getElementById('tab-fichier').style.borderColor  = isF  ? 'var(--accent)' : 'var(--border)';
    document.getElementById('tab-video').style.background     = !isF ? 'var(--accent)' : 'transparent';
    document.getElementById('tab-video').style.color          = !isF ? '#fff' : 'var(--text-muted)';
    document.getElementById('tab-video').style.borderColor    = !isF ? 'var(--accent)' : 'var(--border)';
    document.getElementById('form-fichier-m').style.display   = isF  ? '' : 'none';
    document.getElementById('form-video-m').style.display     = !isF ? '' : 'none';
}
function previewMobile(input) {
    var preview = document.getElementById('file-preview-m');
    var list    = document.getElementById('file-list-m');
    list.innerHTML = '';
    if (!input.files || !input.files.length) { preview.style.display = 'none'; return; }
    var icons = {pdf:'📕',doc:'📄',docx:'📄',xls:'📊',xlsx:'📊',ppt:'📑',pptx:'📑',zip:'🗜️'};
    Array.from(input.files).forEach(function(f) {
        var ext = f.name.split('.').pop().toLowerCase();
        var li  = document.createElement('li');
        li.textContent = (icons[ext] || '📎') + '  ' + f.name + '  (' + (f.size / 1024).toFixed(0) + ' Ko)';
        li.style.cssText = 'font-size:0.78rem;background:#f8fafc;border:1px solid var(--border);border-radius:6px;padding:0.3rem 0.6rem;color:var(--text)';
        list.appendChild(li);
    });
    preview.style.display = 'block';
}
</script>
