/* WordPress-meldingen samengevouwen op WordPress-pagina's (Dashboard, Plugins, ...).
   Zelfde gedrag als het meldingenpaneel in de MK-balk (mk-admin.js): verborgen meldingen worden per
   gebruiker onthouden, want de meeste plugins zetten hun melding op elke pagina opnieuw neer. */
(function () {
    var cfg = window.mkNotices || {};
    var hiddenKeys = (cfg.hidden || []).slice();
    var done = false;
    var observer;

    // Zelfde kenmerk als noticeKey() in mk-admin.js
    function noticeKey(n) {
        var c = n.cloneNode(true);
        Array.prototype.forEach.call(c.querySelectorAll('button, .notice-dismiss, .screen-reader-text, .mk-notice-hide'), function (x) { x.remove(); });
        var text = c.textContent.replace(/\s+/g, ' ').trim().slice(0, 500), h = 5381;
        for (var i = 0; i < text.length; i++) h = ((h << 5) + h + text.charCodeAt(i)) | 0;
        return 'n' + (h >>> 0).toString(36);
    }

    // Stuurt de hele lijst, één keer na een korte pauze (zelfde als in mk-admin.js)
    var saveTimer = null;
    function saveHidden() {
        if (!cfg.ajax) return;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            var data = new FormData();
            data.append('action', 'mkbase_notices');
            data.append('_ajax_nonce', cfg.nonce);
            hiddenKeys.forEach(function (k) { data.append('keys[]', k); });
            fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true });
        }, 150);
    }

    function tryInit() {
        if (done) return;

        // Pagina's zonder .wrap (bijv. WooCommerce-overzichten): direct in de inhoud
        var wrap = document.querySelector('#wpbody-content .wrap') || document.getElementById('wpbody-content');
        if (!wrap) return;

        // div.error: o.a. WooCommerce gebruikt nog de oude klasse zonder .notice.
        // hide-if-js: alleen zichtbaar zonder JavaScript (bijv. op Media), dus geen echte melding.
        function zoek() {
            return Array.prototype.slice.call(
                wrap.querySelectorAll('.notice:not(.inline):not(.hidden):not(.hide-if-js), .updated:not(.inline), div.error:not(.inline):not(.hide-if-js)')
            ).filter(function (n) {
                return !n.closest('.mk-notices-wrapper');
            });
        }
        var allNotices = zoek();

        if (allNotices.length === 0) return;

        done = true;
        if (observer) observer.disconnect();

        var isCollapsed = true;
        try { isCollapsed = sessionStorage.getItem('mk_notices_collapsed') !== 'false'; } catch (e) {}

        var wrapper = document.createElement('div');
        wrapper.className = 'mk-notices-wrapper';

        // Zelfde knop als in de MK-balk: belletje, aantal, pijltje
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mk-notices-toggle';
        btn.setAttribute('aria-controls', 'mk-notices-inner');
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>' +
            '<span></span>' +
            '<svg class="mk-notices-toggle__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

        var inner = document.createElement('div');
        inner.className = 'mk-notices-inner';
        inner.id = 'mk-notices-inner';
        var list = document.createElement('div');
        list.className = 'mk-notices-list';
        var foot = document.createElement('div');
        foot.className = 'mk-notices-foot';
        foot.innerHTML = '<span></span><button type="button">Weer tonen</button>';
        inner.appendChild(list);
        inner.appendChild(foot);

        var hiddenStore = document.createElement('div');

        wrapper.appendChild(btn);
        wrapper.appendChild(inner);
        wrap.insertBefore(wrapper, wrap.firstChild);

        function opnemen(notice) {
            if (hiddenKeys.indexOf(noticeKey(notice)) > -1) { hiddenStore.appendChild(notice); return; }
            // Elke melding is weg te klikken (bij is-dismissible zet WordPress zelf straks een kruisje neer)
            if (!notice.classList.contains('is-dismissible') && !notice.querySelector('.notice-dismiss, .mk-notice-hide')) {
                var x = document.createElement('button');
                x.type = 'button';
                x.className = 'mk-notice-hide';
                x.setAttribute('aria-label', 'Deze melding verbergen');
                x.innerHTML = '<span aria-hidden="true">×</span>';
                notice.appendChild(x);
            }
            list.appendChild(notice);
        }
        allNotices.forEach(opnemen);

        // WordPress verplaatst meldingen soms pas later naar de pagina (common.js): die alsnog opnemen
        new MutationObserver(function () { zoek().forEach(opnemen); }).observe(wrap, { childList: true });

        function update() {
            var count = list.children.length;
            var hidden = hiddenStore.children.length;
            wrapper.hidden = count === 0 && hidden === 0;
            btn.querySelector('span').textContent = count
                ? count + (count !== 1 ? ' WordPress-meldingen' : ' WordPress-melding')
                : 'Geen nieuwe meldingen';
            btn.classList.toggle('is-quiet', count === 0);
            foot.hidden = hidden === 0;
            foot.querySelector('span').textContent = hidden === 1 ? '1 verborgen' : hidden + ' verborgen';
            inner.hidden = isCollapsed;
            btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        }

        btn.addEventListener('click', function () {
            isCollapsed = !isCollapsed;
            try { sessionStorage.setItem('mk_notices_collapsed', isCollapsed ? 'true' : 'false'); } catch (e) {}
            update();
        });

        // Wegklikken (kruisje van WordPress of van ons) wordt onthouden
        list.addEventListener('click', function (e) {
            var x = e.target.closest('.notice-dismiss, .mk-notice-hide');
            var n = x && x.closest('.mk-notices-list > *');
            if (!n) return;
            var key = noticeKey(n);
            if (hiddenKeys.indexOf(key) < 0) hiddenKeys.push(key);
            hiddenStore.appendChild(n);
            saveHidden();
            update();
        }, true);

        foot.querySelector('button').addEventListener('click', function () {
            hiddenKeys = [];
            saveHidden();
            while (hiddenStore.firstChild) list.appendChild(hiddenStore.firstChild);
            update();
        });

        new MutationObserver(update).observe(list, { childList: true });
        update();
    }

    var wpbody = document.getElementById('wpbody-content');
    if (wpbody && window.MutationObserver) {
        observer = new MutationObserver(tryInit);
        observer.observe(wpbody, { childList: true, subtree: true });
    }

    setTimeout(tryInit, 0);
    window.addEventListener('load', function () {
        tryInit();
        if (!done && observer) observer.disconnect();
    });
})();
