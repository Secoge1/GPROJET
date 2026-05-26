<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$reservation   = $reservation ?? [];
$messages      = $messages ?? [];
$reservationId = (int)$reservation['id'];
$dashboardUrl  = $baseUrl . (($user['role'] ?? '') === 'expert' ? '/expert' : '/client');
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };

/**
 * Retourne l'icône SVG + la couleur selon le type MIME ou l'extension du fichier.
 * @return array{svg:string, color:string, label:string}
 */
function fileTypeInfo(string $nomFichier, string $mime = ''): array
{
    $ext = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
    // Détecter par MIME ou extension
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'], true) || str_starts_with($mime, 'image/')) {
        return ['color'=>'#0ea5e9','label'=>'Image',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'];
    }
    if (in_array($ext, ['pdf'], true) || $mime === 'application/pdf') {
        return ['color'=>'#ef4444','label'=>'PDF',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h1.5a1.5 1.5 0 0 0 0-3H9v6m6-6h-2v6m2-4h-2"/></svg>'];
    }
    if (in_array($ext, ['doc','docx'], true) || str_contains($mime, 'word')) {
        return ['color'=>'#2563eb','label'=>'Word',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>'];
    }
    if (in_array($ext, ['xls','xlsx'], true) || str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel')) {
        return ['color'=>'#16a34a','label'=>'Excel',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="17"/><line x1="16" y1="13" x2="8" y2="17"/></svg>'];
    }
    if (in_array($ext, ['ppt','pptx'], true) || str_contains($mime, 'presentation')) {
        return ['color'=>'#f97316','label'=>'PowerPoint',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="10" cy="14" r="2"/><path d="M12 14h4"/></svg>'];
    }
    if (in_array($ext, ['zip','rar','gz','tar','7z'], true) || str_contains($mime, 'zip') || str_contains($mime, 'compressed')) {
        return ['color'=>'#7c3aed','label'=>'Archive',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'];
    }
    if (in_array($ext, ['mp4','avi','mov','mkv','webm'], true) || str_starts_with($mime, 'video/')) {
        return ['color'=>'#db2777','label'=>'Vidéo',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>'];
    }
    if (in_array($ext, ['mp3','wav','ogg','m4a'], true) || str_starts_with($mime, 'audio/')) {
        return ['color'=>'#0891b2','label'=>'Audio',
            'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>'];
    }
    // Défaut : fichier générique
    return ['color'=>'#64748b','label'=>'Fichier',
        'svg'=>'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'];
}

/** Formate une taille de fichier en octets lisiblement. */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' Mo';
    if ($bytes >= 1024)    return round($bytes / 1024)       . ' Ko';
    return $bytes . ' o';
}
?>
<section class="section-desktop page-messages page-messages-conversation conv-page">
    <header class="conv-header">
        <a href="<?= $baseUrl ?>/messages" class="conv-header__back" aria-label="Retour aux messages">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Liste des conversations
        </a>
        <div class="conv-header__main">
            <div class="conv-header__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="conv-header__text">
                <h1 class="conv-header__title">Conversation — Réservation #<?= $reservationId ?></h1>
                <p class="conv-header__sub">
                    <a href="<?= $baseUrl ?>/session/room/<?= $reservationId ?>" class="conv-header__session-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        Session / visio
                    </a>
                </p>
            </div>
        </div>
    </header>

    <div id="messages-container" class="conv-messages">
        <?php foreach ($messages as $m):
            $isMine = (int)$m['expediteur_id'] === (int)$user['id'];
        ?>
        <div class="message-item <?= $isMine ? 'mine' : 'theirs' ?>" data-id="<?= (int)$m['id'] ?>">
            <div class="message-item__bubble">
                <div class="message-item__head">
                    <strong class="message-item__sender"><?= $e(trim($m['prenom'] . ' ' . $m['nom'])) ?></strong>
                    <span class="message-date" title="<?= $e($m['created_at']) ?>">
                        <?= $e(date('d/m/Y H:i', strtotime($m['created_at']))) ?>
                    </span>
                </div>
                <?php if (!empty($m['contenu'])): ?>
                <div class="message-item__body"><?= nl2br($e($m['contenu'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($m['pieces'])): ?>
                <ul class="message-pieces">
                    <?php foreach ($m['pieces'] as $p):
                        $fi = fileTypeInfo($p['nom_fichier'] ?? '', $p['type_mime'] ?? '');
                        $taille = isset($p['taille']) ? formatFileSize((int)$p['taille']) : '';
                    ?>
                    <li class="message-piece-item">
                        <a href="<?= $e($p['url']) ?>" target="_blank" rel="noopener" class="message-piece-link"
                           title="Télécharger <?= $e($p['nom_fichier']) ?>">
                            <span class="message-piece-icon" style="color:<?= $fi['color'] ?>">
                                <?= $fi['svg'] ?>
                            </span>
                            <span class="message-piece-name"><?= $e($p['nom_fichier']) ?></span>
                            <?php if ($taille): ?>
                            <span class="message-piece-size"><?= $e($taille) ?></span>
                            <?php endif; ?>
                            <span class="message-piece-type-badge" style="background:<?= $fi['color'] ?>20;color:<?= $fi['color'] ?>"><?= $fi['label'] ?></span>
                            <svg class="message-piece-dl" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form id="message-form" class="conv-form" enctype="multipart/form-data">
        <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">
        <?= \App\Core\Security::getCsrfField() ?>
        <div class="conv-form__row">
            <textarea id="contenu" name="contenu" rows="2" placeholder="Votre message…" class="conv-form__input"></textarea>
            <button type="submit" class="conv-form__send" aria-label="Envoyer" id="btn-send">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
        <div class="conv-form__files">
            <label for="pieces" class="conv-form__file-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                Pièces jointes
            </label>
            <input type="file" id="pieces" name="pieces[]" multiple
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.zip,.rar"
                   class="conv-form__file-input">
            <span id="file-preview" class="conv-form__file-preview"></span>
        </div>
    </form>
</section>

<style>
/* ── Pièces jointes : icônes et badges ─── */
.message-pieces       { list-style:none; margin:.5rem 0 0; padding:0; display:flex; flex-direction:column; gap:.35rem; }
.message-piece-item   { display:flex; }
.message-piece-link   {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.35rem .65rem; border-radius:8px;
    background:rgba(0,0,0,.04); border:1px solid rgba(0,0,0,.08);
    text-decoration:none; color:inherit; font-size:.82rem;
    transition:background .15s;
    max-width:360px;
}
.message-piece-link:hover { background:rgba(0,0,0,.09); }
.message-piece-icon   { flex-shrink:0; display:flex; }
.message-piece-name   { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.message-piece-size   { font-size:.72rem; color:#9ca3af; flex-shrink:0; }
.message-piece-type-badge {
    font-size:.65rem; font-weight:700; padding:.1rem .4rem;
    border-radius:4px; flex-shrink:0; text-transform:uppercase; letter-spacing:.04em;
}
.message-piece-dl     { flex-shrink:0; opacity:.5; }
.mine .message-piece-link { background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.25); }
.mine .message-piece-link:hover { background:rgba(255,255,255,.25); }
.conv-form__file-preview { font-size:.78rem; color:#64748b; margin-left:.5rem; }
</style>

<script>
(function() {
    var form      = document.getElementById('message-form');
    var container = document.getElementById('messages-container');
    var contenu   = document.getElementById('contenu');
    var btnSend   = document.getElementById('btn-send');
    var resId     = <?= $reservationId ?>;
    var baseUrl   = <?= json_encode($baseUrl) ?>;
    var myId      = <?= (int)($user['id'] ?? 0) ?>;

    // ── Prévisualisation des fichiers sélectionnés ──────────────────
    document.getElementById('pieces').addEventListener('change', function() {
        var names = Array.from(this.files).map(function(f) { return f.name; });
        document.getElementById('file-preview').textContent = names.length ? names.join(', ') : '';
    });

    // ── Icône fichier JS (miroir de la fonction PHP) ────────────────
    function fileIcon(filename, mime) {
        mime = mime || '';
        var ext = (filename.split('.').pop() || '').toLowerCase();
        var images = ['jpg','jpeg','png','gif','webp','svg','bmp'];
        if (images.indexOf(ext) >= 0 || mime.startsWith('image/'))
            return {color:'#0ea5e9', label:'Image', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'};
        if (ext === 'pdf' || mime === 'application/pdf')
            return {color:'#ef4444', label:'PDF', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'};
        if (['doc','docx'].indexOf(ext) >= 0 || mime.includes('word'))
            return {color:'#2563eb', label:'Word', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'};
        if (['xls','xlsx'].indexOf(ext) >= 0 || mime.includes('excel') || mime.includes('spreadsheet'))
            return {color:'#16a34a', label:'Excel', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'};
        if (['zip','rar','gz','tar','7z'].indexOf(ext) >= 0 || mime.includes('zip'))
            return {color:'#7c3aed', label:'Archive', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'};
        return {color:'#64748b', label:'Fichier', svg:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'};
    }

    function buildPiecesHtml(pieces) {
        if (!pieces || !pieces.length) return '';
        var html = '<ul class="message-pieces">';
        pieces.forEach(function(p) {
            var fi = fileIcon(p.nom_fichier || '', p.type_mime || '');
            html += '<li class="message-piece-item"><a href="' + p.url + '" target="_blank" rel="noopener" class="message-piece-link" title="Télécharger ' + (p.nom_fichier||'') + '">';
            html += '<span class="message-piece-icon" style="color:' + fi.color + '">' + fi.svg + '</span>';
            html += '<span class="message-piece-name">' + (p.nom_fichier || 'Fichier').replace(/</g,'&lt;') + '</span>';
            html += '<span class="message-piece-type-badge" style="background:' + fi.color + '20;color:' + fi.color + '">' + fi.label + '</span>';
            html += '<svg class="message-piece-dl" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
            html += '</a></li>';
        });
        html += '</ul>';
        return html;
    }

    function buildMessageHtml(m, isMine) {
        var now   = m.created_at || new Date().toLocaleString('fr-FR');
        var name  = isMine ? 'Moi' : ((m.prenom || '') + ' ' + (m.nom || '')).trim();
        var html  = '<div class="message-item__bubble">';
        html     += '<div class="message-item__head"><strong class="message-item__sender">' + name + '</strong><span class="message-date">' + now + '</span></div>';
        if (m.contenu) html += '<div class="message-item__body">' + m.contenu.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>') + '</div>';
        html     += buildPiecesHtml(m.pieces);
        html     += '</div>';
        return html;
    }

    // ── Envoi du message ────────────────────────────────────────────
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        btnSend.disabled = true;
        var body  = new FormData(form);
        var token = document.querySelector('meta[name="csrf-token"]');
        var xhr   = new XMLHttpRequest();
        xhr.open('POST', baseUrl + '/api/messages/send');
        if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token.getAttribute('content'));
        xhr.onload = function() {
            btnSend.disabled = false;
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success) {
                        var div = document.createElement('div');
                        div.className = 'message-item mine';
                        div.setAttribute('data-id', String(d.id || ''));
                        div.innerHTML = buildMessageHtml({contenu:d.contenu, pieces:d.pieces||[], created_at: new Date().toLocaleString('fr-FR')}, true);
                        container.appendChild(div);
                        container.scrollTop = container.scrollHeight;
                        contenu.value = '';
                        document.getElementById('pieces').value = '';
                        document.getElementById('file-preview').textContent = '';
                    }
                } catch(err) {}
            }
        };
        xhr.onerror = function() { btnSend.disabled = false; };
        xhr.send(body);
    });

    // ── Notification sonore (même moteur que app.js) ─────────────────
    function playNotifSound() {
        if (typeof window.GlobaloPlayNotifSound === 'function') {
            window.GlobaloPlayNotifSound();
            return;
        }
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.45);
            setTimeout(function() { try { ctx.close(); } catch(ex) {} }, 700);
        } catch(ex) {}
    }

    function showBrowserNotif(name, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || !document.hidden) return;
        try {
            new Notification('Message de ' + name, {
                body: body ? String(body).substring(0, 80) : 'Nouveau message reçu',
                icon: baseUrl + '/assets/images/logo.png',
                tag:  'chat-' + resId
            });
        } catch(ex) {}
    }

    function askNotifPermission() {
        if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
    }
    document.addEventListener('click',   askNotifPermission, {once:true});
    document.addEventListener('keydown', askNotifPermission, {once:true});

    // ── Polling nouveaux messages ────────────────────────────────────
    setInterval(function() {
        var lastEl = container.querySelector('.message-item:last-child');
        var lastId = lastEl ? (lastEl.getAttribute('data-id') || 0) : 0;
        var xhr2   = new XMLHttpRequest();
        xhr2.open('GET', baseUrl + '/api/messages/list?reservation_id=' + resId + '&after_id=' + lastId);
        xhr2.onload = function() {
            if (xhr2.status !== 200) return;
            try {
                var d = JSON.parse(xhr2.responseText);
                var hasOther = false, lastSender = '', lastBody = '';
                (d.messages || []).forEach(function(m) {
                    if (container.querySelector('[data-id="' + m.id + '"]')) return;
                    var div = document.createElement('div');
                    div.className  = 'message-item ' + (m.mine ? 'mine' : 'theirs');
                    div.dataset.id = m.id;
                    div.innerHTML  = buildMessageHtml(m, m.mine);
                    container.appendChild(div);
                    container.scrollTop = container.scrollHeight;
                    if (!m.mine) { hasOther = true; lastSender = ((m.prenom||'')+' '+(m.nom||'')).trim(); lastBody = m.contenu || ''; }
                });
                if (hasOther) { playNotifSound(); showBrowserNotif(lastSender, lastBody); }
            } catch(ex) {}
        };
        xhr2.send();
    }, 3000);

    // Scroll au bas au chargement
    container.scrollTop = container.scrollHeight;
})();
</script>
