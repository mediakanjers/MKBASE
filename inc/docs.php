<?php
    // Het dashboard-welkomstblok staat sinds 1.6.0 in inc/admin/dashboard.php

    add_action('admin_menu', function() {
        add_submenu_page(null, 'Mobiel testen', 'Mobiel testen', 'manage_options', 'mkbase-mobile-guide', function() {
            if (!current_user_can('manage_options')) {
                wp_die('Geen toegang.');
            }
            $file = get_template_directory() . '/mobile-testing-guide.html';
            if (!file_exists($file)) {
                wp_die('Bestand niet gevonden.');
            }
            $html = file_get_contents($file);
            preg_match('/<style[^>]*>(.*?)<\/style>/s', $html, $style);
            preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $body);
            echo '<style>' . ($style[1] ?? '') . '</style>';
            echo $body[1] ?? '';
        });
    });

    add_action('admin_enqueue_scripts', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-docs') return;
        // MK-stijl en -balk komen uit inc/admin/ui.php (mk-admin); dit is alleen het documentatie-deel
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-admin-docs', $theme_uri . '/assets/css/admin-docs.css', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/css/admin-docs.css'));
        wp_enqueue_script('mk-admin-docs', $theme_uri . '/assets/js/admin-docs.js', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/js/admin-docs.js'), true);
        // De voorbeelden bij de developer-uitleg gebruiken de echte CSS van de website (alleen de core;
        // de look van het child theme laadt hier niet)
        wp_enqueue_style('mk-docs-meldingen', $theme_uri . '/assets/css/components/notifications.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/notifications.css'));
        if (function_exists('mkbase_ot_actief') && mkbase_ot_actief()) {
            wp_enqueue_style('mk-openingstijden');
            wp_enqueue_script('mk-openingstijden');
        }
    });

    add_action('admin_footer', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-docs') return;
        echo '<div id="mkbase-docs-content" style="display:none;">';
        echo mkbase_get_docs_html();
        echo '</div>';
    });

    function mkbase_docs_settings_link($url, $label) {
        return '<a href="' . esc_url($url) . '" class="mkbase-docs-settings-link">' . esc_html($label) . ' &rarr;</a>';
    }

    // Een kaart met een titel en vragen die je los openklapt ($icon wordt sinds 1.6.0 niet meer getoond;
    // de kaarten zijn genummerd). Elke vraag heeft een eigen id, zodat er naartoe gelinkt kan worden.
    // $dev: een kaart voor developers (label "Dev" bij de titel), los van de uitleg voor klanten.
    function mkbase_docs_section($id, $title, $icon, $items, $dev = false) {
        $html  = '<section class="mkbase-docs-section' . ($dev ? ' mkbase-docs-section--dev' : '') . '" id="docs-' . esc_attr($id) . '" data-section="' . esc_attr($id) . '">';
        $html .= '<h2 class="mkbase-docs-section__title">' . esc_html($title) . ($dev ? ' <span class="mk-dev-tag">Dev</span>' : '') . '</h2>';
        foreach ($items as $i => $item) {
            $qid   = 'docs-' . $id . '-' . $i;
            $html .= '<div class="mkbase-docs-item">';
            $html .= '<button class="mkbase-docs-q" type="button" aria-expanded="false" aria-controls="' . esc_attr($qid) . '">' . $item['label'] . '</button>';
            $html .= '<div class="mkbase-docs-a" id="' . esc_attr($qid) . '" hidden>' . $item['desc'] . '</div>';
            $html .= '</div>';
        }
        $html .= '</section>';
        return $html;
    }

    // ── Bouwstenen voor de developer-uitleg (sinds 1.7.0) ──

    // Codeblok; de kopieerknop zet admin-docs.js erbij
    function mkbase_docs_code($code) {
        return '<pre><code>' . esc_html(trim($code)) . '</code></pre>';
    }

    // Tabel met classes, variabelen of opties. $rijen: [[naam, uitleg], ...]; de eerste kolom wordt code.
    // Met een derde waarde (een CSS-selector) licht dat onderdeel op in het voorbeeld erboven
    // (mkbase_docs_voorbeeld) als je de regel aanwijst.
    function mkbase_docs_tabel($rijen, $koppen = ['Class', 'Wat het is']) {
        $html = '<div class="mk-docs__tabel-wrap"><table class="mk-docs__tabel"><thead><tr><th scope="col">' . esc_html($koppen[0]) . '</th><th scope="col">' . esc_html($koppen[1]) . '</th></tr></thead><tbody>';
        foreach ($rijen as $r) {
            $sel = $r[2] ?? '';
            $html .= '<tr' . ($sel !== '' ? ' class="is-aanwijsbaar" tabindex="0" data-mk-doc-sel="' . esc_attr($sel) . '"' : '') . '>'
                . '<td><code>' . esc_html($r[0]) . '</code></td><td>' . $r[1] . '</td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

    // Een echt voorbeeld van een onderdeel (met de echte CSS); regels in de tabel eronder lichten het aan
    function mkbase_docs_voorbeeld($html, $uitleg = 'Wijs een regel in de tabel aan: dat onderdeel licht hier op.') {
        if ($html === '') return '';
        return '<div class="mk-docs__voorbeeld"><p class="mk-docs__voorbeeld-label">Voorbeeld <span>' . esc_html($uitleg) . '</span></p>'
            . '<div class="mk-docs__voorbeeld-inhoud">' . $html . '</div></div>';
    }

    // Stijlgids: elk voorbeeld gebruikt de echte klassen uit mk-admin.css, dus wat je ziet is wat je krijgt
    function mkbase_docs_stijlgids() {
        // Elk woord heel houden: de browser breekt anders midden in een klasse af (na "--")
        $code = function ($text) {
            return '<code class="mk-sg__code"><span>' . implode('</span> <span>', array_map('esc_html', explode(' ', $text))) . '</span></code>';
        };

        $kleuren = '';
        foreach (['--mk-primary', '--mk-logo', '--mk-tint', '--mk-text', '--mk-bg', '--mk-card', '--mk-border', '--mk-muted', '--mk-ok-text', '--mk-warn-text', '--mk-err', '--mk-focus'] as $var) {
            $kleuren .= '<div class="mk-sg__swatch"><span style="background: var(' . $var . ')"></span><code>' . $var . '</code><small data-mk-var="' . $var . '"></small></div>';
        }

        $html  = '<div class="mk-sg">';

        $html .= mkbase_admin_section('Kleuren', '<div class="mk-sg__swatches">' . $kleuren . '</div>'
            . '<p class="mk-hint">Gebruik altijd de variabele, nooit de kleurcode: in de donkere modus krijgen ze vanzelf een andere waarde.</p>');

        $html .= mkbase_admin_section('Tekst',
            '<p class="mk-h1">Paginatitel 30</p>'
            . '<p class="mk-h2">Sectiekop 17</p>'
            . '<p class="mk-text">Lopende tekst 14 · Inter</p>'
            . '<p class="mk-hint">Hulptekst 12,5 · gedempt</p>'
            . $code('.mk-h1 .mk-h2 .mk-text .mk-hint'));

        $html .= mkbase_admin_section('Knoppen',
            '<div class="mk-sg__row">'
            . '<button type="button" class="mk-btn">Primair</button>'
            . '<button type="button" class="mk-btn mk-btn--secondary">Secundair</button>'
            . '<button type="button" class="mk-btn mk-btn--link">Link-knop</button>'
            . '<button type="button" class="mk-btn mk-btn--danger">Verwijderen</button>'
            . '</div>'
            . $code('.mk-btn .mk-btn--secondary .mk-btn--link .mk-btn--danger'));

        $html .= mkbase_admin_section('Statuslabels',
            '<div class="mk-sg__row">'
            . '<span class="mk-pill mk-pill--live">Live</span>'
            . '<span class="mk-pill mk-pill--gepland">Gepland</span>'
            . '<span class="mk-pill mk-pill--verlopen">Verlopen</span>'
            . '<span class="mk-pill mk-pill--uit">Uit</span>'
            . '</div>'
            . $code('.mk-pill .mk-pill--live .mk-pill--gepland .mk-pill--verlopen .mk-pill--uit'));

        $html .= mkbase_admin_section('Velden',
            '<label class="mk-field"><span class="mk-field__label">Normaal <span class="mk-help-btn" aria-hidden="true">?</span></span><input type="text" value="Bakkerij Jansen"></label>'
            . '<label class="mk-field mk-field--error"><span class="mk-field__label">Met fout</span><input type="text" value="info@bakkerij" aria-invalid="true" aria-describedby="mk-sg-fout"><span class="mk-field__error" id="mk-sg-fout">Vul een volledig e-mailadres in</span></label>'
            . $code('.mk-field .mk-field__label .mk-field--error .mk-field__error .mk-help-btn'));

        $html .= mkbase_admin_section('Schakelaar, bevestiging en sectie',
            '<div class="mk-sg__row">'
            . '<button type="button" class="mk-switch" role="switch" aria-checked="true" aria-labelledby="mk-sg-aan"></button><span id="mk-sg-aan" class="mk-sg__label">Aan</span>'
            . '<button type="button" class="mk-switch" role="switch" aria-checked="false" aria-labelledby="mk-sg-uit"></button><span id="mk-sg-uit" class="mk-sg__label">Uit</span>'
            . '</div>'
            . '<div class="mk-toast mk-toast--inline"><span class="mk-toast__icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span><div><div class="mk-toast__title">Wijzigingen opgeslagen</div></div><button type="button" data-mk-sg-toast>Probeer het</button></div>'
            . $code('.mk-switch · mkToast({ title, sub, action }) · mkbase_admin_section($titel, $html)'));

        $focus = [
            ['<button type="button" class="mk-btn mk-sg__ring">Opslaan</button>', 'Knop'],
            ['<input type="text" class="mk-sg__ring mk-sg__ring--veld" value="Bakkerij Jansen">', 'Veld (ring in de rand)'],
            ['<span class="mk-help-btn mk-sg__ring">?</span>', 'Uitlegknop'],
            ['<span class="mk-switch mk-sg__ring" aria-checked="true"></span>', 'Schakelaar'],
            ['<span class="mk-sg__tab mk-sg__ring">Meldingen</span>', 'Tabblad'],
            ['<span class="mk-sg__link mk-sg__ring">Meer uitleg →</span>', 'Link'],
        ];
        $voorbeelden = '';
        foreach ($focus as [$el, $label]) {
            $voorbeelden .= '<div class="mk-sg__focus">' . $el . '<span class="mk-hint">' . esc_html($label) . '</span></div>';
        }
        $regels = '';
        foreach ([
            'Volgorde van Tab = volgorde op het scherm',
            'Pop-ups en zijpanelen: focus gaat erin, Esc sluit, focus terug naar de knop',
            'Ongedaan maken in de bevestiging is ook met Tab te bereiken',
            'Fouten: focus springt naar de foutmelding bovenaan',
        ] as $regel) {
            $regels .= '<li>' . esc_html($regel) . '</li>';
        }
        $html .= mkbase_admin_section('Focus met het toetsenbord',
            '<p class="mk-text mk-sg__lead">Ga je met Tab door de pagina, dan zie je altijd waar je bent: een ring van 2 px in <code>--mk-focus</code> (blauw, in donker lichtblauw), met 2 px ruimte ertussen. Alleen bij het toetsenbord (<code>:focus-visible</code>), niet bij klikken met de muis. Nooit <code>outline: none</code> zonder vervanging. Dit regelt <code>mk-admin.css</code> al voor alles op de pagina.</p>'
            . '<div class="mk-sg__row mk-sg__row--focus">' . $voorbeelden . '</div>'
            . '<ul class="mk-sg__rules">' . $regels . '</ul>'
            . $code(':focus-visible { outline: 2px solid var(--mk-focus); outline-offset: 2px; }'),
            'mk-sg__wide');

        $html .= '</div>';
        return $html;
    }

    function mkbase_get_docs_html() {
        $html = '';

        // Groepen voor de zijbalk
        $groups = [
            'Beginnen' => [
                'beheer' => 'Werken in het beheer',
            ],
            'Thema' => [
                'instellingen' => 'Website instellingen',
                'menus'        => "Menu's",
                'geavanceerd'  => 'Geavanceerd',
                'meldingen'    => 'Meldingen',
                'openingstijden' => 'Openingstijden',
                '404'          => '404',
            ],
            'Inhoud' => [
                'blokken'      => 'Blokken',
                'afbeeldingen' => 'Afbeeldingen',
            ],
        ];

        if (!function_exists('mkbase_ot_actief') || !mkbase_ot_actief()) unset($groups['Thema']['openingstijden']);

        $plugins = [];
        if (class_exists('WooCommerce'))  $plugins['woocommerce']   = 'WooCommerce';
        if (class_exists('GFForms'))      $plugins['gravity-forms'] = 'Gravity Forms';
        if (defined('WP_ROCKET_VERSION')) $plugins['wp-rocket']     = 'WP Rocket';
        if (defined('WPSEO_VERSION'))     $plugins['yoast-seo']     = 'Yoast SEO';
        if (!empty($plugins))             $groups['Plugins']        = $plugins;

        if (current_user_can('manage_options')) {
            $groups['Developer'] = [
                'dev-blokken'      => 'Blokken bouwen',
                'stijlgids'        => 'Stijlgids',
                'dev-afbeeldingen' => 'Afbeeldingen',
                'lightbox'      => 'Lightbox',
                'mobiel-testen' => 'Mobiel testen',
            ];
        }

        // Zijbalk
        $html .= '<div id="mkbase-docs-layout">';
        $html .= '<nav id="mkbase-docs-sidebar" aria-label="Onderwerpen">';
        $html .= '<label class="mk-search"><span class="screen-reader-text">Zoeken in de documentatie</span><input type="search" id="mkbase-docs-search" placeholder="Zoeken" autocomplete="off"></label>';
        $first = true;
        // Per groep een kop die de onderwerpen in- en uitklapt (admin-docs.js); de onderwerpen staan
        // ingesprongen eronder, zodat je ziet wat bij wat hoort
        foreach ($groups as $group_label => $items) {
            $slug  = sanitize_title($group_label);
            $extra = $group_label === 'Developer' ? ' mkbase-docs-group--developer' : '';
            $html .= '<div class="mkbase-docs-group' . $extra . '" data-group="' . esc_attr($slug) . '">';
            $html .= '<button type="button" class="mkbase-docs-group__label" aria-expanded="true" aria-controls="mkbase-docs-group-' . esc_attr($slug) . '">'
                . '<span class="mkbase-docs-group__naam">' . esc_html($group_label) . ($group_label === 'Developer' ? ' <span class="mk-dev-tag">Dev</span>' : '') . '</span>'
                . '<span class="mkbase-docs-group__aantal">' . count($items) . '</span></button>';
            $html .= '<div class="mkbase-docs-group__items" id="mkbase-docs-group-' . esc_attr($slug) . '">';
            foreach ($items as $key => $label) {
                $active = $first ? ' is-active' : '';
                $html  .= '<button type="button" class="mkbase-docs-nav-item' . $active . '" data-panel="' . esc_attr($key) . '"' . ($first ? ' aria-current="true"' : '') . '>' . esc_html($label) . '</button>';
                $first  = false;
            }
            $html .= '</div></div>';
        }
        $html .= '</nav>';
        $html .= '<div id="mkbase-docs-panels">';

        /* ── WERKEN IN HET BEHEER ─────────────────────────── */
        $html .= '<div class="mkbase-docs-panel is-active" data-panel="beheer">';
        $html .= '<p class="mkbase-docs-intro">Alle pagina\'s onder <strong>Mediakanjers</strong> werken op dezelfde manier. Hier lees je hoe je opslaat, waar je uitleg vindt en hoe je de meldingen van WordPress zelf wegklikt.</p>';

        $html .= mkbase_docs_section('beheer-balk', 'De balk bovenaan', 'dashicons-menu', [
            ['label' => 'Wat staat erin?', 'desc' => 'Bovenaan elke Mediakanjers-pagina staat een balk met waar je bent, tabbladen naar alle Mediakanjers-pagina\'s (links de instellingen van de website, rechts apart Changelog, Documentatie en Diagnose), een knop met de meldingen van WordPress, een knop voor de donkere modus (het maantje) en de knop <strong>Opslaan</strong>. De balk blijft in beeld als je naar beneden scrolt.'],
            ['label' => 'Donkere modus', 'desc' => 'Met het maantje zet je de Mediakanjers-pagina\'s donker of licht. Je keuze wordt per gebruiker onthouden, ook op een andere computer of in een andere browser.'],
        ]);

        $html .= mkbase_docs_section('beheer-opslaan', 'Opslaan en wijzigingen', 'dashicons-saved', [
            ['label' => 'Opslaan', 'desc' => 'Klik op <strong>Opslaan</strong> in de balk, of druk op <strong>Ctrl+S</strong> (Mac: <strong>Cmd+S</strong>). Daarna zie je rechtsonder een bevestiging. De cache van de website wordt meteen geleegd, zodat bezoekers de wijziging direct zien; met <strong>Bekijk website</strong> controleer je het.'],
            ['label' => 'Niet opgeslagen', 'desc' => 'Zodra je iets wijzigt, staat er <strong>Niet-opgeslagen wijzigingen</strong> in de balk. Zet je de wijziging zelf terug zoals het was, dan verdwijnt dat weer.'],
            ['label' => 'Annuleren', 'desc' => 'Verschijnt naast Opslaan zodra er iets gewijzigd is. Hiermee gooi je alle wijzigingen weg en zie je weer de laatst opgeslagen versie.'],
            ['label' => 'Pagina verlaten', 'desc' => 'Ga je naar een andere pagina terwijl er nog iets niet is opgeslagen, dan vraagt het beheer wat je wilt: <strong>Opslaan en verder</strong>, <strong>Niet opslaan</strong> of <strong>Blijven</strong>. Zo raak je niets per ongeluk kwijt.'],
        ]);

        $html .= mkbase_docs_section('beheer-uitleg', 'Uitleg bij de velden', 'dashicons-editor-help', [
            ['label' => 'Het vraagteken', 'desc' => 'Naast elk veld staat een <strong>?</strong>. Klik erop voor een korte uitleg. Ook bij de velden van de Mediakanjers-blokken in de editor.'],
            ['label' => 'Meer uitleg', 'desc' => 'Onder de korte uitleg staat <strong>Meer uitleg</strong>. Dat opent rechts een paneel met het stuk uit deze documentatie dat over dat veld gaat, zonder dat je de pagina verlaat.'],
            ['label' => 'Live voorbeeld', 'desc' => 'Bij <strong>Website instellingen</strong> en <strong>404</strong> zie je naast de velden een voorbeeld dat meeverandert terwijl je typt. Met <strong>Live voorbeeld</strong> in de balk zet je het aan of uit. Bij <strong>Meldingen</strong> heeft elke melding een eigen voorbeeld.'],
        ]);

        $html .= mkbase_docs_section('beheer-wp-meldingen', 'Meldingen van WordPress en plugins', 'dashicons-bell', [
            ['label' => 'Waar staan ze?', 'desc' => 'Meldingen van WordPress en plugins (bijv. over updates) staan samen achter de knop met het belletje en het aantal. Klik erop om ze te bekijken. Dit geldt op de Mediakanjers-pagina\'s en op de andere pagina\'s van het beheer, zoals het Dashboard en Pagina\'s.'],
            ['label' => 'Verbergen', 'desc' => 'Klik op het kruisje bij een melding om hem te verbergen, of op <strong>Alles verbergen</strong>. Verborgen meldingen blijven per gebruiker weg, ook als een plugin ze op elke pagina opnieuw toont. Verandert de tekst van een melding, dan komt hij terug.'],
            ['label' => 'Weer tonen', 'desc' => 'Onderaan de lijst staat hoeveel meldingen je hebt verborgen. Met <strong>Weer tonen</strong> zie je ze allemaal weer.'],
        ]);

        $html .= mkbase_docs_section('beheer-startlijst', 'Startlijst op het dashboard', 'dashicons-yes-alt', [
            ['label' => 'Wat is het?', 'desc' => 'Op een nieuwe website staat op het dashboard een lijst met zes dingen die ingevuld moeten zijn: logo, favicon, bedrijfsgegevens, sociale media, hoofdmenu en de 404-pagina. Wat klaar is, wordt vanzelf afgevinkt, en als alles klaar is verdwijnt de lijst.'],
            ['label' => 'Verbergen', 'desc' => 'Met <strong>Verbergen</strong> haal je de lijst voor jezelf weg. Terugzetten kan onderaan deze documentatie, zolang hij verborgen is.'],
        ]);

        $html .= '</div>';

        /* ── INSTELLINGEN ─────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="instellingen">';
        $html .= '<p class="mkbase-docs-intro">Stel hier de algemene website-informatie in. Deze gegevens zijn beschikbaar via ACF en kunnen overal in het thema worden gebruikt.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-website-instellingen'), 'Ga naar Website instellingen');

        $html .= mkbase_docs_section('logo', 'Logo & favicon', 'dashicons-format-image', [
            ['label' => 'Logo', 'desc' => 'Upload het logo van de website. Dit logo wordt gebruikt in de header. Aanbevolen formaat: SVG of PNG met transparante achtergrond.'],
            ['label' => 'Favicon', 'desc' => 'Het kleine icoon dat in de browsertab verschijnt. Gebruik bij voorkeur een vierkante afbeelding van minimaal 512×512 pixels (PNG of ICO).'],
        ]);

        $html .= mkbase_docs_section('bedrijf', 'Bedrijfsgegevens', 'dashicons-building', [
            ['label' => 'Bedrijfsnaam', 'desc' => 'De officiële naam van het bedrijf. Staat vetgedrukt boven de contactgegevens in de footer.'],
            ['label' => 'Adres', 'desc' => 'Straat en huisnummer, zoals ze in de footer moeten staan (bijv. <code>Marktstraat 12</code>).'],
            ['label' => 'Postcode en plaats', 'desc' => 'Staat in de footer onder het adres (bijv. <code>7511 AB Enschede</code>).'],
            ['label' => 'Telefoon', 'desc' => 'Hoofdtelefoonnummer. Op een telefoon kunnen bezoekers erop tikken om direct te bellen. Het internationale formaat werkt overal (bijv. <code>+31 53 478 61 47</code>).'],
            ['label' => 'E-mail', 'desc' => 'Algemeen e-mailadres; bezoekers kunnen erop klikken om te mailen. Kies een adres dat iemand leest.'],
            ['label' => 'Mobiel', 'desc' => 'Een tweede nummer, bijvoorbeeld voor spoed. Optioneel: leeg laten verbergt het.'],
            ['label' => 'Lege velden', 'desc' => 'Een veld dat je leeg laat, verschijnt niet op de website.'],
        ]);

        $html .= mkbase_docs_section('socials', 'Sociale media', 'dashicons-share', [
            ['label' => 'Sociale media', 'desc' => 'Voeg sociale mediaprofielen toe via de repeater. Kies per rij het platform (Discord, Facebook, Instagram, LinkedIn, Snapchat, TikTok, WhatsApp, X, YouTube) en vul de volledige URL in. Voeg meerdere rijen toe voor meerdere platformen.'],
        ]);

        $html .= '</div>';

        /* ── MENU'S ───────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="menus">';
        $html .= '<p class="mkbase-docs-intro">Via de menubeheer van WordPress stel je de navigatie van de website in. Hier koppel je pagina\'s aan de menulocaties die in het thema zijn geregistreerd.</p>';
        $html .= mkbase_docs_settings_link(admin_url('nav-menus.php'), "Ga naar Menu's");

        $html .= mkbase_docs_section('menus-aanmaken', 'Menu aanmaken & koppelen', 'dashicons-menu', [
            ['label' => 'Waar vind je menu\'s?', 'desc' => 'Ga naar <strong>Weergave → Menu\'s</strong> in het WordPress admin menu.'],
            ['label' => 'Menu aanmaken', 'desc' => 'Klik op <strong>"Nieuw menu aanmaken"</strong>, geef het een naam en klik op <strong>"Menu aanmaken"</strong>.'],
            ['label' => 'Menulocatie koppelen', 'desc' => 'Scroll naar <em>Menu-instellingen</em> onderaan de pagina en vink de gewenste locatie aan (bijv. Hoofdnavigatie of Footer). Klik daarna op <strong>"Menu opslaan"</strong>.'],
        ]);

        $html .= mkbase_docs_section('menus-items', 'Items beheren', 'dashicons-editor-ul', [
            ['label' => 'Items toevoegen', 'desc' => 'Voeg pagina\'s, berichten, categorieën of eigen links toe via het linker paneel. Vink het gewenste item aan en klik op <strong>"Aan menu toevoegen"</strong>.'],
            ['label' => 'Volgorde aanpassen', 'desc' => 'Sleep menu-items naar de gewenste volgorde.'],
            ['label' => 'Submenu maken', 'desc' => 'Schuif een item iets naar <strong>rechts</strong> onder een ander item om er een submenu van te maken. Het bovenliggende item wordt automatisch de parent.'],
            ['label' => 'Item verwijderen', 'desc' => 'Klik op de pijl rechts van een menu-item om het uit te klappen, en klik op <strong>"Verwijderen"</strong>.'],
            ['label' => 'Opslaan', 'desc' => 'Klik altijd op <strong>"Menu opslaan"</strong> rechts bovenaan na het maken van wijzigingen.'],
        ]);

        $html .= '</div>';

        /* ── GEAVANCEERD ──────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="geavanceerd">';
        $html .= '<p class="mkbase-docs-intro">Via het Geavanceerd-menu kun je code-snippets toevoegen aan de HTML-output van de website. Dit is bedoeld voor tracking pixels, tag managers en andere externe scripts. Daarnaast stel je hier in welke blokken er in de editor beschikbaar zijn. <strong>Gebruik dit menu alleen als je weet wat je doet.</strong> Omdat de code op elke pagina komt, kunnen alleen beheerders (en redacteuren op een gewone website) deze pagina openen.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-geavanceerd'), 'Ga naar Geavanceerd');

        $html .= mkbase_docs_section('head', 'In de &lt;head&gt;', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor scripts die zo vroeg mogelijk geladen moeten worden, zoals Google Tag Manager (<code>&lt;script&gt;</code>-gedeelte) of Google Analytics.'],
            ['label' => 'Voorbeeld', 'desc' => mkbase_docs_code('<script>(function(w,d,s,l,i){ ... })(window,document,\'script\',\'dataLayer\',\'GTM-XXXXXXX\');</script>')],
        ]);

        $html .= mkbase_docs_section('body-open', 'Direct na &lt;body&gt;', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor de <code>&lt;noscript&gt;</code>-fallback van Google Tag Manager of andere iframe-gebaseerde trackers die direct na de openings-tag geplaatst moeten worden.'],
        ]);

        $html .= mkbase_docs_section('body-close', 'Onderaan de pagina (voor &lt;/body&gt;)', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor niet-kritische scripts die pas aan het einde van de pagina geladen hoeven te worden, zoals chatwidgets of pop-uptools. Hier hoort de meeste code, op elke pagina.'],
        ]);

        $html .= mkbase_docs_section('homepage-only', 'Alleen op de homepage', 'dashicons-admin-home', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Code die uitsluitend op de homepagina zichtbaar of actief moet zijn. De code wordt geladen in de footer, maar alleen als de bezoeker op de voorpagina zit.'],
        ]);

        $editor_items = [
            ['label' => 'Alleen thema-blokken in de editor', 'desc' => 'Verbergt de standaardblokken van WordPress in de editor, voor iedereen. Alleen de Mediakanjers-blokken blijven over, plus de blokken bij <strong>Extra zichtbare blokken</strong>. <strong>Let op:</strong> pagina\'s die standaardblokken gebruiken (paragraaf, afbeelding, kolommen) zijn daarna niet meer te bewerken.'],
            ['label' => 'Extra zichtbare blokken', 'desc' => 'Standaardblokken die naast de thema-blokken beschikbaar blijven, bijv. Kop of Lijst. Verschijnt alleen als <strong>Alleen thema-blokken</strong> aan staat.'],
        ];
        if (function_exists('mkbase_is_mediakanjers_admin') && mkbase_is_mediakanjers_admin()) {
            $editor_items[] = ['label' => 'Bestandseditor toestaan voor Mediakanjers', 'desc' => 'Zet de thema- en plugin-bestandseditor aan, alleen voor het account <code>mediakanjers</code>. Voor iedereen anders blijft hij altijd uit. Alleen zichtbaar voor dat account.'];
            $editor_items[] = ['label' => 'Volgorde vergrendeld (hele website)', 'desc' => 'Op elke pagina en elk bericht kan niemand nog blokken toevoegen, verwijderen of verplaatsen; de inhoud blijft wel te bewerken. Zet de volgorde eerst goed voordat je dit aanzet. Alleen zichtbaar voor het account <code>mediakanjers</code>.'];
        }
        $html .= mkbase_docs_section('editor', 'Editor', 'dashicons-edit', $editor_items);

        $html .= '</div>';

        /* ── MELDINGEN ────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="meldingen">';
        $html .= '<p class="mkbase-docs-intro">Toon een opvallende balk boven- of onderaan de website, bijvoorbeeld voor een actie, gewijzigde openingstijden of een storing. Je kunt meerdere meldingen maken, ze vooraf inplannen en per melding kiezen waar en hoe ze verschijnen.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-meldingen'), 'Ga naar Meldingen');

        $html .= mkbase_docs_section('melding-overzicht', 'Overzicht en voorbeeld', 'dashicons-welcome-view-site', [
            ['label' => 'Overzicht', 'desc' => 'Naast de titel zie je hoeveel meldingen <strong>Live</strong>, <strong>Gepland</strong>, <strong>Verlopen</strong> of <strong>Uit</strong> zijn. Elke melding toont in de kop haar naam, positie, planning en status, ook als ze is ingeklapt. De aantallen lopen mee terwijl je wijzigt. Een melding die een feestdag of afwijking volgt, heeft het label <strong>Gekoppeld aan Openingstijden</strong>; bestaat die feestdag of afwijking niet meer, dan staat hij op <strong>Niet gekoppeld</strong> en verschijnt hij niet.'],
            ['label' => 'Vanuit Openingstijden', 'desc' => 'Staan de automatische meldingen bij Openingstijden aan, dan zie je ze bovenaan in het blok <strong>Vanuit Openingstijden</strong>, met hun status en tekst. Ze zijn hier alleen te bekijken; aanpassen of uitzetten doe je bij Openingstijden.'],
            ['label' => 'Kop van een melding', 'desc' => 'Met de schakelaar zet je een melding aan of uit. Daarnaast staan <strong>Dupliceren</strong>, <strong>Verwijderen</strong> en het pijltje om in of uit te klappen. Na verwijderen kun je dat nog <strong>Ongedaan maken</strong>; definitief is het pas na <strong>Opslaan</strong>.'],
            ['label' => 'Live voorbeeld', 'desc' => 'Rechts naast elke melding staat een voorbeeld dat meteen meeverandert terwijl je typt, kleuren of een positie kiest. Op de website verschijnt de melding pas na <strong>Opslaan</strong>.'],
            ['label' => 'Bekijk op website', 'desc' => 'Het oog-icoon in de kop opent de website met alleen deze melding erop, ook als hij nog gepland is of uit staat. Alleen jij ziet dat voorbeeld. Je ziet de opgeslagen versie: sla eerst op om nieuwe wijzigingen te zien.'],
            ['label' => 'Meerdere meldingen', 'desc' => 'Klik op <strong>Melding toevoegen</strong>. Staan er meerdere tegelijk live op dezelfde plek, dan komen ze onder elkaar, in de volgorde van de lijst. Sleep een melding aan de puntjes links om de volgorde te wijzigen.'],
            ['label' => 'Voorbeelden', 'desc' => 'Is de lijst leeg, dan kun je beginnen met een lege melding of met een voorbeeld: <strong>Gewijzigde openingstijden</strong>, <strong>Actie of korting</strong> of <strong>Storing</strong>. Pas het daarna aan.'],
        ]);

        $html .= mkbase_docs_section('melding-inhoud', 'Inhoud', 'dashicons-editor-paragraph', [
            ['label' => 'Aan', 'desc' => 'De schakelaar in de kop van elke melding. Uit: de melding is nergens zichtbaar, ongeacht de planning.'],
            ['label' => 'Naam', 'desc' => 'Alleen voor jezelf, om meldingen in het overzicht uit elkaar te houden. Niet zichtbaar op de website.'],
            ['label' => 'Tekst', 'desc' => 'De inhoud van de melding. In de werkbalk: een <strong>kop</strong> (groot, middel of klein), vet, cursief, onderstrepen, een opsomming met bolletjes of nummers, links, midden of rechts uitlijnen, een link, en de gum om opmaak weg te halen (bijv. na plakken uit Word). Bij een balk leest één zin het best, zeker op een telefoon. Wordt de tekst lang, of staat er een lijst in, dan verschijnt een tip om een pop-up of zwevend blok te kiezen. Koppen worden in een balk klein getoond.'],
            ['label' => 'Knop', 'desc' => 'Optioneel: een knop bij de tekst, bijv. "Bekijk de actie". Laat leeg voor geen knop. Met een knop kies je ook de stijl (<strong>Omlijnd</strong> of <strong>Gevuld</strong>, dat meer opvalt) en bij een balk of hij <strong>naast</strong> of <strong>onder</strong> de tekst staat. Bij een pop-up en zwevend blok staat hij altijd onder de tekst.'],
            ['label' => 'Openingstijden in de melding', 'desc' => 'Zet dit aan om de openingstijden onder de tekst te tonen, en kies de roosters en de weergave (Week, Vandaag, Status of Afwijkingen). Bij Week en Vandaag kies je ook of "Nu open" erbij staat. Het live voorbeeld toont de echte openingstijden. Zie ook <strong>Openingstijden → Meldingen bij openingstijden</strong>.'],
        ]);

        $html .= mkbase_docs_section('melding-planning', 'Planning', 'dashicons-calendar-alt', [
            ['label' => 'Wanneer zichtbaar', 'desc' => '<strong>Altijd</strong>: de melding is zichtbaar zolang hij aan staat. <strong>Zelf plannen</strong>: alleen tussen <strong>Vanaf</strong> en <strong>Tot en met</strong>, op de minuut nauwkeurig (tijdzone van de website). Leeg laten mag: zonder Vanaf start hij direct, zonder Tot en met loopt hij door. <strong>Volgens openingstijden</strong>: de melding volgt een feestdag of afwijking (zie hieronder).'],
            ['label' => 'Volgens openingstijden', 'desc' => 'Kies bij <strong>Volgt</strong> een feestdag of afwijking van Openingstijden. De melding verschijnt een aantal dagen vooraf (bij <strong>Vooraf tonen</strong>; leeg is zoals bij Openingstijden) en verdwijnt na de laatste dag. De tijdlijn eronder laat zien wanneer: eerst "Melding vooraf", dan de feestdag of afwijking zelf. Verschuift de datum bij Openingstijden, dan schuift de melding mee.'],
            ['label' => 'Tijdlijn', 'desc' => 'Onder de datums zie je wanneer de melding zichtbaar is ten opzichte van vandaag, en hoeveel dagen het nog duurt.'],
            ['label' => 'Cache', 'desc' => 'Op elk begin- en eindmoment en bij het opslaan wordt de cache van WP Rocket automatisch geleegd, zodat de melding op tijd verschijnt en verdwijnt.'],
        ]);

        $html .= mkbase_docs_section('melding-weergave', 'Weergave', 'dashicons-art', [
            ['label' => 'Stijl', 'desc' => 'Kies een vaste stijl (<strong>Info</strong>, <strong>Succes</strong>, <strong>Waarschuwing</strong> of <strong>Actie</strong>) of <strong>Eigen kleuren</strong>. Zorg bij eigen kleuren voor voldoende contrast tussen tekst en achtergrond.'],
            ['label' => 'Positie', 'desc' => '<ul><li><strong>Bovenaan, blijft staan</strong>: blijft in beeld tijdens het scrollen.</li><li><strong>Bovenaan, scrolt mee weg</strong>: staat boven de header en verdwijnt bij het scrollen.</li><li><strong>Onderaan, blijft staan</strong>: zwevende balk onderaan het scherm.</li><li><strong>Pop-up in het midden</strong>: een venster midden op het scherm. Standaard wordt de pagina erachter donker; zet <strong>Pagina erachter donker maken</strong> uit als de pagina gewoon bruikbaar moet blijven. Met <strong>Scrollen erachter blokkeren</strong> (standaard aan) staat de pagina stil zolang de pop-up open is.</li><li><strong>Zwevend blok</strong>: een kaartje op een vaste plek (links of rechts, boven, midden of onder) dat blijft staan tijdens het scrollen. De breedte stel je zelf in, standaard 360 px. Op een telefoon staan blokken onderaan, over de volle breedte.</li></ul>'],
            ['label' => 'Uitlijning, tekstgrootte en ruimte', 'desc' => '<strong>Uitlijning</strong>: Automatisch (een balk in het midden, een pop-up en blok links), Links of Midden, voor de hele melding. <strong>Tekstgrootte</strong>: Klein, Normaal of Groot; koppen schalen mee. <strong>Ruimte</strong>: Compact, Normaal of Ruim, de ruimte rond de tekst. Het live voorbeeld laat het meteen zien.'],
            ['label' => 'Eén per plek', 'desc' => 'Balken mogen onder elkaar staan. Een pop-up en elke plek van een zwevend blok hebben maar één melding tegelijk. Kies je een plek die in dezelfde periode al bezet is, dan staat er <strong>Bezet</strong> bij en kun je niet opslaan. Plan ze na elkaar in, of kies een andere plek.'],
        ]);

        $html .= mkbase_docs_section('melding-gedrag', 'Gedrag', 'dashicons-clock', [
            ['label' => 'Wanneer verschijnen', 'desc' => '<strong>Direct</strong> bij het openen van de pagina, <strong>na een aantal seconden</strong> (bijv. 10), of <strong>na scrollen</strong>: pas als de bezoeker een stuk naar beneden is gegaan (een schermhoogte is ongeveer 800 px). Met "Bekijk op website" verschijnt hij altijd direct.'],
            ['label' => 'Automatisch verdwijnen', 'desc' => 'De melding verdwijnt na het ingestelde aantal seconden; een dun balkje laat zien hoeveel tijd er nog is. Zolang de bezoeker er met de muis of het toetsenbord op staat, wacht hij. Daarna geldt <strong>Daarna weer tonen</strong>, net als bij wegklikken. Staan er openingstijden in de melding, kies dan minstens 20 seconden of zet het uit; anders verschijnt er een tip.'],
            ['label' => 'Sluitbaar', 'desc' => 'Toont een kruisje waarmee de bezoeker de melding wegklikt. Kies wanneer hij weer verschijnt: op de volgende pagina, bij een volgend bezoek (na het sluiten van de browser), na een aantal dagen, of nooit meer. Wijzig je de tekst, knop of planning, dan ziet iedereen de melding opnieuw. Een pop-up heeft altijd een kruisje (en sluit ook met Esc of een klik ernaast); de keuze Sluitbaar staat er dan niet en "Daarna weer tonen" geldt altijd.'],
        ]);

        $html .= mkbase_docs_section('melding-zichtbaarheid', 'Zichtbaarheid', 'dashicons-visibility', [
            ['label' => 'Waar tonen', 'desc' => '<strong>Hele website</strong>, <strong>Alleen op gekozen pagina\'s</strong> of <strong>Overal behalve gekozen pagina\'s</strong>. Je kunt pagina\'s, berichten en andere berichttypes kiezen.'],
        ]);

        if (current_user_can('manage_options')) {
            // Een melding zoals op de website (balk, met knop en kruisje), voor de tabel met classes
            $melding_voorbeeld = '<div class="mk-notification mk-notification--info mk-notification--balk mk-notification--uitlijnen-auto mk-notification--tekst-normaal mk-notification--ruimte-normaal" style="--mk-notification-bg: #2563eb; --mk-notification-color: #ffffff;">'
                . '<div class="mk-notification__inner"><div class="mk-notification__inner__txt"><p><strong>Nu 20% korting</strong> op het hele assortiment.</p></div>'
                . '<a class="mk-notification__button mk-notification__button--omlijnd" href="#" tabindex="-1">Bekijk de actie</a>'
                . '<span class="mk-notification__close mk-notification__inner__closex" aria-hidden="true"><span class="mk-notification__inner__closex__lineone"></span><span class="mk-notification__inner__closex__linetwo"></span></span>'
                . '</div><span class="mk-notification__timer" aria-hidden="true"></span></div>';
            $html .= mkbase_docs_section('melding-technisch', 'Technisch', 'dashicons-admin-tools', [
                ['label' => 'Bestanden', 'desc' => mkbase_docs_tabel([
                    ['inc/notifications.php', 'Logica: welke meldingen zichtbaar zijn, status, planning, opslaan'],
                    ['template-parts/notifications/notifications.php', 'De HTML; aangeroepen vanuit <code>footer.php</code> van het child theme'],
                    ['assets/css/components/notifications.css', 'De opmaak (laadt alleen op pagina\'s met een zichtbare melding)'],
                    ['assets/js/notifications.js', 'Verschijnen, verdwijnen, sluiten en onthouden'],
                ], ['Bestand', 'Wat erin staat'])],
                ['label' => 'Classes van een melding', 'desc' => mkbase_docs_voorbeeld($melding_voorbeeld) . mkbase_docs_tabel([
                    ['.mk-notification', 'Eén melding', '.mk-notification'],
                    ['.mk-notification--{stijl}', 'De stijl: <code>--info</code>, <code>--succes</code>, <code>--waarschuwing</code>, <code>--actie</code>, <code>--eigen</code>'],
                    ['.mk-notification--balk / --blok / --popup', 'De vorm. Een pop-up is een <code>&lt;dialog&gt;</code>; de donkere laag is <code>::backdrop</code>'],
                    ['.mk-notification__inner', 'De binnenkant: tekst, knop en kruisje naast of onder elkaar', '.mk-notification__inner'],
                    ['.mk-notification__inner__txt', 'De tekst uit de editor (alinea\'s, koppen H2 t/m H4, lijsten)', '.mk-notification__inner__txt'],
                    ['.mk-notification__button', 'De knop, met <code>--omlijnd</code> of <code>--gevuld</code>', '.mk-notification__button'],
                    ['.mk-notification__close', 'Het kruisje', '.mk-notification__close'],
                    ['.mk-notification__timer', 'Het aftelbalkje bij Automatisch verdwijnen', '.mk-notification__timer'],
                    ['.mk-notification__openingstijden', 'Openingstijden in de melding (sinds 1.7.0)'],
                    ['.mk-notification--uitlijnen-{auto|links|midden}', 'Weergave → Uitlijning'],
                    ['.mk-notification--tekst-{klein|normaal|groot}', 'Weergave → Tekstgrootte'],
                    ['.mk-notification--ruimte-{compact|normaal|ruim}', 'Weergave → Ruimte'],
                    ['.mk-notification--knop-onder', 'Bij een balk: de knop onder de tekst'],
                ])],
                ['label' => 'Plekken op de pagina', 'desc' => mkbase_docs_tabel([
                    ['.mk-notifications--boven / --boven-scrollt / --onder', 'De balken, onder elkaar'],
                    ['.mk-notification-blokken', 'Alle zwevende blokken samen'],
                    ['.mk-notifications--blok.mk-notifications--rechts-onder', 'Eén plek voor een blok (ook <code>--links-boven</code>, <code>--rechts-midden</code>, enz.)'],
                ], ['Class', 'Wat het is'])],
                ['label' => 'CSS-variabelen', 'desc' => mkbase_docs_tabel([
                    ['--mk-notification-bg', 'Achtergrondkleur van de melding (per melding gezet)'],
                    ['--mk-notification-color', 'Tekstkleur van de melding'],
                    ['--mk-notification-width', 'Breedte van een zwevend blok'],
                    ['--mk-notification-height', 'Op <code>&lt;html&gt;</code>: de hoogte van de balken bovenaan (de header schuift mee)'],
                    ['--mk-notification-bottom-height', 'Op <code>&lt;html&gt;</code>: de hoogte van de balken onderaan'],
                ], ['Variabele', 'Wat het is'])],
                ['label' => 'CSS overschrijven', 'desc' => 'Onderaan de pagina, in het ingeklapte blok <strong>Technisch</strong>. Op Ja laadt de opmaak niet uit het core thema en schuift de header niet mee; het child theme regelt dan alles zelf.'],
                ['label' => 'Van 1.4.1 naar 1.5.0', 'desc' => 'De losse melding van vóór 1.5.0 wordt bij de update automatisch de eerste melding in de lijst, met dezelfde tekst, kleuren, datums en pagina\'s.'],
            ], true);
        }

        $html .= '</div>';

        /* ── OPENINGSTIJDEN (sinds 1.7.0) ─────────────────── */
        if (function_exists('mkbase_ot_actief') && mkbase_ot_actief()) {
            $html .= '<div class="mkbase-docs-panel" data-panel="openingstijden">';
            $html .= '<p class="mkbase-docs-intro">Vul de openingstijden één keer in bij <strong>Mediakanjers → Openingstijden</strong>. Het blok <strong>Openingstijden</strong> toont ze op elke pagina waar je het neerzet, en Google kan ze bij je bedrijf laten zien. Feestdagen en afwijkende dagen komen er vanzelf bij.</p>';
            $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-openingstijden'), 'Ga naar Openingstijden');

            $html .= mkbase_docs_section('ot-start', 'Zo werkt het', 'dashicons-clock', [
                ['label' => 'Wat geldt er op een dag?', 'desc' => 'Eerst een <strong>afwijkende dag</strong> (bijv. een bedrijfsvakantie), dan een <strong>feestdag</strong>, en anders de <strong>vaste week</strong>. Een afwijkende dag met de status <strong>Gewoon open</strong> maakt een feestdag weer een gewone dag.'],
                ['label' => 'Overzicht', 'desc' => 'Bovenaan kies je het rooster; de tegels tonen voor dat rooster de stand van nu, vandaag, de eerstvolgende afwijking en hoeveel afwijkingen er op de website staan. Bij <strong>Roosters</strong> pas je de naam aan, kies je het rooster voor Google of verwijder je een rooster. Rechts zie je precies wat de website toont, met alle roosters samen. Daarnaast staan hier waarschuwingen, bijvoorbeeld als Google nog geen openingstijden krijgt of de tijdzone niet klopt.'],
                ['label' => 'Wat staat er op de website?', 'desc' => 'Het weekoverzicht toont de week van vandaag, van maandag tot en met zondag. Valt er een feestdag of afwijkende dag in die week, dan staat die op zijn eigen dag, met de datum en de naam. Latere feestdagen en afwijkingen staan eronder, vanaf het aantal dagen van tevoren dat je bij <strong>Algemeen</strong> instelt (standaard 30). Voorbije dagen verdwijnen vanzelf.'],
                ['label' => 'Nu open', 'desc' => 'Of je nu open bent ("Nu open · tot 17:30", "Pauze · weer open om 13:00", "Gesloten · morgen open om 08:30") rekent de browser van de bezoeker uit, in de tijdzone van de website. Daardoor klopt het ook als de pagina uit de cache komt.'],
                ['label' => 'Tijdzone', 'desc' => 'Kies bij <strong>Instellingen → Algemeen → Tijdzone</strong> een stad, zoals <strong>Amsterdam</strong>, en niet "UTC+0" of "UTC+1". Met alleen een afwijking van UTC klopt "Nu open" een uur niet, of alleen in de winter (zomertijd). Het thema zet een nieuwe of bijgewerkte website daarom één keer vanzelf op Amsterdam; kies je daarna zelf iets anders, dan blijft dat staan, en waarschuwen het Overzicht en Diagnose als het niet klopt. Developers: andere stad of uitzetten met het filter <code>mkbase_tijdzone_automatisch</code>.'],
                ['label' => 'Cache', 'desc' => 'Na opslaan wordt de cache van de website geleegd. Daarnaast kijkt het thema elke nacht of er iets op de website verandert, bijvoorbeeld omdat een nieuwe week begint of een feestdag in beeld komt, en leegt dan de cache.'],
            ]);

            $html .= mkbase_docs_section('ot-week', 'Vaste week en roosters', 'dashicons-calendar-alt', [
                ['label' => 'Per dag', 'desc' => 'Kies <strong>Open</strong>, <strong>Op afspraak</strong> of <strong>Gesloten</strong>. Bij Open vul je de tijden in. Een pauze of koopavond? Klik op <strong>+ Tweede tijdvak</strong>; meer dan twee per dag kan niet. Met een opmerking (bijv. "Koopavond") komt er een korte tekst bij de dag.'],
                ['label' => 'Na middernacht', 'desc' => 'Open tot na middernacht? Vul gewoon de echte tijd in, bijv. 22:00 – 02:00. De status "nu open" klopt dan ook na middernacht.'],
                ['label' => 'Maandag kopiëren', 'desc' => 'Met <strong>Maandag kopiëren naar di t/m vr</strong> zet je de tijden van maandag in één keer op de andere werkdagen.'],
                ['label' => 'Meerdere roosters', 'desc' => 'Heb je bijvoorbeeld een winkel en een werkplaats met andere tijden, voeg dan met <strong>+ Rooster</strong> een rooster toe. Met de keuze bovenaan wissel je tussen de roosters. Per blok kies je welke roosters het toont. Een rooster dat nog in een blok staat (ook in een gesynchroniseerd patroon of een template), kun je niet verwijderen; bij opslaan zie je dan waar het staat.'],
                ['label' => 'Live voorbeeld', 'desc' => 'Rechts naast de week zie je meteen hoe het overzicht eruitziet terwijl je typt. Gelijke dagen na elkaar worden samengevoegd, zoals "Maandag t/m vrijdag".'],
            ]);

            $html .= mkbase_docs_section('ot-feestdagen', 'Feestdagen', 'dashicons-star-filled', [
                ['label' => 'Standaard', 'desc' => 'Op een nieuwe website staan de gangbare Nederlandse feestdagen al klaar, allemaal op Gesloten. Pas ze aan, verwijder ze of voeg er een toe; de lijst wordt bewaard bij <strong>Opslaan</strong>.'],
                ['label' => 'Datum', 'desc' => 'De datum wordt elk jaar vanzelf uitgerekend, ook voor Pasen, Hemelvaart en Pinksteren. Koningsdag valt op zondag een dag eerder. Bij <strong>Valt op</strong> zie je de eerstvolgende datum en wanneer de feestdag op de website verschijnt. Staat een feestdag er niet tussen, kies dan <strong>Eigen feestdag</strong> en vul een naam en datum (dag-maand) in.'],
                ['label' => 'Status', 'desc' => '<strong>Gesloten</strong>, <strong>Andere tijden</strong> (maximaal twee tijdvakken) of <strong>Op afspraak</strong>.'],
                ['label' => 'Geldt voor', 'desc' => 'Met meerdere roosters kies je per feestdag voor welke roosters hij geldt. Niets aangevinkt: voor alle. Een rooster dat je niet aanvinkt, volgt die dag gewoon de vaste week.'],
                ['label' => 'Terugzetten', 'desc' => 'Een feestdag per ongeluk verwijderd? Met <strong>Nederlandse feestdagen terugzetten</strong> komen de standaard-feestdagen die ontbreken er weer bij (op Gesloten). Wat er al staat, blijft zoals het is.'],
                ['label' => 'Melding', 'desc' => 'Per feestdag zie je of er een melding bij is, en maak je er een met <strong>Melding maken</strong>. Zie <strong>Meldingen bij openingstijden</strong>.'],
            ]);

            $html .= mkbase_docs_section('ot-afwijkend', 'Afwijkende dagen', 'dashicons-warning', [
                ['label' => 'Wat is het?', 'desc' => 'Eenmalige dagen of periodes: een bedrijfsvakantie, een kortere dag, een inventarisatie. Vul een datum in, en bij een periode ook <strong>Tot en met</strong> (leeg bij één dag).'],
                ['label' => 'Kaarten', 'desc' => 'Elke afwijking is een kaart. In de kop staan de naam, de datum, vanaf wanneer hij op de website staat, en of er een melding bij is. Met de prullenbak verwijder je hem (definitief na Opslaan). Een voorbije afwijking is ingeklapt; open hem met <strong>Bekijken</strong>.'],
                ['label' => 'Komende 120 dagen', 'desc' => 'Rechts naast de kaarten staan alle feestdagen en afwijkingen van de komende 120 dagen op volgorde. Wat al op de website staat, is groen. De lijst wordt bijgewerkt na Opslaan.'],
                ['label' => 'Status', 'desc' => '<strong>Gesloten</strong>, <strong>Andere tijden</strong>, <strong>Op afspraak</strong> of <strong>Gewoon open</strong> (de vaste week, ook op een feestdag). Met een toelichting komt er een korte zin bij, zoals hoe men je dan wel bereikt.'],
                ['label' => 'Overlap', 'desc' => 'Overlappen twee afwijkingen voor hetzelfde rooster, dan zie je een waarschuwing. Op de dagen die ze delen, geldt de bovenste in de lijst; sleep om de volgorde te wijzigen.'],
                ['label' => 'Voorbij', 'desc' => 'Een voorbije afwijking staat niet meer op de website. Je kunt hem laten staan of verwijderen.'],
            ]);

            $html .= mkbase_docs_section('ot-blok', 'Het blok Openingstijden', 'dashicons-screenoptions', [
                ['label' => 'Weergave', 'desc' => '<strong>Week</strong>: het hele overzicht. <strong>Vandaag</strong>: één regel met de tijden van vandaag, bijv. voor de footer. <strong>Status</strong>: alleen "Nu open" of "Gesloten · morgen open om 08:30". <strong>Afwijkingen</strong>: alleen de afwijkende dagen; zijn er geen, dan staat het blok niet op de website.'],
                ['label' => 'Meerdere roosters samen', 'desc' => 'Vink bij <strong>Roosters</strong> meer dan één rooster aan om ze in één overzicht te tonen. In de week staat er dan een kolom per rooster (is het blok smal, op een telefoon of in een kolom, dan per dag onder elkaar); dagen worden alleen samengevoegd als alle roosters gelijk zijn. Bij Vandaag en Status staat er een regel per rooster, met de naam ervoor. Bij de afwijkingen staat de naam van het rooster erbij als een afwijking niet voor alle gekozen roosters geldt.'],
                ['label' => 'Tabjes', 'desc' => 'Vink je twee of meer roosters aan, dan kies je bij <strong>Meerdere roosters</strong> voor <strong>Naast elkaar</strong> (één tabel) of <strong>Tabjes</strong>. Met tabjes staat er per rooster een tabje met een stip: groen als het nu open is, oranje bij een pauze of als het bijna sluit, grijs als het dicht is. Bij het openen van de pagina staat het rooster dat nu open is voorop. De tabjes werken ook met het toetsenbord (pijltjes).'],
                ['label' => 'Bewerkvenster', 'desc' => 'Het venster heeft drie groepen: <strong>Wat toon je</strong> (roosters, weergave, titel en link), <strong>Indeling</strong> (meerdere roosters, breedte en stijl) en <strong>Onderdelen</strong> (wat er wel of niet bij staat). Je ziet alleen wat bij de gekozen weergave hoort.'],
                ['label' => 'Stijl', 'desc' => 'Kies in het bewerkvenster bij <strong>Indeling → Stijl</strong> (of in de zijbalk bij <strong>Stijlen</strong>) hoe het blok eruitziet: <strong>Kaart</strong> (standaard, een witte kaart), <strong>Plat</strong> (zonder achtergrond, bijv. in een kolom of op een gekleurde sectie) of <strong>Donker</strong> (lichte tekst in een donkere kaart, bijv. in de footer).'],
                ['label' => 'Nu open', 'desc' => 'De status laat ook zien hoe lang je nog open bent ("Nu open · tot 17:30 · nog 2 uur"), en het laatste halfuur "Sluit over 25 minuten". In de regel van vandaag laat een streepje zien hoe ver de dag is.'],
                ['label' => 'Komende wijziging', 'desc' => 'Met <strong>Komende wijziging uitlichten</strong> (standaard aan) staat de eerstvolgende feestdag of afwijking opvallend boven de week, bijv. "Let op: maandag 29 maart gesloten (Tweede paasdag)". Hij verdwijnt vanzelf als die voorbij is.'],
                ['label' => 'Link bij Vandaag en Status', 'desc' => 'Bij de weergaves Vandaag en Status kun je een link toevoegen, bijv. naar de contactpagina met het hele overzicht. Zonder eigen tekst staat er "Alle openingstijden".'],
                ['label' => 'Breedte', 'desc' => '<strong>Inhoudsbreedte</strong>: zoals de andere blokken, maximaal 1400 px en in het midden. <strong>Volle breedte (100%)</strong>: het blok vult precies de plek waar het staat, zonder eigen marges. Kies dat als het blok in een kolom of naast tekst staat, zodat het gelijk staat met de tekst links en rechts.'],
                ['label' => 'Opties', 'desc' => 'Een titel, de status "nu open", vandaag uitlichten, gelijke dagen samenvoegen, gesloten dagen tonen, afwijkingen tonen en de komende wijziging uitlichten. Hoe tijden eruitzien (08:30, 8:30 of 8.30 uur) stel je bij <strong>Algemeen</strong> in, voor de hele website.'],
            ]);

            $html .= mkbase_docs_section('ot-meldingen', 'Meldingen bij openingstijden', 'dashicons-megaphone', [
                ['label' => 'Openingstijden in een melding', 'desc' => 'Zet bij een melding (Inhoud) <strong>Openingstijden in de melding</strong> aan en kies de roosters en de weergave. Ideaal als pop-up "Onze openingstijden": het weekoverzicht en "Nu open" staan dan onder de tekst, altijd actueel en met de feestdagen erin.'],
                ['label' => 'Een melding die een afwijking volgt', 'desc' => 'Kies bij een melding (Planning) bij <strong>Wanneer zichtbaar</strong> voor <strong>Volgens openingstijden</strong>, en kies bij <strong>Volgt</strong> een feestdag of afwijking. De melding verschijnt dan vanzelf een aantal dagen van tevoren (standaard zoals bij Openingstijden) en verdwijnt na de laatste dag. Verschuif je de afwijking, dan schuift de melding mee; een feestdag komt elk jaar terug. Bestaat de afwijking niet meer, dan staat de melding op "Niet gekoppeld" en verschijnt hij niet.'],
                ['label' => 'Invullers', 'desc' => 'In de tekst van een gekoppelde melding kun je met de knoppen onder de tekst invoegen: <code>{naam}</code> (bijv. Kerstvakantie), <code>{datum}</code> ("van dinsdag 1 tot en met donderdag 31 december"), <code>{tijden}</code> ("gesloten" of "open van 10:00 tot 14:00"), <code>{rooster}</code> (bijv. "Winkel en Werkplaats") en <code>{toelichting}</code>. Onder de tekst zie je meteen hoe hij op de website komt.'],
                ['label' => 'Melding maken', 'desc' => 'Bij elke feestdag en afwijking staat de knop <strong>Melding maken</strong>. Die opent Meldingen met een nieuwe melding die deze feestdag of afwijking volgt, met de standaardtekst al ingevuld. Controleer de tekst en sla op. Heeft een afwijking al een eigen melding, dan zie je dat bij de afwijking.'],
                ['label' => 'Automatische meldingen', 'desc' => 'Met <strong>Automatisch een melding bij elke feestdag en afwijking</strong> (Overzicht → Algemeen, standaard uit) krijgt elke feestdag en afwijking zonder eigen melding vanzelf een balk met de standaardtekst, in de stijl en op de plek die je kiest. Bij een feestdag of afwijking zet je hem uit met <strong>Geen melding</strong>, of maak je er met <strong>Aanpassen</strong> een eigen melding van. Bij Meldingen staan ze onder <strong>Vanuit Openingstijden</strong>, alleen om te bekijken. Een gesloten feestdag op een dag dat je toch al dicht bent, krijgt geen melding.'],
                ['label' => 'Tips', 'desc' => 'Volgt een melding een afwijking en toont hij ook de weergave Afwijkingen, dan staat die afwijking er twee keer in; er verschijnt dan een tip om Week of Vandaag te kiezen. Ook bij een melding met openingstijden die binnen 20 seconden vanzelf verdwijnt, verschijnt een tip: zoveel tekst lees je niet zo snel.'],
            ]);

            $html .= mkbase_docs_section('ot-google', 'Google', 'dashicons-search', [
                ['label' => 'Hoe werkt het?', 'desc' => 'Kies het rooster van je hoofdlocatie: in het Overzicht met <strong>Gebruiken voor Google</strong> bij het rooster, of bij <strong>Algemeen → Gebruiken voor Google</strong>. Zolang er niets is gekozen, krijgt Google geen openingstijden en zie je daar een melding over in het Overzicht. De website geeft de openingstijden dan mee als gestructureerde gegevens (schema.org), met de feestdagen en afwijkende dagen van de komende periode. Met Yoast SEO komt het bij de bedrijfsgegevens van Yoast; zonder Yoast staat het op de voorpagina.'],
                ['label' => 'Google Bedrijfsprofiel', 'desc' => 'Google gebruikt deze gegevens als aanvulling. Houd de tijden in je Google Bedrijfsprofiel ook bij; dat profiel wint als ze verschillen.'],
            ]);

            if (current_user_can('manage_options')) {
                // Een echt voorbeeld: de week van het eerste rooster, met de opmaak van de core
                $ot_voorbeeld = '';
                if (mkbase_ot_ingevuld() && ($eerste = array_key_first(mkbase_ot_roosters()))) {
                    $ot_voorbeeld = mkbase_openingstijden(['rooster' => $eerste, 'weergave' => 'week', 'titel' => 'Openingstijden']);
                }
                $html .= mkbase_docs_section('ot-dev', 'Voor developers', 'dashicons-editor-code', [
                    ['label' => 'Functie', 'desc' => '<p>Voor bijvoorbeeld de header of footer in het child theme. CSS en JS laden vanzelf mee.</p>'
                        . mkbase_docs_code("<?php echo mkbase_openingstijden([\n    'rooster'  => 'standaard',\n    'weergave' => 'vandaag',\n]); ?>")
                        . mkbase_docs_tabel([
                            ['rooster', 'Kenmerk van het rooster; meerdere met komma\'s of als array voor één overzicht. Leeg: het eerste'],
                            ['weergave', '<code>week</code>, <code>vandaag</code>, <code>status</code> of <code>afwijkingen</code>'],
                            ['titel', 'Een kop boven het blok (leeg: geen kop)'],
                            ['status', 'Status "nu open" tonen (true/false)'],
                            ['vandaag', 'De regel van vandaag uitlichten (true/false)'],
                            ['samenvoegen', 'Gelijke dagen op één regel (true/false)'],
                            ['gesloten', 'Gesloten dagen tonen (true/false)'],
                            ['afwijkingen', 'Afwijkingen onder de week tonen (true/false)'],
                            ['wijziging', 'De komende wijziging boven de week (true/false)'],
                            ['indeling', '<code>naast</code> of <code>tabs</code>, bij meerdere roosters in de week'],
                            ['link', 'Bij vandaag en status: een url, of een ACF-link (<code>url</code>, <code>title</code>, <code>target</code>)'],
                        ], ['Optie', 'Wat het doet'])],
                    ['label' => 'Shortcode', 'desc' => '<p>Met dezelfde opties als de functie.</p>' . mkbase_docs_code('[mk_openingstijden rooster="standaard" weergave="week" titel="Openingstijden"]')],
                    ['label' => 'Kenmerk van een rooster', 'desc' => 'Het eerste rooster heet <code>standaard</code>; andere krijgen bij opslaan een vast kenmerk (bijv. <code>r3f9a1c2b</code>), dat niet verandert als je de naam wijzigt. Je ziet het in het blok als de waarde van Rooster, of met:' . mkbase_docs_code('array_keys(mkbase_ot_roosters())')],
                    ['label' => 'Classes', 'desc' => mkbase_docs_voorbeeld($ot_voorbeeld) . mkbase_docs_tabel([
                        ['.mk-openingstijden', 'Het blok om <code>.mk-ot</code> heen; <code>--vol</code> bij Breedte → Volle breedte, <code>.is-style-kaart / -plat / -donker</code> voor de stijl'],
                        ['.mk-ot', 'Alle weergaves; <code>--week</code>, <code>--vandaag</code>, <code>--status</code>, <code>--afwijkingen</code>, <code>--{kenmerk}</code>', '.mk-ot'],
                        ['.mk-ot__titel', 'De titel', '.mk-ot__titel'],
                        ['.mk-ot__status', '"Nu open · tot 17:30"; <code>--open</code>, <code>--bijna</code>, <code>--pauze</code>, <code>--afspraak</code>, <code>--gesloten</code>', '.mk-ot__status'],
                        ['.mk-ot__stip', 'De stip in de status', '.mk-ot__stip'],
                        ['.mk-ot__status-nog', '"· nog 2 uur" achter de status', '.mk-ot__status-nog'],
                        ['.mk-ot__wijziging', 'De komende wijziging ("Let op: ...")', '.mk-ot__wijziging'],
                        ['.mk-ot__week', 'Het weekoverzicht', '.mk-ot__week'],
                        ['.mk-ot__regel', 'Eén regel; <code>--uitzondering</code> (feestdag of afwijking), <code>.is-vandaag</code>, <code>.is-nu-open</code> (met <code>--mk-ot-voortgang</code>, 0 tot 1)', '.mk-ot__week .mk-ot__regel'],
                        ['.mk-ot__dag', 'De dag(en) in een regel', '.mk-ot__dag'],
                        ['.mk-ot__tijd', 'Eén tijdvak', '.mk-ot__tijd'],
                        ['.mk-ot__extra', 'Opmerking (bijv. Koopavond) of de naam van een feestdag', '.mk-ot__extra'],
                        ['.mk-ot__label', 'Het label "Vandaag"', '.mk-ot__label'],
                        ['.mk-ot__afwijkingen', '"Afwijkende openingstijden" onder de week, met <code>.mk-ot__kop</code> en <code>.mk-ot__lijst</code>', '.mk-ot__afwijkingen'],
                        ['.mk-ot__kalender', 'De kalendertegel in de lijst (in de core verborgen, het child theme toont hem)'],
                        ['.mk-ot__tabel, .mk-ot__cel', 'Meerdere roosters naast elkaar (<code>.mk-ot--meer</code>); onder 480 px breed per dag onder elkaar'],
                        ['.mk-ot__tabs, .mk-ot__tab, .mk-ot__paneel', 'Tabjes (<code>.mk-ot--tabs</code>, <code>.is-tabs</code> zodra JavaScript ze heeft omgezet); <code>.mk-ot__tab--open</code> enz. en <code>[aria-selected="true"]</code>'],
                        ['.mk-ot__rooster', 'Naam van het rooster ("Werkplaats:")'],
                        ['.mk-ot__vandaag, .mk-ot__link', 'Weergave Vandaag, en de link "Alle openingstijden"'],
                    ])],
                    ['label' => 'Opmaak', 'desc' => 'De core (<code>assets/css/components/openingstijden.css</code>, laadt vóór <code>style-main.css</code>) regelt alleen de indeling: regels, de tabel met meerdere roosters en de weergave op een telefoon. Kleuren zijn afgeleid van de tekstkleur, dus zonder child theme is het blok neutraal (zoals in het voorbeeld hierboven). De look (statuslabels, kleuren, het label Vandaag, de kaart) staat in het child theme: <code>web/assets/scss/blocks/_mk-openingstijden.scss</code>, met bovenaan de instellingen en een overzicht van alle classes.'],
                    ['label' => 'Bestaande site bijwerken', 'desc' => 'Child themes worden op bestaande websites niet bijgewerkt. Na de update naar 1.7.0 toont het blok daar dus de neutrale opmaak van de core, tot je de look toevoegt:<ol>'
                        . '<li>Kopieer <code>web/assets/scss/blocks/_mk-openingstijden.scss</code> uit het Mediakanjers child theme (het startpunt voor nieuwe websites) naar dezelfde map in het child theme van de website.</li>'
                        . '<li>Zet in <code>web/assets/scss/style-main.scss</code>, onder BLOCKS:' . mkbase_docs_code('@import "blocks/_mk-openingstijden.scss";') . '</li>'
                        . '<li>Pas de instellingen bovenaan het bestand aan de huisstijl aan en bouw de CSS opnieuw (gulp).</li></ol>'
                        . 'Het bestand gebruikt de standaardvariabelen van het startpunt:' . mkbase_docs_tabel([
                            ['$color-primary', 'Accentkleur (label Vandaag, tabjes, link)'],
                            ['$font-color-normal', 'Tekstkleur in de kaart'],
                            ['$font-size-small, $line-height-small', 'Tekstmaat van het blok'],
                            ['$layout-content-size, $layout-maxwidth', 'Breedte van het blok (Inhoudsbreedte)'],
                            ['$layout-block-spacing', 'Ruimte boven en onder het blok'],
                            ['$h2-color, $h2-font-weight', 'De titel'],
                        ], ['Variabele', 'Waarvoor'])
                        . 'Heeft een ouder child theme die niet, dan geeft het bouwen een foutmelding: voeg ze toe of vervang ze bovenaan het bestand door vaste waarden. Heeft de website een eigen openingstijden-module, dan is dit niet nodig: zet de core-versie dan uit (zie Uitzetten).'],
                    ['label' => 'CSS-variabelen', 'desc' => mkbase_docs_tabel([
                        ['--mk-ot-lijn', 'Scheidingslijnen (core, afgeleid van de tekstkleur)'],
                        ['--mk-ot-gedempt', 'Gesloten dagen, opmerkingen, datums (core)'],
                        ['--mk-ot-vandaag-bg', 'Achtergrond van de regel van vandaag (core)'],
                        ['--mk-ot-label-bg, --mk-ot-label-color', 'Het label "Vandaag" en de kalendertegel (child theme)'],
                        ['--mk-ot-afwijkend-color', 'Tijden van een feestdag of afwijking (child theme)'],
                    ], ['Variabele', 'Wat het is']) . '<p>In een melding zet de core ze alle zes in de kleuren van de melding.</p>'],
                    ['label' => 'Uitzetten', 'desc' => 'Heeft een website een eigen openingstijden-module, zet de core-versie dan uit in het child theme:' . mkbase_docs_code("add_filter('mkbase_openingstijden_actief', '__return_false');") . 'De pagina, het blok en de Google-gegevens verdwijnen dan. Alles in de core heet <code>mkbase_ot_*</code>, zodat het niet botst met een eigen versie.'],
                ], true);
            }
            $html .= '</div>';
        }

        /* ── 404 ──────────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="404">';
        $html .= '<p class="mkbase-docs-intro">Pas hier de inhoud van de 404-pagina aan. Deze pagina verschijnt wanneer een bezoeker een URL bezoekt die niet bestaat.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-404'), 'Ga naar 404 instellingen');

        $html .= mkbase_docs_section('404-inhoud', 'Inhoud', 'dashicons-warning', [
            ['label' => 'Titel', 'desc' => 'De hoofdtitel op de 404-pagina, bijv. <em>"Oeps, deze pagina bestaat niet."</em>'],
            ['label' => 'Tekst', 'desc' => 'Een korte uitleg of tip voor de bezoeker, bijv. een verwijzing naar de homepage of de zoekopdracht.'],
            ['label' => 'Afbeelding', 'desc' => 'Een optionele illustratie of afbeelding die de 404-pagina visueel ondersteunt.'],
            ['label' => 'Afbeeldingsformaat', 'desc' => 'Hoe groot de afbeelding wordt ingeladen. Groter is scherper maar laadt trager; telefoons krijgen automatisch een kleinere versie. Standaard: <strong>Origineel</strong>.'],
        ]);

        $html .= '</div>';

        /* ── BLOKKEN ──────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="blokken">';
        $html .= '<p class="mkbase-docs-intro">De MKBase blokken zijn beschikbaar via de Gutenberg-editor onder de categorie <strong>Mediakanjers</strong>. Voeg een blok toe via het <strong>+</strong>-icoon in de editor en zoek op de naam van het blok. Selecteer een blok en klik in de werkbalk op <strong>Bewerken</strong> om de instellingen te openen.</p>';

        $html .= mkbase_docs_section('blok-breadcrumbs', 'Kruimelpad', 'dashicons-arrow-right-alt', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont het navigatiepad van de huidige pagina, ook wel een "broodkruimelpad" genoemd. Bezoekers zien zo altijd waar ze zich bevinden binnen de website.'],
            ['label' => 'Hoe gebruiken?', 'desc' => 'Voeg het blok toe aan een pagina of template. Er zijn geen instellingen, dus het blok heeft ook geen knop Bewerken: het pad vult zich vanzelf op basis van de paginastructuur.'],
            ['label' => 'Tip', 'desc' => 'Zet de breadcrumbs bovenaan de pagina-inhoud, vóór de hoofdtitel. Zo geven ze de meeste navigatiewaarde.'],
        ]);

        $html .= mkbase_docs_section('blok-loop', 'Berichtenoverzicht', 'dashicons-list-view', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont een grid van berichten of een ander post type. Ideaal voor nieuwsoverzichten, productlijsten of portfoliopagina\'s.'],
            ['label' => 'Soort inhoud', 'desc' => 'Kies welk soort inhoud getoond wordt (bijv. Berichten, Pagina\'s of een eigen post type). De keuze wordt automatisch gevuld met alle beschikbare post types.'],
            ['label' => 'Aantal berichten', 'desc' => 'Bepaal hoeveel berichten er getoond worden, de nieuwste eerst. Zet <strong>Alles tonen</strong> aan om alle berichten te tonen.'],
            ['label' => 'Handmatige selectie', 'desc' => 'Selecteer specifieke berichten die je wilt tonen. Zodra je berichten selecteert, worden Soort inhoud en Aantal berichten niet meer gebruikt; die velden worden dan grijs.'],
            ['label' => 'Kolommen', 'desc' => 'Kies hoeveel kolommen het grid heeft: 1, 2, 3 of 4. Op mobiel worden de kolommen automatisch teruggebracht naar 1.'],
            ['label' => 'Breedte', 'desc' => '<strong>Ingesloten</strong>: de loop past binnen de standaard paginabreedte. <strong>Volledige breedte</strong>: de loop strekt zich uit tot de rand van het scherm.'],
            ['label' => 'Afbeeldingsformaat', 'desc' => 'Hoe groot de uitgelichte afbeeldingen worden ingeladen. Groter is scherper maar laadt trager; telefoons krijgen automatisch een kleinere versie. Standaard: <strong>Groot</strong>. Kies <strong>Origineel</strong> bij 1 kolom over de volledige breedte.'],
            ['label' => 'Toon knop', 'desc' => 'Schakel in om per kaart een knop te tonen. Handig als je bezoekers expliciet wilt aanmoedigen door te klikken.'],
            ['label' => 'Knoptekst', 'desc' => 'De tekst op de knop. Standaard is dit "Meer lezen". Pas aan naar keuze, bijv. "Bekijk product" of "Lees verder". Zichtbaar zodra "Toon knop" ingeschakeld is.'],
        ]);

        $html .= mkbase_docs_section('blok-slider', 'Fotoslider', 'dashicons-images-alt2', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont een scrollbare slider met afbeeldingen. Geschikt voor galerijen, logowanden of afbeeldingscarrousels.'],
            ['label' => 'Naast elkaar (computer)', 'desc' => 'Hoeveel afbeeldingen er tegelijk zichtbaar zijn op een desktopscherm (1–4).'],
            ['label' => 'Naast elkaar (tablet)', 'desc' => 'Hoeveel afbeeldingen er tegelijk zichtbaar zijn op een tabletscherm (1–3). Op mobiel wordt altijd 1 item tegelijk getoond.'],
            ['label' => 'Automatisch afspelen', 'desc' => 'Schakel in om de slider automatisch door de afbeeldingen te laten scrollen. De gebruiker kan de slider altijd handmatig bedienen.'],
            ['label' => 'Vorm', 'desc' => 'Bepaalt de verhouding van elke slide. Kies uit Vierkant (1:1), Standaard (4:3), Portret (3:4), Klassiek (3:2), Klassiek portret (2:3), Breed (16:9) of Groot (9:16). Laat leeg om de originele afmetingen van de afbeelding te gebruiken.'],
            ['label' => 'Groot openen bij klikken', 'desc' => 'Schakel in om een afbeelding groot te openen als een bezoeker erop klikt. In de lightbox kan de bezoeker bladeren door alle afbeeldingen van deze slider.'],
            ['label' => 'Afbeeldingsformaat', 'desc' => 'Hoe groot de afbeeldingen worden ingeladen. Groter is scherper maar laadt trager; telefoons krijgen automatisch een kleinere versie. Standaard: <strong>Origineel</strong>. Bij 3 of 4 items naast elkaar is <strong>Groot</strong> meestal scherp genoeg en sneller.'],
            ['label' => 'Afbeeldingen', 'desc' => 'Klik op <strong>Afbeeldingen toevoegen</strong> en kies er in één keer meerdere (Ctrl of Shift ingedrukt houden). Ze staan als kleine tegels onder elkaar; sleep een tegel aan het nummer om de volgorde te wijzigen, of klik op het kruisje om hem te verwijderen.'],
        ]);

        $html .= mkbase_docs_section('blok-kolommen', 'Kolommen', 'dashicons-columns', [
            ['label' => 'Wat doet het?', 'desc' => 'Zet inhoud naast elkaar in 1 tot 4 kolommen, bijv. tekst naast een afbeelding of drie USP\'s naast elkaar. Op mobiel komen de kolommen onder elkaar te staan.'],
            ['label' => 'Verhouding', 'desc' => 'Klik op het plaatje met de indeling die je wilt, bijv. <strong>66 / 33</strong> voor een brede en een smalle kolom. Ontbrekende kolommen worden vanzelf toegevoegd. <strong>Kolom toevoegen</strong> staat grijs zodra je het aantal van de verhouding hebt bereikt; kies dan een andere verhouding.'],
            ['label' => 'Kolommen', 'desc' => 'De bovenste kolom komt links op de pagina, de onderste rechts. Elke kolom is een kaart met bovenin een samenvatting en de breedte. Kies per kolom bij <strong>Inhoud</strong> wat erin komt: Titel, Tekst, Afbeelding, Video, Knop, Icoon + tekst of Cijfer. Combineren mag, bijv. Titel + Tekst + Knop; de onderdelen verschijnen in die volgorde.'],
            ['label' => 'Video', 'desc' => 'Plak een YouTube- of Vimeo-link. Op de website staat eerst een voorbeeldafbeelding met afspeelknop; de video laadt pas als de bezoeker daarop klikt. Zo laadt de pagina sneller en plaatst YouTube geen cookies voordat de bezoeker kiest om af te spelen.'],
            ['label' => 'Meldingen in de editor', 'desc' => 'Past het aantal kolommen niet bij de gekozen verhouding, of is het blok nog leeg, dan staat er in de editor een oranje melding boven het blok. Op de website is die niet zichtbaar.'],
            ['label' => 'Opmaak per kolom', 'desc' => 'Klap in een kolom <strong>Opmaak van deze kolom</strong> open voor een eigen achtergrondkleur, tekstkleur, afgeronde hoeken, binnenruimte en uitlijning. Bij elke kleurkiezer staan de kleuren van de huisstijl bovenaan.'],
            ['label' => 'Opmaak van het blok', 'desc' => 'In het tabblad <strong>Opmaak</strong>: de verticale uitlijning van de kolommen ten opzichte van elkaar, de <strong>kleur van de hele strook</strong> (tot de randen van het scherm) en/of de <strong>kleur achter de kolommen</strong> (binnen de paginabreedte), de tekstkleur, de binnenruimte en afgeronde hoeken.'],
            ['label' => 'Afbeeldingsformaat', 'desc' => 'Staat naast de afbeelding in de kolom. Bepaalt hoe groot de afbeelding getoond wordt: <strong>Klein</strong> (max. 150px), <strong>Middel</strong> (max. 300px), <strong>Groot</strong> (max. 1024px) of <strong>Origineel</strong>. Nooit breder dan de kolom, dus Origineel vult de kolom. Standaard: <strong>Origineel</strong>.'],
        ]);

        $html .= mkbase_docs_section('blok-geavanceerd', 'Breedte en geavanceerd (Kolommen, Berichtenoverzicht, Fotoslider)', 'dashicons-admin-generic', [
            ['label' => 'Volledige breedte', 'desc' => 'Klik in de werkbalk boven het blok op het uitlijn-icoon en kies <strong>Volledige breedte</strong>. Het blok gaat dan buiten de normale inhoudsbreedte en vult de hele breedte van het scherm.'],
            ['label' => 'HTML-anker', 'desc' => 'Geef het blok een anker, bijv. <code>diensten</code>, via het tabblad <strong>Geavanceerd</strong> in de rechterkolom van de editor. Een link of menu-item naar <code>#diensten</code> scrolt dan naar dit blok.'],
            ['label' => 'Extra CSS-klasse(n)', 'desc' => 'Ook onder <strong>Geavanceerd</strong>. Bedoeld voor de developer, om één specifiek blok een eigen opmaak te geven.'],
        ]);

        // mkbase_docs_section() is beschikbaar vanuit het core thema — niet opnieuw definiëren.
        // $items: array van ['label' => '...', 'desc' => '...'] rijen.
        // $icon: elk geldig Dashicons-icoon, bijv. 'dashicons-cover-image'.
        $html .= apply_filters('mkbase_docs_extra_blocks', '');

        $html .= '</div>';

        /* ── AFBEELDINGEN ─────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="afbeeldingen">';
        $html .= '<p class="mkbase-docs-intro">Goed gebruik van afbeeldingen zorgt voor een snellere website en betere weergave. Volg deze richtlijnen bij het uploaden van afbeeldingen.</p>';

        $html .= mkbase_docs_section('afb-formaten', 'Bestandsformaten', 'dashicons-images-alt2', [
            ['label' => 'JPEG / JPG', 'desc' => 'Gebruik JPEG voor foto\'s en afbeeldingen met veel kleuren. Goede kwaliteit bij relatief kleine bestandsgrootte.'],
            ['label' => 'PNG', 'desc' => 'Gebruik PNG wanneer de afbeelding een <strong>transparante achtergrond</strong> nodig heeft, zoals logo\'s of iconen.'],
            ['label' => 'SVG', 'desc' => 'Ideaal voor logo\'s en iconen — een SVG blijft scherp op elk schermformaat en heeft een zeer kleine bestandsgrootte.'],
            ['label' => 'WebP', 'desc' => 'WebP is een modern formaat dat tot 30% kleiner is dan JPEG bij vergelijkbare kwaliteit. WordPress ondersteunt WebP automatisch vanaf versie 5.8.'],
            ['label' => 'GIF', 'desc' => 'Alleen gebruiken voor eenvoudige animaties. Voor stilstaande afbeeldingen zijn JPEG of PNG altijd beter.'],
        ]);

        $html .= mkbase_docs_section('afb-afmetingen', 'Afmetingen & bestandsgrootte', 'dashicons-fullscreen-alt', [
            ['label' => 'Maximale bestandsgrootte', 'desc' => 'Houd afbeeldingen bij voorkeur <strong>onder de 500 KB</strong>. Grote bestanden vertragen de pagina. Gebruik een tool zoals <em>TinyPNG</em> of <em>Squoosh</em> om afbeeldingen te comprimeren voor het uploaden.'],
            ['label' => 'Breedte', 'desc' => 'Upload afbeeldingen op de breedte waarop ze getoond worden, of iets groter. Een afbeelding van 3000px breed uploaden voor een blok van 800px breed is onnodig groot.'],
            ['label' => 'Aanbevolen breedtes', 'desc' => 'Volledige breedte (banner/hero): <strong>1920px</strong>. Inhoudsblok: <strong>1200px</strong>. Thumbnail / kaart: <strong>800px</strong>.'],
            ['label' => 'Retina schermen', 'desc' => 'Wil je dat afbeeldingen scherp zijn op retina-schermen (bijv. MacBooks en iPhones)? Upload ze op <strong>2× de weergavegrootte</strong>. Dus een afbeelding die 600px breed getoond wordt, upload je op 1200px.'],
            ['label' => 'Kleinere versies', 'desc' => 'Bij het uploaden maakt WordPress automatisch kleinere versies van de afbeelding. De website laat elk apparaat de versie laden die past: een telefoon krijgt een kleine, een groot scherm een grote. Je hoeft zelf geen aparte mobiele afbeelding te uploaden.'],
            ['label' => 'Afbeeldingsformaat in blokken', 'desc' => 'Blokken met afbeeldingen (Image Slider, Kolommen, Loop) en de 404-pagina hebben een keuzeveld <strong>Afbeeldingsformaat</strong>: Klein, Middel, Groot of Origineel. In de Image Slider en de Loop bepaalt het hoe scherp de afbeeldingen zijn: kies <strong>Origineel</strong> voor afbeeldingen over (bijna) de volle breedte, en <strong>Groot</strong> of kleiner als er meerdere naast elkaar staan. In Kolommen bepaalt het hoe groot de afbeelding getoond wordt.'],
        ]);

        $html .= mkbase_docs_section('afb-alt', 'Alt-tekst', 'dashicons-editor-textcolor', [
            ['label' => 'Wat is alt-tekst?', 'desc' => 'Alt-tekst (alternatieve tekst) beschrijft wat er op een afbeelding te zien is. Zoekmachines lezen deze tekst, en schermlezers spreken hem uit voor bezoekers met een visuele beperking.'],
            ['label' => 'Hoe invullen?', 'desc' => 'Beschrijf de afbeelding kort en concreet. Slechte alt-tekst: <em>"afbeelding1.jpg"</em>. Goede alt-tekst: <em>"Twee medewerkers overleggen aan een bureau"</em>.'],
            ['label' => 'Waar invullen?', 'desc' => 'Klik op een afbeelding in de mediabibliotheek en vul het veld <strong>"Alternatieve tekst"</strong> in het rechter paneel in. Dit hoeft maar één keer per afbeelding.'],
            ['label' => 'Decoratieve afbeeldingen', 'desc' => 'Is een afbeelding puur decoratief en voegt ze geen inhoudelijke waarde toe? Laat de alt-tekst dan leeg — schermlezers slaan hem dan over.'],
        ]);

        $html .= '</div>';

        /* ── WOOCOMMERCE ─────────────────────────────────── */
        if (class_exists('WooCommerce')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="woocommerce">';
            $html .= '<p class="mkbase-docs-intro">WooCommerce is de webshop-plugin van deze website. Hier beheer je producten, bestellingen, betaalmethoden en verzendopties.</p>';
            $html .= mkbase_docs_settings_link(admin_url('admin.php?page=wc-admin'), 'Ga naar WooCommerce');

            $html .= mkbase_docs_section('woo-producten', 'Producten beheren', 'dashicons-cart', [
                ['label' => 'Waar vind je producten?', 'desc' => 'Ga naar <strong>Producten</strong> in het WordPress admin menu.'],
                ['label' => 'Product toevoegen', 'desc' => 'Klik op <strong>"Nieuw product"</strong>. Vul de productnaam, beschrijving, prijs en afbeelding in. Sla op via <strong>"Publiceren"</strong>.'],
                ['label' => 'Productafbeelding', 'desc' => 'Stel de hoofdafbeelding in via <em>"Productafbeelding"</em> rechtsonder. Voeg extra afbeeldingen toe via <em>"Productgalerij"</em>.'],
                ['label' => 'Categorieën', 'desc' => 'Wijs het product toe aan een of meerdere categorieën via het paneel <em>"Productcategorieën"</em>. Categorieën kun je zelf aanmaken via <strong>Producten → Categorieën</strong>.'],
                ['label' => 'Voorraad', 'desc' => 'Beheer de voorraad via het tabblad <strong>"Voorraad"</strong> op de productpagina. Schakel voorraadbeheer in en vul het huidige aantal in.'],
            ]);

            $html .= mkbase_docs_section('woo-bestellingen', 'Bestellingen', 'dashicons-clipboard', [
                ['label' => 'Waar vind je bestellingen?', 'desc' => 'Ga naar <strong>WooCommerce → Bestellingen</strong>. Hier zie je een overzicht van alle bestellingen.'],
                ['label' => 'Bestelstatus', 'desc' => 'Elke bestelling heeft een status: <em>In afwachting van betaling</em>, <em>Verwerken</em>, <em>Voltooid</em>, <em>Geannuleerd</em>, etc. Pas de status aan door op een bestelling te klikken en de status te wijzigen via het rechter paneel.'],
                ['label' => 'Bestelling bekijken', 'desc' => 'Klik op een bestelling om de details te zien: klantgegevens, bestelde producten, betaling en verzending.'],
                ['label' => 'Notities', 'desc' => 'Voeg interne notities toe onderaan een bestelling om bijzonderheden bij te houden. Je kunt ook een notitie sturen naar de klant via <em>"Klant notificeren"</em>.'],
            ]);

            $html .= mkbase_docs_section('woo-instellingen', 'Winkel instellingen', 'dashicons-admin-settings', [
                ['label' => 'Waar vind je instellingen?', 'desc' => 'Ga naar <strong>WooCommerce → Instellingen</strong>.'],
                ['label' => 'Algemeen', 'desc' => 'Stel hier het winkeladres, de valuta en de landen in waarnaar je verkoopt of verzendt.'],
                ['label' => 'Winkelindeling', 'desc' => 'Kies hoeveel producten per rij en per pagina getoond worden via <strong>Instellingen → Producten → Weergave</strong>.'],
                ['label' => 'E-mails', 'desc' => 'Pas de automatische e-mails aan via <strong>Instellingen → E-mails</strong>. Denk aan de bevestigingsmail, verzendinformatie en administratieve meldingen.'],
            ]);

            $html .= mkbase_docs_section('woo-betalen', 'Betaalmethoden', 'dashicons-money-alt', [
                ['label' => 'Waar instellen?', 'desc' => 'Ga naar <strong>WooCommerce → Instellingen → Betalingen</strong>.'],
                ['label' => 'Beschikbare methoden', 'desc' => 'WooCommerce biedt standaard directe bankoverschrijving en contant bij levering. Voor iDEAL, creditcard of andere methoden is een betaalprovider zoals <em>Mollie</em> of <em>Pay.</em> nodig (aparte plugin).'],
                ['label' => 'Methode inschakelen', 'desc' => 'Klik op de schakelaar naast een betaalmethode om die in of uit te schakelen. Klik op <strong>"Beheren"</strong> voor de instellingen per methode.'],
            ]);

            $html .= mkbase_docs_section('woo-verzending', 'Verzending', 'dashicons-admin-site', [
                ['label' => 'Waar instellen?', 'desc' => 'Ga naar <strong>WooCommerce → Instellingen → Verzending</strong>.'],
                ['label' => 'Verzendzone', 'desc' => 'Maak verzendzone aan voor Nederland en/of België. Koppel hier de gewenste verzendmethoden aan (gratis verzending, vast tarief of lokaal ophalen).'],
                ['label' => 'Gratis verzending', 'desc' => 'Stel een minimumbedrag in voor gratis verzending via <strong>"Gratis verzending"</strong> binnen een verzendzone. Bijv. gratis bij een bestelling van €50 of meer.'],
                ['label' => 'Vast tarief', 'desc' => 'Voeg een vast verzendtarief toe en vul de kosten in. Je kunt meerdere tarieven per zone instellen.'],
            ]);

            $html .= '</div>';
        }

        /* ── GRAVITY FORMS ────────────────────────────────── */
        if (class_exists('GFForms')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="gravity-forms">';
            $html .= '<p class="mkbase-docs-intro">Gravity Forms is de formulierenbuilder op deze website. Hiermee maak je contactformulieren, aanmeldingen en meer zonder code.</p>';
            $html .= mkbase_docs_settings_link(admin_url('admin.php?page=gf_edit_forms'), 'Ga naar Formulieren');
            $html .= mkbase_docs_section('gf-aanmaken', 'Formulier aanmaken', 'dashicons-feedback', [
                ['label' => 'Waar vind je formulieren?', 'desc' => 'Ga naar <strong>Formulieren</strong> in het WordPress admin menu.'],
                ['label' => 'Nieuw formulier', 'desc' => 'Klik op <strong>"Nieuw formulier"</strong>, geef het een naam en klik op <strong>"Formulier aanmaken"</strong>. Je komt direct in de formulierbuilder.'],
                ['label' => 'Velden toevoegen', 'desc' => 'Sleep velden vanuit het rechter paneel naar het formulier, of klik op een veldtype om het toe te voegen. Veelgebruikte velden: <em>Eén regel tekst</em> (naam), <em>E-mailadres</em>, <em>Paragraaf</em> (bericht), <em>Telefoonnummer</em>.'],
                ['label' => 'Veld instellen', 'desc' => 'Klik op een veld om het te bewerken. Stel het label in, of het veld verplicht is (<strong>Vereist</strong>), en voeg eventueel een plaatsaanduidingstekst toe.'],
                ['label' => 'Opslaan', 'desc' => 'Klik op <strong>"Formulier bijwerken"</strong> rechtsboven om wijzigingen op te slaan.'],
            ]);
            $html .= mkbase_docs_section('gf-insluiten', 'Formulier insluiten', 'dashicons-shortcode', [
                ['label' => 'Via de editor', 'desc' => 'Voeg het Gravity Forms-blok toe in de Gutenberg-editor en selecteer het gewenste formulier uit de lijst.'],
                ['label' => 'Via shortcode', 'desc' => 'Voeg de shortcode <code>[gravityforms id="1"]</code> toe in de teksteditor. Vervang <code>1</code> door het ID van jouw formulier. Het ID zie je in het formulierenoverzicht onder <strong>Formulieren</strong>.'],
            ]);
            $html .= mkbase_docs_section('gf-notificaties', 'Notificaties (e-mails)', 'dashicons-email', [
                ['label' => 'Wat zijn notificaties?', 'desc' => 'Notificaties zijn de e-mails die verstuurd worden na het invullen van een formulier — bijvoorbeeld een bevestiging naar de invuller en een melding naar de beheerder.'],
                ['label' => 'Instellen', 'desc' => 'Ga naar het formulier → <strong>Instellingen → Notificaties</strong>. Klik op een notificatie om hem te bewerken. Pas het <em>Aan</em>-adres aan naar het gewenste e-mailadres.'],
                ['label' => 'Merge tags', 'desc' => 'Gebruik merge tags om formulierinvoer in de e-mail te tonen, bijv. <code>{Name:1}</code> voor de naam. Klik op het <strong>mergecode-icoon</strong> naast een veld om beschikbare tags te zien.'],
            ]);
            $html .= mkbase_docs_section('gf-bevestiging', 'Bevestigingspagina', 'dashicons-yes-alt', [
                ['label' => 'Wat is een bevestiging?', 'desc' => 'Na het versturen van het formulier ziet de bezoeker een bevestiging — dit kan een tekst zijn, een aparte pagina, of een redirect.'],
                ['label' => 'Instellen', 'desc' => 'Ga naar het formulier → <strong>Instellingen → Bevestigingen</strong>. Kies het type: <em>Tekst</em> (bericht onder het formulier), <em>Pagina</em> (redirect naar een WordPress-pagina) of <em>Redirect</em> (naar een eigen URL).'],
            ]);
            $html .= mkbase_docs_section('gf-inzendingen', 'Inzendingen bekijken', 'dashicons-list-view', [
                ['label' => 'Inzendingen', 'desc' => 'Alle ingevulde formulieren worden opgeslagen onder <strong>Formulieren → Inzendingen</strong>. Klik op een inzending om de volledige details te bekijken.'],
                ['label' => 'Exporteren', 'desc' => 'Ga naar <strong>Formulieren → Import/Export</strong> om inzendingen te exporteren als CSV-bestand, bijv. voor gebruik in Excel.'],
            ]);
            $html .= '</div>';
        }

        /* ── WP ROCKET ────────────────────────────────────── */
        if (defined('WP_ROCKET_VERSION')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="wp-rocket">';
            $html .= '<p class="mkbase-docs-intro">WP Rocket versnelt de website door pagina\'s op te slaan als statische bestanden. De meeste instellingen staan correct geconfigureerd — als klant hoef je normaal alleen de cache leeg te maken.</p>';
            $html .= mkbase_docs_settings_link(admin_url('options-general.php?page=wprocket'), 'Ga naar WP Rocket');
            $html .= mkbase_docs_section('rocket-wat', 'Wat doet WP Rocket?', 'dashicons-performance', [
                ['label' => 'Caching', 'desc' => 'WP Rocket slaat pagina\'s op als statische HTML-bestanden, zodat ze veel sneller laden voor bezoekers. De meeste instellingen staan al correct geconfigureerd — je hoeft hier normaal niets aan te doen.'],
                ['label' => 'Waar vind je WP Rocket?', 'desc' => 'Ga naar <strong>Instellingen → WP Rocket</strong> in het WordPress admin menu.'],
            ]);
            $html .= mkbase_docs_section('rocket-cache', 'Cache leegmaken', 'dashicons-update', [
                ['label' => 'Wanneer leegmaken?', 'desc' => 'Maak de cache leeg nadat je <strong>wijzigingen hebt opgeslagen</strong> die nog niet zichtbaar zijn op de website, of na het aanpassen van CSS en lay-out.'],
                ['label' => 'Hoe leegmaken?', 'desc' => 'Klik op de <strong>WP Rocket-knop</strong> in de zwarte adminbalk bovenaan en kies <strong>"Cache leegmaken"</strong>. De cache wordt direct gewist en pagina\'s worden opnieuw opgebouwd bij het eerste bezoek.'],
                ['label' => 'Automatisch', 'desc' => 'WP Rocket maakt de cache automatisch leeg wanneer je een pagina of bericht opslaat. Handmatig leegmaken is alleen nodig bij wijzigingen buiten de editor, zoals aanpassingen via de thema-instellingen.'],
            ]);
            $html .= mkbase_docs_section('rocket-uitsluitingen', 'Pagina\'s uitsluiten', 'dashicons-hidden', [
                ['label' => 'Wanneer uitsluiten?', 'desc' => 'Sommige pagina\'s zoals een winkelwagen, checkout of inlogpagina mogen niet gecached worden omdat ze dynamische inhoud tonen per gebruiker.'],
                ['label' => 'Hoe instellen?', 'desc' => 'Ga naar <strong>Instellingen → WP Rocket → Cache</strong> en voeg de URL\'s toe onder <em>"Nooit de volgende pagina\'s cachen"</em>. WooCommerce-pagina\'s worden automatisch uitgesloten als WooCommerce actief is.'],
            ]);
            $html .= '</div>';
        }

        /* ── YOAST SEO ────────────────────────────────────── */
        if (defined('WPSEO_VERSION')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="yoast-seo">';
            $html .= '<p class="mkbase-docs-intro">Yoast SEO helpt je om pagina\'s beter vindbaar te maken in zoekmachines. Per pagina stel je een focuszoekwoord in en krijg je direct feedback over de inhoud.</p>';
            $html .= mkbase_docs_settings_link(admin_url('admin.php?page=wpseo_dashboard'), 'Ga naar Yoast SEO');
            $html .= mkbase_docs_section('yoast-wat', 'Wat doet Yoast SEO?', 'dashicons-search', [
                ['label' => 'SEO', 'desc' => 'Yoast SEO helpt je om pagina\'s en berichten beter vindbaar te maken in zoekmachines zoals Google. Per pagina stel je een focuszoekwoord in en krijg je feedback over de inhoud en leesbaarheid.'],
                ['label' => 'Waar vind je Yoast?', 'desc' => 'De Yoast SEO-instellingen staan onderaan elke pagina en elk bericht in de editor, in het blok met het Yoast-logo. Algemene instellingen vind je via <strong>Yoast SEO</strong> in het admin menu.'],
            ]);
            $html .= mkbase_docs_section('yoast-meta', 'Meta titel & beschrijving', 'dashicons-editor-paste-text', [
                ['label' => 'Wat is het?', 'desc' => 'De meta titel en beschrijving zijn de tekst die Google toont in de zoekresultaten. Een goede titel en beschrijving zorgen voor meer clicks vanuit Google.'],
                ['label' => 'Instellen', 'desc' => 'Scroll op een pagina of bericht naar het Yoast SEO-blok onderaan. Klik op <strong>"Zoekweergave bewerken"</strong> om de meta titel en beschrijving in te vullen.'],
                ['label' => 'Meta titel', 'desc' => 'Houd de titel tussen de <strong>50 en 60 tekens</strong>. Yoast toont een kleurindicator die aangeeft of de lengte goed is. Verwerk het belangrijkste zoekwoord in de titel.'],
                ['label' => 'Meta beschrijving', 'desc' => 'Houd de beschrijving tussen de <strong>120 en 156 tekens</strong>. Beschrijf kort waar de pagina over gaat en zet de bezoeker aan tot klikken.'],
            ]);
            $html .= mkbase_docs_section('yoast-keyphrase', 'Focuszoekwoord', 'dashicons-tag', [
                ['label' => 'Wat is het?', 'desc' => 'Het focuszoekwoord is het woord of de zin waarop je de pagina wilt laten scoren in Google. Yoast analyseert de inhoud en geeft feedback op basis van dit zoekwoord.'],
                ['label' => 'Instellen', 'desc' => 'Vul het focuszoekwoord in het Yoast SEO-blok in bij <strong>"Focuszoekwoord"</strong>. Kies een specifiek zoekwoord dat bezoekers ook daadwerkelijk gebruiken in Google.'],
                ['label' => 'SEO-analyse', 'desc' => 'Yoast toont een groene, oranje of rode indicator. Groen betekent dat de pagina goed geoptimaliseerd is. Oranje of rood geeft aan dat er nog verbeterpunten zijn — klik op de indicator voor details.'],
            ]);
            $html .= mkbase_docs_section('yoast-social', 'Social media preview', 'dashicons-share', [
                ['label' => 'Wat is het?', 'desc' => 'Wanneer iemand een pagina deelt op Facebook of X (Twitter), bepaal je hier welke afbeelding en tekst getoond worden.'],
                ['label' => 'Instellen', 'desc' => 'Klik in het Yoast SEO-blok op het <strong>Facebook- of X-tabblad</strong>. Upload hier een afbeelding (aanbevolen: <strong>1200×630 pixels</strong>) en pas de titel en beschrijving aan indien gewenst.'],
            ]);
            $html .= '</div>';
        }

        /* ── BLOKKEN BOUWEN (developer only) ─────────────── */
        if (current_user_can('manage_options')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="dev-blokken">';
            $html .= '<p class="mkbase-docs-intro">Hulpfuncties uit het core thema voor de <code>render.php</code> van een blok. Een compleet startpunt staat in het child theme: <code>template-parts/blocks/_voorbeeld/</code>, met een stappenplan in de <code>README.md</code>. Mappen die met <code>_</code> beginnen worden niet als blok geregistreerd.</p>';

            $html .= mkbase_docs_section('dev-blok-attrs', 'Anker en extra classes', 'dashicons-admin-generic', [
                ['label' => 'Gebruik', 'desc' => '<p>Op de buitenste <code>&lt;div&gt;</code> van het blok. Voegt het HTML-anker (<code>id</code>) en de "Extra CSS-klasse(n)" uit de editor toe aan de eigen classes.</p>'
                    . mkbase_docs_code("<div<?php echo mkbase_block_attrs(\$block, 'mk-team'); ?>>")
                    . '<p>Meerdere eigen classes: geef een array mee.</p>' . mkbase_docs_code("<div<?php echo mkbase_block_attrs(\$block, ['mk-team', 'mk-team--groot']); ?>>")],
                ['label' => 'block.json', 'desc' => '<p>Het veld HTML-anker en "Volledige breedte" in de blokwerkbalk:</p>' . mkbase_docs_code("\"supports\": {\n    \"anchor\": true,\n    \"align\": [\"full\"]\n}")],
                ['label' => 'Volledige breedte', 'desc' => '<p>Geeft de buitenste <code>&lt;div&gt;</code> de class <code>.alignfull</code>. Voor de core-blokken staat de opmaak in <code>assets/css/components/mk-align.css</code> (laadt alleen waar nodig). Voor een eigen blok in het child theme:</p>' . mkbase_docs_code('.mk-team.alignfull { max-width: none; width: 100%; }')],
            ]);

            $html .= mkbase_docs_section('dev-blok-notice', 'Meldingen in de editor', 'dashicons-warning', [
                ['label' => 'Gebruik', 'desc' => '<p>Een oranje melding, alleen in de editor. Handig bij een leeg blok of een instelling die niet klopt.</p>' . mkbase_docs_code("if (\$is_preview) echo mkbase_block_notice(__('Team: voeg teamleden toe.', 'mk'));")],
            ]);

            $html .= mkbase_docs_section('dev-blok-video', 'Video', 'dashicons-video-alt3', [
                ['label' => 'Gebruik', 'desc' => mkbase_docs_code("echo mkbase_video(get_field('video'), \$is_preview);") . '<p>Met een ACF oEmbed-veld. Op de site eerst een afspeelknop (bij YouTube met voorbeeldafbeelding); de video laadt pas na een klik, YouTube via <code>youtube-nocookie.com</code> en Vimeo met <code>dnt=1</code>. In de editor staat de video er direct. Script en CSS laden automatisch mee.</p>'],
                ['label' => 'Styling', 'desc' => '<p>Pas in het child theme de variabelen aan op <code>.mk-video</code>, of overschrijf de classes.</p>' . mkbase_docs_tabel([
                    ['--mk-video-bg', 'Achtergrond achter de video'],
                    ['--mk-video-icon-bg', 'Achtergrond van de afspeelknop'],
                    ['--mk-video-icon-bg-hover', 'Idem, met de muis erop'],
                    ['--mk-video-icon-color', 'Kleur van het afspeel-icoon'],
                    ['.mk-video__play', 'De afspeelknop'],
                    ['.mk-video__poster', 'De voorbeeldafbeelding'],
                    ['.mk-video__icon', 'Het afspeel-icoon'],
                ], ['Variabele of class', 'Wat het is'])],
            ]);

            $html .= mkbase_docs_section('dev-blok-kaart', 'Berichtkaart', 'dashicons-id', [
                ['label' => 'Gebruik', 'desc' => '<p>Dezelfde kaart als het Loop-blok en de archiefpagina, met de <code>.mk-loop__*</code>-classes.</p>' . mkbase_docs_code("get_template_part('template-parts/content/card', null, [\n    'post'       => \$post,\n    'image_size' => 'large',\n    'knop_tekst' => '',\n    'is_preview' => \$is_preview,\n]);")],
            ]);

            $html .= '</div>';
        }

        /* ── STIJLGIDS (developer only) ──────────────────── */
        if (current_user_can('manage_options')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="stijlgids">';
            $html .= '<p class="mkbase-docs-intro">Alle bouwstenen van de MK-admin, met de klasse eronder. Gebruik deze klassen in nieuwe MK-pagina\'s, dan klopt de stijl vanzelf in licht en donker. Ze werken op pagina\'s met <code>body.mk-admin</code> (zie <code>mkbase_admin_pages()</code> in <code>inc/admin/ui.php</code>); de stijl staat in <code>assets/css/mk-admin.css</code>.</p>';
            $html .= mkbase_docs_stijlgids();
            $html .= '</div>';
        }

        /* ── AFBEELDINGEN (developer only) ───────────────── */
        if (current_user_can('manage_options')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="dev-afbeeldingen">';
            $html .= '<p class="mkbase-docs-intro"><code>mkbase_image()</code> zet een afbeelding uit de mediabibliotheek om naar een complete <code>&lt;img&gt;</code>-tag. Gebruik hem in blokken en templates altijd, in plaats van zelf een <code>&lt;img&gt;</code> te schrijven.</p>';

            $html .= mkbase_docs_section('dev-img-waarom', 'Waarom gebruiken we dit?', 'dashicons-lightbulb', [
                ['label' => 'Het probleem', 'desc' => 'Een eigen <code>' . esc_html('<img src="<?php echo $afbeelding[\'url\']; ?>">') . '</code> laadt altijd de originele, volledige foto, ook op een telefoon. Er staat geen breedte en hoogte op, dus de pagina verspringt tijdens het laden, en de alt-tekst moet je zelf ophalen. Dat moet je in elk blok opnieuw goed doen, en dat gaat snel mis.'],
                ['label' => 'De oplossing', 'desc' => 'Eén functie die dit altijd goed doet. Elke developer krijgt hetzelfde resultaat zonder erover na te denken, en elk blok is daardoor even snel en toegankelijk.'],
                ['label' => 'Op één plek aan te passen', 'desc' => 'Willen we later iets veranderen voor alle afbeeldingen (bijv. een extra attribuut of ander laadgedrag), dan passen we alleen <code>inc/helpers/image.php</code> aan in een core-update. Alle blokken en child themes nemen het dan automatisch over.'],
                ['label' => 'Waarom niet WordPress zelf?', 'desc' => 'WordPress heeft <code>wp_get_attachment_image()</code>, maar die werkt alleen met een ID en geeft niets terug voor SVG\'s. <code>mkbase_image()</code> gebruikt die functie, maar accepteert ook een ACF-array of URL en vangt SVG\'s en externe afbeeldingen op.'],
            ]);

            $html .= mkbase_docs_section('dev-img-gebruik', 'Gebruik', 'dashicons-format-image', [
                ['label' => 'Basis', 'desc' => mkbase_docs_code("<?php echo mkbase_image(\$afbeelding, 'large'); ?>") . '<p><code>$afbeelding</code> is een ACF-afbeeldingsveld (array, ID of URL) of een attachment-ID. Is het leeg, dan geeft de functie niets terug; een eigen <code>if</code> is niet nodig.</p>'],
                ['label' => 'Maat', 'desc' => '<p>Het tweede argument is de WordPress-afbeeldingsmaat. Kies de maat die het dichtst bij de weergavegrootte ligt; de browser kiest daarnaast zelf een kleinere versie op kleine schermen.</p>' . mkbase_docs_tabel([
                    ['thumbnail', 'Klein vierkant, bijv. een avatar'],
                    ['medium', 'Kleine afbeelding naast tekst'],
                    ['large', 'De meeste afbeeldingen in een blok'],
                    ['full', 'Het origineel; voor (bijna) de volle breedte'],
                ], ['Maat', 'Wanneer'])],
                ['label' => 'Class of attributen', 'desc' => mkbase_docs_code("mkbase_image(\$afbeelding, 'large', ['class' => 'mk-blok__afbeelding'])") . '<p>Voor een puur decoratieve afbeelding (bijv. een icoon naast tekst):</p>' . mkbase_docs_code("mkbase_image(\$icoon, 'thumbnail', ['alt' => ''])")],
                ['label' => 'Uitgelichte afbeelding', 'desc' => mkbase_docs_code("mkbase_image(get_post_thumbnail_id(\$post_id), 'large')")],
                ['label' => 'Formaat kiesbaar in het blok', 'desc' => '<p>Zet het keuzeveld <strong>Afbeeldingsformaat</strong> bij de ACF-velden van het blok (key, standaardformaat, veldnaam):</p>'
                    . mkbase_docs_code("mkbase_image_size_field('field_mk_team_afbeeldingsformaat', 'large', 'team_afbeeldingsformaat')")
                    . '<p>In de <code>render.php</code>:</p>' . mkbase_docs_code("mkbase_image(\$afbeelding, get_field('team_afbeeldingsformaat') ?: 'large')")
                    . '<p>Kies als standaard <code>full</code> voor afbeeldingen over (bijna) de volle breedte, anders <code>large</code> of <code>medium</code>. De Image Slider, Kolommen, Loop en 404-pagina hebben dit veld al.</p>'],
            ]);

            $html .= mkbase_docs_section('dev-img-wat', 'Wat doet het precies?', 'dashicons-performance', [
                ['label' => 'Stap voor stap', 'desc' => '1. Zoekt uit welk bestand het is: uit een ACF-array of ID haalt hij het ID; bij een URL zoekt hij het bijbehorende bestand in de mediabibliotheek op.<br>2. Maakt de <code>&lt;img&gt;</code> via WordPress, met alle onderstaande voordelen.<br>3. Bij een SVG of een URL buiten de mediabibliotheek kan WordPress dat niet; dan maakt hij zelf een <code>&lt;img&gt;</code> met alt-tekst, lazy loading en (bij een SVG) de breedte en hoogte uit het bestand.<br>4. Leeg veld of onbekend bestand: geeft niets terug.'],
                ['label' => 'Passende maat', 'desc' => 'Levert de kleinere versies mee die WordPress bij het uploaden aanmaakt (<code>srcset</code>). Een telefoon laadt zo niet de originele, grote foto.'],
                ['label' => 'Breedte en hoogte', 'desc' => 'Zet <code>width</code> en <code>height</code> op de afbeelding, zodat de pagina niet verspringt tijdens het laden (goed voor de Core Web Vitals van Google).'],
                ['label' => 'Alt-tekst en lazy loading', 'desc' => 'Neemt de alt-tekst over uit de mediabibliotheek en laadt afbeeldingen pas als ze in beeld komen.'],
                ['label' => 'SVG\'s', 'desc' => 'WordPress kent de maten van een SVG niet en kan er geen kleinere versies van maken. De functie toont dan het originele bestand, met de breedte en hoogte uit het SVG-bestand zelf (<code>width</code>/<code>height</code> of <code>viewBox</code>) en de alt-tekst uit de mediabibliotheek.'],
                ['label' => 'Externe afbeeldingen', 'desc' => '<p>Bij een URL buiten de mediabibliotheek zijn de maten onbekend. Geef ze dan zelf mee:</p>' . mkbase_docs_code("mkbase_image(\$url, 'large', ['width' => 800, 'height' => 600])")],
                ['label' => 'Child theme', 'desc' => 'Beschikbaar vanaf core 1.4.1. Het voorbeeldblok in het child theme (<code>template-parts/blocks/_voorbeeld/</code>) laat zien hoe je het gebruikt.'],
            ]);

            $html .= '</div>';
        }

        /* ── LIGHTBOX (developer only) ───────────────────── */
        if (current_user_can('manage_options')) {
            $html .= '<div class="mkbase-docs-panel" data-panel="lightbox">';
            $html .= '<p class="mkbase-docs-intro">Het thema heeft een eigen lightbox-laag, <code>mk-lightbox</code>, voor foto\'s, video, iframes en eigen HTML-content. Gebruik in blokken en child themes <strong>alleen deze laag</strong> — nooit de library eronder (Tobii) zelf. Zo kan die library later vervangen worden zonder dat blokken of websites aangepast hoeven te worden.</p>';

            $html .= mkbase_docs_section('lightbox-inladen', 'Inladen', 'dashicons-download', [
                ['label' => 'Script', 'desc' => mkbase_docs_code("wp_enqueue_script('mk-lightbox');") . '<p>Bij voorkeur in de <code>render.php</code> van het blok dat hem gebruikt. Zo laadt hij alleen op pagina\'s waar het blok staat.</p>'],
                ['label' => 'CSS', 'desc' => 'Hoeft niet apart ingeladen te worden: de CSS laadt automatisch mee met het script, en staat altijd vóór <code>style-main.css</code> van het child theme. Je eigen styling wint dus altijd.'],
            ]);

            $html .= mkbase_docs_section('lightbox-gebruik', 'Content openen', 'dashicons-format-gallery', [
                ['label' => 'Foto', 'desc' => mkbase_docs_code('<a href="foto.jpg" data-mk-lightbox><img src="foto-klein.jpg" alt="Bijschrift"></a>') . '<p><code>href</code> verwijst naar de grote afbeelding. Het bijschrift komt uit de <code>alt</code> van de <code>&lt;img&gt;</code> in de link.</p>'],
                ['label' => 'Video', 'desc' => mkbase_docs_code('<a href="https://youtu.be/ID" data-mk-lightbox data-mk-lightbox-type="video">Bekijk de video</a>') . '<p>Werkt met YouTube-, Vimeo- en eigen videolinks (bijv. <code>.mp4</code>). YouTube opent via <code>youtube-nocookie.com</code> en Vimeo met <code>dnt=1</code>, voor zo min mogelijk tracking.</p>'],
                ['label' => 'Iframe', 'desc' => mkbase_docs_code('<a href="https://..." data-mk-lightbox data-mk-lightbox-type="iframe">Bekijk de kaart</a>') . '<p>Bijvoorbeeld een kaart of een externe pagina.</p>'],
                ['label' => 'Eigen HTML', 'desc' => mkbase_docs_code('<a href="#element-id" data-mk-lightbox data-mk-lightbox-type="html">Open</a>') . '<p>Opent het element met dat id. Het element wordt verplaatst (niet gekopieerd), dus formulieren en scripts daarin blijven werken. Het verdwijnt daardoor ook van de pagina zelf.</p>'],
                ['label' => 'Galerij', 'desc' => '<p>Links met <strong>dezelfde waarde</strong> voor <code>data-mk-lightbox</code> vormen samen een galerij. Zonder waarde opent een link los. Geef elk blok een eigen waarde, bijv. met <code>wp_unique_id()</code>.</p>' . mkbase_docs_code('<a href="foto-1.jpg" data-mk-lightbox="project-1">...</a>' . "\n" . '<a href="foto-2.jpg" data-mk-lightbox="project-1">...</a>')],
            ]);

            $html .= mkbase_docs_section('lightbox-styling', 'Styling vanuit het child theme', 'dashicons-art', [
                ['label' => 'CSS-variabelen', 'desc' => '<p>Zet deze variabelen in het child theme (bijv. op <code>:root</code>). In het Mediakanjers child theme staan ze in <code>components/_lightbox.scss</code>.</p>' . mkbase_docs_tabel([
                    ['--mk-lightbox-background', 'De donkere laag over de pagina'],
                    ['--mk-lightbox-button-color', 'Sluiten, vorige en volgende'],
                    ['--mk-lightbox-nav-background', 'Achtergrond van vorige en volgende'],
                    ['--mk-lightbox-caption-background', 'Achtergrond van het bijschrift'],
                    ['--mk-lightbox-caption-color', 'Tekstkleur van het bijschrift'],
                    ['--mk-lightbox-counter-color', 'De teller ("3 / 8")'],
                    ['--mk-lightbox-z-index', 'Laag boven de rest van de pagina'],
                ], ['Variabele', 'Wat het is'])],
                ['label' => 'Detailstyling', 'desc' => 'Gebruik <code>.mk-lightbox</code> als basisselector. Gebruik niet Tobii\'s eigen <code>--tobii-*</code>-variabelen; die vallen weg als de library ooit vervangen wordt. Moet je toch een onderdeel van Tobii zelf stylen (bijv. <code>.mk-lightbox .tobii__btn--next</code>), houd dat dan beperkt — dat deel moet bij een overstap nagelopen worden.'],
            ]);

            $html .= mkbase_docs_section('lightbox-fancybox', 'Fancybox (verouderd)', 'dashicons-warning', [
                ['label' => 'Bestaande websites', 'desc' => 'Fancybox (<code>fancybox-js</code>) blijft werken op websites die hem al gebruiken, maar wordt niet meer onderhouden en werkt niet met jQuery 4. Gebruik voor nieuwe blokken altijd <code>mk-lightbox</code>. Hetzelfde geldt voor Owl Carousel (<code>owl-carousel-js</code>): gebruik voor nieuwe sliders Swiper 14 (<code>swiper-v14-js</code>).'],
                ['label' => 'CSS op bestaande websites', 'desc' => 'Laad je <code>owl-carousel-js</code>, <code>fancybox-js</code> of <code>swiper-js</code> via de core, dan komt de CSS vanzelf mee. Bundelt het child theme de library zelf, dan zet de core de CSS er alsnog bij zodra er een <code>owl-carousel</code>, <code>data-fancybox</code> of <code>swiper-container</code> op de pagina staat (tot en met 1.3.0 laadde die CSS op elke pagina). Wil je dat niet, bijv. omdat je een eigen versie gebruikt, schakel de stylesheet dan uit met <code>wp_deregister_style(\'owl-carousel-css\')</code>.'],
            ]);

            $html .= '</div>';
        }

        /* ── MOBIEL TESTEN (developer only) ──────────────── */
        if (current_user_can('manage_options')) {
            $guide_url = admin_url('admin.php?page=mkbase-mobile-guide');
            $html .= '<div class="mkbase-docs-panel" data-panel="mobiel-testen">';
            $html .= '<p class="mkbase-docs-intro">Handleiding voor het testen van een lokale WordPress-omgeving op een fysiek mobiel apparaat via ngrok. Inclusief installatie, Apache-configuratie en wp-config.php snippet.</p>';
            $html .= '<a href="' . esc_url($guide_url) . '" target="_blank" rel="noopener" class="mkbase-docs-settings-link">Open de handleiding &rarr;</a>';
            $html .= mkbase_docs_section('mobiel-samenvatting', 'Wat staat erin?', 'dashicons-smartphone', [
                ['label' => 'Wat is ngrok?', 'desc' => 'Uitleg over ngrok en waarom je het nodig hebt om een lokale site bereikbaar te maken op je telefoon.'],
                ['label' => 'Installatie (1× per computer)', 'desc' => 'ngrok installeren via winget, account aanmaken, statisch domein reserveren en authtoken koppelen.'],
                ['label' => 'Laragon &amp; Apache (1× per computer)', 'desc' => 'Centrale <code>ngrok.conf</code>, <code>ngrok-prepend.php</code> en <code>ngrok-site</code> script installeren in <code>C:\laragon\</code>.'],
                ['label' => 'Per project (geen actie)', 'desc' => 'Niets te doen — de centrale setup dekt elk project automatisch. Geen wp-config.php aanpassen.'],
                ['label' => 'Dagelijks gebruik', 'desc' => 'Twee stappen per testsessie: Laragon starten → <code>ngrok-site &lt;sitenaam&gt;</code> typen → vaste URL openen op telefoon.'],
                ['label' => 'Problemen oplossen', 'desc' => 'Veelvoorkomende fouten: redirect loop, afbeeldingen die niet laden, "command not found", versiefouten.'],
            ]);
            $html .= '</div>';
        }

        $html .= '<p class="mk-docs__empty" hidden>Niets gevonden. Probeer een ander woord.</p>';
        $html .= '<div class="mk-docs__help"><span>Kom je er niet uit? Mediakanjers helpt je graag verder.</span><a href="https://mediakanjers.nl/contact" target="_blank" rel="noopener">Neem contact op</a></div>';
        if (function_exists('mkbase_startlijst_hidden') && mkbase_startlijst_hidden()) {
            $html .= '<p class="mk-docs__restore">De startlijst op het dashboard is verborgen. <a href="' . esc_url(mkbase_startlijst_restore_url()) . '">Startlijst weer tonen</a></p>';
        }
        $html .= '</div>'; // #mkbase-docs-panels
        $html .= '</div>'; // #mkbase-docs-layout

        return $html;
    }
?>
