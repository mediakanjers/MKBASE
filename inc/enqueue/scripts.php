<?php
    // =============================================================================
    // Enqueue Scripts
    // =============================================================================

    function mkbase_enqueue_scripts() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        $child_uri = get_stylesheet_directory_uri();
        $child_dir = get_stylesheet_directory();

        // Vendor scripts — alleen registreren, child theme enqueued ze wanneer nodig
        wp_register_script('swiper-v14-js', $theme_uri . '/assets/vendor/swiper/v14/swiper-bundle.min.js', [], '14.3.0', true);
        wp_register_script('sameheight', $theme_uri . '/assets/js/sameheight.js', ['jquery'], null, true);

        // Lightbox — blokken en child themes enqueuen alleen 'mk-lightbox' (de eigen laag),
        // nooit 'tobii-js' zelf. Gebruik: zie assets/js/mk-lightbox.js
        wp_register_script('tobii-js', $theme_uri . '/assets/vendor/tobii/tobii.min.js', [], '3.2.0', true);
        wp_register_script('mk-lightbox', $theme_uri . '/assets/js/mk-lightbox.js', ['tobii-js'], mkbase_asset_version($theme_dir . '/assets/js/mk-lightbox.js'), true);
        wp_localize_script('mk-lightbox', 'mkLightboxConfig', [
            'labels' => [
                'navLabel'              => [__('Vorige', 'mk'), __('Volgende', 'mk')],
                'closeLabel'            => __('Lightbox sluiten', 'mk'),
                'dialogTitle'           => __('Lightbox', 'mk'),
                'loadingIndicatorLabel' => __('Bezig met laden', 'mk'),
                'captionToggleLabel'    => [__('Bijschrift verbergen', 'mk'), __('Bijschrift tonen', 'mk')],
                'announcementLabel'     => [__('Slide', 'mk'), __('van', 'mk')],
            ],
        ]);

        // Video pas laden na een klik — wordt ge-enqueued door mkbase_video()
        wp_register_script('mk-video', $theme_uri . '/assets/js/mk-video.js', [], mkbase_asset_version($theme_dir . '/assets/js/mk-video.js'), true);

        // Verouderd — alleen nog voor bestaande sites die ze al gebruiken, niet voor nieuwe blokken.
        // Fancybox 3 en Owl Carousel 2 worden niet meer onderhouden en werken niet met jQuery 4.
        // Swiper 5 blijft onder de oude handle staan; nieuwe blokken gebruiken 'swiper-v14-js'.
        wp_register_script('fancybox-js', $theme_uri . '/assets/vendor/fancybox/jquery.fancybox.min.js', ['jquery'], null, true);
        wp_register_script('owl-carousel-js', $theme_uri . '/assets/vendor/owl/owl.carousel.min.js', ['jquery'], null, true);
        wp_register_script('swiper-js', $theme_uri . '/assets/vendor/swiper/swiper.min.js', [], null, true);

        // Meldingen (notificatiebalk): zie inc/notifications.php

        // Minified script uit child theme dist
        wp_enqueue_script('mk-main-script', $child_uri . '/dist/scripts/scripts.min.js', [], mkbase_asset_version($child_dir . '/dist/scripts/scripts.min.js'), true);
    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_scripts');
?>