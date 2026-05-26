/**
 * GLOBALO - Growth: Analytics & conversion tracking
 * Loads GA4 and Facebook Pixel only if user consented (cookie-consent.js).
 * Usage: window.growthTrack('event_name', { param: 'value' });
 */
(function () {
    'use strict';

    var config = window.GLOBALO_GROWTH || {};
    var gaId = config.gaId || '';
    var fbPixelId = config.fbPixelId || '';
    var linkedInId = config.linkedInId || '';
    var gaLoaded = false;
    var fbLoaded = false;

    function hasConsent() {
        return window.getConsent && window.getConsent();
    }

    function loadGA() {
        if (!gaId || gaLoaded) return;
        gaLoaded = true;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', gaId, { anonymize_ip: true });
    }

    function loadFbPixel() {
        if (!fbPixelId || fbLoaded) return;
        fbLoaded = true;
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', fbPixelId);
        fbq('track', 'PageView');
    }

    function loadLinkedIn() {
        if (!linkedInId) return;
        var s = document.createElement('script');
        s.innerHTML = '_linkedin_partner_id = "' + linkedInId + '"; window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || []; window._linkedin_data_partner_ids.push(_linkedin_partner_id);';
        document.head.appendChild(s);
        s = document.createElement('script');
        s.async = true;
        s.src = 'https://snap.licdn.com/li.lms-analytics/insight.min.js';
        document.head.appendChild(s);
    }

    window.growthLoadTracking = function () {
        var c = hasConsent();
        if (!c) return;
        if (c.analytics && gaId) loadGA();
        if (c.marketing && fbPixelId) loadFbPixel();
        if (c.marketing && linkedInId) loadLinkedIn();
    };

    window.growthTrack = function (eventName, params) {
        params = params || {};
        var c = hasConsent();
        if (c && c.analytics && gaId && window.gtag) {
            gtag('event', eventName, params);
        }
        if (c && c.marketing && fbPixelId && window.fbq) {
            if (eventName === 'sign_up') fbq('track', 'CompleteRegistration', params);
            else if (eventName === 'purchase') fbq('track', 'Purchase', { value: params.value, currency: params.currency || 'XOF' });
            else fbq('trackCustom', eventName, params);
        }
    };

    // Load tracking if consent already given (e.g. returning visitor)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.growthLoadTracking);
    } else {
        window.growthLoadTracking();
    }
})();
