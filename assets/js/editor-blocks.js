/**
 * Editor-only UX voor het Kolommen-blok (mk/kolommen):
 * - live voorbeeldbalk (bovenaan het blok, boven de tabs) die kolomverdeling,
 *   padding, verticale uitlijning en achtergrondkleur direct toont
 * - "Kolom N · X%" label bij elke repeater-rij
 * - waarschuwing als het aantal toegevoegde kolommen niet overeenkomt met de verhouding
 */
(function ($) {
    if (typeof acf === 'undefined') return;

    var PADDING_PX = { geen: 0, klein: 10, normaal: 20, groot: 32 };
    var ALIGN_MAP  = { boven: 'flex-start', midden: 'center', onder: 'flex-end', uitgerekt: 'stretch' };

    // Welke velden bij elk content-type horen — voor de groeps-labels in een kolom-rij.
    var TYPE_FIELD_GROUPS = [
        { type: 'titel',       label: 'Titel',              fields: ['titel_tekst', 'titel_niveau'] },
        { type: 'tekst',       label: 'Tekst',               fields: ['tekst'] },
        { type: 'afbeelding',  label: 'Afbeelding',          fields: ['afbeelding'] },
        { type: 'video',       label: 'Video',               fields: ['video'] },
        { type: 'knop',        label: 'Knop',                fields: ['knop', 'knop_stijl'] },
        { type: 'icoon_tekst', label: 'Icoon + tekst',       fields: ['icoon', 'icoon_tekst'] },
        { type: 'cijfer',      label: 'Cijfer',              fields: ['cijfer_waarde', 'cijfer_label'] },
    ];

    // De altijd-zichtbare stijlvelden (niet gebonden aan een content-type) — samen
    // in hun eigen "Kolom-opmaak"-vak, na alle content-type-groepen.
    var OPMAAK_FIELDS = ['kolom_achtergrond', 'kolom_tekstkleur', 'kolom_afgerond', 'kolom_padding', 'kolom_uitlijning'];

    // Groepering van de blok-brede velden op het tabblad "Opmaak" (niet in de
    // repeater) — zelfde vak-stijl als de content-type-groepen per kolom.
    var BLOCK_OPMAAK_GROUPS = [
        { label: 'Uitlijning & padding',       fields: ['verticale_uitlijning', 'padding'] },
        { label: 'Achtergrond & tekstkleur',   fields: ['achtergrond_breed', 'achtergrond_grid', 'tekstkleur', 'blok_afgerond'] },
    ];

    /**
     * Bundelt de gegeven velden (gezocht binnen $scope) in een eigen afgebakend
     * vak met kopje — hergebruikt voor zowel de content-type-groepen per kolom
     * als de blok-brede Opmaak-groepen. Het vak verbergt zichzelf via CSS (:has)
     * zodra zijn velden door conditional logic verborgen worden.
     */
    function wrapGroup($scope, fieldNames, label, extraClass) {
        var $fields = $();
        fieldNames.forEach(function (name) {
            var $f = $scope.find('.acf-field[data-name="' + name + '"]').first();
            if ($f.length) $fields = $fields.add($f);
        });
        if (!$fields.length || $fields.first().closest('.mk-type-group').length) return;

        $fields.wrapAll('<div class="mk-type-group' + (extraClass ? ' ' + extraClass : '') + '"></div>');
        $fields.first().closest('.mk-type-group').prepend('<div class="mk-type-group__label">' + label + '</div>');
        restartEditors($fields);
    }

    /**
     * Een teksteditor (TinyMCE) die na het opstarten verplaatst wordt, verliest zijn
     * iframe-inhoud: leeg en niet meer te typen. Na wrapAll daarom opnieuw opstarten;
     * de tekst staat nog in de textarea.
     */
    function restartEditors($fields) {
        if (!window.tinymce || !acf.getField) return;
        $fields.filter('.acf-field-wysiwyg').add($fields.find('.acf-field-wysiwyg')).each(function () {
            var field = acf.getField($(this));
            var id = field && field.$input().attr('id');
            if (!id || !tinymce.get(id)) return;
            acf.tinymce.destroy(id);
            field.initializeEditor();
        });
    }

    /**
     * Bundelt elke groep velden die bij hetzelfde gekozen content-type hoort
     * (Titel, Tekst, Knop, ...) in een eigen afgebakend vak met kopje, zodat bij
     * meerdere gekozen types per kolom elk onderdeel duidelijk op zichzelf staat
     * i.p.v. dat alles ononderbroken doorloopt.
     */
    function ensureTypeGroupLabels($repeaterField) {
        var $rows = $repeaterField.find('> .acf-input .acf-row:not(.acf-clone)');

        $rows.each(function () {
            var $row = $(this);
            TYPE_FIELD_GROUPS.forEach(function (group) {
                wrapGroup($row, group.fields, group.label);
            });
            wrapGroup($row, OPMAAK_FIELDS, 'Opmaak van deze kolom', 'mk-type-group--opmaak mk-type-group--kolom');
            makeCollapsible($row.find('.mk-type-group--kolom').first());
        });
    }

    // "Opmaak van deze kolom" staat standaard dicht: de inhoud is waar een klant meestal voor komt
    function makeCollapsible($group) {
        if (!$group.length || $group.data('mkCollapsible')) return;
        $group.data('mkCollapsible', true).addClass('is-closed');
        var $label = $group.children('.mk-type-group__label');
        var $btn = $('<button type="button" class="mk-type-group__toggle" aria-expanded="false"></button>').text($label.text());
        $label.replaceWith($btn);
        $btn.on('click', function () {
            var open = $group.hasClass('is-closed');
            $group.toggleClass('is-closed', !open);
            $btn.attr('aria-expanded', open ? 'true' : 'false');
        });
    }

    function ensureBlockOpmaakGroups($fieldsWrap) {
        BLOCK_OPMAAK_GROUPS.forEach(function (group) {
            wrapGroup($fieldsWrap, group.fields, group.label, 'mk-type-group--opmaak');
        });
    }

    function parseRatios(value) {
        if (!value) return [];
        var parts = String(value).split('-').map(function (n) { return parseInt(n, 10); });
        return parts.filter(function (n) { return !isNaN(n); });
    }

    function readExtras($fieldsWrap) {
        return {
            achtergrondBreed: $fieldsWrap.find('.acf-field[data-name="achtergrond_breed"] input[type="hidden"]').val() || '',
            achtergrondGrid: $fieldsWrap.find('.acf-field[data-name="achtergrond_grid"] input[type="hidden"]').val() || '',
            tekstkleur: $fieldsWrap.find('.acf-field[data-name="tekstkleur"] input[type="hidden"]').val() || '',
            padding: $fieldsWrap.find('.acf-field[data-name="padding"] input:checked').val() || 'geen',
            uitlijning: $fieldsWrap.find('.acf-field[data-name="verticale_uitlijning"] input:checked').val() || 'boven',
            afgerond: $fieldsWrap.find('.acf-field[data-name="blok_afgerond"] input[type="checkbox"]').is(':checked'),
        };
    }

    /**
     * Voorbeeldbalk staat bovenaan het hele veldenblok (buiten de tabs), zodat hij
     * zichtbaar blijft ongeacht of je op het tabblad "Inhoud" of "Opmaak" zit.
     * De buitenste panel-achtergrond toont de "volledige breedte"-kleur, de balk
     * zelf toont de "binnen grid"-kleur — zelfde gelaagdheid als op de site.
     */
    function renderPreviewBar($fieldsWrap, ratios, extras) {
        var $wrap = $fieldsWrap.find('.mk-kolommen-preview');
        if (!$wrap.length) {
            $wrap = $(
                '<div class="mk-kolommen-preview">' +
                    '<div class="mk-kolommen-preview__bar"></div>' +
                '</div>'
            );
            $fieldsWrap.prepend($wrap);
        }

        $wrap.css('backgroundColor', extras.achtergrondBreed || 'transparent');

        var $bar = $wrap.find('.mk-kolommen-preview__bar');
        $bar.empty();
        var stretched = extras.uitlijning === 'uitgerekt';
        $bar.css({
            padding: (PADDING_PX[extras.padding] || 0) + 'px',
            backgroundColor: extras.achtergrondGrid || 'transparent',
            alignItems: ALIGN_MAP[extras.uitlijning] || 'flex-start',
        });
        $bar.toggleClass('mk-kolommen-preview__bar--rounded', !!extras.afgerond);

        if (!ratios.length) return;

        ratios.forEach(function (pct, i) {
            var tall = stretched || i % 2 === 0;
            var $seg = $(
                '<div class="mk-kolommen-preview__seg' + (tall ? ' mk-kolommen-preview__seg--tall' : '') + '">' +
                    '<span>' + (i + 1) + '</span><em>' + pct + '%</em>' +
                '</div>'
            ).css('flex', pct + ' 0 0');

            if (extras.tekstkleur) {
                $seg.find('span, em').css('color', extras.tekstkleur);
            }

            $seg.appendTo($bar);
        });
    }

    /**
     * Kop bovenin elke kolom-kaart: nummer, wat erin staat ("Titel + Tekst") en de breedte,
     * plus een knop om in of uit te klappen. Blijft zichtbaar als de kolom is ingeklapt
     * (-collapsed-target). De smalle balk links is alleen nog de sleepgreep.
     */
    function updateRowLabels($repeaterField, ratios) {
        var $rows = $repeaterField.find('> .acf-input .acf-row:not(.acf-clone)');

        $rows.each(function (i) {
            var $row    = $(this);
            var pct     = ratios[i];
            var isExtra = pct === undefined;
            var $fields = $row.children('.acf-fields').first();

            $row.toggleClass('mk-row--excess', isExtra);
            $row.find('> .acf-row-handle.order .mk-kolom-label').remove();

            var $head = $fields.children('.mk-col-head');
            if (!$head.length) {
                $head = $(
                    '<div class="mk-col-head -collapsed-target">' +
                        '<span class="mk-col-head__num"></span>' +
                        '<span class="mk-col-head__sum"></span>' +
                        '<span class="mk-col-head__width"></span>' +
                        '<button type="button" class="mk-col-head__toggle"></button>' +
                    '</div>'
                );
                $fields.prepend($head);
                $head.find('.mk-col-head__toggle').on('click', function () {
                    $row.find('> .acf-row-handle.order [data-event="collapse-row"]').first().trigger('click');
                });
            }

            var types = $fields.find('.acf-field[data-name="type"] input:checked').map(function () {
                return $.trim($(this).parent().text());
            }).get();
            var collapsed = $row.hasClass('-collapsed');

            $head.find('.mk-col-head__num').text(i + 1);
            $head.find('.mk-col-head__sum').text(types.length ? types.join(' + ') : 'Nog leeg: kies hieronder wat erin komt');
            $head.find('.mk-col-head__width').text(isExtra ? 'Niet zichtbaar' : pct + '%').toggleClass('is-warn', isExtra);
            $head.find('.mk-col-head__toggle')
                .text(collapsed ? 'Openklappen' : 'Inklappen')
                .attr('aria-expanded', collapsed ? 'false' : 'true')
                .attr('aria-label', (collapsed ? 'Kolom ' : 'Kolom ') + (i + 1) + (collapsed ? ' openklappen' : ' inklappen'));
        });

        return $rows.length;
    }

    function updateMismatchNotice($verhoudingField, rowCount, ratioCount) {
        var $notice = $verhoudingField.parent().find('> .mk-kolommen-mismatch');

        if (rowCount === ratioCount || ratioCount === 0) {
            $notice.remove();
            return;
        }

        if (!$notice.length) {
            $notice = $('<div class="mk-kolommen-mismatch"></div>');
            $verhoudingField.after($notice);
        }

        var msg = rowCount > ratioCount
            ? 'Let op: je hebt meer kolommen toegevoegd dan de gekozen verhouding (' + ratioCount + '). De kolom(men) met een rode gestippelde rand hieronder worden niet zichtbaar op de website. Verwijder ze, of kies hierboven een verhouding met meer kolommen.'
            : 'Let op: er ' + (ratioCount - rowCount === 1 ? 'ontbreekt' : 'ontbreken') + ' nog ' + (ratioCount - rowCount) + ' kolom(men) om het gekozen aantal van ' + ratioCount + ' te halen.';

        $notice.text(msg);
    }

    /**
     * Voegt automatisch lege kolommen toe als de verhouding meer kolommen vraagt
     * dan er nu staan. Verwijdert nooit rijen — dat zou ingevulde content van de
     * klant stilletjes kunnen weggooien.
     */
    function autoAddMissingRows($fieldsWrap, ratios) {
        var $repeaterField = $fieldsWrap.find('.acf-field[data-name="kolommen"]').first();
        if (!$repeaterField.length) return;

        var rowCount = $repeaterField.find('.acf-row:not(.acf-clone)').length;
        var missing  = ratios.length - rowCount;
        if (missing <= 0) return;

        // Let op: [data-event="add-row"] komt ook per rij voor (klein "+"-icoon, voegt
        // een rij ín vóór die rij) — .acf-repeater-add-row is specifiek de knop onderaan.
        var $addBtn = $repeaterField.find('.acf-repeater-add-row').first();
        if (!$addBtn.length) return;

        for (var i = 0; i < missing; i++) {
            $addBtn.trigger('click');
        }
    }

    /**
     * Vergrendelt de "Kolom toevoegen"-knop zodra het aantal rijen het aantal
     * kolommen van de gekozen verhouding heeft bereikt — voorkomt dat de klant
     * per ongeluk overtollige kolommen aanmaakt die niet getoond worden.
     */
    function updateAddButtonState($repeaterField, rowCount, ratioCount) {
        var $addBtn = $repeaterField.find('.acf-repeater-add-row').first();
        if (!$addBtn.length) return;

        var maxReached = ratioCount > 0 && rowCount >= ratioCount;
        $addBtn.toggleClass('mk-add-row--disabled', maxReached).attr('aria-disabled', maxReached ? 'true' : null);

        // Uitleggen waarom de knop grijs is, anders lijkt het kapot
        var $actions = $addBtn.closest('.acf-actions');
        var $hint = $actions.children('.mk-add-hint');
        if (!maxReached) { $hint.remove(); return; }
        if (!$hint.length) $hint = $('<span class="mk-add-hint"></span>').prependTo($actions);
        var label = $repeaterField.closest('.acf-fields').find('.acf-field[data-name="verhouding"] select option:selected').text().split('—').pop();
        $hint.text('Bij ' + $.trim(label) + ' horen ' + ratioCount + (ratioCount === 1 ? ' kolom' : ' kolommen') + '. Kies hierboven een andere verhouding voor meer kolommen.');
    }

    // Verhouding als plaatjes van de indeling; de keuzelijst blijft (verborgen) het echte veld
    function ensureRatioTiles($verhoudingField) {
        var $select = $verhoudingField.find('> .acf-input select');
        var $tiles = $verhoudingField.find('.mk-ratios');
        if (!$tiles.length) {
            $tiles = $('<div class="mk-ratios" role="group"></div>').attr('aria-label', 'Verhouding');
            $select.find('option').each(function () {
                var value = this.value;
                var parts = parseRatios(value);
                var $b = $('<button type="button" class="mk-ratio"></button>').attr('data-value', value);
                var $vis = $('<span class="mk-ratio__vis" aria-hidden="true"></span>');
                parts.forEach(function (p) { $('<i></i>').css('flex', p + ' 0 0').appendTo($vis); });
                // Gelijke delen kort: 3 × 33, 4 × 25
                var same = parts.length > 2 && parts.every(function (p) { return p === parts[0]; });
                var text = parts.length === 1 ? '100%' : (same ? parts.length + ' × ' + parts[0] : parts.join(' / '));
                $b.append($vis).append($('<span class="mk-ratio__label"></span>').text(text));
                $b.attr('aria-label', $.trim($(this).text()));
                $tiles.append($b);
            });
            $tiles.on('click', '.mk-ratio', function () {
                $select.val($(this).attr('data-value')).trigger('change');
            });
            $select.addClass('mk-visually-hidden').attr('tabindex', '-1').attr('aria-hidden', 'true');
            $select.after($tiles);
        }
        $tiles.find('.mk-ratio').each(function () {
            var on = $(this).attr('data-value') === $select.val();
            $(this).toggleClass('is-selected', on).attr('aria-pressed', on ? 'true' : 'false');
        });
    }

    // Klein schetsje bij de twee achtergrondkleuren van het blok, zodat het verschil meteen duidelijk is
    var SKETCH = {
        achtergrond_breed: '<svg width="56" height="30" viewBox="0 0 56 30" aria-hidden="true"><rect x="0" y="4" width="56" height="22" fill="#0B6FB8"/><rect x="12" y="8" width="14" height="14" rx="2" fill="#fff"/><rect x="30" y="8" width="14" height="14" rx="2" fill="#fff"/></svg>',
        achtergrond_grid:  '<svg width="56" height="30" viewBox="0 0 56 30" aria-hidden="true"><rect x="0" y="4" width="56" height="22" fill="#E3E8EF"/><rect x="9" y="6" width="38" height="18" rx="3" fill="#0B6FB8"/><rect x="12" y="9" width="14" height="12" rx="2" fill="#fff"/><rect x="30" y="9" width="14" height="12" rx="2" fill="#fff"/></svg>'
    };
    function ensureSketches($fieldsWrap) {
        Object.keys(SKETCH).forEach(function (name) {
            var $label = $fieldsWrap.find('.acf-field[data-name="' + name + '"] > .acf-label').first();
            if ($label.length && !$label.find('.mk-sketch').length) $label.prepend('<span class="mk-sketch">' + SKETCH[name] + '</span>');
        });
    }

    /**
     * Rijen staan standaard ingeklapt (native ACF-collapse) — alleen "Type"
     * blijft zichtbaar. Elke rij wordt maar één keer aangeraakt (via het
     * data-attribuut), zodat een rij die de klant zelf heeft opengeklapt
     * niet steeds opnieuw dichtklapt bij een volgende refresh.
     */
    function collapseNewRows($repeaterField) {
        $repeaterField.find('> .acf-input .acf-row:not(.acf-clone)').each(function () {
            var $row = $(this);
            if ($row.data('mkCollapseInit')) return;
            $row.data('mkCollapseInit', true);

            if (!$row.hasClass('-collapsed')) {
                $row.addClass('-collapsed');
                acf.doAction('hide', $row, 'collapse');
            }
        });
    }

    function refresh($scope) {
        $scope.find('.acf-field[data-name="verhouding"]').each(function () {
            var $verhoudingField = $(this);
            var $select  = $verhoudingField.find('> .acf-input select');
            if (!$select.length) return;

            var $fieldsWrap   = $verhoudingField.closest('.acf-fields');
            var $repeaterField = $fieldsWrap.find('.acf-field[data-name="kolommen"]').first();
            if (!$repeaterField.length) return;

            var ratios = parseRatios($select.val());
            var extras = readExtras($fieldsWrap);

            collapseNewRows($repeaterField);
            ensureRatioTiles($verhoudingField);
            ensureTypeGroupLabels($repeaterField);
            ensureBlockOpmaakGroups($fieldsWrap);
            ensureSketches($fieldsWrap);
            renderPreviewBar($fieldsWrap, ratios, extras);
            var rowCount = updateRowLabels($repeaterField, ratios);
            updateMismatchNotice($verhoudingField, rowCount, ratios.length);
            updateAddButtonState($repeaterField, rowCount, ratios.length);
        });
    }

    acf.addAction('new_field/name=verhouding', function (field) {
        refresh(field.$el.closest('.acf-fields'));
    });

    // Rechtstreeks op de onderliggende elementen luisteren i.p.v. op ACF's change_field-actie:
    // die bleek niet betrouwbaar te vuren voor het native select-veld "Verhouding". Sinds ACF
    // Blocks V3 staan de velden in het uitklap-paneel (.acf-block-form-modal), niet meer
    // genest in [data-type^="mk/"] — de veldnamen zijn uniek genoeg om zonder die scoping te kunnen.
    $(document).on('change', '.acf-block-form-modal .acf-field[data-name="verhouding"] select', function () {
        var $fieldsWrap = $(this).closest('.acf-fields');
        autoAddMissingRows($fieldsWrap, parseRatios($(this).val()));
        refresh($fieldsWrap);
    });

    $(document).on('change', '.acf-block-form-modal .acf-field[data-name="achtergrond_breed"] input[type="hidden"], .acf-block-form-modal .acf-field[data-name="achtergrond_grid"] input[type="hidden"], .acf-block-form-modal .acf-field[data-name="tekstkleur"] input[type="hidden"]', function () {
        refresh($(this).closest('.acf-fields'));
    });

    $(document).on('change', '.acf-block-form-modal .acf-field[data-name="padding"] input, .acf-block-form-modal .acf-field[data-name="verticale_uitlijning"] input, .acf-block-form-modal .acf-field[data-name="blok_afgerond"] input', function () {
        refresh($(this).closest('.acf-fields'));
    });

    // Repeater-rijen worden via acf.doAction('append', ...) geïnitialiseerd bij toevoegen.
    acf.addAction('append', function () {
        setTimeout(function () { refresh($(document)); }, 0);
    });

    // Verwijderen/dupliceren/inklappen van een rij triggert geen ACF-actie — reageren op de knoppen zelf.
    $(document).on('click', '.acf-block-form-modal [data-event="remove-row"], .acf-block-form-modal [data-event="duplicate-row"], .acf-block-form-modal [data-event="collapse-row"]', function () {
        setTimeout(function () { refresh($(document)); }, 50);
    });

    // Samenvatting in de kolomkop bijwerken als je kiest wat erin komt
    $(document).on('change', '.acf-block-form-modal .acf-field[data-name="kolommen"] .acf-field[data-name="type"] input', function () {
        refresh($(this).closest('.acf-field[data-name="kolommen"]').closest('.acf-fields'));
    });

    // Na slepen (nieuwe volgorde) de nummers en breedtes opnieuw zetten
    $(document).on('sortstop', '.acf-block-form-modal .acf-field[data-name="kolommen"] tbody', function () {
        setTimeout(function () { refresh($(document)); }, 0);
    });

    // Vangnet voor blokken die al in de canvas stonden vóór dit script actief werd.
    $(function () { refresh($(document)); });
})(jQuery);

/**
 * Toont bovenin élk mk/-blok (binnen de velden-kaart, niet erboven) een vast
 * label met de blok-titel en -omschrijving uit block.json, zodat direct
 * duidelijk is welk blok je toevoegt of aanpast. Via jQuery in de ACF-velden-
 * container — dat DOM-gebied wordt door ACF zelf beheerd (niet door Gutenberg's
 * React-boom), dus een losse toevoeging blijft daar stabiel staan, in
 * tegenstelling tot de blok-wrapper zelf.
 */
(function ($) {
    if (typeof wp === 'undefined' || !wp.blocks) return;

    function ensureBlockLabels($scope) {
        $scope.find('[data-type^="mk/"]').each(function () {
            var $block = $(this);
            var $card  = $block.find('.acf-block-fields, > .acf-fields').first();
            if (!$card.length || $card.children('.mk-block-label').length) return;

            var blockType = wp.blocks.getBlockType($block.attr('data-type'));
            if (!blockType) return;

            var $label = $('<div class="mk-block-label"><strong></strong><span></span></div>');
            $label.find('strong').text(blockType.title || '');
            $label.find('span').text(blockType.description || '');
            if (!blockType.description) $label.find('span').remove();

            $card.prepend($label);
        });
    }

    // Nieuwe blokken/rijen laden hun velden async in — hergebruikt ACF's 'append'-actie
    // en een MutationObserver als vangnet voor het moment dat een heel nieuw blok wordt
    // ingevoegd (dat verloopt niet altijd via dezelfde actie als repeater-rijen).
    if (typeof acf !== 'undefined') {
        acf.addAction('append', function () {
            setTimeout(function () { ensureBlockLabels($(document)); }, 0);
        });
    }

    if (typeof MutationObserver !== 'undefined') {
        var mkLabelTimer = null;
        var observer = new MutationObserver(function () {
            clearTimeout(mkLabelTimer);
            mkLabelTimer = setTimeout(function () { ensureBlockLabels($(document)); }, 150);
        });
        $(function () {
            var target = document.querySelector('.block-editor-writing-flow') || document.body;
            observer.observe(target, { childList: true, subtree: true });
        });
    }

    $(function () { ensureBlockLabels($(document)); });
})(jQuery);

/**
 * Blokvenster (ACF Blocks V3, .acf-block-form-modal): eigen kop met icoon en link naar de uitleg,
 * onderbalk met Klaar, huisstijlkleuren in de kleurkiezer, "Bewerken" als tekst in de werkbalk,
 * en per blok wat extra hulp (Fotoslider: meerdere afbeeldingen tegelijk; Berichtenoverzicht:
 * "Alles tonen" en grijze velden bij een handmatige selectie).
 *
 * De kop van WordPress (React) wordt niet aangepast maar verborgen; onze kop staat ernaast.
 * Zo raakt React nooit elementen kwijt die het zelf beheert.
 */
(function ($) {
    if (typeof acf === 'undefined' || !window.wp || !wp.data || !wp.blocks) return;
    var cfg = window.mkEditor || {};
    var NO_FIELDS = ['mk/breadcrumbs']; // blokken zonder instellingen: geen knop Bewerken

    // ── Teksteditor in een blok: ruimte rondom en een gewone lettergrootte ──
    acf.add_filter('wysiwyg_tinymce_settings', function (init) {
        init.content_style = (init.content_style || '') +
            'body{margin:0;padding:10px 14px;font:14px/1.6 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:#0F1B2D}' +
            'p{margin:0 0 8px}p:last-child{margin-bottom:0}a{color:#0B6FB8}';
        return init;
    });

    // ── Huisstijlkleuren bovenaan elke kleurkiezer ───────────
    acf.add_filter('color_picker_args', function (args) {
        if (cfg.palette && cfg.palette.length) args.palettes = cfg.palette;
        return args;
    });

    // ── "Bewerken" als tekst bij het potlood in de werkbalk ──
    var editLabel = (acf.__ && acf.__('Edit Block')) || 'Edit Block';
    var css = document.createElement('style');
    var sel = 'button[aria-label="' + editLabel.replace(/"/g, '\\"') + '"]';
    css.textContent =
        '.block-editor-block-toolbar ' + sel + '{width:auto;padding:0 12px 0 8px;gap:6px}' +
        '.block-editor-block-toolbar ' + sel + '::after{content:"Bewerken";font-size:13px;font-weight:600}' +
        'body.mk-sel-geen-velden .block-editor-block-toolbar ' + sel + ',body.mk-sel-geen-velden .acf-blocks-open-expanded-editor-btn{display:none}';
    document.head.appendChild(css);

    var lastName = null;
    wp.data.subscribe(function () {
        var b = wp.data.select('core/block-editor').getSelectedBlock();
        var name = b ? b.name : null;
        if (name === lastName) return;
        lastName = name;
        document.body.classList.toggle('mk-sel-geen-velden', NO_FIELDS.indexOf(name) > -1);
    });

    // ── Kop en onderbalk van het venster ─────────────────────
    function clickDone(done) {
        var tries = 0;
        (function go() {
            if (!done.isConnected) return;
            if (!done.disabled) done.click();
            else if (tries++ < 30) setTimeout(go, 100); // nog bezig met laden
        })();
    }

    function decorateModal(modal) {
        if (modal.dataset.mkReady) return;
        var content = modal.querySelector('.components-modal__content');
        var header = content && content.querySelector('.components-modal__header');
        var done = header && header.querySelector('.components-button.is-primary');
        if (!done) return;
        modal.dataset.mkReady = '1';
        modal.classList.add('mk-bv');

        var block = wp.data.select('core/block-editor').getSelectedBlock();
        var type = block && wp.blocks.getBlockType(block.name);
        var title = type ? type.title : (header.querySelector('h1') || {}).textContent || '';
        var src = type && type.icon && type.icon.src;

        var head = document.createElement('div');
        head.className = 'mk-bv__head';
        head.innerHTML =
            '<span class="mk-bv__icon" aria-hidden="true"><span class="dashicons dashicons-' + (typeof src === 'string' ? src : 'block-default') + '"></span></span>' +
            '<div class="mk-bv__titles"><div class="mk-bv__title"></div><div class="mk-bv__sub"></div></div>' +
            '<button type="button" class="mk-bv__close" aria-label="Sluiten"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
        head.querySelector('.mk-bv__title').textContent = title;

        var sub = head.querySelector('.mk-bv__sub');
        sub.textContent = type && type.category === 'mediakanjers' ? 'Mediakanjers-blok' : (type && type.description) || '';
        var docs = block && cfg.docs && cfg.docs[block.name];
        if (docs && window.mkHelpOpen) {
            sub.appendChild(document.createTextNode(' · '));
            var link = document.createElement('button');
            link.type = 'button';
            link.className = 'mk-bv__docs';
            link.textContent = 'uitleg';
            link.addEventListener('click', function () { window.mkHelpOpen(docs, title, link); });
            sub.appendChild(link);
        }
        head.querySelector('.mk-bv__close').addEventListener('click', function () { clickDone(done); });
        content.insertBefore(head, content.firstChild);

        var foot = document.createElement('div');
        foot.className = 'mk-bv__foot';
        foot.innerHTML = '<span>Je wijzigingen zie je meteen in de pagina. Vergeet daarna niet de pagina op te slaan.</span><button type="button" class="mk-bv__done">Klaar</button>';
        foot.querySelector('.mk-bv__done').addEventListener('click', function () { clickDone(done); });
        content.appendChild(foot);
    }

    // ── Fotoslider: meerdere afbeeldingen tegelijk toevoegen ──
    // ACF kiest in een repeater al meerdere afbeeldingen tegelijk (extra rijen komen er vanzelf bij);
    // deze knop maakt de eerste rij aan en opent meteen het mediavenster.
    function decorateSlider($field) {
        if ($field.data('mkSlider')) return updateSlider($field);
        var repeater = acf.getField($field);
        var $actions = $field.find('> .acf-input > .acf-repeater > .acf-actions').first();
        if (!repeater || !$actions.length) return;
        $field.data('mkSlider', true).addClass('mk-slides');

        var $btn = $('<button type="button" class="mk-add-images"><span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>Afbeeldingen toevoegen</button>');
        $actions.prepend($btn);
        $field.find('> .acf-input > .acf-repeater').first().before('<p class="mk-slides__empty">Nog geen afbeeldingen. Klik op <strong>Afbeeldingen toevoegen</strong> en kies er meteen meerdere (Ctrl of Shift ingedrukt houden).</p>');

        $btn.on('click', function () {
            var before = $field.find('.acf-row:not(.acf-clone)').length;
            repeater.add();
            var $row = $field.find('.acf-row:not(.acf-clone)').eq(before);
            var img = $row.length && acf.getField($row.find('.acf-field-image').first());
            if (!img) return;
            img.selectAttachment();
            removeIfEmpty(repeater, $row, img);
        });
        updateSlider($field);
    }

    // Niets gekozen in het mediavenster: de lege rij die we net maakten weer weghalen
    function removeIfEmpty(repeater, $row, img) {
        var seen = false, tries = 0;
        (function check() {
            var open = $('.media-modal:visible').length > 0;
            if (open) seen = true;
            if (seen && !open) {
                if (!img.val() && $row.closest('body').length) repeater.remove($row);
                return;
            }
            if (tries++ < 6000) setTimeout(check, 200); // maximaal 20 minuten
        })();
    }

    function updateSlider($field) {
        $field.toggleClass('is-empty', !$field.find('.acf-row:not(.acf-clone)').length);
    }

    // ── Berichtenoverzicht: "Alles tonen" en handmatige selectie ──
    function decorateLoop($fields) {
        var $num = $fields.find('.acf-field[data-name="number_of_posts"]').first();
        if (!$num.length || $num.data('mkLoop')) return;
        $num.data('mkLoop', true);

        var $input = $num.find('input[type="number"]').first();
        var $all = $('<label class="mk-all"><input type="checkbox"> Alles tonen</label>');
        $num.children('.acf-input').append($all); // niet naast het getal: dat wordt verborgen bij "Alles tonen"
        var $cb = $all.find('input');
        function syncAll() {
            var all = String($input.val()) === '-1';
            $cb.prop('checked', all);
            $num.toggleClass('mk-all-on', all);
        }
        $cb.on('change', function () {
            if (this.checked) {
                if (String($input.val()) !== '-1') $input.data('mkPrev', $input.val());
                $input.val(-1);
            } else {
                var prev = parseInt($input.data('mkPrev'), 10);
                $input.val(prev > 0 ? prev : 6);
            }
            $input.trigger('input').trigger('change');
            syncAll();
        });
        $input.on('input change', syncAll);
        syncAll();

        var $select = $fields.find('.acf-field[data-name="bericht_object"] select').first();
        var $targets = $fields.find('.acf-field[data-name="query_loop"], .acf-field[data-name="number_of_posts"]');
        $targets.each(function () {
            $(this).append('<p class="mk-overruled-note">Wordt niet gebruikt: je hebt hieronder zelf berichten gekozen.</p>');
        });
        function syncOver() {
            var has = ($select.val() || []).length > 0;
            $targets.toggleClass('mk-overruled', has);
        }
        $select.on('change', syncOver);
        syncOver();
    }

    // ── Alles toepassen zodra het venster (of een nieuw veld) verschijnt ──
    function scan() {
        document.querySelectorAll('.acf-block-form-modal').forEach(function (modal) {
            decorateModal(modal);
            var $m = $(modal);
            $m.find('.acf-field[data-name="slides"]').each(function () { decorateSlider($(this)); });
            $m.find('.acf-field[data-name="number_of_posts"]').each(function () { decorateLoop($(this).closest('.acf-fields')); });
        });
    }

    var queued = false;
    new MutationObserver(function () {
        if (queued) return;
        queued = true;
        requestAnimationFrame(function () { queued = false; scan(); });
    }).observe(document.body, { childList: true, subtree: true });
    scan();
})(jQuery);

/**
 * Voorkomt dat een link in de gerenderde preview van een mk/-blok de
 * block-editor-iframe zelf laat navigeren. De canvas is sinds WP 6.3 een
 * <iframe name="editor-canvas">; klik je daarin op een <a href> uit een
 * server-rendered blok (bijv. een kaart of knop met een link), dan navigeert
 * de iframe écht weg naar die URL. Dat maakt de pagina die je aan het
 * bewerken bent onbereikbaar én breekt Gutenberg's/ACF's eigen opruim- en
 * inline-editing-logica (SecurityError door cross-origin na de navigatie),
 * wat de hele editor kan laten crashen — inclusief het inserter-paneel.
 *
 * Eerdere aanpak was een click-listener met preventDefault(), maar die vangt
 * alleen een náve muisklik af — ACF Pro's eigen "Auto Inline Editing" (in
 * acf-pro-blocks.min.js) interacteert los van een click-event ook met de
 * gerenderde preview en kan zo alsnog navigatie triggeren. In plaats van
 * klikken te onderscheppen wordt het href-attribuut zelf verwijderd bij elke
 * link binnen een blok-preview in de canvas: zonder href is er domweg niets
 * om naartoe te navigeren, via welk mechanisme dan ook. De originele URL
 * blijft bewaard in data-mk-original-href, mocht die ooit nog nodig zijn.
 *
 * Scoping is bewust NIET op `[data-type^="mk/"]` (bleek te smal: een child
 * thema kan blokken ook via het oudere acf_register_block_type()-PHP-array
 * registreren, zonder block.json — ACF namespaced die dan automatisch als
 * "acf/<naam>", niet als "mk/<naam>", waardoor zo'n scoping ze mist). In
 * plaats daarvan op `.acf-block-preview`: de class die ACF zelf om élke
 * server-side gerenderde blok-preview zet, ongeacht naamgeving, registratie-
 * methode (block.json of PHP-array) of apiVersion — dus ook toekomstige
 * blokken en blokken uit child-thema's, zonder dat een blok hier zelf iets
 * voor hoeft te regelen in zijn render.php.
 *
 * Een <form> in een blok-preview (bijv. een zoekformulier) heeft hetzelfde
 * navigatie-risico bij een submit (Enter in een veld, of een submit-knop) —
 * href weghalen bestaat daar niet voor (een formulier zonder action verzendt
 * gewoon naar de huidige pagina). Een submit-listener is hier wél
 * betrouwbaar: verzenden kan alleen via het submit-event, er is geen
 * "omweg" zoals bij klikken op een link.
 */
(function () {
    function neutralizeLinks(doc) {
        doc.querySelectorAll('.acf-block-preview a[href]').forEach(function (link) {
            link.dataset.mkOriginalHref = link.getAttribute('href');
            link.removeAttribute('href');
        });
    }

    function guardIframe(iframe) {
        if (!iframe || iframe.dataset.mkLinkGuard) return;
        var doc = iframe.contentDocument;
        if (!doc || !doc.body) return;
        iframe.dataset.mkLinkGuard = '1';

        neutralizeLinks(doc);

        doc.addEventListener('submit', function (e) {
            if (e.target.closest && e.target.closest('.acf-block-preview')) {
                e.preventDefault();
            }
        }, true);

        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function () { neutralizeLinks(doc); });
            observer.observe(doc.body, { childList: true, subtree: true });
        }
    }

    function findAndGuard() {
        document.querySelectorAll('iframe[name="editor-canvas"]').forEach(function (iframe) {
            guardIframe(iframe);
            iframe.addEventListener('load', function () {
                iframe.dataset.mkLinkGuard = '';
                guardIframe(iframe);
            });
        });
    }

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(findAndGuard);
        observer.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', findAndGuard);
    findAndGuard();
})();

/**
 * ACF's eigen link-veld (bijv. de knop van het Kolommen- of Hero-blok) toont
 * de gekozen link als klikbare <a class="link-node"> in het uitklap-paneel
 * zelf — dat paneel zit in het hoofd-adminvenster, niet in de canvas-iframe
 * (zie class-acf-field-link.php in ACF Pro). Zonder dat de klant expliciet
 * "open in nieuw tabblad" kiest, is target="" (dus hetzelfde als _self) — een
 * klik daarop navigeert dan de hele wp-admin-pagina weg, met verlies van
 * onopgeslagen wijzigingen. Forceert target="_blank" zodat de link nog
 * gewoon te bekijken is, zonder de editor-sessie te verliezen.
 */
(function () {
    function fixLinkFieldTargets(scope) {
        scope.querySelectorAll('.acf-link .link-node').forEach(function (link) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
        });
    }

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () { fixLinkFieldTargets(document); });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', function () { fixLinkFieldTargets(document); });
    fixLinkFieldTargets(document);
})();
