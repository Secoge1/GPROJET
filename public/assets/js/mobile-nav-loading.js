/**
 * GLOBALO — Indicateur de chargement pour la version « app » (layout-mobile).
 * Affiche une superposition après clic sur lien interne ou envoi de formulaire
 * jusqu’au déchargement de la page ou restauration bfcache.
 */
(function () {
    'use strict';

    var root = document.body;
    if (!root || !root.classList.contains('layout-mobile')) {
        return;
    }

    var TIMEOUT_HIDE_MS = 45000;
    var hideTimer = null;

    function ensureOverlayEl() {
        var el = document.getElementById('globalo-app-nav-loading');
        if (el) {
            return el;
        }
        el = document.createElement('div');
        el.id = 'globalo-app-nav-loading';
        el.className = 'globalo-app-nav-loading';
        el.setAttribute('aria-hidden', 'true');
        el.setAttribute('role', 'status');
        el.innerHTML =
            '<div class="globalo-app-nav-loading__backdrop" aria-hidden="true"></div>' +
            '<div class="globalo-app-nav-loading__content">' +
            '<span class="globalo-app-nav-loading__spinner" aria-hidden="true"></span>' +
            '<span class="globalo-app-nav-loading__label">Chargement…</span>' +
            '</div>';
        document.body.appendChild(el);
        return el;
    }

    function hide() {
        clearTimeout(hideTimer);
        hideTimer = null;
        var el = document.getElementById('globalo-app-nav-loading');
        if (!el) {
            return;
        }
        el.classList.remove('globalo-app-nav-loading--visible');
        el.setAttribute('aria-hidden', 'true');
        root.classList.remove('globalo-app-nav-loading--active');
    }

    function show() {
        clearTimeout(hideTimer);
        var el = ensureOverlayEl();
        el.classList.add('globalo-app-nav-loading--visible');
        el.setAttribute('aria-hidden', 'false');
        root.classList.add('globalo-app-nav-loading--active');
        void el.offsetWidth;
        hideTimer = setTimeout(hide, TIMEOUT_HIDE_MS);
    }

    function closestAnchor(el) {
        while (el && el !== document.body) {
            if (el.tagName === 'A' && el.href) {
                return el;
            }
            el = el.parentElement;
        }
        return null;
    }

    function wantsLoadingForAnchor(ev, anchor) {
        if (anchor.getAttribute && anchor.getAttribute('data-no-nav-loading') === '1') {
            return false;
        }
        if (ev.button !== undefined && ev.button !== 0) {
            return false;
        }
        if (ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.altKey) {
            return false;
        }
        var targetAttr = anchor.getAttribute('target');
        if (targetAttr === '_blank' || targetAttr === '_parent' || targetAttr === '_top') {
            return false;
        }
        if (anchor.hasAttribute('download')) {
            return false;
        }

        var hrefAttr = anchor.getAttribute('href');
        if (!hrefAttr) {
            return false;
        }
        var trimmed = hrefAttr.trim();
        if (trimmed === '' || trimmed === '#' || trimmed.toLowerCase().indexOf('javascript:') === 0) {
            return false;
        }
        /* Navigation intra-page : pas de transition plein écran courante pour #section */
        if (trimmed.charAt(0) === '#') {
            return false;
        }
        if (/^(mailto:|tel:|sms:)/i.test(trimmed)) {
            return false;
        }

        var url;
        try {
            url = new URL(anchor.href);
        } catch (e) {
            return false;
        }
        if (url.origin !== window.location.origin) {
            return false;
        }

        return true;
    }

    /** Clic lien : phase bubble après les handlers pouvant faire preventDefault. */
    document.addEventListener(
        'click',
        function (ev) {
            if (ev.defaultPrevented) {
                return;
            }
            var anchor = closestAnchor(ev.target);
            if (!anchor) {
                return;
            }
            if (!wantsLoadingForAnchor(ev, anchor)) {
                return;
            }
            var startUrl = window.location.href;
            show();
            /* Si la navigation ne démarre pas (erreur, même page), retirer l’overlay qui bloque les clics */
            setTimeout(function () {
                if (window.location.href === startUrl) {
                    hide();
                }
            }, 900);
        },
        false
    );

    /** Envoi formulaire : capture pour avoir le feedback même si la page bouge vite. */
    document.addEventListener(
        'submit',
        function (ev) {
            var form = ev.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (ev.defaultPrevented) {
                return;
            }
            if (form.getAttribute('data-no-nav-loading') === '1') {
                return;
            }
            show();
        },
        true
    );

    window.addEventListener('pageshow', function () {
        hide();
    });
    window.addEventListener('load', hide);
    document.addEventListener('DOMContentLoaded', hide);
})();
