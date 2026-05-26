<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$reservation   = $reservation ?? [];
$messages      = $messages ?? [];
$user          = $user ?? [];
$reservationId = (int)($reservation['id'] ?? 0);
$role          = $user['role'] ?? 'client';
$myId          = (int)($user['id'] ?? 0);

if ($role === 'client') {
    $otherName = trim(($reservation['expert_prenom'] ?? '') . ' ' . ($reservation['expert_nom'] ?? ''));
    if (!$otherName) $otherName = $reservation['expert_titre'] ?? 'Expert';
    $otherLabel = $reservation['expert_titre'] ?? 'Expert';
} else {
    $otherName  = trim(($reservation['client_prenom'] ?? '') . ' ' . ($reservation['client_nom'] ?? ''));
    if (!$otherName) $otherName = 'Client';
    $otherLabel = 'Client';
}
$otherInitials = strtoupper(mb_substr(explode(' ', $otherName)[0], 0, 1) . mb_substr(explode(' ', $otherName)[1] ?? '', 0, 1)) ?: '?';
$colors = ['#2563eb','#16a34a','#7c3aed','#0d9488','#d97706'];
$otherColor = $colors[abs(crc32($otherName)) % count($colors)];

$statut_lb = ['en_attente'=>'En attente','confirme'=>'Confirmée','annule'=>'Annulée','termine'=>'Terminée','paye'=>'Payée'];
$resStatut = $reservation['statut'] ?? '';

function fmt_msg_date(string $dt): string {
    $ts = strtotime($dt);
    if ($ts === false) return $dt;
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('H:i', $ts);
    }
    return date('d/m · H:i', $ts);
}
?>

<div id="chat-wrap" class="mob-conv">

    <header class="mob-conv__header">
        <a href="<?= \App\Core\Security::escape($messages_list_url ?? $baseUrl . '/messages') ?>" class="mob-conv__back" aria-label="Retour aux messages">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div class="mob-conv__avatar" style="background:<?= $otherColor ?>"><?= $otherInitials ?></div>
        <div class="mob-conv__head-text">
            <p class="mob-conv__name"><?= $e($otherName) ?></p>
            <p class="mob-conv__label"><?= $e($otherLabel) ?><?php if ($resStatut && isset($statut_lb[$resStatut])): ?> · <span class="mob-conv__statut <?= $resStatut === 'confirme' ? 'mob-conv__statut--ok' : '' ?>"><?= $statut_lb[$resStatut] ?></span><?php endif; ?></p>
        </div>
        <?php if (!empty($reservation['statut']) && $reservation['statut'] === 'confirme'): ?>
        <a href="<?= $baseUrl ?>/session/room/<?= $reservationId ?>" class="mob-conv__session-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
            Session
        </a>
        <?php endif; ?>
    </header>

    <?php if (!empty($reservation['demande_titre'])): ?>
    <div class="mob-conv__demande">📋 <?= $e($reservation['demande_titre']) ?></div>
    <?php endif; ?>

    <div id="messages-container" class="mob-conv__messages">
        <?php if (empty($messages)): ?>
        <div class="mob-conv__empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>Envoyez le premier message !</p>
        </div>
        <?php endif; ?>
        <?php foreach ($messages as $m):
            $isMine = (int)$m['expediteur_id'] === $myId;
            $senderInitials = strtoupper(mb_substr($m['prenom'] ?? '', 0, 1) . mb_substr($m['nom'] ?? '', 0, 1)) ?: '?';
        ?>
        <div class="mob-msg msg-row <?= $isMine ? 'msg-mine' : 'msg-theirs' ?>" data-id="<?= (int)$m['id'] ?>">
            <?php if (!$isMine): ?>
            <div class="mob-msg__avatar" style="background:<?= $otherColor ?>"><?= $senderInitials ?></div>
            <?php endif; ?>
            <div class="mob-msg__content">
                <?php if (!$isMine): ?>
                <span class="mob-msg__sender"><?= $e(trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''))) ?></span>
                <?php endif; ?>
                <?php if (!empty($m['contenu'])): ?>
                <div class="mob-msg__bubble <?= $isMine ? 'mob-msg__bubble--mine' : 'mob-msg__bubble--theirs' ?>"><?= nl2br($e($m['contenu'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($m['pieces'])): ?>
                <div class="mob-msg__pieces">
                    <?php foreach ($m['pieces'] as $p):
                        $ext2 = strtolower(pathinfo($p['nom_fichier'] ?? '', PATHINFO_EXTENSION));
                        $mime2 = $p['type_mime'] ?? '';
                        if (in_array($ext2, ['jpg','jpeg','png','gif','webp'], true) || str_starts_with($mime2, 'image/'))
                            { $pIcon = '🖼️'; $pColor = '#0ea5e9'; $pLabel = 'Image'; }
                        elseif ($ext2 === 'pdf' || $mime2 === 'application/pdf')
                            { $pIcon = '📄'; $pColor = '#ef4444'; $pLabel = 'PDF'; }
                        elseif (in_array($ext2, ['doc','docx'], true))
                            { $pIcon = '📝'; $pColor = '#2563eb'; $pLabel = 'Word'; }
                        elseif (in_array($ext2, ['xls','xlsx'], true))
                            { $pIcon = '📊'; $pColor = '#16a34a'; $pLabel = 'Excel'; }
                        elseif (in_array($ext2, ['zip','rar','gz'], true))
                            { $pIcon = '🗜️'; $pColor = '#7c3aed'; $pLabel = 'Archive'; }
                        else
                            { $pIcon = '📎'; $pColor = '#64748b'; $pLabel = 'Fichier'; }
                    ?>
                    <a href="<?= $e($p['url']) ?>" target="_blank" rel="noopener"
                       class="mob-msg__piece <?= $isMine ? 'mob-msg__piece--mine' : '' ?>"
                       style="border-left:3px solid <?= $pColor ?>">
                        <span style="font-size:1rem"><?= $pIcon ?></span>
                        <span class="mob-piece__name"><?= $e($p['nom_fichier'] ?? 'Fichier') ?></span>
                        <span class="mob-piece__badge" style="background:<?= $pColor ?>20;color:<?= $pColor ?>"><?= $pLabel ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <span class="mob-msg__time"><?= $e(fmt_msg_date($m['created_at'] ?? '')) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form id="message-form" class="mob-conv__form" data-no-nav-loading="1">
        <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">
        <?= \App\Core\Security::getCsrfField() ?>
        <label for="pieces-btn" class="mob-conv__file-btn" aria-label="Joindre un fichier">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            <input type="file" id="pieces-btn" name="pieces[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip">
        </label>
        <div class="mob-conv__input-wrap">
            <textarea id="contenu" name="contenu" rows="1" placeholder="Votre message…" class="mob-conv__input"></textarea>
        </div>
        <button type="submit" id="send-btn" class="mob-conv__send" aria-label="Envoyer">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </form>
    <div id="file-indicator" class="mob-conv__file-indicator"></div>
</div>

<script>
(function() {
    const form      = document.getElementById('message-form');
    const container = document.getElementById('messages-container');
    const contenu   = document.getElementById('contenu');
    const sendBtn   = document.getElementById('send-btn');
    const fileInput = document.getElementById('pieces-btn');
    const fileIndicator = document.getElementById('file-indicator');
    const reservationId = <?= $reservationId ?>;
    const baseUrl   = '<?= $baseUrl ?>';
    const myId      = <?= $myId ?>;
    const otherColor = '<?= $otherColor ?>';

    // Scroll to bottom
    function scrollBottom() {
        container.scrollTop = container.scrollHeight;
    }
    scrollBottom();

    // Indicateur fichier joint
    fileInput.addEventListener('change', function() {
        if (fileInput.files.length) {
            fileIndicator.style.display = 'block';
            fileIndicator.textContent = '📎 ' + Array.from(fileInput.files).map(f => f.name).join(', ');
        } else {
            fileIndicator.style.display = 'none';
        }
    });
    // Auto-resize textarea
    contenu.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // Icône fichier JS
    function mobileFileIcon(filename, mime) {
        mime = mime || '';
        var ext = (filename.split('.').pop() || '').toLowerCase();
        if (['jpg','jpeg','png','gif','webp'].indexOf(ext) >= 0 || mime.startsWith('image/')) return {icon:'🖼️',color:'#0ea5e9',label:'Image'};
        if (ext === 'pdf' || mime === 'application/pdf') return {icon:'📄',color:'#ef4444',label:'PDF'};
        if (['doc','docx'].indexOf(ext) >= 0) return {icon:'📝',color:'#2563eb',label:'Word'};
        if (['xls','xlsx'].indexOf(ext) >= 0) return {icon:'📊',color:'#16a34a',label:'Excel'};
        if (['zip','rar','gz'].indexOf(ext) >= 0) return {icon:'🗜️',color:'#7c3aed',label:'Archive'};
        return {icon:'📎',color:'#64748b',label:'Fichier'};
    }

    function buildMobPiecesHtml(pieces, isMine) {
        if (!pieces || !pieces.length) return '';
        var html = '<div class="mob-msg__pieces">';
        pieces.forEach(function(p) {
            var fi = mobileFileIcon(p.nom_fichier || '', p.type_mime || '');
            var mineClass = isMine ? ' mob-msg__piece--mine' : '';
            html += '<a href="' + p.url + '" target="_blank" rel="noopener" class="mob-msg__piece' + mineClass + '" style="border-left:3px solid ' + fi.color + '">';
            html += '<span style="font-size:1rem">' + fi.icon + '</span>';
            html += '<span class="mob-piece__name">' + (p.nom_fichier||'Fichier').replace(/</g,'&lt;') + '</span>';
            html += '<span class="mob-piece__badge" style="background:' + fi.color + '20;color:' + fi.color + '">' + fi.label + '</span>';
            html += '</a>';
        });
        html += '</div>';
        return html;
    }

    // Créer une bulle HTML (côté moi)
    function makeBubble(id, contenu, time, pieces) {
        const wrap = document.createElement('div');
        wrap.className = 'mob-msg msg-row msg-mine';
        wrap.dataset.id = id;
        let inner = '<div class="mob-msg__content">';
        if (contenu) {
            const safe = String(contenu).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
            inner += '<div class="mob-msg__bubble mob-msg__bubble--mine">' + safe + '</div>';
        }
        if (pieces && pieces.length) inner += buildMobPiecesHtml(pieces, true);
        inner += '<span class="mob-msg__time">' + time + '</span></div>';
        wrap.innerHTML = inner;
        return wrap;
    }

    // Créer une bulle côté "other"
    function makeOtherBubble(m) {
        const wrap = document.createElement('div');
        wrap.className = 'mob-msg msg-row msg-theirs';
        wrap.dataset.id = m.id;
        const initials = ((m.prenom||'').charAt(0) + (m.nom||'').charAt(0)).toUpperCase() || '?';
        let inner = '<div class="mob-msg__avatar" style="background:' + otherColor + '">' + initials + '</div>';
        inner += '<div class="mob-msg__content">';
        inner += '<span class="mob-msg__sender">' + ((m.prenom||'') + ' ' + (m.nom||'')).trim() + '</span>';
        if (m.contenu) {
            const safe = String(m.contenu).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
            inner += '<div class="mob-msg__bubble mob-msg__bubble--theirs">' + safe + '</div>';
        }
        if (m.pieces && m.pieces.length) inner += buildMobPiecesHtml(m.pieces, false);
        inner += '<span class="mob-msg__time">' + (m.created_at||'').slice(11,16) + '</span></div>';
        wrap.innerHTML = inner;
        return wrap;
    }

    // Envoi du message
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!contenu.value.trim() && !fileInput.files.length) return;
        sendBtn.disabled = true;
        sendBtn.style.opacity = '0.5';
        const body = new FormData(form);
        const token = document.querySelector('meta[name="csrf-token"]');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', baseUrl + '/api/messages/send');
        if (token && token.getAttribute('content')) xhr.setRequestHeader('X-CSRF-TOKEN', token.getAttribute('content'));
        xhr.onload = function() {
            sendBtn.disabled = false;
            sendBtn.style.opacity = '1';
            if (xhr.status === 200) {
                try {
                    const d = JSON.parse(xhr.responseText);
                    if (d.success) {
                        const now = new Date();
                        const time = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
                        const bubble = makeBubble(d.id || Date.now(), d.contenu || contenu.value, time, d.pieces || []);
                        container.appendChild(bubble);
                        contenu.value = '';
                        contenu.style.height = 'auto';
                        fileInput.value = '';
                        fileIndicator.style.display = 'none';
                        scrollBottom();
                    }
                } catch(err) {}
            }
        };
        xhr.onerror = function() { sendBtn.disabled = false; sendBtn.style.opacity = '1'; };
        xhr.send(body);
    });

    // Envoi via Entrée (sans Shift)
    contenu.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.metaKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });

    // ── Notification sonore (partagée avec app.js : AudioContext débloqué au geste) ──
    function playNotifSound() {
        if (typeof window.GlobaloPlayNotifSound === 'function') {
            window.GlobaloPlayNotifSound();
            return;
        }
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.28, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.45);
            setTimeout(function() { try { ctx.close(); } catch(e) {} }, 700);
        } catch(e) {}
    }

    // ── Notification navigateur (quand l'onglet est en arrière-plan) ──
    function showBrowserNotif(senderName, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || !document.hidden) return;
        try {
            new Notification('Message de ' + senderName, {
                body: body ? String(body).substring(0, 80) : 'Nouveau message reçu',
                icon: baseUrl + '/assets/images/logo.png',
                tag: 'chat-' + reservationId
            });
        } catch(e) {}
    }

    // Demander la permission dès que l'utilisateur interagit avec la page
    function askNotifPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        document.removeEventListener('click', askNotifPermission);
        document.removeEventListener('keydown', askNotifPermission);
    }
    document.addEventListener('click', askNotifPermission, { once: true });
    document.addEventListener('keydown', askNotifPermission, { once: true });

    // ── Polling nouveaux messages ──
    setInterval(function() {
        const lastEl = container.querySelector('.msg-row:last-child');
        const afterId = lastEl ? (lastEl.getAttribute('data-id') || 0) : 0;
        const xhr = new XMLHttpRequest();
        xhr.open('GET', baseUrl + '/api/messages/list?reservation_id=' + reservationId + '&after_id=' + afterId);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const d = JSON.parse(xhr.responseText);
                    let added = false;
                    let hasOtherMsg = false;
                    let lastSenderName = '';
                    let lastBody = '';
                    (d.messages || []).forEach(function(m) {
                        if (container.querySelector('[data-id="' + m.id + '"]')) return;
                        const isOther = !(parseInt(m.user_id) === myId || m.mine);
                        const bubble = isOther
                            ? makeOtherBubble(m)
                            : makeBubble(m.id, m.contenu, (m.created_at||'').slice(11,16));
                        container.appendChild(bubble);
                        added = true;
                        if (isOther) {
                            hasOtherMsg = true;
                            lastSenderName = ((m.prenom || '') + ' ' + (m.nom || '')).trim();
                            lastBody = m.contenu || '';
                        }
                    });
                    if (added) {
                        scrollBottom();
                        if (hasOtherMsg) {
                            playNotifSound();
                            showBrowserNotif(lastSenderName, lastBody);
                        }
                    }
                } catch(err) {}
            }
        };
        xhr.send();
    }, 3000);
})();
</script>
