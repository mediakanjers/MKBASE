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
        add_theme_support('woocommerce');
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
?>