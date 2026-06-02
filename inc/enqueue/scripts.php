<?php
    // =============================================================================
    // Enqueue Scripts
    // =============================================================================

    function mkbase_enqueue_scripts() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        $child_uri = get_stylesheet_directory_uri();
        $child_dir = get_stylesheet_directory();

        // jQuery
        wp_enqueue_script('jquery');

        // Vendor scripts — alleen registreren, child theme enqueued ze wanneer nodig
        wp_register_script('fancybox-js', $theme_uri . '/assets/vendor/fancybox/jquery.fancybox.min.js', ['jquery'], null, true);
        wp_register_script('owl-carousel-js', $theme_uri . '/assets/vendor/owl/owl.carousel.min.js', ['jquery'], null, true);
        wp_register_script('swiper-js', $theme_uri . '/assets/vendor/swiper/swiper.min.js', [], null, true);
        wp_register_script('sameheight', $theme_uri . '/assets/js/sameheight.js', ['jquery'], null, true);

        // Notificatiebalk — alleen laden als een melding actief is
        if (get_field('melding_tonen', 'option') === 'Ja') {
            $load = true;
            if (get_field('data_opgeven', 'option') === 'ja') {
                $today = date('Ymd');
                $begin = get_field('begindatum', 'option');
                $eind  = get_field('einddatum', 'option');
                if (($begin && $today < $begin) || ($eind && $today > $eind)) {
                    $load = false;
                }
            }
            if ($load) {
                wp_enqueue_script('notification', $theme_uri . '/assets/js/notification.js', ['jquery'], null, true);
                wp_localize_script('notification', 'mkbaseConfig', [
                    'cssOverschrijven' => get_field('css_overschrijven', 'option') === 'Ja' ? 'ja' : 'nee',
                ]);
            }
        }

        // Minified script uit child theme dist
        wp_enqueue_script('mk-main-script', $child_uri . '/dist/scripts/scripts.min.js', [], filemtime($child_dir . '/dist/scripts/scripts.min.js'), true);
    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_scripts');
?>