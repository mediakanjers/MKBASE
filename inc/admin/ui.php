<?php
    // =============================================================================
    // MK admin — gedeelde basis voor alle Mediakanjers-pagina's
    // =============================================================================
    // De MK-balk (kruimelpad, meldingen, opslaan, tabbladen), de body-class `mk-admin`
    // waarop assets/css/mk-admin.css is gescoped, en donkere modus per gebruiker.

    // Alle MK-pagina's, in de volgorde van de tabbladen. 'acf' = instellingenpagina met opslaan,
    // 'intro' = korte uitleg onder de paginatitel, 'note' = waarschuwing boven de kaarten.
    function mkbase_admin_pages() {
        return [
            'acf-options-website-instellingen' => ['label' => 'Website instellingen', 'acf' => true,
                'intro' => 'De gegevens van je bedrijf en je huisstijl. Ze worden op de hele website gebruikt, bijvoorbeeld in de header en de footer.'],
            'acf-options-geavanceerd'          => ['label' => 'Geavanceerd',          'acf' => true,
                'intro' => 'Code voor tracking en externe diensten, en de instellingen van de editor.',
                'note'  => '<strong>Let op:</strong> een fout in deze code kan de hele website breken. Twijfel je, vraag het dan aan Mediakanjers.'],
            'acf-options-meldingen'            => ['label' => 'Meldingen',            'acf' => true,
                'intro' => 'Toon een balk boven- of onderaan de website, voor een actie, gewijzigde openingstijden of een storing. Plan ze vooraf in en kies per melding waar hij verschijnt.'],
            'acf-options-404'                  => ['label' => '404',                  'acf' => true,
                'intro' => 'Wat een bezoeker ziet als een pagina niet bestaat. Een vriendelijke tekst en een duidelijke weg terug houden bezoekers op je website.'],
            'mkbase-changelog'                 => ['label' => 'Changelog',            'acf' => false,
                'intro' => 'Alles wat er per versie van het MKBase-thema is veranderd.'],
            'mkbase-docs'                      => ['label' => 'Documentatie',         'acf' => false],
            'mkbase-diagnose'                  => ['label' => 'Diagnose',             'acf' => false,
                'intro' => 'Controleert omgeving, performance, server en het thema. Bevindingen staan ook in de PHP-errorlog, zodat je ze ziet als de admin niet bereikbaar is.'],
        ];
    }

    // Slug van de huidige MK-pagina, of null buiten de MK-pagina's
    function mkbase_admin_current_page() {
        if (!is_admin()) return null;
        $page = sanitize_key($_GET['page'] ?? '');
        return isset(mkbase_admin_pages()[$page]) ? $page : null;
    }

    function mkbase_admin_is_dark() {
        return (bool) get_user_meta(get_current_user_id(), 'mkbase_dark', true);
    }

    add_filter('admin_body_class', function ($classes) {
        $page = mkbase_admin_current_page();
        if (!$page) return $classes;
        $classes .= ' mk-admin mk-page-' . $page;
        if (mkbase_admin_is_dark()) $classes .= ' mk-dark dark';
        return $classes;
    });

    add_action('admin_enqueue_scripts', function () {
        $page = mkbase_admin_current_page();
        if (!$page) return;

        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_style('mk-admin-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap', [], null);
        wp_enqueue_style('mk-admin', $theme_uri . '/assets/css/mk-admin.css', ['mk-admin-fonts'], mkbase_asset_version($theme_dir . '/assets/css/mk-admin.css'));
        wp_enqueue_script('mk-admin', $theme_uri . '/assets/js/mk-admin.js', [], mkbase_asset_version($theme_dir . '/assets/js/mk-admin.js'), true);
        wp_localize_script('mk-admin', 'mkAdmin', [
            'ajax'   => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce('mkbase_admin'),
            'isAcf'  => mkbase_admin_pages()[$page]['acf'],
            'saved'  => mkbase_admin_pages()[$page]['acf'] && ($_GET['message'] ?? '') === '1',
            'rocket' => function_exists('rocket_clean_domain'),
            'site'   => home_url('/'),
            'groups' => mkbase_admin_group_descriptions(),
            'intro'  => mkbase_admin_pages()[$page]['intro'] ?? '',
            'note'   => mkbase_admin_pages()[$page]['note'] ?? '',
            'live'   => $page === 'acf-options-website-instellingen' ? mkbase_admin_live_preview_data() : null,
            'socials' => $page === 'acf-options-website-instellingen' ? mkbase_admin_social_icons() : null,
            'hiddenNotices' => mkbase_admin_hidden_notices(),
        ]);

        // Oude pagina-scripts lezen de donkere modus nog uit localStorage: gelijk houden met de gebruiker
        wp_add_inline_script('mk-admin', 'try{localStorage.setItem("mkbase-dark","' . (mkbase_admin_is_dark() ? '1' : '0') . '")}catch(e){}', 'before');
    }, 5);

    // Live voorbeeld bij Website instellingen: de menu's zoals het child theme ze toont (header-default, nav-footer)
    function mkbase_admin_live_preview_data() {
        $labels = function ($menu) {
            $items = wp_get_nav_menu_items($menu) ?: [];
            $top = array_filter($items, function ($item) { return !$item->menu_item_parent; });
            return array_values(array_map(function ($item) { return wp_strip_all_tags($item->title); }, array_slice($top, 0, 5)));
        };
        return [
            'name'   => get_bloginfo('name'),
            'menu'   => $labels('Hoofdmenu'),
            'footer' => $labels('Footermenu'),
        ];
    }

    // De echte icoontjes van de sociale media, zoals de website ze toont (template-parts/contact/socials.php
    // van het child theme). Eerst die van het child theme, anders de reservekopie in het core thema.
    function mkbase_admin_social_icons() {
        $icons = [];
        foreach (['discord', 'facebook', 'instagram', 'linkedin', 'snapchat', 'tiktok', 'whatsapp', 'x', 'youtube'] as $platform) {
            foreach ([get_stylesheet_directory(), get_template_directory()] as $dir) {
                $file = $dir . '/assets/images/socials/' . $platform . '.svg';
                if (!is_readable($file)) continue;
                // Alleen het svg-element, zonder de xml-declaratie ervoor
                if (preg_match('/<svg\b.*<\/svg>/is', (string) file_get_contents($file), $m)) $icons[$platform] = $m[0];
                break;
            }
        }
        return $icons;
    }

    // Beschrijving per veldgroep ('description' in inc/acf/fields.php), links in de kaart getoond
    function mkbase_admin_group_descriptions() {
        if (!function_exists('acf_get_field_groups')) return [];
        $out = [];
        foreach (acf_get_field_groups() as $group) {
            if (!empty($group['description'])) $out['acf-' . $group['key']] = $group['description'];
        }
        return $out;
    }

    // De MK-balk, bovenaan #wpcontent (in_admin_header draait vóór #wpbody)
    add_action('in_admin_header', function () {
        $current = mkbase_admin_current_page();
        if (!$current) return;
        $pages = mkbase_admin_pages();
        $dark  = mkbase_admin_is_dark();
        ?>
        <div class="mk-bar">
            <div class="mk-bar__inner">
                <a class="mk-bar__logo" href="<?php echo esc_url(admin_url()); ?>" aria-label="Naar het dashboard">
                    <svg width="32" height="32" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="16" fill="#29A8E0"/><text x="16" y="21" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-weight="800" font-size="13" fill="#fff">MK</text></svg>
                </a>
                <nav class="mk-bar__crumbs" aria-label="Kruimelpad">
                    <span>Mediakanjers</span><span aria-hidden="true">/</span><span aria-current="page"><?php echo esc_html($pages[$current]['label']); ?></span>
                </nav>
                <div class="mk-bar__actions">
                    <button type="button" class="mk-bar__notices" aria-expanded="false" aria-controls="mk-notices-panel" hidden>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="mk-bar__notices-label"></span>
                    </button>
                    <span class="mk-bar__dirty" hidden><span aria-hidden="true"></span>Niet-opgeslagen wijzigingen</span>
                    <button type="button" class="mk-bar__dark" aria-pressed="<?php echo $dark ? 'true' : 'false'; ?>" aria-label="Donkere modus" title="Donkere modus">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    </button>
                    <?php if ($pages[$current]['acf']): ?>
                        <button type="button" class="mk-btn mk-btn--secondary mk-bar__cancel" hidden>Annuleren</button>
                        <button type="button" class="mk-btn mk-bar__save" title="Opslaan (Ctrl+S)">Opslaan <kbd>Ctrl S</kbd></button>
                    <?php endif; ?>
                </div>
                <div id="mk-notices-panel" class="mk-notices-panel" role="region" aria-label="WordPress-meldingen" hidden>
                    <div class="mk-notices-panel__head"><strong>WordPress-meldingen</strong></div>
                    <div class="mk-notices-panel__list"></div>
                </div>
            </div>
            <nav class="mk-bar__tabs" aria-label="Mediakanjers">
                <?php foreach ($pages as $slug => $page): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html($page['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    });

    // Donkere modus aan/uit, onthouden bij de gebruiker
    add_action('wp_ajax_mkbase_set_dark', function () {
        check_ajax_referer('mkbase_admin');
        $on = !empty($_POST['dark']);
        update_user_meta(get_current_user_id(), 'mkbase_dark', $on ? 1 : 0);
        wp_send_json_success(['dark' => $on]);
    });

    // Een sectie (kaart met titel) in de MK-stijl, voor nieuwe MK-pagina's. $content is HTML en wordt
    // niet geëscaped. Voorbeelden van alle bouwstenen: Documentatie → Developer → Stijlgids.
    function mkbase_admin_section($title, $content, $class = '') {
        return '<section class="mk-section' . ($class ? ' ' . esc_attr($class) : '') . '">'
            . '<h2 class="mk-h2">' . esc_html($title) . '</h2>'
            . $content
            . '</section>';
    }

    // Verborgen WordPress-meldingen, per gebruiker. De meeste plugins zetten hun melding op elke pagina
    // opnieuw neer; daarom onthoudt het thema zelf welke je hebt verborgen (een kenmerk van de tekst,
    // zie noticeKey() in mk-admin.js en admin-notices.js). Verandert de tekst, dan komt de melding terug.
    function mkbase_admin_hidden_notices() {
        $keys = get_user_meta(get_current_user_id(), 'mkbase_verborgen_meldingen', true);
        return is_array($keys) ? array_values($keys) : [];
    }

    add_action('wp_ajax_mkbase_notices', function () {
        check_ajax_referer('mkbase_admin');
        // De pagina stuurt steeds de hele lijst (één verzoek, na een korte pauze), zodat snel na elkaar
        // wegklikken elkaar niet kan overschrijven
        $keys = array_slice(array_values(array_unique(array_filter(array_map('sanitize_key', (array) ($_POST['keys'] ?? []))))), -200);
        update_user_meta(get_current_user_id(), 'mkbase_verborgen_meldingen', $keys);
        wp_send_json_success(['hidden' => $keys]);
    });

    // Na opslaan van een MK-instellingenpagina de cache van de website legen
    // (Meldingen doet dat zelf, met de planning erbij: zie inc/notifications.php)
    add_action('acf/save_post', function ($post_id) {
        if ($post_id !== 'options') return;
        $page = mkbase_admin_current_page();
        if (!$page || $page === 'acf-options-meldingen') return;
        if (function_exists('rocket_clean_domain')) rocket_clean_domain();
    }, 20);

    // "Opslaan en verder" in het venster bij weggaan: na opslaan door naar de gekozen pagina
    add_filter('wp_redirect', function ($location) {
        if (empty($_POST['mk_redirect']) || !mkbase_admin_current_page()) return $location;
        $target = wp_validate_redirect(esc_url_raw(wp_unslash($_POST['mk_redirect'])), '');
        return $target ?: $location;
    });
?>
