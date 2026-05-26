/**
 * GLOBALO - Cookie consent (GDPR)
 */
(function () {
    'use strict';
    var COOKIE_NAME = 'globalo_consent';
    var COOKIE_DAYS = 365;
    var STORAGE_KEY = 'globalo_consent';

    function getConsent() {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                var parsed = JSON.parse(stored);
                if (parsed && typeof parsed.analytics === 'boolean') return parsed;
            }
        } catch (e) {}
        try {
            var match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
            if (match) {
                var decoded = JSON.parse(decodeURIComponent(match[2]));
                if (decoded && typeof decoded.analytics === 'boolean') return decoded;
            }
        } catch (e) {}
        return null;
    }

    function setConsent(analytics, marketing) {
        var payload = { analytics: !!analytics, marketing: !!marketing, timestamp: new Date().toISOString() };
        var str = JSON.stringify(payload);
        try { localStorage.setItem(STORAGE_KEY, str); } catch (e) {}
        document.cookie = COOKIE_NAME + '=' + encodeURIComponent(str) + ';path=/;max-age=' + (COOKIE_DAYS * 86400) + ';SameSite=Lax';
        if (window.growthLoadTracking) window.growthLoadTracking();
    }

    function showBanner() {
        var el = document.getElementById('cookie-consent-banner');
        if (el) { el.style.display = 'block'; el.setAttribute('aria-hidden', 'false'); }
    }
    function hideBanner() {
        var el = document.getElementById('cookie-consent-banner');
        if (el) { el.style.display = 'none'; el.setAttribute('aria-hidden', 'true'); }
    }

    function init() {
        if (getConsent() !== null) {
            if (window.growthLoadTracking) window.growthLoadTracking();
            /* Lien "Préférences cookies" masqué en bas de page (ne pas l'afficher) */
            return;
        }
        showBanner();
        document.getElementById('cookie-accept-all')?.addEventListener('click', function () { setConsent(true, true); hideBanner(); });
        document.getElementById('cookie-reject-all')?.addEventListener('click', function () { setConsent(false, false); hideBanner(); });
        document.getElementById('cookie-customize')?.addEventListener('click', function () {
            var panel = document.getElementById('cookie-customize-panel');
            if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        document.getElementById('cookie-save-prefs')?.addEventListener('click', function () {
            var analytics = document.getElementById('cookie-pref-analytics')?.checked ?? true;
            var marketing = document.getElementById('cookie-pref-marketing')?.checked ?? true;
            setConsent(analytics, marketing);
            hideBanner();
        });
        document.getElementById('cookie-preferences-btn')?.addEventListener('click', function () {
            var c = getConsent();
            document.getElementById('cookie-pref-analytics').checked = c ? c.analytics : true;
            document.getElementById('cookie-pref-marketing').checked = c ? c.marketing : true;
            document.getElementById('cookie-customize-panel').style.display = 'block';
            showBanner();
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    window.getConsent = getConsent;
    window.setConsent = setConsent;
})();
