/* Dashboard: startlijst verbergen (per gebruiker; terughalen via Mediakanjers → Documentatie) */
(function () {
    'use strict';

    var cfg = window.mkDash || {};
    var btn = document.querySelector('.mk-start__hide');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var list = btn.closest('.mk-start');
        var body = new FormData();
        body.append('action', 'mkbase_startlijst');
        body.append('_ajax_nonce', cfg.nonce);
        btn.disabled = true;
        fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j || !j.success) throw new Error('startlijst');
                list.classList.add('is-hiding');
                setTimeout(function () {
                    list.remove();
                    // Focus niet laten verdwijnen: naar het welkomstblok
                    var title = document.getElementById('mk-welcome-title');
                    if (title) { title.setAttribute('tabindex', '-1'); title.focus(); }
                }, 200);
            })
            .catch(function () { btn.disabled = false; });
    });
})();
