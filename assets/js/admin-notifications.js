/**
 * Mediakanjers → Meldingen. Bouwt op de ACF-repeater voort (opslaan, planning en migratie
 * blijven van ACF): kop per melding, live voorbeeld, statuslabels, verwijderen met
 * ongedaan maken, sjablonen bij een lege lijst. Zie inc/notifications.php.
 * MK-balk, toast en "niet opgeslagen" komen uit mk-admin.js.
 */
(function ($) {
    if (typeof acf === 'undefined') return;

    var config = window.mkNotificationsAdmin || { now: '', styles: {}, host: '' };
    var REPEATER = '.acf-field[data-key="field_mk_meldingen"]';

    var POSITIES = {
        boven: 'Bovenaan, blijft staan',
        boven_scrollt: 'Bovenaan, scrolt mee weg',
        onder: 'Onderaan, blijft staan',
        popup: 'Pop-up in het midden',
        blok: 'Zwevend blok'
    };
    var KORT = { boven: 'Bovenaan', boven_scrollt: 'Bovenaan, scrolt mee', onder: 'Onderaan', popup: 'Pop-up' };
    var PLEKKEN = {
        links_boven: 'linksboven', links_midden: 'links in het midden', links_onder: 'linksonder',
        rechts_boven: 'rechtsboven', rechts_midden: 'rechts in het midden', rechts_onder: 'rechtsonder'
    };

    function plaatsTekst(r) {
        if (r.positie === 'blok') return 'Blok ' + (PLEKKEN[r.blokPlek] || '');
        return KORT[r.positie] || '';
    }
    var MAANDEN = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    var ICON = {
        dup: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        del: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>',
        view: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
        chevron: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
        bell: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
        plus: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>'
    };

    function esc(text) {
        return String(text == null ? '' : text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Waarde van een subveld in deze rij
    function waarde($row, key) {
        var field = acf.getFields({ key: key, parent: $row })[0];
        if (!field) return '';
        if (field.get('type') === 'wysiwyg') {
            var textarea = field.$el.find('textarea.wp-editor-area')[0];
            var editor = textarea && window.tinymce && tinymce.get(textarea.id);
            if (editor && !editor.isHidden()) return editor.getContent();
        }
        return field.val();
    }

    function aan(v) { return v === true || v === 1 || v === '1'; }

    // 'Y-m-d H:i:s' → '20 dec 08:00'
    function kortDatum(v) {
        var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(v || '');
        return m ? parseInt(m[3], 10) + ' ' + MAANDEN[parseInt(m[2], 10) - 1] + ' ' + m[4] + ':' + m[5] : '';
    }
    function alsDatum(v) {
        var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(v || '');
        return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) : null;
    }

    function rij($row) {
        var tekst = waarde($row, 'field_mk_melding_tekst') || '';
        var knop = waarde($row, 'field_mk_melding_knop') || {};
        var stijl = waarde($row, 'field_mk_melding_stijl') || 'info';
        return {
            naam: waarde($row, 'field_mk_melding_naam'),
            tekst: tekst,
            tekstLeeg: $('<div>').html(tekst).text().trim() === '',
            knop: typeof knop === 'object' && knop ? knop : {},
            actief: aan(waarde($row, 'field_mk_melding_actief')),
            planning: aan(waarde($row, 'field_mk_melding_planning')),
            start: waarde($row, 'field_mk_melding_start'),
            eind: waarde($row, 'field_mk_melding_eind'),
            stijl: stijl,
            kleur: config.styles[stijl] || [waarde($row, 'field_mk_melding_achtergrond') || '#ff0000', waarde($row, 'field_mk_melding_tekstkleur') || '#ffffff'],
            positie: waarde($row, 'field_mk_melding_positie') || 'boven',
            blokPlek: waarde($row, 'field_mk_melding_blok_plek') || 'rechts_onder',
            breedte: parseInt(waarde($row, 'field_mk_melding_blok_breedte'), 10) || 360,
            backdrop: waarde($row, 'field_mk_melding_backdrop') === '' ? true : aan(waarde($row, 'field_mk_melding_backdrop')),
            tonen: waarde($row, 'field_mk_melding_tonen') || 'direct',
            tonenSec: parseInt(waarde($row, 'field_mk_melding_tonen_sec'), 10) || 10,
            tonenPx: parseInt(waarde($row, 'field_mk_melding_tonen_px'), 10) || 600,
            auto: aan(waarde($row, 'field_mk_melding_auto')),
            autoSec: parseInt(waarde($row, 'field_mk_melding_auto_sec'), 10) || 8,
            sluitbaar: aan(waarde($row, 'field_mk_melding_sluitbaar'))
        };
    }

    // Zelfde regels als mkbase_notification_slot() en mkbase_notifications_overlap() in PHP:
    // een pop-up en elke blokplek is er maar één tegelijk, balken mogen onder elkaar.
    function plek(r) {
        if (r.positie === 'popup') return 'popup';
        if (r.positie === 'blok') return 'blok_' + r.blokPlek;
        return null;
    }
    function overlap(a, b) {
        var van = function (r) { return r.planning && r.start ? r.start : '0000'; };
        var tot = function (r) { return r.planning && r.eind ? r.eind : '9999'; };
        return van(a) <= tot(b) && van(b) <= tot(a);
    }
    // Plekken die andere actieve meldingen in een overlappende periode al innemen: { plek: naam }
    function bezetDoorAnderen($row, r) {
        var bezet = {};
        rows().each(function () {
            if (this === $row[0]) return;
            var o = rij($(this));
            var p = plek(o);
            if (p && o.actief && overlap(o, r)) bezet[p] = o.naam || 'Naamloze melding';
        });
        return bezet;
    }

    function verschijntTekst(r) {
        if (r.tonen === 'tijd') return 'na ' + r.tonenSec + ' seconden';
        if (r.tonen === 'scroll') return 'na ' + r.tonenPx + ' px scrollen';
        return 'direct';
    }

    function gedragTekst(r) {
        var delen = [];
        if (r.tonen === 'tijd') delen.push('na ' + r.tonenSec + ' s');
        if (r.tonen === 'scroll') delen.push('na ' + r.tonenPx + ' px scrollen');
        if (r.auto) delen.push('verdwijnt na ' + r.autoSec + ' s');
        return delen.join(', ');
    }

    // Zelfde logica als mkbase_notification_status() in PHP
    function status(r) {
        if (!r.actief) return 'uit';
        if (r.tekstLeeg && !r.knop.url) return 'leeg';
        if (r.planning) {
            if (r.start && config.now < r.start) return 'gepland';
            if (r.eind && config.now > r.eind) return 'verlopen';
        }
        return 'live';
    }
    var LABEL = { live: 'Live', gepland: 'Gepland', verlopen: 'Verlopen', uit: 'Uit', leeg: 'Geen tekst' };

    function planningTekst(r) {
        if (!r.planning || (!r.start && !r.eind)) return 'altijd zichtbaar';
        if (r.start && r.eind) return kortDatum(r.start) + ' – ' + kortDatum(r.eind);
        return r.start ? 'vanaf ' + kortDatum(r.start) : 'tot ' + kortDatum(r.eind);
    }

    function rows() {
        return $(REPEATER + ' .acf-row').not('.acf-clone').not('.mk-removed');
    }

    // ── Kop per melding ──────────────────────────────────────
    function ensureHead($row) {
        var $fields = $row.children('.acf-fields');
        if ($fields.children('.mk-mhead').length) return;

        var $head = $(
            '<div class="mk-mhead">' +
                '<span class="mk-mhead__grip" aria-hidden="true"></span>' +
                '<span class="mk-mhead__swatch" aria-hidden="true"></span>' +
                '<button type="button" class="mk-mhead__title" data-act="toggle"><span class="mk-mhead__name"></span><span class="mk-mhead__meta"></span></button>' +
                '<span class="mk-pill mk-mhead__status"></span>' +
                '<button type="button" class="mk-mhead__switch" data-act="onoff" aria-label="Melding aan of uit"><span></span></button>' +
                '<span class="mk-mhead__actions">' +
                    '<button type="button" class="mk-icon-btn" data-act="view">' + ICON.view + '</button>' +
                    '<button type="button" class="mk-icon-btn" data-act="dup" aria-label="Dupliceren" title="Dupliceren">' + ICON.dup + '</button>' +
                    '<button type="button" class="mk-icon-btn" data-act="del" aria-label="Verwijderen" title="Verwijderen">' + ICON.del + '</button>' +
                    '<button type="button" class="mk-icon-btn mk-mhead__chevron" data-act="toggle">' + ICON.chevron + '</button>' +
                '</span>' +
            '</div>'
        );
        $fields.prepend($head);
    }

    function renderHead($row, r, s) {
        var $head = $row.children('.acf-fields').children('.mk-mhead');
        var open = !$row.hasClass('-collapsed');
        $head.find('.mk-mhead__swatch').css('background', r.kleur[0]);
        $head.find('.mk-mhead__name').text(r.naam || 'Naamloze melding');
        var gedrag = gedragTekst(r);
        $head.find('.mk-mhead__meta').text(plaatsTekst(r) + ' · ' + planningTekst(r) + (gedrag ? ' · ' + gedrag : ''));
        $head.find('.mk-mhead__status').attr('class', 'mk-pill mk-pill--' + s + ' mk-mhead__status').text(LABEL[s]);
        $head.find('.mk-mhead__switch').attr('aria-pressed', r.actief ? 'true' : 'false');
        $head.find('[data-act="toggle"]').attr('aria-expanded', open ? 'true' : 'false');
        $head.find('.mk-mhead__chevron').attr('aria-label', open ? 'Inklappen' : 'Uitklappen');

        // Alleen opgeslagen meldingen (ACF-id "row-N") bestaan al op de website
        var saved = opgeslagenIndex($row) !== null;
        $head.find('[data-act="view"]')
            .prop('disabled', !saved)
            .attr('aria-label', saved ? 'Bekijk op website' : 'Bekijk op website (sla eerst op)')
            .attr('title', saved ? 'Bekijk op website' : 'Sla eerst op om deze melding op de website te bekijken');
    }

    function opgeslagenIndex($row) {
        var m = /^row-(\d+)$/.exec($row.attr('data-id') || '');
        return m ? parseInt(m[1], 10) : null;
    }

    // ── Live voorbeeld ───────────────────────────────────────
    function renderPreview($row, r) {
        var $preview = $row.find('[data-mk-notification-preview]').first();
        if (!$preview.length) return;
        var vorm = r.positie === 'popup' ? 'popup' : r.positie === 'blok' ? 'blok' : 'balk';
        // Pop-up is altijd te sluiten (zie de template); blok en balk alleen als "Sluitbaar" aan staat
        var kruisje = r.sluitbaar || vorm === 'popup';
        var bar = '<div class="mk-pv__bar mk-pv__bar--' + vorm + '" style="background:' + esc(r.kleur[0]) + ';color:' + esc(r.kleur[1]) +
                (vorm === 'blok' ? ';width:' + Math.round(Math.min(r.breedte, 640) / 1280 * 100 * 1.6) + '%' : '') + '">'
            + '<span class="mk-pv__txt">' + (r.tekstLeeg ? '<em>Nog geen tekst</em>' : r.tekst) + '</span>'
            + (r.knop.url ? '<span class="mk-pv__btn">' + esc(r.knop.title || 'Lees meer') + '</span>' : '')
            + (kruisje ? '<span class="mk-pv__close" aria-hidden="true">×</span>' : '')
            + (r.auto ? '<span class="mk-pv__timer" aria-hidden="true"></span>' : '')
            + '</div>';
        var onder = r.positie === 'onder';
        var label = vorm === 'blok' ? 'Blok ' + (PLEKKEN[r.blokPlek] || '') + ', ' + r.breedte + ' px' : (POSITIES[r.positie] || '');
        var laag = '';
        if (vorm === 'popup') laag = '<div class="mk-pv__layer mk-pv__layer--popup' + (r.backdrop ? ' has-backdrop' : '') + '">' + bar + '</div>';
        if (vorm === 'blok') laag = '<div class="mk-pv__layer mk-pv__layer--blok mk-pv__layer--' + esc(r.blokPlek.replace('_', '-')) + '">' + bar + '</div>';
        var gedrag = gedragTekst(r);
        $preview.html(
            '<div class="mk-pv__label"><span>Live voorbeeld</span><span>' + esc(label) + '</span></div>' +
            '<div class="mk-pv__frame">' +
                '<div class="mk-pv__chrome"><i></i><i></i><i></i><span>' + esc(config.host || 'jouwwebsite.nl') + '</span></div>' +
                '<div class="mk-pv__page">' +
                    (vorm === 'balk' && !onder ? bar : '') +
                    '<div class="mk-pv__nav"><i></i><span><i></i><i></i><i></i></span></div>' +
                    '<div class="mk-pv__body"><i></i><i></i><i></i><i></i></div>' +
                    (vorm === 'balk' && onder ? bar : '') +
                    laag +
                '</div>' +
            '</div>' +
            (gedrag ? '<p class="mk-pv__gedrag">Verschijnt ' + esc(verschijntTekst(r)) + (r.auto ? ' en verdwijnt na ' + r.autoSec + ' seconden' : '') + '.</p>' : '')
        );
    }

    // ── Planning: tijdlijn onder de datums ───────────────────
    function renderTimeline($row, r) {
        var $eind = $row.find('[data-key="field_mk_melding_eind"]').first();
        if (!$eind.length) return;
        var $tl = $eind.children('.mk-timeline');
        if (!$tl.length) $tl = $('<div class="mk-timeline" aria-live="polite"></div>').appendTo($eind);

        var now = alsDatum(config.now), start = alsDatum(r.start), eind = alsDatum(r.eind);
        if (!start && !eind) { $tl.html('<p class="mk-timeline__txt">Zonder datums is de melding meteen en altijd zichtbaar zolang hij aan staat.</p>'); return; }

        var van = start || now, tot = eind || new Date(van.getTime() + 30 * 864e5);
        var min = Math.min(now, van), max = Math.max(tot, now);
        var span = Math.max(max - min, 1);
        var pct = function (d) { return ((d - min) / span) * 100; };
        var dag = function (ms) { return Math.round(ms / 864e5); };

        var zin;
        if (eind && now > eind) zin = 'Verlopen. De melding is niet meer zichtbaar.';
        else if (start && now < start) zin = 'Begint over ' + dag(start - now) + ' dagen' + (eind ? ' en loopt ' + Math.max(dag(eind - start), 1) + ' dagen' : '') + '.';
        else zin = eind ? 'Nu zichtbaar, nog ' + Math.max(dag(eind - now), 0) + ' dagen.' : 'Nu zichtbaar, zonder einddatum.';

        $tl.html(
            '<div class="mk-timeline__labels"><span>Vandaag</span><span>' + esc(start ? kortDatum(r.start).replace(/ \d\d:\d\d$/, '') : '') + '</span><span>' + esc(eind ? kortDatum(r.eind).replace(/ \d\d:\d\d$/, '') : '') + '</span></div>' +
            '<div class="mk-timeline__track"><span class="mk-timeline__range" style="left:' + pct(van) + '%;right:' + (100 - pct(tot)) + '%"></span><span class="mk-timeline__now" style="left:' + pct(now) + '%"></span></div>' +
            '<p class="mk-timeline__txt">' + esc(zin) + ' De cache wordt op beide momenten automatisch geleegd.</p>'
        );
    }

    // ── Weergave: kleurvlak bij stijl, schets bij positie ────
    function decorateChoices($row) {
        $row.find('[data-key="field_mk_melding_stijl"] .acf-button-group label').each(function () {
            if ($(this).children('.mk-swatch').length) return;
            var v = $(this).find('input').val();
            var bg = (config.styles[v] || [])[0] || 'conic-gradient(#e11d48, #f59e0b, #15803d, #2563eb, #e11d48)';
            $('<span class="mk-swatch" aria-hidden="true"></span>').css('background', bg).prependTo(this);
        });
        $row.find('[data-key="field_mk_melding_positie"] .acf-button-group label').each(function () {
            if ($(this).children('.mk-pos').length) return;
            var v = $(this).find('input').val();
            $('<span class="mk-pos mk-pos--' + v + '" aria-hidden="true"><i></i><i></i><b></b></span>').prependTo(this);
        });
        // Plek van het zwevende blok: schetsje van het scherm met het blok op die plek
        $row.find('[data-key="field_mk_melding_blok_plek"] .acf-button-group label').each(function () {
            if ($(this).children('.mk-plek').length) return;
            var v = $(this).find('input').val().replace('_', '-');
            $('<span class="mk-plek mk-plek--' + v + '" aria-hidden="true"><b></b></span>').prependTo(this);
        });
    }

    // Plekken die al bezet zijn door een andere actieve melding in dezelfde periode
    function renderBezet($row, r) {
        var bezet = bezetDoorAnderen($row, r);
        var markeer = function (key, waardeNaarPlek) {
            $row.find('[data-key="' + key + '"] .acf-button-group label').each(function () {
                var p = waardeNaarPlek($(this).find('input').val());
                var door = p && bezet[p];
                $(this).toggleClass('is-taken', !!door).attr('title', door ? 'Bezet door "' + door + '"' : null);
            });
        };
        markeer('field_mk_melding_positie', function (v) { return v === 'popup' ? 'popup' : null; });
        markeer('field_mk_melding_blok_plek', function (v) { return 'blok_' + v; });

        // Waarschuwing onder Positie als deze melding een bezette plek heeft
        var p = plek(r);
        var $pos = $row.find('[data-key="field_mk_melding_positie"]').first();
        var $warn = $pos.children('.mk-conflict');
        if (p && r.actief && bezet[p]) {
            if (!$warn.length) $warn = $('<p class="mk-conflict" role="status"></p>').appendTo($pos);
            $warn.text('Deze plek is in dezelfde periode al bezet door "' + bezet[p] + '". Kies een andere plek of plan ze na elkaar in; anders kun je niet opslaan.');
        } else {
            $warn.remove();
        }
    }

    // ── Alles voor één rij ───────────────────────────────────
    function render($row) {
        ensureHead($row);
        decorateChoices($row);
        var r = rij($row);
        var s = status(r);
        renderHead($row, r, s);
        renderPreview($row, r);
        renderTimeline($row, r);
        renderBezet($row, r);
        $row.attr('data-status', s);
    }

    // ── Statuslabels naast de titel ──────────────────────────
    function renderStats() {
        var counts = { live: 0, gepland: 0, verlopen: 0, uit: 0 };
        rows().each(function () {
            var s = $(this).attr('data-status');
            if (counts[s] != null) counts[s]++;
        });
        $('.mk-stats [data-status]').each(function () {
            $(this).find('b').text(counts[$(this).data('status')] || 0);
        });
        renderEmpty();
    }

    function renderAll() { rows().each(function () { render($(this)); }); renderStats(); }

    var timer;
    function later($row) {
        clearTimeout(timer);
        // Alle rijen: een wijziging in de ene melding kan een plek bij een andere vrijmaken of bezetten
        timer = setTimeout(renderAll, 120);
    }

    // ── Knoppen in de kop ────────────────────────────────────
    $(document).on('click', REPEATER + ' .mk-mhead [data-act]', function (e) {
        e.preventDefault();
        var $row = $(this).closest('.acf-row');
        var act = $(this).data('act');

        if (act === 'toggle') {
            $row.find('> .acf-row-handle.order [data-event="collapse-row"]').trigger('click');
            setTimeout(function () { render($row); }, 30);
        } else if (act === 'onoff') {
            var field = acf.getFields({ key: 'field_mk_melding_actief', parent: $row })[0];
            if (field) field.$input().prop('checked', !aan(field.val())).trigger('change');
            later($row);
        } else if (act === 'view') {
            var i = opgeslagenIndex($row);
            if (i === null || !config.view) return;
            window.open(config.view + '&mk_melding_voorbeeld=' + i, '_blank', 'noopener');
            if (window.mkAdminIsDirty && window.mkAdminIsDirty() && window.mkToast) {
                window.mkToast({
                    title: 'Je ziet de opgeslagen versie',
                    sub: 'Sla eerst op om je wijzigingen op de website te zien',
                    tone: 'neutral',
                    icon: ICON.view.replace('stroke="currentColor"', 'stroke="#9AA9BD"')
                });
            }
        } else if (act === 'dup') {
            $row.find('> .acf-row-handle.remove [data-event="duplicate-row"]').trigger('click');
        } else if (act === 'del') {
            verwijder($row);
        }
    });

    // Verwijderen met ongedaan maken: de rij verdwijnt meteen uit beeld, maar telt pas
    // niet meer mee bij Opslaan (uitgeschakelde velden worden niet verstuurd).
    function verwijder($row) {
        var naam = rij($row).naam || 'Naamloze melding';
        var $inputs = $row.find(':input:not(:disabled)');
        $inputs.prop('disabled', true);
        $row.addClass('mk-removed');
        if (window.mkAdminDirty) window.mkAdminDirty();
        renderStats();

        var gedaan = false;
        if (window.mkToast) {
            window.mkToast({
                title: '“' + naam + '” verwijderd',
                sub: 'Definitief na Opslaan',
                tone: 'neutral',
                icon: ICON.del.replace('stroke="currentColor"', 'stroke="#9AA9BD"'),
                timeout: 8000,
                action: {
                    label: 'Ongedaan maken',
                    onClick: function () {
                        if (gedaan) return;
                        gedaan = true;
                        $inputs.prop('disabled', false);
                        $row.removeClass('mk-removed');
                        renderStats();
                        if (window.mkAdminDirty) window.mkAdminDirty();
                        $row.find('.mk-mhead__title').trigger('focus');
                    }
                }
            });
        }
    }

    // ── Technisch: inklapbaar blok onder de lijst ────────────
    function buildTechnisch() {
        var $field = $('.acf-field[data-key="field_6977205593693"]');
        if (!$field.length || $field.closest('.mk-details').length) return;
        var $details = $('<details class="mk-details"><summary><span>Technisch</span><small>CSS van de meldingen zelf regelen in het child theme</small></summary><div class="mk-details__body"></div></details>');
        $details.insertAfter($(REPEATER));
        $details.find('.mk-details__body').append($field);
    }

    // ── Titel en statuslabels naast elkaar ───────────────────
    function buildPageHead() {
        var $stats = $('.mk-stats').first();
        var $h1 = $('#wpbody-content > .wrap > h1');
        if (!$stats.length || !$h1.length || $('.mk-pagehead').length) return;
        var $head = $('<div class="mk-pagehead"><div class="mk-pagehead__text"></div></div>').insertBefore($h1);
        $head.children().append($h1, $h1.next('.mk-intro'));
        $head.append($stats);
    }

    // ── Lege lijst: sjablonen ────────────────────────────────
    var SJABLONEN = [
        { titel: 'Gewijzigde openingstijden', uitleg: 'Info-stijl, bovenaan, met planning voor bijvoorbeeld de feestdagen.', naam: 'Gewijzigde openingstijden',
          tekst: 'Rond de feestdagen zijn we beperkt bereikbaar. Bekijk onze aangepaste openingstijden.', stijl: 'info', positie: 'boven', planning: true, sluitbaar: true },
        { titel: 'Actie of korting', uitleg: 'Actie-stijl, bovenaan. Voeg zelf een knop naar de actiepagina toe.', naam: 'Actie',
          tekst: '<strong>Nu 20% korting</strong> op het hele assortiment.', stijl: 'actie', positie: 'boven', planning: false, sluitbaar: true },
        { titel: 'Storing', uitleg: 'Waarschuwing-stijl, niet sluitbaar, op de hele website.', naam: 'Storing',
          tekst: 'Door een storing is betalen tijdelijk niet mogelijk. We werken aan een oplossing.', stijl: 'waarschuwing', positie: 'boven', planning: false, sluitbaar: false }
    ];

    function renderEmpty() {
        var $rep = $(REPEATER);
        var leeg = rows().length === 0;
        var $empty = $rep.find('.mk-empty');
        $rep.toggleClass('mk-is-empty', leeg);
        if (!leeg) { $empty.remove(); return; }
        if ($empty.length) return;

        var kaarten = SJABLONEN.map(function (t, i) {
            var c = config.styles[t.stijl];
            return '<button type="button" class="mk-template" data-sjabloon="' + i + '">' +
                '<span class="mk-template__pv" style="background:' + c[0] + ';color:' + c[1] + '">' + t.tekst + '</span>' +
                '<strong>' + esc(t.titel) + '</strong><span class="mk-template__txt">' + esc(t.uitleg) + '</span></button>';
        }).join('');
        $('<div class="mk-empty">' +
            '<span class="mk-empty__icon">' + ICON.bell + '</span>' +
            '<h2>Nog geen meldingen</h2>' +
            '<p>Een melding is een balk boven- of onderaan je website. Begin leeg, of kies een voorbeeld en pas het aan.</p>' +
            '<button type="button" class="mk-btn" data-sjabloon="leeg">' + ICON.plus + ' Lege melding</button>' +
            '<div class="mk-templates">' + kaarten + '</div>' +
        '</div>').prependTo($rep.children('.acf-input'));
    }

    function nieuweRij() {
        $(REPEATER).find('.acf-repeater > .acf-actions [data-event="add-row"]').last().trigger('click');
        return rows().last();
    }

    function kies($row, key, value) {
        var $label = $row.find('[data-key="' + key + '"] input[value="' + value + '"]').closest('label');
        if ($label.length && !$label.hasClass('selected')) $label.trigger('click');
    }
    function zet($row, key, on) {
        var f = acf.getFields({ key: key, parent: $row })[0];
        if (f) f.$input().prop('checked', on).trigger('change');
    }

    $(document).on('click', REPEATER + ' [data-sjabloon]', function () {
        var i = $(this).data('sjabloon');
        var $row = nieuweRij();
        if (i === 'leeg' || !SJABLONEN[i]) { later($row); return; }
        var t = SJABLONEN[i];
        var naam = acf.getFields({ key: 'field_mk_melding_naam', parent: $row })[0];
        if (naam) naam.val(t.naam);
        kies($row, 'field_mk_melding_stijl', t.stijl);
        kies($row, 'field_mk_melding_positie', t.positie);
        zet($row, 'field_mk_melding_planning', t.planning);
        zet($row, 'field_mk_melding_sluitbaar', t.sluitbaar);
        // De editor start na het toevoegen; de tekst zetten zodra hij klaar is
        var textarea = $row.find('[data-key="field_mk_melding_tekst"] textarea.wp-editor-area')[0];
        if (textarea) textarea.value = t.tekst;
        var tries = 0;
        (function zetTekst() {
            var ed = textarea && window.tinymce && tinymce.get(textarea.id);
            if (ed && ed.initialized) { ed.setContent(t.tekst); later($row); }
            else if (tries++ < 40) setTimeout(zetTekst, 100);
        })();
        if (window.mkAdminDirty) window.mkAdminDirty();
        later($row);
    });

    // ── Bijwerken bij elke wijziging ─────────────────────────
    $(document).on('input change', REPEATER + ' .acf-row', function () {
        later($(this).closest('.acf-row'));
    });
    acf.addAction('wysiwyg_tinymce_init', function (editor) {
        editor.on('keyup change undo redo', function () { later($(editor.getElement()).closest('.acf-row')); });
    });
    acf.addAction('append', function ($el) { later($el && $el.hasClass('acf-row') ? $el : null); });
    acf.addAction('remove', function () { later(); });
    acf.addAction('ready', function () {
        buildPageHead();
        buildTechnisch();
        renderAll();
    });
})(jQuery);
