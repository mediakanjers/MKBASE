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
        { type: 'knop',        label: 'Knop / CTA',          fields: ['knop', 'knop_stijl'] },
        { type: 'icoon_tekst', label: 'Icoon + tekst',       fields: ['icoon', 'icoon_tekst'] },
        { type: 'cijfer',      label: 'Cijfer / statistiek', fields: ['cijfer_waarde', 'cijfer_label'] },
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
            wrapGroup($row, OPMAAK_FIELDS, 'Kolom-opmaak', 'mk-type-group--opmaak');
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

    function updateRowLabels($repeaterField, ratios) {
        var $rows = $repeaterField.find('> .acf-input .acf-row:not(.acf-clone)');

        $rows.each(function (i) {
            var $row    = $(this);
            var pct     = ratios[i];
            var isExtra = pct === undefined;

            $row.toggleClass('mk-row--excess', isExtra);

            var $handle = $row.find('> .acf-row-handle.order').first();
            if (!$handle.length) return;

            $handle.find('.mk-kolom-label').remove();

            // Geen "Kolom N" meer ervoor — ACF toont het rijnummer al op de rij
            // zelf; dat + deze tekst samen in de smalle rij-handle brak vervelend
            // af en zag er daardoor uit als een verwarrende herhaling van cijfers.
            var text = isExtra ? 'Niet zichtbaar' : pct + '% breedte';
            var cls = 'mk-kolom-label' + (isExtra ? ' mk-kolom-label--warn' : '');
            $handle.append('<span class="' + cls + '">' + text + '</span>');
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
        $addBtn.toggleClass('mk-add-row--disabled', maxReached);
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
            ensureTypeGroupLabels($repeaterField);
            ensureBlockOpmaakGroups($fieldsWrap);
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

    // Verwijderen/dupliceren van een rij triggert geen ACF-actie — reageren op de knoppen zelf.
    $(document).on('click', '.acf-block-form-modal [data-event="remove-row"], .acf-block-form-modal [data-event="duplicate-row"]', function () {
        setTimeout(function () { refresh($(document)); }, 50);
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
 * Voorkomt dat een link in de gerenderde preview van een mk/-blok de
 * block-editor-iframe zelf laat navigeren. De canvas is sinds WP 6.3 een
 * <iframe name="editor-canvas">; klik je daarin op een <a href> uit een
 * server-rendered blok (bijv. een kaart of knop met een link), dan navigeert
 * de iframe écht weg naar die URL. Dat maakt de pagina die je aan het
 * bewerken bent onbereikbaar én breekt Gutenberg's eigen opruim-logica
 * (SecurityError door cross-origin na de navigatie), wat de hele editor kan
 * laten crashen — inclusief het blokken-inserter-paneel.
 *
 * Dit is bewust blok-agnostisch (elk `[data-type^="mk/"]`, ook toekomstige
 * blokken) zodat een nieuw blok met een link hier niet apart tegen hoeft te
 * beschermen in zijn render.php — deze guard vangt het altijd af, ongeacht
 * of het blok zelf al voorzichtig is met $is_preview.
 */
(function () {
    function guardIframe(iframe) {
        if (!iframe || iframe.dataset.mkLinkGuard) return;
        var doc = iframe.contentDocument;
        if (!doc) return;
        iframe.dataset.mkLinkGuard = '1';

        doc.addEventListener('click', function (e) {
            var link = e.target.closest && e.target.closest('a[href]');
            if (link && link.closest('[data-type^="mk/"]')) {
                e.preventDefault();
            }
        }, true);
    }

    function findAndGuard() {
        document.querySelectorAll('iframe[name="editor-canvas"]').forEach(function (iframe) {
            guardIframe(iframe);
            iframe.addEventListener('load', function () { guardIframe(iframe); });
        });
    }

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(findAndGuard);
        observer.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', findAndGuard);
    findAndGuard();
})();
