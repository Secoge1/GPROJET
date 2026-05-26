/**
 * Barre de recherche intelligente (accueil) — suggestions via /api/search/suggest
 */
(function () {
    'use strict';

    /**
     * @param {HTMLElement} root
     */
    function mount(root) {
        var api = root.getAttribute('data-smart-search-api') || '';
        var app = root.getAttribute('data-smart-search-app') === '1';
        var input = root.querySelector('.js-smart-search-input');
        var list = root.querySelector('.js-smart-search-results');
        var form = root.querySelector('.js-smart-search-form');
        if (!input) {
            return;
        }

        var open = false;

        var phRaw = root.getAttribute('data-smart-search-placeholders');
        /** @type {string[]} */
        var placeholders = [];
        if (phRaw) {
            try {
                placeholders = JSON.parse(phRaw);
            } catch (e) {
                placeholders = [];
            }
        }

        function buildUrl(query) {
            var u = api + (api.indexOf('?') >= 0 ? '&' : '?') + 'app=' + (app ? '1' : '0');
            if (query) {
                u += '&q=' + encodeURIComponent(query);
            }
            return u;
        }

        /** @type {AbortController|null} */
        var abortCtl = null;
        /** @type {number|undefined} */
        var timer;
        /** @type {number|undefined} */
        var phTimer;

        function setOpen(flag) {
            open = !!flag;
            if (!list) {
                return;
            }
            list.hidden = !open;
            list.setAttribute('aria-hidden', open ? 'false' : 'true');
            input.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function renderItems(items) {
            if (!list) {
                return;
            }
            list.innerHTML = '';
            var ul = document.createElement('ul');
            ul.className = 'smart-search-dropdown__items';
            ul.setAttribute('role', 'listbox');
            ul.id = root.id ? root.id + '-listbox' : 'smart-search-listbox';

            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                var li = document.createElement('li');
                li.setAttribute('role', 'none');

                var a = document.createElement('a');
                a.href = it.url || '#';
                a.className = 'smart-search-dropdown__item';
                a.setAttribute('role', 'option');
                a.innerHTML =
                    '<span class="smart-search-dropdown__label"></span>' +
                    '<span class="smart-search-dropdown__sub"></span>';
                var labelEl = a.querySelector('.smart-search-dropdown__label');
                var subEl = a.querySelector('.smart-search-dropdown__sub');
                if (labelEl) {
                    labelEl.textContent = it.label || '';
                }
                if (subEl) {
                    subEl.textContent = it.sublabel || '';
                }
                li.appendChild(a);
                ul.appendChild(li);
            }

            list.appendChild(ul);
            setOpen(items.length > 0);
            input.setAttribute('aria-controls', ul.id);
        }

        async function fetchSuggest(q) {
            if (!api) {
                return;
            }
            if (abortCtl) {
                abortCtl.abort();
            }
            abortCtl = new AbortController();
            try {
                var res = await fetch(buildUrl(q), {
                    credentials: 'same-origin',
                    signal: abortCtl.signal,
                    headers: { Accept: 'application/json' }
                });
                if (!res.ok) {
                    return;
                }
                var json = await res.json();
                if (json && json.ok && json.items && json.items.length) {
                    renderItems(json.items);
                } else if (document.activeElement === input) {
                    setOpen(false);
                }
            } catch (e) {
                if (!abortCtl.signal.aborted) {
                    /* ignore réseau */
                }
            }
        }

        function scheduleFetch() {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                var q = (input.value || '').trim();
                fetchSuggest(q);
            }, 200);
        }

        function rotatePlaceholder() {
            if ((input.value || '').trim() || document.activeElement === input) {
                return;
            }
            if (!placeholders.length) {
                return;
            }
            var ix = Number(input.getAttribute('data-ph-i') || 0);
            ix = (ix + 1) % placeholders.length;
            input.setAttribute('data-ph-i', String(ix));
            input.setAttribute('placeholder', placeholders[ix]);
        }

        function startPlaceholderRotate() {
            if (!placeholders.length || phTimer) {
                return;
            }
            phTimer = window.setInterval(rotatePlaceholder, 3500);
        }

        function stopPlaceholderRotate() {
            if (phTimer) {
                window.clearInterval(phTimer);
                phTimer = undefined;
            }
        }

        input.addEventListener('focus', function () {
            stopPlaceholderRotate();
            scheduleFetch();
        });

        input.addEventListener('blur', function () {
            window.setTimeout(function () {
                if (root.contains(document.activeElement)) {
                    return;
                }
                if (!(input.value || '').trim() && placeholders.length) {
                    startPlaceholderRotate();
                }
            }, 200);
        });

        input.addEventListener('input', function () {
            scheduleFetch();
            if ((input.value || '').trim()) {
                stopPlaceholderRotate();
            }
        });

        window.addEventListener('click', function (ev) {
            if (!open) {
                return;
            }
            if (root.contains(/** @type {Node} */ (ev.target))) {
                return;
            }
            setOpen(false);
        });

        root.addEventListener('keydown', function (ev) {
            if (ev.target !== input || ev.key !== 'Escape') {
                return;
            }
            setOpen(false);
        });

        if (form) {
            form.addEventListener('submit', function () {
                setOpen(false);
            });
        }

        if (placeholders.length && !(input.value || '').trim()) {
            input.setAttribute('placeholder', placeholders[0]);
            input.setAttribute('data-ph-i', '0');
            if (document.activeElement !== input) {
                startPlaceholderRotate();
            }
        }
    }

    document.querySelectorAll('[data-smart-search]').forEach(function (el) {
        mount(el);
    });
})();
