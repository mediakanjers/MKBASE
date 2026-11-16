/* Uitleg met ? bij elk veld (zie inc/admin/help.php). Werkt op de MK-pagina's en in de blokeditor:
   een ?-knop achter het label, een korte uitleg, en "Meer uitleg" in een zijpaneel uit de Documentatie. */
(function () {
    'use strict';

    var cfg = window.mkHelp || {};
    var pop = null, popBtn = null, panel = null, docsDoc = null;

    function el(tag, cls, html) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (html != null) n.innerHTML = html;
        return n;
    }

    // ── ?-knop achter het label ──────────────────────────────
    function decorate(field) {
        if (field.dataset.mkHelpReady) return;
        var label = field.querySelector(':scope > .acf-label');
        if (!label) return;
        field.dataset.mkHelpReady = '1';
        var name = (label.querySelector('label') || label).textContent.replace('*', '').trim();
        var btn = el('button', 'mk-help-btn', '?');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Uitleg over ' + name);
        btn.setAttribute('aria-expanded', 'false');
        btn.dataset.mkName = name;
        var target = label.querySelector('label');
        if (target) target.insertAdjacentElement('afterend', btn); else label.appendChild(btn);
    }

    function scan(root) {
        (root.querySelectorAll ? root : document).querySelectorAll('.acf-field[data-mk-help]').forEach(decorate);
    }

    // ── Korte uitleg ─────────────────────────────────────────
    function closePop(focusBack) {
        if (!pop) return;
        pop.remove();
        pop = null;
        if (popBtn) {
            popBtn.setAttribute('aria-expanded', 'false');
            if (focusBack) popBtn.focus();
        }
        popBtn = null;
    }

    function openPop(btn) {
        var field = btn.closest('.acf-field');
        closePop(false);
        popBtn = btn;
        btn.setAttribute('aria-expanded', 'true');

        pop = el('div', 'mk-help-pop');
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Uitleg: ' + btn.dataset.mkName);
        pop.tabIndex = -1;
        pop.appendChild(el('strong')).textContent = btn.dataset.mkName;
        pop.appendChild(el('p')).textContent = field.dataset.mkHelp;
        if (field.dataset.mkDocs) {
            var more = el('button', 'mk-help-more', 'Meer uitleg →');
            more.type = 'button';
            more.addEventListener('click', function () {
                var docs = field.dataset.mkDocs, name = btn.dataset.mkName;
                closePop(false);
                openPanel(docs, name, btn);
            });
            pop.appendChild(more);
        }
        document.body.appendChild(pop);

        // Onder de knop, binnen het scherm
        var r = btn.getBoundingClientRect();
        var w = pop.offsetWidth;
        var left = Math.min(Math.max(8, r.left + window.scrollX - 12), window.scrollX + document.documentElement.clientWidth - w - 8);
        pop.style.left = left + 'px';
        pop.style.top = (r.bottom + window.scrollY + 8) + 'px';
        pop.focus({ preventScroll: true });
    }

    // ── Zijpaneel met de Documentatie ────────────────────────
    function loadDocs() {
        if (docsDoc) return Promise.resolve(docsDoc);
        var body = new FormData();
        body.append('action', 'mkbase_docs_html');
        body.append('_ajax_nonce', cfg.nonce);
        return fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j || !j.success) throw new Error('docs');
                docsDoc = new DOMParser().parseFromString('<div>' + j.data.html + '</div>', 'text/html');
                return docsDoc;
            });
    }

    function closePanel() {
        if (!panel) return;
        var back = panel._back;
        panel.remove();
        panel = null;
        document.removeEventListener('keydown', panelKeys);
        if (back) back.focus();
    }

    function panelKeys(e) {
        if (e.key === 'Escape') closePanel();
    }

    function openPanel(docs, name, back) {
        closePanel();
        var parts = docs.split('/');
        var topic = parts[0], section = parts[1];

        panel = el('aside', 'mk-help-panel');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'false');
        panel.setAttribute('aria-label', 'Uitleg: ' + name);
        panel._back = back;
        panel.innerHTML =
            '<div class="mk-help-panel__head"><span class="mk-help-panel__icon" aria-hidden="true">?</span>' +
            '<div><div class="mk-help-panel__eyebrow">Uitleg</div><div class="mk-help-panel__title"></div></div>' +
            '<button type="button" class="mk-help-panel__close" aria-label="Sluiten">×</button></div>' +
            '<div class="mk-help-panel__body"><p class="mk-help-panel__loading">Laden…</p></div>' +
            '<div class="mk-help-panel__foot"><a target="_blank" rel="noopener">Open in Documentatie</a><span>Esc om te sluiten</span></div>';
        panel.querySelector('.mk-help-panel__title').textContent = name;
        panel.querySelector('.mk-help-panel__foot a').href = cfg.docs + '#' + docs;
        panel.querySelector('.mk-help-panel__close').addEventListener('click', closePanel);
        document.body.appendChild(panel);
        document.addEventListener('keydown', panelKeys);
        panel.querySelector('.mk-help-panel__close').focus();

        loadDocs().then(function (doc) {
            if (!panel) return;
            var body = panel.querySelector('.mk-help-panel__body');
            body.innerHTML = '';
            var nodes = section ? [doc.getElementById('docs-' + section)] : Array.prototype.slice.call(doc.querySelectorAll('.mkbase-docs-panel[data-panel="' + topic + '"] .mkbase-docs-section'));
            nodes = nodes.filter(Boolean);
            if (!nodes.length) { body.innerHTML = '<p>Er staat (nog) geen uitleg over dit onderwerp in de Documentatie.</p>'; return; }
            var intro = doc.querySelector('.mkbase-docs-panel[data-panel="' + topic + '"] .mkbase-docs-intro');
            if (intro && !section) body.appendChild(el('p', 'mk-help-panel__intro')).innerHTML = intro.innerHTML;
            var norm = function (s) { return s.toLowerCase().replace(/\s+/g, ' ').trim(); };
            var wanted = norm(name), current = null;
            nodes.forEach(function (sec) {
                var block = el('section', 'mk-help-panel__section');
                block.appendChild(el('h3')).textContent = sec.querySelector('.mkbase-docs-section__title').textContent;
                sec.querySelectorAll('.mkbase-docs-item').forEach(function (item) {
                    var q = item.querySelector('.mkbase-docs-q'), a = item.querySelector('.mkbase-docs-a');
                    var dl = el('div', 'mk-help-panel__item');
                    dl.appendChild(el('div', 'mk-help-panel__q')).textContent = q.textContent;
                    dl.appendChild(el('div', 'mk-help-panel__a', a.innerHTML));
                    // Het stuk over dit veld uitlichten
                    var qn = norm(q.textContent);
                    if (!current && (qn === wanted || qn.indexOf(wanted) === 0 || wanted.indexOf(qn) === 0)) {
                        current = dl;
                        dl.classList.add('is-current');
                    }
                    block.appendChild(dl);
                });
                body.appendChild(block);
            });
            if (current) {
                var top = current.offsetTop - body.offsetTop; // beide t.o.v. het (fixed) paneel
                if (top + current.offsetHeight > body.clientHeight) body.scrollTop = top - 12;
            }
        }).catch(function () {
            if (panel) panel.querySelector('.mk-help-panel__body').innerHTML = '<p>De uitleg kon niet worden geladen. Gebruik "Open in Documentatie".</p>';
        });
    }

    // Voor het blokvenster in de editor (editor-blocks.js): de link "uitleg" opent hetzelfde zijpaneel
    window.mkHelpOpen = openPanel;

    // ── Klikken, Esc en nieuwe velden ────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.mk-help-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation(); // niet ook het label (en dus het veld) activeren
            if (popBtn === btn) closePop(true); else openPop(btn);
            return;
        }
        if (pop && !pop.contains(e.target)) closePop(false);
        if (panel && !panel.contains(e.target) && !e.target.closest('.mk-help-pop')) closePanel();
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && pop) { e.stopPropagation(); closePop(true); }
    }, true);

    window.addEventListener('scroll', function () { closePop(false); }, { passive: true, capture: true });

    scan(document);
    // ACF voegt velden later toe (nieuwe rij, blok selecteren in de editor)
    if (window.MutationObserver) {
        var queued = false;
        new MutationObserver(function () {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function () { queued = false; scan(document); });
        }).observe(document.body, { childList: true, subtree: true });
    }
})();
