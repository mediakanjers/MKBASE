/**
 * Mediakanjers → Meldingen: live voorbeeld en status per melding, en dark mode
 * (zelfde voorkeur als Changelog / Documentatie / Diagnose). Zie inc/notifications.php.
 */
(function ($) {
    var config = window.mkNotificationsAdmin || { now: '', styles: {} };

    // ── Dark mode ──────────────────────────────────────────────
    function applyDark(dark) {
        document.body.classList.toggle('dark', dark);
        var toggle = document.getElementById('mk-dark-toggle');
        if (toggle) toggle.textContent = dark ? '☀️  Light' : '🌙  Dark';
        try { localStorage.setItem('mkbase-dark', dark ? '1' : '0'); } catch (e) {}
    }
    try { applyDark(localStorage.getItem('mkbase-dark') === '1'); } catch (e) {}
    document.addEventListener('click', function (e) {
        if (e.target.id === 'mk-dark-toggle') applyDark(!document.body.classList.contains('dark'));
    });

    if (typeof acf === 'undefined') return;

    var POSITIES = {
        boven: 'Bovenaan, blijft staan',
        boven_scrollt: 'Bovenaan, scrolt mee weg',
        onder: 'Onderaan, blijft staan'
    };

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Waarde van een subveld in deze rij
    function waarde($row, key) {
        var field = acf.getFields({ key: key, parent: $row })[0];
        if (!field) return '';

        if (field.get('type') === 'wysiwyg') {
            var textarea = field.$el.find('textarea.wp-editor-area')[0];
            var editor   = textarea && window.tinymce && tinymce.get(textarea.id);
            if (editor && !editor.isHidden()) return editor.getContent();
        }
        return field.val();
    }

    // Aan/uit-veld: ACF geeft 1, '1' of true
    function aan(v) { return v === true || v === 1 || v === '1'; }

    function datum(waardeTekst) {
        // 'Y-m-d H:i:s' → 'd-m-Y H:i'
        var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(waardeTekst || '');
        return m ? m[3] + '-' + m[2] + '-' + m[1] + ' ' + m[4] + ':' + m[5] : '';
    }

    function status(rij) {
        if (!rij.actief) return { key: 'uit', label: 'Uit' };
        if (rij.tekstLeeg && !rij.knop.url) return { key: 'leeg', label: 'Geen tekst' };
        if (rij.planning) {
            if (rij.start && config.now < rij.start) return { key: 'gepland', label: 'Gepland vanaf ' + datum(rij.start) };
            if (rij.eind && config.now > rij.eind) return { key: 'verlopen', label: 'Verlopen sinds ' + datum(rij.eind) };
            if (rij.eind) return { key: 'live', label: 'Live tot ' + datum(rij.eind) };
        }
        return { key: 'live', label: 'Live' };
    }

    function render($row) {
        var $preview = $row.find('[data-mk-notification-preview]').first();
        if (!$preview.length) return;

        var tekst = waarde($row, 'field_mk_melding_tekst') || '';
        var knop  = waarde($row, 'field_mk_melding_knop') || {};
        var rij   = {
            actief:    aan(waarde($row, 'field_mk_melding_actief')),
            planning:  aan(waarde($row, 'field_mk_melding_planning')),
            start:     waarde($row, 'field_mk_melding_start'),
            eind:      waarde($row, 'field_mk_melding_eind'),
            tekstLeeg: $('<div>').html(tekst).text().trim() === '',
            knop:      typeof knop === 'object' ? knop : {}
        };

        var stijl  = waarde($row, 'field_mk_melding_stijl') || 'info';
        var kleur  = config.styles[stijl] || [
            waarde($row, 'field_mk_melding_achtergrond') || '#ff0000',
            waarde($row, 'field_mk_melding_tekstkleur') || '#ffffff'
        ];
        var positie   = waarde($row, 'field_mk_melding_positie') || 'boven';
        var sluitbaar = aan(waarde($row, 'field_mk_melding_sluitbaar'));
        var s         = status(rij);

        var html = '<div class="mk-notif-preview__meta">'
            + '<span class="mk-notif-badge mk-notif-badge--' + s.key + '">' + escapeHtml(s.label) + '</span>'
            + '<span class="mk-notif-preview__position">' + escapeHtml(POSITIES[positie] || '') + '</span>'
            + '</div>'
            + '<div class="mk-notif-preview__bar" style="background:' + escapeHtml(kleur[0]) + ';color:' + escapeHtml(kleur[1]) + '">'
            + '<div class="mk-notif-preview__txt">' + (rij.tekstLeeg ? '<em>Nog geen tekst</em>' : tekst) + '</div>'
            + (rij.knop.url ? '<span class="mk-notif-preview__btn">' + escapeHtml(rij.knop.title || 'Lees meer') + '</span>' : '')
            + (sluitbaar ? '<span class="mk-notif-preview__close" aria-hidden="true">×</span>' : '')
            + '</div>';

        $preview.html(html);

        // Status ook zichtbaar als de rij is ingeklapt
        var $handle = $row.children('.acf-row-handle.order');
        var $badge  = $handle.find('.mk-notif-row-badge');
        if (!$badge.length) $badge = $('<span class="mk-notif-row-badge"></span>').appendTo($handle);
        $badge.attr('class', 'mk-notif-row-badge mk-notif-badge--' + s.key).attr('title', s.label);
    }

    function rijen() {
        return $('.acf-field[data-key="field_mk_meldingen"] .acf-row').not('.acf-clone');
    }

    function renderAll() { rijen().each(function () { render($(this)); }); }

    var timer;
    function later($row) {
        clearTimeout(timer);
        timer = setTimeout(function () { $row ? render($row) : renderAll(); }, 120);
    }

    $(document).on('input change', '.acf-field[data-key="field_mk_meldingen"] .acf-row', function () {
        later($(this).closest('.acf-row'));
    });

    acf.addAction('wysiwyg_tinymce_init', function (editor) {
        editor.on('keyup change undo redo', function () {
            later($(editor.getElement()).closest('.acf-row'));
        });
    });

    acf.addAction('append', function () { later(); });
    acf.addAction('ready', renderAll);
})(jQuery);
