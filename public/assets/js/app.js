/**
 * GLOBALO - Script commun (CSRF pour AJAX, PWA, utilitaires)
 */
(function () {
    'use strict';

    // PWA : enregistrer le Service Worker pour rendre l'app téléchargeable / utilisable hors-ligne
    if ('serviceWorker' in navigator) {
        var swPath = (document.querySelector('script[src*="app.js"]') || {}).src || '';
        swPath = swPath.replace(/\/assets\/js\/app\.js.*$/, '/sw.js');
        navigator.serviceWorker.register(swPath).then(function () {}).catch(function () {});
    }

    // Envoyer le token CSRF dans les requêtes AJAX (header)
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : null;

    if (token && typeof XMLHttpRequest !== 'undefined') {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function () {
            origOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function (body) {
            this.setRequestHeader('X-CSRF-TOKEN', token);
            origSend.apply(this, arguments);
        };
    }

    // Fetch avec CSRF
    window.globaloFetch = function (url, options) {
        options = options || {};
        options.headers = options.headers || {};
        if (token) {
            options.headers['X-CSRF-TOKEN'] = token;
        }
        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }
        return fetch(url, options);
    };

    // Growth: auto-track page events (view_expert_profile, etc.)
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.querySelector('[data-growth-track]');
        if (el && typeof window.growthTrack === 'function') {
            var eventName = el.getAttribute('data-growth-track');
            var params = {};
            if (el.getAttribute('data-expert-id')) params.expert_id = el.getAttribute('data-expert-id');
            window.growthTrack(eventName, params);
        }
        document.getElementById('expert-copy-link')?.addEventListener('click', function () {
            var url = this.getAttribute('data-url');
            if (url && navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    var btn = document.getElementById('expert-copy-link');
                    if (btn) { btn.textContent = 'Copié !'; setTimeout(function () { btn.textContent = 'Copier le lien'; }, 2000); }
                });
            }
        });

        initGlobaloNotifSound();
    });

    /**
     * Son de notification : déblocage AudioContext au 1er geste utilisateur (obligatoire Chrome/Safari),
     * anti-doublon court, polling du nombre de notifications non lues (réservations + messages).
     */
    function initGlobaloNotifSound() {
        var body = document.body;
        if (!body || !body.getAttribute('data-user-id')) {
            return;
        }
        var base = body.getAttribute('data-base-url') || '';
        if (!base && typeof window.location !== 'undefined') {
            base = window.location.origin + (window.location.pathname.replace(/\/[^/]*$/, '') || '');
        }
        base = base.replace(/\/$/, '');

        var audioCtx = null;
        var lastPlayAt = 0;
        var lastNotifCount = null;

        function unlockAudio() {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
            } catch (e) { /* ignore */ }
        }
        ['click', 'keydown', 'touchstart'].forEach(function (ev) {
            document.addEventListener(ev, unlockAudio, { once: true, passive: true });
        });

        function isReservationRelatedNotifType(type) {
            if (!type || typeof type !== 'string') {
                return false;
            }
            return /^(nouvelle_reservation|paiement_recu|session_terminee|acceptee|refusee|livraison_travail|expert_accepte_urgence|avis_client|session_professeur|mission_urgence)$/.test(type);
        }

        function playTone() {
            var now = Date.now();
            if (now - lastPlayAt < 650) {
                return;
            }
            lastPlayAt = now;
            unlockAudio();
            try {
                var ctx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                audioCtx = ctx;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.26, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.45);
            } catch (e) { /* ignore */ }
        }

        /** Double bref : réservations / missions / sessions (distinct du message chat). */
        function playReservationTone() {
            var now = Date.now();
            if (now - lastPlayAt < 650) {
                return;
            }
            lastPlayAt = now;
            unlockAudio();
            try {
                var ctx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                audioCtx = ctx;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                function beep(startTime, freq) {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, startTime);
                    gain.gain.setValueAtTime(0.24, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.14);
                    osc.start(startTime);
                    osc.stop(startTime + 0.14);
                }
                var t0 = ctx.currentTime;
                beep(t0, 920);
                beep(t0 + 0.18, 740);
            } catch (e) { /* ignore */ }
        }

        window.GlobaloPlayNotifSound = playTone;

        function updateNavBadges(d) {
            if (!d || !d.badges || typeof d.badges !== 'object') {
                return;
            }
            function setBadge(key, n) {
                var el = document.querySelector('.nav-badge[data-nav-badge="' + key + '"]');
                if (!el) {
                    return;
                }
                n = parseInt(String(n), 10) || 0;
                if (n < 1) {
                    el.setAttribute('hidden', '');
                    el.textContent = '';
                    return;
                }
                el.removeAttribute('hidden');
                el.textContent = n > 99 ? '99+' : String(n);
            }
            setBadge('messages', d.badges.messages);
            setBadge('reservations', d.badges.reservations);
        }

        function pollCount() {
            if (!base) {
                return;
            }
            window.globaloFetch(base + '/api/notifications/count', { credentials: 'include' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d || typeof d.count !== 'number') {
                        return;
                    }
                    updateNavBadges(d);
                    var c = d.count;
                    if (lastNotifCount !== null && c > lastNotifCount) {
                        var lastType = d.last && d.last.type ? d.last.type : '';
                        if (isReservationRelatedNotifType(lastType)) {
                            playReservationTone();
                        } else {
                            playTone();
                        }
                        if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
                            try {
                                var title = (d.last && d.last.titre) ? String(d.last.titre) : 'GLOBALO';
                                var body = 'Vous avez une nouvelle notification.';
                                if (d.last && d.last.contenu) {
                                    body = String(d.last.contenu);
                                    if (body.length > 160) {
                                        body = body.substring(0, 157) + '…';
                                    }
                                } else if (d.last && d.last.titre) {
                                    body = String(d.last.titre);
                                }
                                new Notification(title, {
                                    body: body,
                                    icon: base + '/assets/images/logo.png',
                                    tag: 'globalo-notif-' + String(c)
                                });
                            } catch (err) { /* ignore */ }
                        }
                    }
                    lastNotifCount = c;
                })
                .catch(function () { /* hors ligne ou session expirée */ });
        }

        lastNotifCount = null;
        window.globaloFetch(base + '/api/notifications/count', { credentials: 'include' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && typeof d.count === 'number') {
                    lastNotifCount = d.count;
                }
                updateNavBadges(d);
            })
            .catch(function () {});

        setInterval(pollCount, 20000);
    }
})();
