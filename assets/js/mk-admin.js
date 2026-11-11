/* mk-admin.js — gedrag van de MK-balk en gedeelde onderdelen op alle Mediakanjers-pagina's */
(function () {
    'use strict';

    var cfg = window.mkAdmin || {};
    var $ = window.jQuery;
    var bar = document.querySelector('.mk-bar');
    if (!bar) return;

    var ICON_CHECK = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    var ICON_WARN = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>';

    function el(tag, cls, html) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (html != null) node.innerHTML = html;
        return node;
    }

    // ── Toast ────────────────────────────────────────────────
    // mkToast({ title, sub, action: { label, href | onClick }, timeout })
    function toast(opts) {
        var old = document.querySelector('.mk-toast:not(.mk-toast--inline)'); // niet het voorbeeld in de Stijlgids
        if (old) old.remove();

        var node = el('div', 'mk-toast');
        node.setAttribute('role', 'status');
        node.appendChild(el('span', 'mk-toast__icon' + (opts.tone ? ' mk-toast__icon--' + opts.tone : ''), opts.icon || ICON_CHECK));
        var text = el('div');
        text.appendChild(el('div', 'mk-toast__title')).textContent = opts.title;
        if (opts.sub) text.appendChild(el('div', 'mk-toast__sub')).textContent = opts.sub;
        node.appendChild(text);

        if (opts.action) {
            var act;
            if (opts.action.href) {
                act = el('a');
                act.href = opts.action.href;
                if (opts.action.blank) { act.target = '_blank'; act.rel = 'noopener'; }
            } else {
                act = el('button');
                act.type = 'button';
                act.addEventListener('click', function () { opts.action.onClick(); close(); });
            }
            act.textContent = opts.action.label;
            node.appendChild(act);
        }

        document.body.appendChild(node);
        var timer = setTimeout(close, opts.timeout || 6000);
        node.addEventListener('mouseenter', function () { clearTimeout(timer); });
        node.addEventListener('focusin', function () { clearTimeout(timer); });
        node.addEventListener('mouseleave', function () { timer = setTimeout(close, 3000); });

        function close() {
            clearTimeout(timer);
            node.classList.add('is-leaving');
            setTimeout(function () { node.remove(); if (opts.onClose) opts.onClose(); }, 200);
        }
        return { close: close };
    }
    window.mkToast = toast;

    // ── Schakelaar (.mk-switch) ──────────────────────────────
    // Zet aria-checked om en stuurt een change-event, zodat een pagina erop kan reageren
    document.addEventListener('click', function (e) {
        var sw = e.target.closest && e.target.closest('.mk-switch');
        if (!sw || sw.disabled) return;
        sw.setAttribute('aria-checked', sw.getAttribute('aria-checked') === 'true' ? 'false' : 'true');
        sw.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // ── Venster ──────────────────────────────────────────────
    // mkDialog({ title, text, buttons: [{ label, variant, onClick }] }) — Esc of buiten klikken = sluiten
    function dialog(opts) {
        var back = el('div', 'mk-dialog-backdrop');
        var box = el('div', 'mk-dialog');
        box.setAttribute('role', 'alertdialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-labelledby', 'mk-dialog-title');
        box.appendChild(el('span', 'mk-dialog__icon', ICON_WARN));
        var h = el('h2'); h.id = 'mk-dialog-title'; h.textContent = opts.title; box.appendChild(h);
        if (opts.text) box.appendChild(el('p')).textContent = opts.text;

        var actions = el('div', 'mk-dialog__actions');
        opts.buttons.forEach(function (b) {
            var btn = el('button', 'mk-btn' + (b.variant ? ' mk-btn--' + b.variant : ''));
            btn.type = 'button';
            btn.textContent = b.label;
            btn.addEventListener('click', function () { close(); if (b.onClick) b.onClick(); });
            actions.appendChild(btn);
        });
        box.appendChild(actions);
        back.appendChild(box);

        var previous = document.activeElement;
        document.body.appendChild(back);
        actions.querySelector('button').focus();

        function onKey(e) {
            if (e.key === 'Escape') close();
            if (e.key === 'Tab') { // Focus binnen het venster houden
                var items = box.querySelectorAll('button');
                var first = items[0], last = items[items.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        }
        document.addEventListener('keydown', onKey);
        back.addEventListener('click', function (e) { if (e.target === back) close(); });

        function close() {
            document.removeEventListener('keydown', onKey);
            back.remove();
            if (previous && previous.focus) previous.focus();
        }
        return { close: close };
    }
    window.mkDialog = dialog;

    // ── Donkere modus (per gebruiker) ────────────────────────
    var darkBtn = bar.querySelector('.mk-bar__dark');
    if (darkBtn) {
        darkBtn.addEventListener('click', function () {
            var on = !document.body.classList.contains('mk-dark');
            document.body.classList.toggle('mk-dark', on);
            document.body.classList.toggle('dark', on);
            darkBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            try { localStorage.setItem('mkbase-dark', on ? '1' : '0'); } catch (e) {}

            var data = new FormData();
            data.append('action', 'mkbase_set_dark');
            data.append('_ajax_nonce', cfg.nonce);
            if (on) data.append('dark', '1');
            // keepalive: het opslaan loopt door, ook als je direct naar een andere pagina gaat
            fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true });
        });
    }

    // ── WordPress-meldingen in een paneel ────────────────────
    var noticeBtn = bar.querySelector('.mk-bar__notices');
    var panel = document.getElementById('mk-notices-panel');
    var list = panel && panel.querySelector('.mk-notices-panel__list');

    // Verborgen meldingen: de meeste plugins zetten hun melding op elke pagina opnieuw neer, dus het
    // thema onthoudt (per gebruiker) een kenmerk van de tekst. Zelfde berekening als in admin-notices.js.
    var hiddenKeys = (cfg.hiddenNotices || []).slice();
    var hiddenStore = document.createElement('div'); // verborgen meldingen van deze pagina, voor "Weer tonen"
    function noticeKey(n) {
        var c = n.cloneNode(true);
        Array.prototype.forEach.call(c.querySelectorAll('button, .notice-dismiss, .screen-reader-text, .mk-notice-hide'), function (x) { x.remove(); });
        var text = c.textContent.replace(/\s+/g, ' ').trim().slice(0, 500), h = 5381;
        for (var i = 0; i < text.length; i++) h = ((h << 5) + h + text.charCodeAt(i)) | 0;
        return 'n' + (h >>> 0).toString(36);
    }
    // Stuurt de hele lijst, één keer na een korte pauze (snel na elkaar wegklikken = één verzoek)
    var saveTimer = null;
    function saveHidden() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            var data = new FormData();
            data.append('action', 'mkbase_notices');
            data.append('_ajax_nonce', cfg.nonce);
            hiddenKeys.forEach(function (k) { data.append('keys[]', k); });
            fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true });
        }, 150);
    }
    function hideNotice(n) {
        var key = noticeKey(n);
        if (hiddenKeys.indexOf(key) < 0) hiddenKeys.push(key);
        hiddenStore.appendChild(n);
        return key;
    }

    function collectNotices() {
        var wrap = document.querySelector('#wpbody-content');
        if (!wrap || !list) return;
        var found = wrap.querySelectorAll('.notice:not(.inline):not(.hidden), .updated:not(.inline), div.error:not(.inline)');
        Array.prototype.forEach.call(found, function (n) {
            if (n.closest('.mk-notices-panel') || n.closest('.acf-field') || n.closest('#poststuff')) return;
            // De standaard "bijgewerkt"-melding na opslaan wordt de toast
            if (cfg.saved && /notice-success|updated/.test(n.className) && /bijgewerkt|updated|opgeslagen|saved/i.test(n.textContent)) {
                n.remove();
                return;
            }
            if (hiddenKeys.indexOf(noticeKey(n)) > -1) { hiddenStore.appendChild(n); return; }
            // Elke melding is weg te klikken, ook als de plugin zelf geen kruisje geeft
            // (bij is-dismissible zet WordPress zelf straks een kruisje neer)
            if (!n.classList.contains('is-dismissible') && !n.querySelector('.notice-dismiss, .mk-notice-hide')) {
                var x = el('button', 'mk-notice-hide', '<span aria-hidden="true">×</span>');
                x.type = 'button';
                x.setAttribute('aria-label', 'Deze melding verbergen');
                n.appendChild(x);
            }
            list.appendChild(n);
        });
        updateCount();
    }

    function updateCount() {
        var count = list.children.length;
        var hidden = hiddenStore.children.length;
        // Alleen verborgen meldingen: knop blijft (zonder aantal), zodat je ze kunt terughalen
        noticeBtn.hidden = count === 0 && hidden === 0;
        noticeBtn.classList.toggle('is-quiet', count === 0);
        noticeBtn.querySelector('.mk-bar__notices-label').innerHTML = count
            ? '<b>' + count + '</b><span class="mk-bar__notices-text"> ' + (count === 1 ? 'WordPress-melding' : 'WordPress-meldingen') + '</span>'
            : '';
        noticeBtn.setAttribute('aria-label', count ? count + (count === 1 ? ' WordPress-melding' : ' WordPress-meldingen') : 'WordPress-meldingen (' + hidden + ' verborgen)');
        if (panel) {
            panel.classList.toggle('is-empty', count === 0);
            var foot = panel.querySelector('.mk-notices-panel__foot');
            foot.hidden = hidden === 0;
            foot.querySelector('span').textContent = hidden === 1 ? '1 verborgen' : hidden + ' verborgen';
        }
    }

    function closePanel() {
        if (!panel) return;
        panel.hidden = true;
        noticeBtn.setAttribute('aria-expanded', 'false');
    }

    if (noticeBtn && panel) {
        noticeBtn.addEventListener('click', function () {
            var open = panel.hidden;
            panel.hidden = !open;
            noticeBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!panel.hidden && !panel.contains(e.target) && !noticeBtn.contains(e.target)) closePanel();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) { closePanel(); noticeBtn.focus(); }
        });
        // Alles verbergen: wegklikbare meldingen ook bij de plugin wegklikken, en alles onthouden
        var hideAll = el('button', 'mk-notices-panel__hide', 'Alles verbergen');
        hideAll.type = 'button';
        panel.querySelector('.mk-notices-panel__head').appendChild(hideAll);
        hideAll.addEventListener('click', function () {
            // Elke melding heeft een kruisje (van de plugin of van ons): die aanklikken laat de plugin
            // het ook weten, en de klik hieronder onthoudt hem
            Array.prototype.slice.call(list.children).forEach(function (n) {
                var x = n.querySelector('.notice-dismiss, .mk-notice-hide');
                if (x) x.click(); else { hideNotice(n); saveHidden(); }
            });
            updateCount();
            noticeBtn.focus();
        });

        // Eén melding wegklikken: ons kruisje of dat van WordPress
        list.addEventListener('click', function (e) {
            var x = e.target.closest('.notice-dismiss, .mk-notice-hide');
            if (!x) return;
            var n = x.closest('.mk-notices-panel__list > *');
            if (!n) return;
            hideNotice(n);
            saveHidden();
            updateCount();
            if (!list.children.length) noticeBtn.focus();
        }, true);

        // Onderaan: verborgen meldingen weer tonen
        var foot = el('div', 'mk-notices-panel__foot', '<span></span>');
        var showAgain = el('button', 'mk-notices-panel__hide', 'Weer tonen');
        showAgain.type = 'button';
        foot.appendChild(showAgain);
        foot.hidden = true;
        panel.appendChild(el('p', 'mk-notices-panel__empty', 'Geen nieuwe meldingen.'));
        panel.appendChild(foot);
        showAgain.addEventListener('click', function () {
            hiddenKeys = [];
            saveHidden();
            while (hiddenStore.firstChild) list.appendChild(hiddenStore.firstChild);
            updateCount();
        });

        // Meldingen die WordPress zelf weghaalt, verdwijnen uit de telling
        new MutationObserver(updateCount).observe(list, { childList: true });
        collectNotices();
        window.addEventListener('load', collectNotices);
    }

    // ── Introductie en waarschuwing onder de paginatitel ─────
    var h1 = document.querySelector('#wpbody-content > .wrap > h1');
    if (h1 && (cfg.intro || cfg.note)) {
        var after = h1;
        if (cfg.intro) { after = el('p', 'mk-intro'); after.textContent = cfg.intro; h1.insertAdjacentElement('afterend', after); }
        if (cfg.note) {
            var note = el('div', 'mk-note', ICON_WARN.replace('width="22" height="22"', 'width="20" height="20"') + '<span>' + cfg.note + '</span>');
            note.setAttribute('role', 'note');
            after.insertAdjacentElement('afterend', note);
        }
    }

    // ── Uitleg bij veldgroepen ───────────────────────────────
    Object.keys(cfg.groups || {}).forEach(function (id) {
        var head = document.querySelector('#' + id + ' > .postbox-header');
        if (head && !head.querySelector('.mk-group-desc')) head.appendChild(el('p', 'mk-group-desc')).textContent = cfg.groups[id];
    });

    // ── Bevestiging na opslaan ───────────────────────────────
    if (cfg.saved) {
        toast({
            title: 'Wijzigingen opgeslagen',
            sub: cfg.rocket ? 'De cache van de website is geleegd' : '',
            action: { label: 'Bekijk website', href: cfg.site, blank: true }
        });
        // ?message=1 uit de adresbalk, zodat verversen geen tweede bevestiging geeft
        if (window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.delete('message');
            window.history.replaceState(null, '', url);
        }
    }

    // ── Teksteditor (bijv. meldingtekst): ruimte rondom en een gewone lettergrootte ──
    if (window.acf && acf.add_filter) {
        acf.add_filter('wysiwyg_tinymce_settings', function (init) {
            var dark = document.body.classList.contains('mk-dark');
            init.content_style = (init.content_style || '') +
                'body{margin:0;padding:10px 14px;font:14px/1.6 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;' +
                'color:' + (dark ? '#E6EDF7' : '#0F1B2D') + ';background:' + (dark ? '#0E1626' : '#FFFFFF') + '}' +
                'p{margin:0 0 8px}p:last-child{margin-bottom:0}a{color:' + (dark ? '#7CC6F2' : '#0B6FB8') + '}';
            return init;
        });
    }

    // ── Opslaan, Ctrl+S en niet-opgeslagen wijzigingen ───────
    var form = cfg.isAcf ? document.getElementById('post') : null;
    if (!form) return;

    var saveBtn = bar.querySelector('.mk-bar__save');
    var cancelBtn = bar.querySelector('.mk-bar__cancel');
    var dirtyEl = bar.querySelector('.mk-bar__dirty');
    var dirty = false;
    var submitting = false;
    var tracking = false; // ACF zet bij het opbouwen zelf waarden: pas na het laden meetellen
    var basis = null;     // Formulier zoals het geladen is; terugdraaien = weer "opgeslagen"
    var basisTijd = 0;
    window.addEventListener('load', function () { setTimeout(function () { basis = snapshot(); basisTijd = Date.now(); tracking = true; }, 400); });

    // Alles wat Opslaan zou versturen (uitgeschakelde velden, zoals verwijderde rijen, tellen niet mee).
    // _acf_changed zet ACF bij de eerste wijziging op 1 en nooit meer terug: niet meetellen.
    function snapshot() {
        if (window.tinymce && window.tinymce.triggerSave) window.tinymce.triggerSave();
        var delen = [];
        new FormData(form).forEach(function (v, k) {
            if (k !== 'mk_redirect' && k !== '_acf_changed') delen.push(k + '=' + (typeof v === 'string' ? v : v.name));
        });
        return delen.join('&');
    }

    function setDirty(on) {
        if (on && !tracking) return;
        dirty = on;
        dirtyEl.hidden = !on;
        if (cancelBtn) cancelBtn.hidden = !on;
    }

    // Na een wijziging vergelijken met het geladen formulier (kort gewacht, ACF werkt velden in stappen bij)
    var checkTimer = null;
    function check() {
        if (!tracking) return;
        clearTimeout(checkTimer);
        checkTimer = setTimeout(function () { setDirty(snapshot() !== basis); }, 150);
    }

    // Voor andere scripts (bijv. Meldingen: rij verwijderen zonder invoerveld te wijzigen)
    window.mkAdminDirty = check;
    window.mkAdminIsDirty = function () { return dirty; };

    // Annuleren: wijzigingen weggooien door de pagina opnieuw te laden
    if (cancelBtn) cancelBtn.addEventListener('click', function () {
        setDirty(false);
        window.location.reload();
    });

    function save(redirect) {
        var publish = document.getElementById('publish');
        if (!publish) return;
        var field = form.querySelector('input[name="mk_redirect"]');
        if (redirect) {
            if (!field) { field = el('input'); field.type = 'hidden'; field.name = 'mk_redirect'; form.appendChild(field); }
            field.value = redirect;
        } else if (field) {
            field.remove();
        }
        if (saveBtn) saveBtn.classList.add('is-busy');
        // ACF verstuurt het formulier na zijn controle zelf opnieuw (zonder submit-event),
        // dus nu al aangeven dat weggaan mag. Bij een fout zet validation_failure het terug.
        submitting = true;
        publish.click();
    }

    if (saveBtn) saveBtn.addEventListener('click', function () { save(); });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && !e.altKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            save();
        }
    });

    // Wijzigingen bijhouden. Via jQuery, omdat ACF zijn velden met jQuery.trigger('change') bijwerkt.
    var mark = function (e) { if (!e || !e.target || !e.target.closest || !e.target.closest('.acf-hidden')) check(); };
    if ($) $(form).on('change input', ':input', mark);
    else form.addEventListener('input', mark);

    form.addEventListener('submit', function () { submitting = true; });

    if (window.acf && window.acf.addAction) {
        // Rij toegevoegd, verwijderd of versleept, en de editor (TinyMCE) die zelf bijhoudt
        // (bij "remove" staat de rij er nog even: na de animatie opnieuw kijken)
        ['append', 'remove', 'sortstop'].forEach(function (a) { window.acf.addAction(a, function () { check(); setTimeout(check, 400); }); });
        window.acf.addAction('wysiwyg_tinymce_init', function (ed) {
            ed.on('change input undo redo keyup', check);
            // Een editor die na het vastleggen start, schrijft zijn tekst iets anders terug: opnieuw vastleggen
            // (alleen vlak na het laden; een editor in een nieuwe rij is wél een wijziging)
            if (tracking && !dirty && Date.now() - basisTijd < 3000) setTimeout(function () { if (!dirty) basis = snapshot(); }, 50);
        });
        window.acf.addAction('validation_failure', function () {
            submitting = false;
            if (saveBtn) saveBtn.classList.remove('is-busy');
            // Focus op de foutmelding bovenaan, niet op het veld zelf: ACF haalt de rode
            // markering weg zodra een fout veld focus krijgt. Naar het veld scrollen doet ACF zelf.
            setTimeout(function () {
                var msg = form.querySelector('.acf-error-message');
                if (!msg) return;
                msg.setAttribute('tabindex', '-1');
                msg.focus({ preventScroll: true });
            }, 50);
        });
        // ACF's eigen waarschuwing bij weggaan uitzetten: dat doet deze balk nu
        if (window.acf.unload) window.acf.unload.active = false;
    }

    window.addEventListener('beforeunload', function (e) {
        if (!dirty || submitting) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // Klik op een link binnen wp-admin terwijl er wijzigingen openstaan: eigen venster
    document.addEventListener('click', function (e) {
        if (!dirty || submitting || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download') || form.contains(a) || a.closest('.media-modal, .mk-toast')) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) return;
        var to = new URL(a.href, window.location.href);
        if (to.origin !== window.location.origin) return;

        e.preventDefault();
        dialog({
            title: 'Je wijzigingen zijn nog niet opgeslagen',
            text: 'Wat wil je doen voordat je deze pagina verlaat?',
            buttons: [
                { label: 'Opslaan en verder', onClick: function () { save(to.href); } },
                { label: 'Niet opslaan', variant: 'danger', onClick: function () { setDirty(false); window.location.href = to.href; } },
                { label: 'Blijven', variant: 'link' }
            ]
        });
    }, true);

    // ── Afbeeldingsvelden: knoppen en bestandsgegevens onder de afbeelding ──
    function decorateImage(up) {
        if (up.dataset.mkReady) return;
        up.dataset.mkReady = '1';
        var input = up.querySelector('input[type="hidden"]');

        // Lege staat in het Nederlands
        var empty = up.querySelector('.hide-if-value p');
        var add = up.querySelector('.hide-if-value a[data-name="add"]');
        if (empty && add) {
            empty.textContent = '';
            empty.appendChild(el('span', 'mk-img-empty', 'Nog geen afbeelding gekozen'));
            add.textContent = 'Afbeelding kiezen';
            empty.appendChild(add);
        }

        var tools = el('div', 'show-if-value mk-img-tools');
        var replace = el('button', 'mk-btn mk-btn--secondary mk-btn--sm', 'Vervangen');
        var remove = el('button', 'mk-btn mk-btn--link mk-btn--sm', 'Verwijderen');
        var meta = el('span', 'mk-img-meta');
        replace.type = remove.type = 'button';
        tools.appendChild(replace); tools.appendChild(remove); tools.appendChild(meta);
        up.appendChild(tools);

        // De verborgen ACF-links doen het echte werk (mediavenster openen, waarde leegmaken)
        replace.addEventListener('click', function () { $(up.querySelector('a[data-name="add"]')).trigger('click'); });
        remove.addEventListener('click', function () { $(up.querySelector('a[data-name="remove"]')).trigger('click'); });

        function updateMeta() {
            meta.textContent = '';
            var id = parseInt(input && input.value, 10);
            if (!id || !window.wp || !wp.media) return;
            var att = wp.media.attachment(id);
            att.fetch().done(function () {
                var parts = [String(att.get('subtype') || '').toUpperCase().replace('SVG+XML', 'SVG')];
                if (att.get('width')) parts.push(att.get('width') + ' × ' + att.get('height'));
                if (att.get('filesizeHumanReadable')) parts.push(att.get('filesizeHumanReadable'));
                meta.textContent = parts.filter(Boolean).join(' · ');
            });
        }
        if (input) $(input).on('change', updateMeta);
        // wp.media laadt pas ná dit script
        if (document.readyState === 'complete') updateMeta();
        else window.addEventListener('load', updateMeta);
    }

    // ── Sociale media: het echte icoon op de merkkleur ───────
    // Icoontjes uit het thema (cfg.socials, zie mkbase_admin_social_icons()); de letters zijn alleen de terugval.
    var SOCIAL = {
        facebook: ['f', '#1877F2'], instagram: ['IG', '#C13584'], linkedin: ['in', '#0A66C2'], x: ['X', '#0F1B2D'],
        youtube: ['YT', '#FF0000'], tiktok: ['TT', '#111111'], whatsapp: ['WA', '#25D366'], snapchat: ['SC', '#E6C400'], discord: ['DC', '#5865F2']
    };
    function paintSocial(node, platform) {
        var s = SOCIAL[platform] || ['?', '#56667A'];
        var svg = cfg.socials && cfg.socials[platform];
        if (svg) node.innerHTML = svg; else node.textContent = s[0];
        node.style.background = s[1];
        node.classList.toggle('has-icon', !!svg);
        // Snapchat is geel: een donker icoon is daarop beter leesbaar
        node.classList.toggle('is-dark', platform === 'snapchat');
    }
    function decorateSocial(row) {
        var select = row.querySelector('[data-name="platform"] select');
        if (!select || row.querySelector('.mk-social-badge')) return;
        var badge = el('span', 'mk-social-badge');
        badge.setAttribute('aria-hidden', 'true');
        select.parentNode.insertBefore(badge, select);
        function paint() {
            paintSocial(badge, select.value);
        }
        select.addEventListener('change', paint);
        paint();
    }

    // ── Codevelden: regelnummers ─────────────────────────────
    function decorateCode(ta) {
        if (ta.closest('.mk-code')) return;
        ta.setAttribute('wrap', 'off');
        ta.setAttribute('spellcheck', 'false');
        var wrap = el('div', 'mk-code');
        var lines = el('pre', 'mk-code__lines');
        lines.setAttribute('aria-hidden', 'true');
        ta.parentNode.insertBefore(wrap, ta);
        wrap.appendChild(lines);
        wrap.appendChild(ta);
        function sync() {
            var n = Math.max(ta.value.split('\n').length, 1);
            var out = [];
            for (var i = 1; i <= n; i++) out.push(i);
            lines.textContent = out.join('\n');
            lines.scrollTop = ta.scrollTop;
        }
        ta.addEventListener('input', sync);
        ta.addEventListener('scroll', function () { lines.scrollTop = ta.scrollTop; });
        sync();
    }

    function decorate(root) {
        Array.prototype.forEach.call(root.querySelectorAll('.acf-image-uploader'), decorateImage);
        Array.prototype.forEach.call(root.querySelectorAll('[data-name="socials"] .acf-row:not(.acf-clone)'), decorateSocial);
        Array.prototype.forEach.call(root.querySelectorAll('.mk-page-acf-options-geavanceerd .acf-field-textarea textarea'), decorateCode);
    }
    decorate(document);
    if (window.acf && window.acf.addAction) window.acf.addAction('append', function ($el) { decorate($el[0].parentNode || $el[0]); });

    // ── 404: vereenvoudigd live voorbeeld naast de velden ────
    if (document.body.classList.contains('mk-page-acf-options-404')) {
        var box = document.querySelector('.postbox.acf-postbox');
        if (box) {
            var split = el('div', 'mk-split');
            box.parentNode.insertBefore(split, box);
            split.appendChild(box);
            var host = (cfg.site || '').replace(/^https?:\/\//, '').replace(/\/$/, '');
            var preview = el('aside', 'mk-preview',
                '<div class="mk-preview__label">Voorbeeld</div>' +
                '<div class="mk-preview__frame">' +
                    '<div class="mk-preview__chrome"><span></span><span></span><span></span><span class="mk-preview__url"></span></div>' +
                    '<div class="mk-preview__page">' +
                        '<div class="mk-preview__text"><strong class="mk-preview__title"></strong><span class="mk-preview__body"></span><span class="mk-preview__btn">Terug naar homepage</span></div>' +
                        '<img class="mk-preview__img" alt="">' +
                    '</div>' +
                '</div>' +
                '<p class="mk-preview__hint">Zo ziet de 404-pagina er ongeveer uit. Je wijzigingen zie je hier meteen; op de website pas na Opslaan.</p>');
            preview.setAttribute('aria-label', 'Voorbeeld van de 404-pagina');
            split.appendChild(preview);
            preview.querySelector('.mk-preview__url').textContent = host + '/bestaat-niet';

            var titleIn = form.querySelector('[data-name="titel"] input');
            var textIn = form.querySelector('[data-name="tekst"] textarea');
            var imgIn = form.querySelector('[data-name="afbeelding"] img[data-name="image"]');
            var imgUp = form.querySelector('[data-name="afbeelding"] .acf-image-uploader');
            var render = function () {
                preview.querySelector('.mk-preview__title').textContent = titleIn ? titleIn.value : '';
                preview.querySelector('.mk-preview__body').textContent = textIn ? textIn.value : '';
                var pImg = preview.querySelector('.mk-preview__img');
                var hasImg = imgUp && imgUp.classList.contains('has-value') && imgIn && imgIn.getAttribute('src');
                pImg.hidden = !hasImg;
                if (hasImg) pImg.src = imgIn.getAttribute('src');
            };
            [titleIn, textIn].forEach(function (i) { if (i) i.addEventListener('input', render); });
            if (imgUp) new MutationObserver(render).observe(imgUp, { attributes: true, subtree: true, attributeFilter: ['src', 'class'] });
            render();
        }
    }

    // ── Website instellingen: vereenvoudigd live voorbeeld van header en footer ──
    // Volgt het child theme: header = logo + Hoofdmenu, footer = Menu, Contact (contact-info.php) en Socials.
    var fieldsBox = document.getElementById('postbox-container-2');
    if (cfg.live && fieldsBox) {
        var live = cfg.live;
        var LIVE_KEY = 'mkbase-live';
        var liveOn = true;
        try { liveOn = localStorage.getItem(LIVE_KEY) !== '0'; } catch (e) {}

        // Knop in de MK-balk
        var liveBtn = el('button', 'mk-bar__live',
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>Live voorbeeld');
        liveBtn.type = 'button';
        var darkBtn = bar.querySelector('.mk-bar__dark');
        darkBtn.parentNode.insertBefore(liveBtn, darkBtn);

        // Velden links, voorbeeld rechts
        var wrap = el('div', 'mk-live');
        fieldsBox.parentNode.insertBefore(wrap, fieldsBox);
        wrap.appendChild(fieldsBox);
        var site = el('aside', 'mk-live__preview',
            '<div class="mk-live__head"><strong>Zo ziet het eruit</strong><span class="mk-live__state"></span>' +
                '<div class="mk-segmented mk-live__size" role="group" aria-label="Schermgrootte">' +
                    '<button type="button" data-size="desktop" aria-pressed="true">Computer</button>' +
                    '<button type="button" data-size="mobile" aria-pressed="false">Mobiel</button>' +
                '</div></div>' +
            '<div class="mk-site">' +
                '<div class="mk-site__chrome"><span></span><span></span><span></span>' +
                    '<span class="mk-site__tab" data-f="favicon"><img alt=""><i></i><b></b></span></div>' +
                '<div class="mk-site__url"></div>' +
                '<div class="mk-site__header">' +
                    '<span class="mk-site__logo" data-f="logo"><img alt=""><b></b></span>' +
                    '<nav class="mk-site__nav"></nav><span class="mk-site__burger" aria-hidden="true"><i></i><i></i><i></i></span>' +
                '</div>' +
                '<div class="mk-site__body" aria-hidden="true"><i></i><i></i><i></i><i></i></div>' +
                '<div class="mk-site__footer">' +
                    '<div class="mk-site__col"><h5>Menu</h5><div class="mk-site__fmenu"></div></div>' +
                    '<div class="mk-site__col mk-site__contact"><h5>Contact</h5>' +
                        '<strong data-f="bedrijfsnaam"></strong><span data-f="adres"></span><span data-f="postcode"></span>' +
                        '<span data-f="telefoon"></span><span data-f="mobiel"></span><span data-f="email"></span>' +
                        '<em class="mk-site__empty">Nog geen contactgegevens</em></div>' +
                    '<div class="mk-site__col"><h5>Socials</h5><div class="mk-site__socials" data-f="socials"></div></div>' +
                '</div>' +
            '</div>' +
            '<p class="mk-preview__hint">Een vereenvoudigde weergave. Het veld waar je in typt, licht op in het voorbeeld. Op de website zie je het pas na Opslaan.</p>');
        site.setAttribute('aria-label', 'Live voorbeeld van de website');
        wrap.appendChild(site);

        var frame = site.querySelector('.mk-site');
        site.querySelector('.mk-site__url').textContent = (cfg.site || '').replace(/^https?:\/\//, '').replace(/\/$/, '');
        site.querySelector('.mk-site__tab b').textContent = live.name || '';

        function fillNav(box, labels, max) {
            box.textContent = '';
            if (!labels.length) { for (var i = 0; i < max; i++) box.appendChild(el('i', 'mk-site__ph')); return; }
            labels.forEach(function (l) { box.appendChild(el('span')).textContent = l; });
        }
        fillNav(site.querySelector('.mk-site__nav'), live.menu || [], 4);
        fillNav(site.querySelector('.mk-site__fmenu'), live.footer || [], 3);

        function field(name) { return form.querySelector('.acf-field[data-name="' + name + '"]'); }
        function val(name) {
            var f = field(name), i = f && f.querySelector('input[type="text"], input[type="email"], input[type="url"], input[type="tel"]');
            return i ? i.value.trim() : '';
        }
        function imgSrc(name) {
            var f = field(name), up = f && f.querySelector('.acf-image-uploader');
            var img = up && up.classList.contains('has-value') && up.querySelector('img[data-name="image"]');
            return img ? img.getAttribute('src') : '';
        }
        function setImg(box, src) {
            var img = box.querySelector('img');
            img.hidden = !src;
            if (src && img.getAttribute('src') !== src) img.src = src;
            box.classList.toggle('has-img', !!src);
        }

        var queued = false;
        function render() {
            queued = false;
            setImg(site.querySelector('.mk-site__tab'), imgSrc('favicon'));
            var logoBox = site.querySelector('.mk-site__logo');
            setImg(logoBox, imgSrc('logo'));
            logoBox.querySelector('b').textContent = val('bedrijfsnaam') || live.name || '';

            var any = false;
            ['bedrijfsnaam', 'adres', 'postcode', 'telefoon', 'mobiel', 'email'].forEach(function (n) {
                var node = site.querySelector('.mk-site__contact [data-f="' + n + '"]'), v = val(n);
                node.textContent = v;
                node.hidden = !v; // lege velden toont het child theme niet
                if (v) any = true;
            });
            site.querySelector('.mk-site__empty').hidden = any;

            var socials = site.querySelector('.mk-site__socials');
            socials.textContent = '';
            form.querySelectorAll('[data-name="socials"] .acf-row:not(.acf-clone)').forEach(function (row) {
                var sel = row.querySelector('[data-name="platform"] select');
                if (!sel || !sel.value) return;
                var b = el('span', 'mk-site__social');
                paintSocial(b, sel.value);
                b.title = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : sel.value;
                socials.appendChild(b);
            });
            if (!socials.children.length) socials.appendChild(el('em', 'mk-site__empty', 'Nog geen kanalen'));

            site.querySelector('.mk-live__state').textContent = dirty ? 'Nog niet opgeslagen, alleen jij ziet dit' : 'Zoals het op de website staat';
        }
        function schedule() { if (!queued) { queued = true; requestAnimationFrame(render); } }

        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);
        ['logo', 'favicon'].forEach(function (n) {
            var up = field(n) && field(n).querySelector('.acf-image-uploader');
            if (up) new MutationObserver(schedule).observe(up, { attributes: true, subtree: true, attributeFilter: ['src', 'class'] });
        });
        var socialsField = field('socials');
        if (socialsField) new MutationObserver(schedule).observe(socialsField, { childList: true, subtree: true });
        render();

        // Het veld waar je in zit, licht op in het voorbeeld
        function highlight(name) {
            site.querySelectorAll('.is-hl').forEach(function (n) { n.classList.remove('is-hl'); });
            if (!name) return;
            site.querySelectorAll('[data-f="' + name + '"]').forEach(function (n) { n.classList.add('is-hl'); });
        }
        form.addEventListener('focusin', function (e) {
            var f = e.target.closest('.acf-field[data-name="socials"]') || e.target.closest('.acf-field[data-name]');
            highlight(f ? f.dataset.name : '');
        });
        form.addEventListener('focusout', function () {
            setTimeout(function () { if (!form.contains(document.activeElement)) highlight(''); }, 0);
        });

        // Computer of mobiel
        site.querySelector('.mk-live__size').addEventListener('click', function (e) {
            var b = e.target.closest('button');
            if (!b) return;
            this.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
            frame.classList.toggle('is-mobile', b.dataset.size === 'mobile');
        });

        // Aan/uit, onthouden in deze browser
        function setLive(on) {
            liveOn = on;
            liveBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            wrap.classList.toggle('is-on', on);
            site.hidden = !on;
            try { localStorage.setItem(LIVE_KEY, on ? '1' : '0'); } catch (e) {}
        }
        liveBtn.addEventListener('click', function () { setLive(!liveOn); });
        setLive(liveOn);

        // Voorbeeld blijft onder de MK-balk staan tijdens het scrollen
        function barOffset() { document.documentElement.style.setProperty('--mk-bar-bottom', Math.round(bar.getBoundingClientRect().bottom) + 'px'); }
        window.addEventListener('resize', barOffset);
        barOffset();
    }
})();
