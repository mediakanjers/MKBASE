<?php
    // =============================================================================
    // Enqueue Styles (Front-end)
    // =============================================================================

    function mkbase_enqueue_styles() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        $child_uri = get_stylesheet_directory_uri();
        $child_dir = get_stylesheet_directory();

        // Core theme style.css
        wp_enqueue_style( 'mk-core-style', $theme_uri . '/style.css', array(), filemtime($theme_dir . '/style.css'));

        // Child theme style.css
        wp_enqueue_style( 'mk-child-style', $child_uri . '/style.css', array('mk-core-style'), filemtime($child_dir . '/style.css') );

        // Vendor styles
        wp_enqueue_style('fancybox-css', $theme_uri . '/assets/vendor/fancybox/jquery.fancybox.min.css');
        wp_enqueue_style('owl-carousel-css', $theme_uri . '/assets/vendor/owl/owl.carousel.min.css');
        wp_enqueue_style('swiper-css', $theme_uri . '/assets/vendor/swiper/swiper.min.css');

        // Core component: notifications
        $customcssoverwrite = get_field('css_overschrijven', 'option');
        if($customcssoverwrite == 'Nee') {
            wp_enqueue_style('mk-notifications-css', $theme_uri . '/assets/css/components/notifications.css', array('mk-core-style'), filemtime($theme_dir . '/assets/css/components/notifications.css'));
        };

        // Main compiled SCSS from /dist
        wp_enqueue_style( 'mkbase-style-main', $child_uri . '/dist/css/style-main.css', array(), filemtime($child_dir . '/dist/css/style-main.css') );
    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_styles');


    // =============================================================================
    // Enqueue Styles (Login screen)
    // =============================================================================
    function mijn_login_stijl() {
        wp_enqueue_style('mijn-login-css', get_template_directory_uri() . '/assets/css/login.css');
    }
    add_action('login_enqueue_scripts', 'mijn_login_stijl');

?>