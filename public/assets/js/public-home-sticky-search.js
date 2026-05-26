/**
 * Affiche la recherche compacte dans le header lorsque la barre hero sort du viewport (effet type Fiverr).
 */
(function () {
    'use strict';

    if (!document.body.classList.contains('has-home-sticky-search')) {
        return;
    }

    var heroAnchor = document.getElementById('smart-search-home');
    var headerSlot = document.getElementById('header-smart-search-slot');

    if (!heroAnchor || !headerSlot) {
        return;
    }

    function setCompact(on) {
        document.body.classList.toggle('public-home--hero-search-compact', !!on);
        headerSlot.setAttribute('aria-hidden', on ? 'false' : 'true');
    }

    var hdr = document.querySelector('.header-desktop');
    var inner = document.querySelector('.header-inner');
    var hdrTop = hdr ? parseInt(window.getComputedStyle(hdr).paddingTop, 10) || 12 : 12;
    var innerH = inner ? inner.offsetHeight : 72;
    var headerOffset = Math.min(108, Math.max(52, hdrTop + innerH));

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    setCompact(!entry.isIntersecting);
                });
            },
            {
                root: null,
                rootMargin: '-' + Math.round(headerOffset) + 'px 0px 0px 0px',
                threshold: 0
            }
        );
        observer.observe(heroAnchor);
    } else {
        setCompact(window.scrollY > 120);
        window.addEventListener('scroll', function () {
            setCompact(window.scrollY > 120);
        }, { passive: true });
    }
})();
