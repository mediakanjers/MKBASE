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