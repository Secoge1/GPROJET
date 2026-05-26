<?php
/**
 * Partial — Widget chat IA RH
 * Variables attendues : $agentInfo (array), $welcomeAnalysis (string), $ia_active (bool), $agentType (string)
 */
$agentInfo       = $agentInfo ?? [];
$welcomeAnalysis = $welcomeAnalysis ?? '';
$ia_active       = $ia_active ?? false;
$agentType       = $agentType ?? 'manager';
$sessionId       = 'rh_' . $agentType . '_' . (session_id() ?: uniqid());
?>
<div class="rh-chat" id="rh-chat" data-agent="<?= \App\Core\Security::escape($agentType) ?>" data-session="<?= \App\Core\Security::escape($sessionId) ?>" style="--agent-color:<?= \App\Core\Security::escape($agentInfo['couleur'] ?? '#10b981') ?>">

    <div class="rh-chat__header">
        <div class="rh-chat__agent-info">
            <div class="rh-chat__avatar" style="background:<?= \App\Core\Security::escape($agentInfo['gradient'] ?? '') ?>">
                <span><?= \App\Core\Security::escape($agentInfo['emoji'] ?? '🤖') ?></span>
            </div>
            <div>
                <strong><?= \App\Core\Security::escape($agentInfo['nom'] ?? 'IA') ?></strong>
                <span class="rh-chat__agent-sub"><?= \App\Core\Security::escape($agentInfo['titre'] ?? '') ?></span>
            </div>
        </div>
        <div class="rh-chat__status-badge <?= $ia_active ? 'rh-chat__status-badge--active' : '' ?>">
            <span class="rh-pulse-dot"></span>
            <?= $ia_active ? 'IA Active' : 'Mode hors ligne' ?>
        </div>
    </div>

    <div class="rh-chat__messages" id="rh-chat-messages">
        <!-- Message de bienvenue de l'IA -->
        <div class="rh-chat__msg rh-chat__msg--ai">
            <div class="rh-chat__msg-avatar"><?= \App\Core\Security::escape($agentInfo['emoji'] ?? '🤖') ?></div>
            <div class="rh-chat__msg-bubble">
                <div class="rh-chat__msg-name"><?= \App\Core\Security::escape($agentInfo['nom'] ?? 'IA') ?></div>
                <div class="rh-chat__msg-text rh-markdown"><?= nl2br(\App\Core\Security::escape($welcomeAnalysis)) ?></div>
            </div>
        </div>
    </div>

    <div class="rh-chat__footer">
        <div class="rh-chat__suggestions" id="rh-chat-suggestions">
            <?php
            $suggestions = [
                'inscriptions' => ['Analyse les nouvelles inscriptions', 'Qui valider en priorité ?', 'Génère un rapport hebdomadaire', 'Profils incomplets à relancer'],
                'profils'      => ['Score des profils experts', 'Experts avec profil faible', 'Suggestions d\'amélioration', 'Clients VIP à identifier'],
                'marketing'    => ['Génère une campagne email', 'Segment le plus actif ?', 'Recommandations pour le Mali', 'Idées de contenus réseaux sociaux'],
                'manager'      => ['Résumé de la semaine', 'Points d\'attention prioritaires', 'Comparaison pays', 'Prévisions de croissance'],
            ];
            foreach ($suggestions[$agentType] ?? $suggestions['manager'] as $s):
            ?>
            <button class="rh-chat__suggestion" onclick="rhChatSend(this.textContent)"><?= \App\Core\Security::escape($s) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="rh-chat__input-wrap">
            <textarea
                id="rh-chat-input"
                class="rh-chat__input"
                placeholder="Posez une question à <?= \App\Core\Security::escape($agentInfo['nom'] ?? 'l\'IA') ?>..."
                rows="1"
                maxlength="2000"
                <?= !$ia_active ? 'disabled' : '' ?>
            ></textarea>
            <button
                id="rh-chat-send"
                class="rh-chat__send-btn"
                onclick="rhChatSubmit()"
                <?= !$ia_active ? 'disabled' : '' ?>
                title="Envoyer"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
        <?php if (!$ia_active): ?>
        <p class="rh-chat__no-ia">⚠️ Configurez <code>OPENAI_API_KEY</code>, <code>GEMINI_API_KEY</code> ou <code>MISTRAL_API_KEY</code> dans <code>.env</code> pour activer l'IA.</p>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const chat   = document.getElementById('rh-chat');
    const msgs   = document.getElementById('rh-chat-messages');
    const input  = document.getElementById('rh-chat-input');
    const agent  = chat.dataset.agent;
    const session = chat.dataset.session;
    const baseUrl = document.querySelector('[data-base-url]')?.dataset.baseUrl || '';
    let sending  = false;

    // Auto-resize textarea
    if (input) {
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 140) + 'px';
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); rhChatSubmit(); }
        });
    }

    window.rhChatSend = function(text) {
        if (input) { input.value = text; rhChatSubmit(); }
    };

    window.rhChatSubmit = async function() {
        const text = (input?.value || '').trim();
        if (!text || sending) return;

        sending = true;
        appendMsg('user', text);
        if (input) { input.value = ''; input.style.height = 'auto'; }

        // Masquer les suggestions
        const sugg = document.getElementById('rh-chat-suggestions');
        if (sugg) sugg.style.display = 'none';

        // Afficher indicateur de typing
        const typing = appendTyping();

        try {
            const res = await fetch(baseUrl + '/api/rh/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ agent_type: agent, message: text, session_id: session })
            });
            const data = await res.json();
            typing.remove();
            if (data.success) {
                appendMsg('ai', data.content);
            } else {
                appendMsg('ai', '❌ ' + (data.error || 'Erreur inconnue'));
            }
        } catch(err) {
            typing.remove();
            appendMsg('ai', '❌ Impossible de contacter l\'IA. Vérifiez votre connexion.');
        }
        sending = false;
    };

    function appendMsg(role, text) {
        const agentEmoji = '<?= \App\Core\Security::escape($agentInfo['emoji'] ?? '🤖') ?>';
        const agentNom   = '<?= \App\Core\Security::escape($agentInfo['nom'] ?? 'IA') ?>';
        const div = document.createElement('div');
        div.className = 'rh-chat__msg rh-chat__msg--' + role;
        if (role === 'ai') {
            div.innerHTML = `<div class="rh-chat__msg-avatar">${agentEmoji}</div>
            <div class="rh-chat__msg-bubble">
                <div class="rh-chat__msg-name">${agentNom}</div>
                <div class="rh-chat__msg-text rh-markdown">${escHtml(text).replace(/\n/g,'<br>').replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>')}</div>
            </div>`;
        } else {
            div.innerHTML = `<div class="rh-chat__msg-bubble rh-chat__msg-bubble--user">
                <div class="rh-chat__msg-text">${escHtml(text)}</div>
            </div><div class="rh-chat__msg-avatar rh-chat__msg-avatar--user">👤</div>`;
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
    }

    function appendTyping() {
        const agentEmoji = '<?= \App\Core\Security::escape($agentInfo['emoji'] ?? '🤖') ?>';
        const div = document.createElement('div');
        div.className = 'rh-chat__msg rh-chat__msg--ai rh-chat__msg--typing';
        div.innerHTML = `<div class="rh-chat__msg-avatar">${agentEmoji}</div>
        <div class="rh-chat__msg-bubble"><div class="rh-typing-dots"><span></span><span></span><span></span></div></div>`;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
