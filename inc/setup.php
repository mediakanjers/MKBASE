<?php
    // This theme requires ACF PRO to work properly
    if (!class_exists('ACF')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('Het MK Core thema vereist dat de plugin "Advanced Custom Fields" actief is. Activeer ACF om verder te gaan.', 'mk-core') . '</strong></p></div>';
        });
        add_action('admin_init', function() {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('Het MK Core thema vereist dat de plugin "Advanced Custom Fields" actief is. Neem contact op met de beheerder.', 'mk-core'));
            }
        });
        add_action('template_redirect', function() {
            if (!is_admin()) {
                wp_die(esc_html__('Deze website vereist dat de plugin "Advanced Custom Fields" actief is. Neem contact op met de beheerder.', 'mk-core'));
            }
        });
        return; 
    }

    // Add WooCommerce support only if WooCommerce is installed
    if ( class_exists( 'WooCommerce' ) ) {
        add_theme_support( 'woocommerce' );
    }

    // Adds standard functions to this theme
    function my_theme_setup() {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');

        // Laadt de site-CSS uitsluitend in de block-editor iframe-canvas (niet in de
        // admin-chrome eromheen) — zodat blok-previews (ACF Blocks V3) er hetzelfde
        // uitzien als op de live site, zonder WP's eigen admin-knoppen/UI te beïnvloeden.
        add_theme_support('editor-styles');
        add_editor_style('dist/css/style-main.css');

        // editor-blocks.css wordt óók via enqueue_block_editor_assets geladen (voor het
        // ACF-uitklap-paneel, dat buiten de iframe in het hoofd-adminvenster zit), maar
        // dát mechanisme bereikt de iframe zelf niet — alleen add_editor_style() wordt
        // door WP in de canvas-iframe gekloond. Nodig voor regels die de daadwerkelijke
        // blok-preview ín de canvas moeten stylen (bijv. het Image Slider-voorbeeld).
        // Let op: een thema-relatief pad, geen volledige URL — met een "https://"-string
        // behandelt WP dit als een extern stylesheet en haalt het via wp_remote_get() op,
        // wat op een lokale omgeving met een zelfondertekend certificaat kan mislukken.
        add_editor_style('assets/css/editor-blocks.css');
    }
    add_action( 'after_setup_theme', 'my_theme_setup' );

    add_action('wp_head', 'custom_favicon_from_acf');

    function custom_favicon_from_acf() {
        $favicon = get_field('favicon', 'option'); 
        if ($favicon && isset($favicon['url'])) {
            echo '<link rel="icon" href="' . esc_url($favicon['url']) . '" sizes="32x32">' . "\n";
            echo '<link rel="apple-touch-icon" href="' . esc_url($favicon['url']) . '">' . "\n";
        }
    }

    // Geavanceerd: code-injectie vanuit ACF optiepagina
    add_action('wp_head', function() {
        $code = get_field('in_de_head', 'option');
        if ($code) echo $code . "\n";
    });

    add_action('wp_body_open', function() {
        $code = get_field('direct_na_body', 'option');
        if ($code) echo $code . "\n";
    });

    add_action('wp_footer', function() {
        $code = get_field('voor_de_sluitende_body', 'option');
        if ($code) echo $code . "\n";

        if (is_front_page()) {
            $code = get_field('alleen_op_de_homepage', 'option');
            if ($code) echo $code . "\n";
        }

        $code = get_field('alleen_in_de_footer', 'option');
        if ($code) echo $code . "\n";
    });

    function mkbase_main_class() {
        return is_front_page() ? 'voorpagina' : 'vervolgpagina';
    }

    // Remove CSS GF
    add_filter( 'gform_disable_css', '__return_true' );

    // Preview mode uitschakelen — concepten/pagina's kunnen niet meer bekeken worden via ?preview=true
    add_action('template_redirect', function() {
        if (!is_preview()) return;

        $post     = get_queried_object();
        $redirect = ($post instanceof WP_Post && get_post_status($post) === 'publish')
            ? get_permalink($post)
            : home_url('/');

        wp_safe_redirect($redirect);
        exit;
    });

    // Verberg de preview-knop in de editor (klassieke metabox + blokkeneditor)
    function mkbase_hide_preview_button() {
        echo '<style>#preview-action, .editor-preview-dropdown { display: none !important; }</style>';
    }
    add_action('admin_head-post.php', 'mkbase_hide_preview_button');
    add_action('admin_head-post-new.php', 'mkbase_hide_preview_button');

    // Is de huidige gebruiker het Mediakanjers-beheeraccount? Wordt gebruikt om een paar
    // gevoelige instellingen (bestandseditor, paginastructuur vergrendelen) exclusief aan
    // dat account voor te behouden — andere beheerders/klanten zien deze opties niet.
    function mkbase_is_mediakanjers_admin() {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        return strtolower($user->user_login) === 'mediakanjers' && current_user_can('administrator');
    }

    // Thema-/plugin-bestandseditor: standaard voor iedereen uit, ook voor Mediakanjers —
    // alleen aan te zetten via de verborgen toggle in Geavanceerd (zichtbaar voor Mediakanjers).
    add_action('init', function() {
        if (defined('DISALLOW_FILE_EDIT')) return;

        if (mkbase_is_mediakanjers_admin() && function_exists('get_field') && get_field('bestandseditor_mediakanjers', 'option')) {
            return;
        }

        define('DISALLOW_FILE_EDIT', true);
    });

    // Toont de mkbase core-thema-versie naast WordPress' eigen "Versie X.X" rechtsonder
    // in wp-admin — get_template() i.p.v. get_stylesheet() zodat dit altijd het core
    // thema is, ook wanneer een child thema actief is.
    add_filter('update_footer', function($text) {
        $theme = wp_get_theme(get_template());
        return $text . ' | MKBase core thema versie: ' . esc_html($theme->get('Version'));
    }, 11);

    // Admin notices samenvouwen — alleen laden op pagina's waar notices verwacht worden
    add_action('admin_enqueue_scripts', function($hook) {
        $relevant = ['index.php', 'plugins.php', 'plugin-install.php', 'themes.php', 'update-core.php', 'options-general.php'];
        $is_mkbase = isset($_GET['page']) && strpos($_GET['page'], 'mkbase') === 0;
        if (!in_array($hook, $relevant, true) && !$is_mkbase) return;

        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_script('mk-admin-notices', $theme_uri . '/assets/js/admin-notices.js', [], filemtime($theme_dir . '/assets/js/admin-notices.js'), true);
        wp_enqueue_style('mk-admin-notices', $theme_uri . '/assets/css/admin-notices.css', [], filemtime($theme_dir . '/assets/css/admin-notices.css'));
    });
?>