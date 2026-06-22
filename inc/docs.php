<?php
    add_action('wp_dashboard_setup', function() {
        wp_add_dashboard_widget('mkbase_docs_widget', 'Mediakanjers', 'mkbase_render_dashboard_widget');

        // Zet de widget bovenaan
        global $wp_meta_boxes;
        $widget = $wp_meta_boxes['dashboard']['normal']['core']['mkbase_docs_widget'] ?? null;
        if ($widget) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['mkbase_docs_widget']);
            $wp_meta_boxes['dashboard']['normal']['core'] = array_merge(
                ['mkbase_docs_widget' => $widget],
                $wp_meta_boxes['dashboard']['normal']['core']
            );
        }
    });

    function mkbase_render_dashboard_widget() {
        $logo          = get_template_directory_uri() . '/assets/images/mk_logo_tekst.svg';
        $url_docs      = admin_url('admin.php?page=mkbase-docs');
        $url_changelog = admin_url('admin.php?page=mkbase-changelog');
        ?>
        <div class="mkbase-dash">
            <div class="mkbase-dash__header">
                <img src="<?php echo esc_url($logo); ?>" alt="Mediakanjers" class="mkbase-dash__logo">
                <div class="mkbase-dash__intro">
                    <h3>Welkom bij je website</h3>
                    <p>In de documentatie vind je uitleg over alle blokken en instellingen van dit thema.</p>
                </div>
                <div class="mkbase-dash__actions">
                    <a href="<?php echo esc_url($url_docs); ?>" class="mkbase-dash__btn mkbase-dash__btn--primary">Bekijk documentatie</a>
                    <a href="<?php echo esc_url($url_changelog); ?>" class="mkbase-dash__btn mkbase-dash__btn--secondary">Changelog</a>
                </div>
            </div>
            <div class="mkbase-dash__cards">
                <div class="mkbase-dash__card">
                    <span class="dashicons dashicons-book-alt"></span>
                    <div class="mkbase-dash__card-body">
                        <strong>Documentatie</strong>
                        <p>Uitleg over alle blokken, meldingen, instellingen en meer — overzichtelijk per onderwerp.</p>
                        <a href="<?php echo esc_url($url_docs); ?>">Ga naar documentatie &rarr;</a>
                    </div>
                </div>
                <div class="mkbase-dash__card">
                    <span class="dashicons dashicons-email-alt"></span>
                    <div class="mkbase-dash__card-body">
                        <strong>Vragen?</strong>
                        <p>Kom je er niet uit? Neem contact op met Mediakanjers, we helpen je graag verder.</p>
                        <a href="https://mediakanjers.nl" target="_blank" rel="noopener">Ga naar mediakanjers.nl &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

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

    add_action('admin_enqueue_scripts', function($hook) {
        if ($hook !== 'index.php') return;
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-admin-dashboard', $theme_uri . '/assets/css/admin-dashboard.css', [], filemtime($theme_dir . '/assets/css/admin-dashboard.css'));
    });

    add_action('admin_footer', function() {
        if (get_current_screen()->id !== 'dashboard') return;
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var w=document.getElementById("mkbase_docs_widget"),h=document.querySelector(".metabox-holder");if(w&&h)h.insertBefore(w,h.firstChild);});</script>';
    });

    add_action('admin_enqueue_scripts', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-docs') return;
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', [], null);
        wp_enqueue_style('mk-admin-shared', $theme_uri . '/assets/css/admin-shared.css', [], filemtime($theme_dir . '/assets/css/admin-shared.css'));
        wp_enqueue_style('mk-admin-docs', $theme_uri . '/assets/css/admin-docs.css', ['mk-admin-shared'], filemtime($theme_dir . '/assets/css/admin-docs.css'));
        wp_enqueue_script('mk-admin-docs', $theme_uri . '/assets/js/admin-docs.js', [], filemtime($theme_dir . '/assets/js/admin-docs.js'), true);
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

    function mkbase_docs_section($id, $title, $icon, $items) {
        $html  = '<div class="mkbase-docs-section" data-section="' . esc_attr($id) . '">';
        $html .= '<button class="mkbase-docs-toggle" type="button" aria-expanded="false">';
        $html .= '<span class="mkbase-docs-toggle__icon dashicons ' . esc_attr($icon) . '"></span>';
        $html .= '<span class="mkbase-docs-toggle__title">' . esc_html($title) . '</span>';
        $html .= '<span class="mkbase-docs-toggle__arrow dashicons dashicons-arrow-down-alt2"></span>';
        $html .= '</button>';
        $html .= '<div class="mkbase-docs-body" style="display:none;">';
        $html .= '<dl class="mkbase-docs-list">';
        foreach ($items as $item) {
            $html .= '<div class="mkbase-docs-item">';
            $html .= '<dt>' . $item['label'] . '</dt>';
            $html .= '<dd>' . $item['desc'] . '</dd>';
            $html .= '</div>';
        }
        $html .= '</dl>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    function mkbase_get_docs_html() {
        $html  = '<div class="mk-cover">';
        $html .= '<div class="mk-cover__eyebrow">MKBase Theme &nbsp;·&nbsp; Mediakanjers</div>';
        $html .= '<div class="mk-cover__title">Documentatie</div>';
        $html .= '<div class="mk-cover__sub">Uitleg over alle instellingen, blokken en mogelijkheden van het MKBase thema — rechtstreeks in je WordPress admin.</div>';
        $html .= '<div class="mk-cover__tags">';
        $html .= '<span class="mk-cover__tag">Website instellingen</span>';
        $html .= '<span class="mk-cover__tag">Menu\'s</span>';
        $html .= '<span class="mk-cover__tag">Blokken</span>';
        $html .= '<span class="mk-cover__tag">Afbeeldingen</span>';
        if (class_exists('GFForms'))      $html .= '<span class="mk-cover__tag">Gravity Forms</span>';
        if (defined('WP_ROCKET_VERSION')) $html .= '<span class="mk-cover__tag">WP Rocket</span>';
        if (defined('WPSEO_VERSION'))     $html .= '<span class="mk-cover__tag">Yoast SEO</span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= mkbase_admin_page_nav('mkbase-docs');

        // Groepen voor de zijbalk
        $groups = [
            'Thema' => [
                'instellingen' => 'Website instellingen',
                'menus'        => "Menu's",
                'geavanceerd'  => 'Geavanceerd',
                'meldingen'    => 'Meldingen',
                '404'          => '404',
            ],
            'Inhoud' => [
                'blokken'      => 'Blokken',
                'afbeeldingen' => 'Afbeeldingen',
            ],
        ];

        $plugins = [];
        if (class_exists('GFForms'))      $plugins['gravity-forms'] = 'Gravity Forms';
        if (defined('WP_ROCKET_VERSION')) $plugins['wp-rocket']     = 'WP Rocket';
        if (defined('WPSEO_VERSION'))     $plugins['yoast-seo']     = 'Yoast SEO';
        if (!empty($plugins))             $groups['Plugins']        = $plugins;

        if (current_user_can('manage_options')) {
            $groups['Developer'] = [
                'mobiel-testen' => 'Mobiel testen',
            ];
        }

        // Zijbalk
        $html .= '<div id="mkbase-docs-layout">';
        $html .= '<nav id="mkbase-docs-sidebar">';
        $first = true;
        foreach ($groups as $group_label => $items) {
            $extra = $group_label === 'Developer' ? ' mkbase-docs-group--developer' : '';
            $html .= '<div class="mkbase-docs-group' . $extra . '">';
            $html .= '<span class="mkbase-docs-group__label">' . esc_html($group_label) . '</span>';
            foreach ($items as $key => $label) {
                $active = $first ? ' is-active' : '';
                $html  .= '<button class="mkbase-docs-nav-item' . $active . '" data-panel="' . esc_attr($key) . '">' . esc_html($label) . '</button>';
                $first  = false;
            }
            $html .= '</div>';
        }
        $html .= '</nav>';
        $html .= '<div id="mkbase-docs-panels">';

        /* ── INSTELLINGEN ─────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel is-active" data-panel="instellingen">';
        $html .= '<p class="mkbase-docs-intro">Stel hier de algemene website-informatie in. Deze gegevens zijn beschikbaar via ACF en kunnen overal in het thema worden gebruikt.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-website-instellingen'), 'Ga naar Website instellingen');

        $html .= mkbase_docs_section('logo', 'Logo & favicon', 'dashicons-format-image', [
            ['label' => 'Logo', 'desc' => 'Upload het logo van de website. Dit logo wordt gebruikt in de header. Aanbevolen formaat: SVG of PNG met transparante achtergrond.'],
            ['label' => 'Favicon', 'desc' => 'Het kleine icoon dat in de browsertab verschijnt. Gebruik bij voorkeur een vierkante afbeelding van minimaal 512×512 pixels (PNG of ICO).'],
        ]);

        $html .= mkbase_docs_section('bedrijf', 'Bedrijfsgegevens', 'dashicons-building', [
            ['label' => 'Bedrijfsnaam', 'desc' => 'De officiële naam van het bedrijf. Wordt gebruikt in de footer, contactpagina en metadata.'],
            ['label' => 'Adres', 'desc' => 'Straatnaam en huisnummer van het bedrijf.'],
            ['label' => 'Postcode', 'desc' => 'Postcode van het bedrijf (bijv. <code>1234 AB</code>).'],
            ['label' => 'Telefoon', 'desc' => 'Hoofdtelefoonnummer. Gebruik het internationaal formaat voor klikbare links (bijv. <code>+31 20 123 4567</code>).'],
            ['label' => 'Email', 'desc' => 'Algemeen e-mailadres van het bedrijf.'],
            ['label' => 'Mobiel', 'desc' => 'Mobiel telefoonnummer, indien van toepassing.'],
        ]);

        $html .= mkbase_docs_section('socials', 'Sociale media', 'dashicons-share', [
            ['label' => 'Socials', 'desc' => 'Voeg sociale mediaprofielen toe via de repeater. Kies per rij het platform (Discord, Facebook, Instagram, LinkedIn, Snapchat, TikTok, WhatsApp, X, YouTube) en vul de volledige URL in. Voeg meerdere rijen toe voor meerdere platformen.'],
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
        $html .= '<p class="mkbase-docs-intro">Via het Geavanceerd-menu kun je code-snippets toevoegen aan de HTML-output van de website. Dit is bedoeld voor tracking pixels, tag managers en andere externe scripts. <strong>Gebruik dit menu alleen als je weet wat je doet.</strong></p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-geavanceerd'), 'Ga naar Geavanceerd');

        $html .= mkbase_docs_section('head', 'In de &lt;head&gt;', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor scripts die zo vroeg mogelijk geladen moeten worden, zoals Google Tag Manager (<code>&lt;script&gt;</code>-gedeelte) of Google Analytics.'],
            ['label' => 'Voorbeeld', 'desc' => '<code>&lt;script&gt;(function(w,d,s,l,i){...})&lt;/script&gt;</code>'],
        ]);

        $html .= mkbase_docs_section('body-open', 'Direct na &lt;body&gt;', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor de <code>&lt;noscript&gt;</code>-fallback van Google Tag Manager of andere iframe-gebaseerde trackers die direct na de openings-tag geplaatst moeten worden.'],
        ]);

        $html .= mkbase_docs_section('body-close', 'Voor de sluitende &lt;/body&gt;', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Voor niet-kritische scripts die pas aan het einde van de pagina geladen hoeven te worden, zoals chatwidgets of pop-uptools.'],
        ]);

        $html .= mkbase_docs_section('homepage-only', 'Alleen op de homepage', 'dashicons-admin-home', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Code die uitsluitend op de homepagina zichtbaar of actief moet zijn. De code wordt geladen in de footer, maar alleen als de bezoeker op de voorpagina zit.'],
        ]);

        $html .= mkbase_docs_section('footer-only', 'Alleen in de footer', 'dashicons-editor-code', [
            ['label' => 'Wanneer gebruiken?', 'desc' => 'Extra code die sowieso in de footer geladen mag worden, ongeacht de pagina. Geschikt voor scripts die op elke pagina actief moeten zijn maar niet tijdkritisch zijn.'],
        ]);

        $html .= '</div>';

        /* ── MELDINGEN ────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="meldingen">';
        $html .= '<p class="mkbase-docs-intro">De notificatiebalk toont een opvallende melding bovenaan de website. Ideaal voor tijdelijke berichten, acties of belangrijke mededelingen.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-meldingen'), 'Ga naar Meldingen');

        $html .= mkbase_docs_section('melding-tekst', 'Tekst', 'dashicons-editor-paragraph', [
            ['label' => 'Tekst melding', 'desc' => 'De inhoud van de melding. Je kunt tekst opmaken via de editor (vet, cursief, links). Houd de tekst kort en krachtig voor de beste leesbaarheid.'],
        ]);

        $html .= mkbase_docs_section('melding-data', 'Data', 'dashicons-calendar-alt', [
            ['label' => 'Data opgeven', 'desc' => 'Kies <strong>Ja</strong> om de melding automatisch op een bepaalde periode te tonen of te verbergen. Kies <strong>Nee</strong> om de melding altijd te tonen (zolang "Melding tonen" op Ja staat).'],
            ['label' => 'Begindatum', 'desc' => 'De datum vanaf wanneer de melding zichtbaar is. Verschijnt alleen als "Data opgeven" op Ja staat.'],
            ['label' => 'Einddatum', 'desc' => 'De datum tot wanneer de melding zichtbaar is. Na deze datum verdwijnt de melding automatisch. Verschijnt alleen als "Data opgeven" op Ja staat.'],
        ]);

        $html .= mkbase_docs_section('melding-opmaak', 'Opmaak', 'dashicons-art', [
            ['label' => 'Achtergrondkleur', 'desc' => 'De achtergrondkleur van de notificatiebalk. Kies een kleur die opvalt maar wel bij de huisstijl past.'],
            ['label' => 'Tekstkleur', 'desc' => 'De kleur van de tekst in de melding. Zorg voor voldoende contrast met de achtergrondkleur (minimaal 4,5:1 contrastverhouding voor toegankelijkheid).'],
        ]);

        $html .= mkbase_docs_section('melding-zichtbaarheid', 'Zichtbaarheid', 'dashicons-visibility', [
            ['label' => 'Waar zichtbaar', 'desc' => 'Selecteer op welke pagina\'s de melding getoond wordt. Laat het veld leeg om de melding op <strong>alle pagina\'s</strong> te tonen. Selecteer een of meerdere pagina\'s om de melding alleen daar te tonen.'],
            ['label' => 'Melding tonen', 'desc' => 'De hoofdschakelaar. Zet dit op <strong>Ja</strong> om de melding te activeren. Staat dit op Nee, dan is de melding nergens zichtbaar, ongeacht de overige instellingen.'],
        ]);

        $html .= mkbase_docs_section('melding-voorbeeld', 'Voorbeeld', 'dashicons-welcome-view-site', [
            ['label' => 'Notificatie voorbeeld', 'desc' => 'In het tabblad "Voorbeeld" zie je direct hoe de melding eruit zal zien met de huidige kleur- en tekstinstellingen. Er is geen aparte opslaan-actie nodig — sla de pagina op en het voorbeeld wordt bijgewerkt.'],
        ]);

        $html .= '</div>';

        /* ── 404 ──────────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="404">';
        $html .= '<p class="mkbase-docs-intro">Pas hier de inhoud van de 404-pagina aan. Deze pagina verschijnt wanneer een bezoeker een URL bezoekt die niet bestaat.</p>';
        $html .= mkbase_docs_settings_link(admin_url('admin.php?page=acf-options-404'), 'Ga naar 404 instellingen');

        $html .= mkbase_docs_section('404-inhoud', 'Inhoud', 'dashicons-warning', [
            ['label' => 'Titel', 'desc' => 'De hoofdtitel op de 404-pagina, bijv. <em>"Oeps, deze pagina bestaat niet."</em>'],
            ['label' => 'Tekst', 'desc' => 'Een korte uitleg of tip voor de bezoeker, bijv. een verwijzing naar de homepage of de zoekopdracht.'],
            ['label' => 'Afbeelding', 'desc' => 'Een optionele illustratie of afbeelding die de 404-pagina visueel ondersteunt.'],
        ]);

        $html .= '</div>';

        /* ── BLOKKEN ──────────────────────────────────────── */
        $html .= '<div class="mkbase-docs-panel" data-panel="blokken">';
        $html .= '<p class="mkbase-docs-intro">De MKBase blokken zijn beschikbaar via de Gutenberg-editor onder de categorie <strong>Mediakanjers</strong>. Voeg een blok toe via het <strong>+</strong>-icoon in de editor en zoek op de naam van het blok.</p>';

        $html .= mkbase_docs_section('blok-breadcrumbs', 'Breadcrumbs', 'dashicons-arrow-right-alt', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont het navigatiepad van de huidige pagina, ook wel een "broodkruimelpad" genoemd. Bezoekers zien zo altijd waar ze zich bevinden binnen de website.'],
            ['label' => 'Hoe gebruiken?', 'desc' => 'Voeg het blok toe aan een pagina of template. Er zijn geen instellingen — het pad wordt automatisch gegenereerd op basis van de paginastructuur.'],
            ['label' => 'Tip', 'desc' => 'Zet de breadcrumbs bovenaan de pagina-inhoud, vóór de hoofdtitel. Zo geven ze de meeste navigatiewaarde.'],
        ]);

        $html .= mkbase_docs_section('blok-loop', 'Post type loop', 'dashicons-list-view', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont een grid van berichten of een ander post type. Ideaal voor nieuwsoverzichten, productlijsten of portfoliopagina\'s.'],
            ['label' => 'Post type', 'desc' => 'Kies welk post type getoond wordt (bijv. Berichten, Pagina\'s of een eigen post type). De keuze wordt automatisch gevuld met alle beschikbare post types.'],
            ['label' => 'Aantal berichten', 'desc' => 'Bepaal hoeveel berichten er getoond worden. Gebruik <code>-1</code> om alle berichten te tonen.'],
            ['label' => 'Handmatige selectie', 'desc' => 'Selecteer specifieke berichten die je wilt tonen. Zodra je berichten selecteert, worden het ingestelde post type en aantal genegeerd — de handmatige selectie heeft prioriteit.'],
            ['label' => 'Kolommen', 'desc' => 'Kies hoeveel kolommen het grid heeft: 1, 2, 3 of 4. Op mobiel worden de kolommen automatisch teruggebracht naar 1.'],
            ['label' => 'Breedte', 'desc' => '<strong>Ingesloten</strong>: de loop past binnen de standaard paginabreedte. <strong>Volledige breedte</strong>: de loop strekt zich uit tot de rand van het scherm.'],
            ['label' => 'Toon knop', 'desc' => 'Schakel in om per kaart een knop te tonen. Handig als je bezoekers expliciet wilt aanmoedigen door te klikken.'],
            ['label' => 'Knoptekst', 'desc' => 'De tekst op de knop. Standaard is dit "Meer lezen". Pas aan naar keuze, bijv. "Bekijk product" of "Lees verder". Zichtbaar zodra "Toon knop" ingeschakeld is.'],
        ]);

        $html .= mkbase_docs_section('blok-slider', 'Image Slider', 'dashicons-images-alt2', [
            ['label' => 'Wat doet het?', 'desc' => 'Toont een scrollbare slider met afbeeldingen. Geschikt voor galerijen, logo\'swanden of afbeeldingscarrousels.'],
            ['label' => 'Items desktop', 'desc' => 'Hoeveel afbeeldingen er tegelijk zichtbaar zijn op een desktopscherm (1–4).'],
            ['label' => 'Items tablet', 'desc' => 'Hoeveel afbeeldingen er tegelijk zichtbaar zijn op een tabletscherm (1–3). Op mobiel wordt altijd 1 item tegelijk getoond.'],
            ['label' => 'Autoplay', 'desc' => 'Schakel in om de slider automatisch door de afbeeldingen te laten scrollen. De gebruiker kan de slider altijd handmatig bedienen.'],
            ['label' => 'Aspect ratio', 'desc' => 'Bepaalt de verhouding van elke slide. Kies uit Vierkant (1:1), Standaard (4:3), Portret (3:4), Klassiek (3:2), Klassiek portret (2:3), Breed (16:9) of Groot (9:16). Laat leeg om de originele afmetingen van de afbeelding te gebruiken.'],
            ['label' => 'Slides', 'desc' => 'Voeg afbeeldingen toe via de repeater. Klik op "Nieuwe rij" om een extra slide toe te voegen. Sleep de rijen om de volgorde te wijzigen.'],
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
        ]);

        $html .= mkbase_docs_section('afb-alt', 'Alt-tekst', 'dashicons-editor-textcolor', [
            ['label' => 'Wat is alt-tekst?', 'desc' => 'Alt-tekst (alternatieve tekst) beschrijft wat er op een afbeelding te zien is. Zoekmachines lezen deze tekst, en schermlezers spreken hem uit voor bezoekers met een visuele beperking.'],
            ['label' => 'Hoe invullen?', 'desc' => 'Beschrijf de afbeelding kort en concreet. Slechte alt-tekst: <em>"afbeelding1.jpg"</em>. Goede alt-tekst: <em>"Twee medewerkers overleggen aan een bureau"</em>.'],
            ['label' => 'Waar invullen?', 'desc' => 'Klik op een afbeelding in de mediabibliotheek en vul het veld <strong>"Alternatieve tekst"</strong> in het rechter paneel in. Dit hoeft maar één keer per afbeelding.'],
            ['label' => 'Decoratieve afbeeldingen', 'desc' => 'Is een afbeelding puur decoratief en voegt ze geen inhoudelijke waarde toe? Laat de alt-tekst dan leeg — schermlezers slaan hem dan over.'],
        ]);

        $html .= '</div>';

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

        /* ── MOBIEL TESTEN (developer only) ──────────────── */
        if (current_user_can('manage_options')) {
            $guide_url = admin_url('admin.php?page=mkbase-mobile-guide');
            $html .= '<div class="mkbase-docs-panel" data-panel="mobiel-testen">';
            $html .= '<p class="mkbase-docs-intro">Handleiding voor het testen van een lokale WordPress-omgeving op een fysiek mobiel apparaat via ngrok. Inclusief installatie, Apache-configuratie en wp-config.php snippet.</p>';
            $html .= '<a href="' . esc_url($guide_url) . '" target="_blank" rel="noopener" class="mkbase-docs-settings-link">Open de handleiding &rarr;</a>';
            $html .= mkbase_docs_section('mobiel-samenvatting', 'Wat staat erin?', 'dashicons-smartphone', [
                ['label' => 'Wat is ngrok?', 'desc' => 'Uitleg over ngrok en waarom je het nodig hebt om een lokale site bereikbaar te maken op je telefoon.'],
                ['label' => 'Installatie (1× per computer)', 'desc' => 'ngrok installeren via winget, account aanmaken, statisch domein reserveren en authtoken koppelen.'],
                ['label' => 'Laragon & Apache (1× per computer)', 'desc' => 'Centrale <code>ngrok.conf</code>, <code>ngrok-prepend.php</code> en <code>ngrok-site</code> script installeren in <code>C:\laragon\</code>.'],
                ['label' => 'Per project (geen actie)', 'desc' => 'Niets te doen — de centrale setup dekt elk project automatisch. Geen wp-config.php aanpassen.'],
                ['label' => 'Dagelijks gebruik', 'desc' => 'Twee stappen per testsessie: Laragon starten → <code>ngrok-site &lt;sitenaam&gt;</code> typen → vaste URL openen op telefoon.'],
                ['label' => 'Problemen oplossen', 'desc' => 'Veelvoorkomende fouten: redirect loop, afbeeldingen die niet laden, "command not found", versiefouten.'],
            ]);
            $html .= '</div>';
        }

        $html .= '</div>'; // #mkbase-docs-panels
        $html .= '</div>'; // #mkbase-docs-layout

        return $html;
    }
?>
