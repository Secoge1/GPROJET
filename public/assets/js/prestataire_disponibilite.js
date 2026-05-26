/**
 * Modale rappel disponibilité (expert / professeur validés).
 */
(function () {
    'use strict';

    var modal = document.getElementById('prestataire-dispo-modal');
    if (!modal) return;

    var checkbox = modal.querySelector('.prestataire-dispo-modal__switch-input');
    var card = modal.querySelector('.prestataire-dispo-modal__toggle-card');
    var labelEl = modal.querySelector('[data-dispo-status-label]');
    var hintEl = modal.querySelector('[data-dispo-status-hint]');
    var feedback = modal.querySelector('[data-dispo-feedback]');
    var saveBtn = modal.querySelector('[data-dispo-save]');
    var toggleUrl = modal.getAttribute('data-toggle-url') || '';
    var dismissUrl = modal.getAttribute('data-dismiss-url') || '';
    var csrf = modal.getAttribute('data-csrf') || '';
    var metaCsrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf && metaCsrf) csrf = metaCsrf.getAttribute('content') || '';

    document.body.classList.add('prestataire-dispo-modal-open');

    function setFeedback(msg, type) {
        if (!feedback) return;
        feedback.textContent = msg || '';
        feedback.classList.remove('is-error', 'is-success');
        if (type) feedback.classList.add(type === 'error' ? 'is-error' : 'is-success');
    }

    function updateUi(on) {
        if (card) card.setAttribute('data-dispo-state', on ? 'on' : 'off');
        if (labelEl) labelEl.textContent = on ? 'Disponible maintenant' : 'Hors ligne';
        if (hintEl) {
            hintEl.textContent = on
                ? 'Les clients peuvent vous trouver et vous contacter.'
                : 'Vous n’apparaissez pas dans les listes publiques.';
        }
        if (saveBtn) {
            saveBtn.textContent = on ? 'Confirmer' : 'Activer ma disponibilité';
        }
    }

    function closeModal() {
        modal.classList.add('is-closed');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('prestataire-dispo-modal-open');
        setTimeout(function () {
            if (modal.parentNode) modal.parentNode.removeChild(modal);
        }, 200);
    }

    function postForm(url, fields) {
        var body = new FormData();
        Object.keys(fields).forEach(function (k) {
            body.append(k, fields[k]);
        });
        body.append('csrf_token', csrf);
        var headers = {};
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        return fetch(url, { method: 'POST', body: body, headers: headers, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); });
    }

    function setBusy(busy) {
        [saveBtn].concat(Array.prototype.slice.call(modal.querySelectorAll('[data-dispo-dismiss]'))).forEach(function (btn) {
            if (btn) btn.disabled = !!busy;
        });
    }

    if (checkbox) {
        checkbox.addEventListener('change', function () {
            updateUi(checkbox.checked);
            setFeedback('', null);
        });
        updateUi(checkbox.checked);
    }

    function dismissOnly() {
        if (!dismissUrl) {
            closeModal();
            return;
        }
        setBusy(true);
        postForm(dismissUrl, {})
            .finally(function () {
                setBusy(false);
                closeModal();
            });
    }

    modal.querySelectorAll('[data-dispo-dismiss], [data-dispo-close]').forEach(function (el) {
        el.addEventListener('click', dismissOnly);
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            dismissOnly();
        }
    });

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            var on = checkbox ? checkbox.checked : false;
            var ctaActivate = saveBtn.textContent.indexOf('Activer') !== -1;
            if (!on && ctaActivate) {
                on = true;
                if (checkbox) checkbox.checked = true;
                updateUi(true);
            }
            if (!toggleUrl) return;
            setBusy(true);
            setFeedback('', null);
            postForm(toggleUrl, { disponible: on ? '1' : '0' })
                .then(function (res) {
                    if (!res.ok || !res.data || !res.data.ok) {
                        setFeedback((res.data && res.data.message) || 'Une erreur est survenue.', 'error');
                        return;
                    }
                    updateUi(!!res.data.disponible);
                    if (checkbox) checkbox.checked = !!res.data.disponible;
                    setFeedback(res.data.message || '', 'success');
                    setTimeout(closeModal, on ? 600 : 400);
                })
                .catch(function () {
                    setFeedback('Connexion impossible. Réessayez.', 'error');
                })
                .finally(function () {
                    setBusy(false);
                });
        });
    }
})();
