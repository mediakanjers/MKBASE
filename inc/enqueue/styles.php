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
        wp_enqueue_style( 'mk-core-style', $theme_uri . '/style.css', array(), mkbase_asset_version($theme_dir . '/style.css'));

        // Child theme style.css
        wp_enqueue_style( 'mk-child-style', $child_uri . '/style.css', array('mk-core-style'), mkbase_asset_version($child_dir . '/style.css') );

        // Vendor styles — alleen registreren, ze laden automatisch mee met hun script (zie hieronder)
        wp_register_style('swiper-v14-css', $theme_uri . '/assets/vendor/swiper/v14/swiper-bundle.min.css', [], '14.3.0');
        wp_register_style('tobii-css', $theme_uri . '/assets/vendor/tobii/tobii.min.css', [], '3.2.0');
        wp_register_style('mk-lightbox-css', $theme_uri . '/assets/css/components/mk-lightbox.css', ['tobii-css'], mkbase_asset_version($theme_dir . '/assets/css/components/mk-lightbox.css'));
        // "Volledige breedte" van de blokken — wordt ge-enqueued door mkbase_block_attrs()
        wp_register_style('mk-align', $theme_uri . '/assets/css/components/mk-align.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/mk-align.css'));
        wp_register_style('mk-video-css', $theme_uri . '/assets/css/components/mk-video.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/mk-video.css'));
        wp_register_style('fancybox-css', $theme_uri . '/assets/vendor/fancybox/jquery.fancybox.min.css');
        wp_register_style('owl-carousel-css', $theme_uri . '/assets/vendor/owl/owl.carousel.min.css');
        wp_register_style('swiper-css', $theme_uri . '/assets/vendor/swiper/swiper.min.css');

        // Meldingen (notificatiebalk): zie inc/notifications.php

        // Main compiled SCSS from /dist
        wp_enqueue_style( 'mkbase-style-main', $child_uri . '/dist/css/style-main.css', array(), mkbase_asset_version($child_dir . '/dist/css/style-main.css') );
    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_styles');


    // =============================================================================
    // Vendor-CSS alleen laden op pagina's waar het bijbehorende script laadt
    // =============================================================================

    // Script-handle => style-handle
    function mkbase_vendor_styles() {
        return [
            'fancybox-js'     => 'fancybox-css',
            'owl-carousel-js' => 'owl-carousel-css',
            'swiper-js'       => 'swiper-css',
            'swiper-v14-js'   => 'swiper-v14-css',
            'tobii-js'        => 'tobii-css',
            'mk-lightbox'     => 'mk-lightbox-css',
            'mk-video'        => 'mk-video-css',
        ];
    }

    // Twee momenten: na de enqueues van het child theme, en vlak voor de footer-scripts
    // voor blokken die het script pas in hun render.php enqueuen.
    function mkbase_enqueue_vendor_styles() {
        foreach (mkbase_vendor_styles() as $script => $style) {
            if (wp_script_is($script, 'enqueued')) {
                wp_enqueue_style($style);
            }
        }
    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_vendor_styles', 100);
    add_action('wp_print_footer_scripts', 'mkbase_enqueue_vendor_styles', 9);

    // Laat ge-enqueuede CSS zet WordPress achteraan in de <head>, ná style-main.css, waardoor
    // vendor-CSS de eigen styling van het child theme zou kunnen overschrijven. Daarom worden
    // deze stylesheets in de uiteindelijke HTML teruggezet vóór style-main.css (WP 6.9+ output buffer).
    // Prioriteit 20: ná WordPress' eigen filter (10) dat de laat geprinte CSS naar de <head> verplaatst.
    function mkbase_move_vendor_styles_before_main($html) {
        $main    = '/<link\b[^>]*\bid=([\'"])mkbase-style-main-css\1[^>]*>/';
        $handles = array_merge(array_values(mkbase_vendor_styles()), ['mk-image-slider', 'mk-align']);

        foreach ($handles as $handle) {
            if (!preg_match($main, $html, $m, PREG_OFFSET_CAPTURE)) return $html;

            $tag = '/<link\b[^>]*\bid=([\'"])' . preg_quote($handle, '/') . '-css\1[^>]*>\s*/';
            if (!preg_match($tag, $html, $t, PREG_OFFSET_CAPTURE) || $t[0][1] < $m[0][1]) continue;

            $html = substr_replace($html, '', $t[0][1], strlen($t[0][0]));
            $html = substr_replace($html, $t[0][0], $m[0][1], 0);
        }
        return $html;
    }
    add_filter('wp_template_enhancement_output_buffer', 'mkbase_move_vendor_styles_before_main', 20);


    // =============================================================================
    // Enqueue Styles (Login screen)
    // =============================================================================
    function mijn_login_stijl() {
        wp_enqueue_style('mijn-login-css', get_template_directory_uri() . '/assets/css/login.css');
    }
    add_action('login_enqueue_scripts', 'mijn_login_stijl');

?>