/* Mediakanjers → Changelog: inhoud in de pagina zetten, versies in-/uitklappen, filteren en zoeken.
   De MK-balk, stijl en donkere modus komen uit mk-admin.js / mk-admin.css. */
(function () {
    'use strict';

    // De changelog wordt in de footer geprint (inc/changelog.php); hier op zijn plek zetten
    var content = document.getElementById('mkbase-changelog-content');
    var wrap = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;
    var form = wrap.querySelector('form');
    if (form) form.hidden = true;
    var anchor = wrap.querySelector('.mk-intro') || wrap.querySelector('h1');
    wrap.insertBefore(content, anchor ? anchor.nextSibling : null);
    content.style.display = '';

    var blocks = Array.prototype.slice.call(content.querySelectorAll('.mkbase-version-block'));

    function setOpen(block, open) {
        var btn = block.querySelector('.mkbase-version-toggle');
        var body = block.querySelector('.mkbase-version-body');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.hidden = !open;
    }

    // ── In- en uitklappen ────────────────────────────────────
    blocks.forEach(function (block) {
        block.querySelector('.mkbase-version-toggle').addEventListener('click', function () {
            setOpen(block, this.getAttribute('aria-expanded') !== 'true');
        });
    });

    // ── Naar een versie springen (lijst rechts) ──────────────
    content.querySelectorAll('.mk-cl__versions a').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var block = document.getElementById(a.getAttribute('href').slice(1));
            if (!block) return;
            e.preventDefault();
            setOpen(block, true);
            block.scrollIntoView({ behavior: 'smooth', block: 'start' });
            block.querySelector('.mkbase-version-toggle').focus({ preventScroll: true });
        });
    });

    // ── Kopieerknop bij code ─────────────────────────────────
    content.querySelectorAll('pre').forEach(function (pre) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mk-cl__copy';
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

    // ── Filteren op soort en zoeken ──────────────────────────
    var filter = 'all';
    var search = document.getElementById('mkbase-changelog-search');
    var empty = content.querySelector('.mk-cl__empty');
    var tabs = content.querySelectorAll('.mkbase-filter-tab');
    var openState = blocks.map(function (b) { return b.querySelector('.mkbase-version-toggle').getAttribute('aria-expanded') === 'true'; });

    function apply() {
        var q = (search && search.value || '').trim().toLowerCase();
        var actief = filter !== 'all' || q !== '';
        var total = 0;

        blocks.forEach(function (block, i) {
            var shown = 0;
            block.querySelectorAll('li').forEach(function (li) {
                var ok = (filter === 'all' || li.querySelector('.mkbase-type-' + filter))
                    && (!q || li.textContent.toLowerCase().indexOf(q) !== -1);
                li.hidden = !ok;
                if (ok) shown++;
            });
            // Kopjes (Nieuw, Verbetering, …) zonder zichtbare punten verbergen
            block.querySelectorAll('.mkbase-version-body h3').forEach(function (h3) {
                var ul = h3.nextElementSibling;
                h3.hidden = !!(ul && ul.tagName === 'UL' && !ul.querySelector('li:not([hidden])'));
            });
            block.hidden = actief && shown === 0;
            // Tijdens filteren/zoeken alles met treffers open; daarna terug naar hoe het was
            setOpen(block, actief ? shown > 0 : openState[i]);
            total += shown;
        });
        if (empty) empty.hidden = !(actief && total === 0);
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            if (filter === 'all' && !(search && search.value)) {
                openState = blocks.map(function (b) { return b.querySelector('.mkbase-version-toggle').getAttribute('aria-expanded') === 'true'; });
            }
            filter = tab.getAttribute('data-filter');
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            apply();
        });
    });
    if (search) search.addEventListener('input', apply);
})();
