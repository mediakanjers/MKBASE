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

        // Zoek- en archiefpagina (search.php, archive.php): basisopmaak, alleen op die pagina's en
        // vóór style-main.css. Nodig voor bestaande websites waarvan het child theme deze pagina's
        // (sinds 1.4.1 nieuw in de core) niet opmaakt; het child theme wint altijd.
        if (is_search() || is_archive()) {
            wp_enqueue_style('mk-overzicht', $theme_uri . '/assets/css/components/mk-overzicht.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/mk-overzicht.css'));
        }

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
        $handles = array_merge(array_values(mkbase_vendor_styles()), ['mk-image-slider', 'mk-align', 'mk-openingstijden']);

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

    // Vangnet voor bestaande websites (Owl Carousel, Fancybox 3, Swiper 5): tot en met 1.3.0 laadde
    // de core hun CSS op elke pagina. Een child theme dat de library zelf meebundelt, of het script
    // niet via de core-handle laadt, rekende daarop. Staat de markup op de pagina maar ontbreekt de
    // CSS, dan komt die er alsnog bij, vóór style-main.css. Pagina's zonder zo'n slider of
    // lightbox laden niets extra; heeft een child theme de stylesheet zelf uitgeschakeld
    // (wp_deregister_style), dan blijft hij uit.
    function mkbase_legacy_vendor_styles_fallback($html) {
        $legacy = [
            'owl-carousel-css' => '/\bclass=(["\'])[^"\']*\bowl-carousel\b/',
            'fancybox-css'     => '/\sdata-fancybox\b/',
            'swiper-css'       => '/\bclass=(["\'])[^"\']*\bswiper-container\b/',
        ];
        $main = '/<link\b[^>]*\bid=([\'"])mkbase-style-main-css\1[^>]*>/';

        foreach ($legacy as $handle => $markup) {
            $style = wp_styles()->registered[$handle] ?? null;
            if (!$style || !$style->src) continue;                                      // uitgeschakeld door het child theme
            if (preg_match('/\bid=([\'"])' . preg_quote($handle, '/') . '-css\1/', $html)) continue; // staat er al
            if (!preg_match($markup, $html)) continue;                                  // niet gebruikt op deze pagina
            if (!preg_match($main, $html, $m, PREG_OFFSET_CAPTURE)) return $html;

            $tag  = '<link rel="stylesheet" id="' . esc_attr($handle) . '-css" href="' . esc_url($style->src) . '" media="all" />' . "\n";
            $html = substr_replace($html, $tag, $m[0][1], 0);
        }
        return $html;
    }
    add_filter('wp_template_enhancement_output_buffer', 'mkbase_legacy_vendor_styles_fallback', 15);


    // =============================================================================
    // Enqueue Styles (Login screen)
    // =============================================================================
    function mijn_login_stijl() {
        wp_enqueue_style('mijn-login-css', get_template_directory_uri() . '/assets/css/login.css');
    }
    add_action('login_enqueue_scripts', 'mijn_login_stijl');

?>