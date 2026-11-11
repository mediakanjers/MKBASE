/* Mediakanjers → Documentatie: inhoud op zijn plek, onderwerpen kiezen (ook via #onderwerp in de URL),
   vragen openklappen en zoeken. MK-balk, stijl en donkere modus komen uit mk-admin.js / mk-admin.css. */
(function () {
    'use strict';

    var content = document.getElementById('mkbase-docs-content');
    var wrap = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;
    var form = wrap.querySelector('form');
    if (form) form.hidden = true;
    var h1 = wrap.querySelector('h1');
    if (h1) h1.classList.add('screen-reader-text'); // het onderwerp is de zichtbare titel
    wrap.insertBefore(content, h1 ? h1.nextSibling : null);
    content.style.display = '';

    var navItems = Array.prototype.slice.call(content.querySelectorAll('.mkbase-docs-nav-item'));
    var panels = Array.prototype.slice.call(content.querySelectorAll('.mkbase-docs-panel'));

    // ── Kop per onderwerp: titel, introductie en knop naar de instellingen ──
    panels.forEach(function (panel) {
        var key = panel.getAttribute('data-panel');
        var nav = content.querySelector('.mkbase-docs-nav-item[data-panel="' + key + '"]');
        var head = document.createElement('div');
        head.className = 'mk-docs__head';
        var text = document.createElement('div');
        text.className = 'mk-docs__headtext';
        var title = document.createElement('h2');
        title.className = 'mk-docs__title';
        title.textContent = nav ? nav.textContent : key;
        title.tabIndex = -1;
        text.appendChild(title);
        var intro = panel.querySelector(':scope > .mkbase-docs-intro');
        if (intro) text.appendChild(intro);
        head.appendChild(text);
        var link = panel.querySelector(':scope > .mkbase-docs-settings-link');
        if (link) { link.classList.add('mk-btn'); head.appendChild(link); }
        panel.insertBefore(head, panel.firstChild);
    });

    // ── Onderwerp kiezen ─────────────────────────────────────
    function show(key, focus) {
        var found = false;
        panels.forEach(function (p) {
            var on = p.getAttribute('data-panel') === key;
            p.classList.toggle('is-active', on);
            p.hidden = !on;
            if (on) found = true;
        });
        if (!found) return false;
        navItems.forEach(function (b) {
            var on = b.getAttribute('data-panel') === key;
            b.classList.toggle('is-active', on);
            if (on) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current');
        });
        if (window.history.replaceState) window.history.replaceState(null, '', '#' + key);
        if (focus) {
            var t = content.querySelector('.mkbase-docs-panel.is-active .mk-docs__title');
            if (t) t.focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        return true;
    }

    navItems.forEach(function (btn) {
        btn.addEventListener('click', function () {
            clearSearch();
            show(btn.getAttribute('data-panel'), true);
        });
        btn.addEventListener('keydown', function (e) {
            var i = navItems.indexOf(btn);
            var next = e.key === 'ArrowDown' ? navItems[i + 1] : e.key === 'ArrowUp' ? navItems[i - 1] : null;
            if (next) { e.preventDefault(); next.focus(); next.click(); }
        });
    });

    // #onderwerp of #onderwerp/sectie in de URL (bijv. vanuit "Meer uitleg")
    function fromHash() {
        var parts = decodeURIComponent(window.location.hash.slice(1)).split('/');
        if (!parts[0] || !show(parts[0], false)) { show(panels[0] && panels[0].getAttribute('data-panel'), false); return; }
        if (parts[1]) {
            var sec = document.getElementById('docs-' + parts[1]);
            if (sec) {
                sec.querySelectorAll('.mkbase-docs-q').forEach(function (q) { setItem(q, true); });
                setTimeout(function () { sec.scrollIntoView({ block: 'start' }); }, 50);
            }
        }
    }

    // ── Vragen openklappen ───────────────────────────────────
    function setItem(q, open) {
        q.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.getElementById(q.getAttribute('aria-controls')).hidden = !open;
    }
    content.querySelectorAll('.mkbase-docs-q').forEach(function (q) {
        q.addEventListener('click', function () { setItem(q, q.getAttribute('aria-expanded') !== 'true'); });
    });

    // ── Kopieerknop bij code ─────────────────────────────────
    content.querySelectorAll('pre').forEach(function (pre) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mk-docs__copy';
        btn.textContent = 'Kopiëren';
        btn.addEventListener('click', function () {
            var code = pre.querySelector('code');
            navigator.clipboard.writeText((code ? code.textContent : pre.textContent).trim()).then(function () {
                btn.textContent = 'Gekopieerd';
                setTimeout(function () { btn.textContent = 'Kopiëren'; }, 2000);
            });
        });
        pre.appendChild(btn);
    });

    // ── Stijlgids: kleurwaarden tonen (ook na wisselen van licht/donker) en de toast proberen ──
    var vars = content.querySelectorAll('[data-mk-var]');
    function kleurwaarden() {
        var stijl = getComputedStyle(document.body);
        Array.prototype.forEach.call(vars, function (el) {
            el.textContent = stijl.getPropertyValue(el.getAttribute('data-mk-var')).trim();
        });
    }
    if (vars.length) {
        kleurwaarden();
        new MutationObserver(kleurwaarden).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }
    var toastKnop = content.querySelector('[data-mk-sg-toast]');
    if (toastKnop && window.mkToast) toastKnop.addEventListener('click', function () {
        window.mkToast({ title: 'Wijzigingen opgeslagen', sub: 'Dit is een voorbeeld', action: { label: 'Ongedaan maken', onClick: function () {} } });
    });

    // ── Zoeken in alle onderwerpen ───────────────────────────
    var search = document.getElementById('mkbase-docs-search');
    var empty = content.querySelector('.mk-docs__empty');
    var layout = document.getElementById('mkbase-docs-layout');
    var activeKey = null;

    function clearSearch() {
        if (!search || !search.value) return;
        search.value = '';
        runSearch();
    }

    function runSearch() {
        var q = search.value.trim().toLowerCase();
        var searching = q.length >= 2;
        layout.classList.toggle('is-searching', searching);

        if (!searching) {
            content.querySelectorAll('.mkbase-docs-section, .mkbase-docs-item').forEach(function (el) { el.hidden = false; });
            content.querySelectorAll('.mkbase-docs-q').forEach(function (b) { setItem(b, false); });
            if (empty) empty.hidden = true;
            if (activeKey) { show(activeKey, false); activeKey = null; }
            return;
        }
        if (!activeKey) {
            var cur = content.querySelector('.mkbase-docs-panel.is-active');
            activeKey = cur ? cur.getAttribute('data-panel') : null;
        }

        var hits = 0;
        panels.forEach(function (panel) {
            var panelHits = 0;
            panel.querySelectorAll('.mkbase-docs-section').forEach(function (sec) {
                var secTitle = sec.querySelector('.mkbase-docs-section__title').textContent.toLowerCase();
                var secHits = 0;
                sec.querySelectorAll('.mkbase-docs-item').forEach(function (item) {
                    var ok = secTitle.indexOf(q) !== -1 || item.textContent.toLowerCase().indexOf(q) !== -1;
                    item.hidden = !ok;
                    setItem(item.querySelector('.mkbase-docs-q'), ok);
                    if (ok) secHits++;
                });
                sec.hidden = secHits === 0;
                panelHits += secHits;
            });
            panel.hidden = panelHits === 0;
            hits += panelHits;
        });
        if (empty) empty.hidden = hits > 0;
    }
    if (search) search.addEventListener('input', runSearch);

    fromHash();
    window.addEventListener('hashchange', fromHash);
})();
