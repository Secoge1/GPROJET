/**
 * GLOBALO - Chatbot flottant (widget sur les pages web)
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'globalo_chatbot_uid';

    var widget = document.getElementById('chatbot-widget');
    if (!widget) return;

    var baseUrl = widget.getAttribute('data-base-url') || '';
    var panel = document.getElementById('chatbot-panel');
    var toggle = document.getElementById('chatbot-toggle');
    var closeBtn = document.getElementById('chatbot-close');
    var messagesEl = document.getElementById('chatbot-messages');
    var welcomeEl = document.getElementById('chatbot-welcome');
    var inputEl = document.getElementById('chatbot-input');
    var sendBtn = document.getElementById('chatbot-send');
    var quickActionsEl = document.getElementById('chatbot-quick-actions');
    var badgeEl = document.getElementById('chatbot-badge');
    var isAuthenticated = widget.getAttribute('data-user-auth') === '1';
    var userRole = (widget.getAttribute('data-user-role') || '').toLowerCase();

    var conversationUid = sessionStorage.getItem(STORAGE_KEY);
    var loading = false;

    /* Chatbot fermé par défaut : ne jamais ouvrir automatiquement au chargement */
    if (panel) {
        panel.setAttribute('hidden', '');
        panel.classList.remove('chatbot-panel-open');
    }
    if (toggle) toggle.classList.remove('chatbot-toggle-open');
    if (badgeEl) badgeEl.classList.remove('chatbot-badge-hidden');

    function openPanel() {
        panel.removeAttribute('hidden');
        panel.classList.add('chatbot-panel-open');
        toggle.classList.add('chatbot-toggle-open');
        if (badgeEl) badgeEl.classList.add('chatbot-badge-hidden');
        inputEl.focus();
    }

    function closePanel() {
        panel.setAttribute('hidden', '');
        panel.classList.remove('chatbot-panel-open');
        toggle.classList.remove('chatbot-toggle-open');
        if (badgeEl) badgeEl.classList.remove('chatbot-badge-hidden');
    }

    function togglePanel() {
        if (panel.hasAttribute('hidden')) openPanel(); else closePanel();
    }

    toggle.addEventListener('click', togglePanel);

    if (closeBtn) {
        closeBtn.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closePanel();
        });
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
    }

    function hideWelcome() {
        if (welcomeEl) welcomeEl.style.display = 'none';
    }

    function addBubble(role, text) {
        hideWelcome();
        var div = document.createElement('div');
        div.className = 'chatbot-bubble chatbot-bubble-' + role;
        var p = document.createElement('p');
        p.textContent = text;
        div.appendChild(p);
        messagesEl.appendChild(div);
        scrollMessages();
    }

    function getDashboardUrlForRole(role) {
        if (role === 'expert') return baseUrl + '/expert/reservations';
        if (role === 'etudiant') return baseUrl + '/etudiant';
        if (role === 'professeur') return baseUrl + '/professeur';
        return baseUrl + '/client/reservations';
    }

    function addTypingIndicator() {
        var div = document.createElement('div');
        div.className = 'chatbot-bubble chatbot-bubble-assistant chatbot-typing';
        div.id = 'chatbot-typing';
        div.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(div);
        scrollMessages();
    }

    function removeTypingIndicator() {
        var el = document.getElementById('chatbot-typing');
        if (el) el.remove();
    }

    function addExpertCards(experts) {
        if (!experts || !experts.length) return;
        hideWelcome();
        var wrap = document.createElement('div');
        wrap.className = 'chatbot-experts-wrap';
        experts.forEach(function (ex) {
            var card = document.createElement('div');
            card.className = 'chatbot-expert-card';
            var name = ex.name || 'Expert';
            var titre = ex.titre || '';
            var note = ex.note_moyenne != null ? ex.note_moyenne + ' ★' : '';
            var avis = ex.nombre_avis ? ' (' + ex.nombre_avis + ' avis)' : '';
            var tarif = ex.tarif_horaire != null ? ex.tarif_horaire.toFixed(0) + ' /h' : '';
            var dispo = ex.disponible ? '<span class="chatbot-expert-badge">Disponible</span>' : '';
            var profileUrl = baseUrl + '/expert/' + (ex.slug || ('expert-' + ex.id));
            card.innerHTML =
                '<div class="chatbot-expert-name">' + escapeHtml(name) + '</div>' +
                (titre ? '<div class="chatbot-expert-titre">' + escapeHtml(titre) + '</div>' : '') +
                (note ? '<div class="chatbot-expert-note">' + escapeHtml(note) + avis + '</div>' : '') +
                (tarif ? '<div class="chatbot-expert-tarif">' + escapeHtml(tarif) + '</div>' : '') +
                (dispo ? '<div class="chatbot-expert-dispo">' + dispo + '</div>' : '') +
                '<a href="' + profileUrl + '" class="chatbot-expert-btn">Voir le profil</a>';
            wrap.appendChild(card);
        });
        messagesEl.appendChild(wrap);
        scrollMessages();
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function scrollMessages() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function getCsrfHeader() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function sendMessage(text) {
        text = (text || '').trim();
        if (!text || loading) return;

        addBubble('user', text);
        inputEl.value = '';
        loading = true;
        addTypingIndicator();
        sendBtn.disabled = true;

        var payload = { message: text };
        if (conversationUid) payload.conversation_uid = conversationUid;

        var opts = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        };
        var csrf = getCsrfHeader();
        if (csrf) opts.headers['X-CSRF-TOKEN'] = csrf;

        fetch(baseUrl + '/api/chatbot/send', opts)
            .then(function (res) {
                if (!res.ok) return res.json().then(function (d) { throw new Error(d.error || res.statusText); });
                return res.json();
            })
            .then(function (data) {
                removeTypingIndicator();
                var reply = (data && data.reply) ? String(data.reply).trim() : '';
                if (!reply) reply = 'Désolé, je n\'ai pas pu répondre. Réessayez ou utilisez les boutons ci-dessous.';
                addBubble('assistant', reply);
                if (data.experts && data.experts.length) addExpertCards(data.experts);
                if (data.conversation_uid) {
                    conversationUid = data.conversation_uid;
                    sessionStorage.setItem(STORAGE_KEY, conversationUid);
                }
            })
            .catch(function (err) {
                removeTypingIndicator();
                var msg = (err && err.message) ? err.message : 'Désolé, le service est temporairement indisponible.';
                addBubble('assistant', msg);
            })
            .then(function () {
                loading = false;
                sendBtn.disabled = false;
            });
    }

    function quickAction(action) {
        var msg = '';
        switch (action) {
            case 'find_expert':
                msg = 'Je cherche un expert. Pose-moi 2 ou 3 questions puis propose-moi les profils les plus adaptés.';
                break;
            case 'post_request':
                msg = 'Je veux publier une demande d\'assistance. Aide-moi a preparer un titre, une description, la duree et un budget.';
                break;
            case 'my_sessions':
                if (!isAuthenticated) {
                    addBubble('assistant', 'Pour consulter vos sessions, connectez-vous d\'abord a votre compte. Ensuite je pourrai vous guider selon votre role.');
                    setTimeout(function () {
                        window.location.href = baseUrl + '/auth/connexion';
                    }, 700);
                    return;
                }
                msg = 'Aide-moi a retrouver mes sessions en cours et les prochaines actions a faire.';
                setTimeout(function () {
                    window.location.href = getDashboardUrlForRole(userRole);
                }, 1200);
                break;
            case 'support':
                msg = 'J\'ai besoin d\'aide du support. Demande-moi mon probleme et donne-moi la meilleure marche a suivre.';
                break;
            default:
                msg = action;
        }
        if (msg) sendMessage(msg);
    }

    sendBtn.addEventListener('click', function () {
        sendMessage(inputEl.value);
    });
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(inputEl.value);
        }
    });

    if (quickActionsEl) {
        quickActionsEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.chatbot-quick-btn');
            if (btn && btn.dataset.action) quickAction(btn.dataset.action);
        });
    }
})();
