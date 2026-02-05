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

        // Vendor scripts
        wp_enqueue_script('fancybox-js', $theme_uri . '/assets/vendor/fancybox/jquery.fancybox.min.js', ['jquery'], null, true);
        wp_enqueue_script('owl-carousel-js', $theme_uri . '/assets/vendor/owl/owl.carousel.min.js', ['jquery'], null, true);
        wp_enqueue_script('swiper-js', $theme_uri . '/assets/vendor/swiper/swiper.min.js', [], null, true);

        // Minified script uit child theme dist
        wp_enqueue_script( 'mk-main-script', $child_uri . '/dist/scripts/scripts.min.js',[],filemtime($child_dir . '/dist/scripts/scripts.min.js'),true );

        // Custom scrips
        wp_enqueue_script('sameheight', $theme_uri . '/assets/js/sameheight.js', ['jquery'], null, true);
        wp_enqueue_script('notification', $theme_uri . '/assets/js/notification.js', ['jquery'], null, true);


    }
    add_action('wp_enqueue_scripts', 'mkbase_enqueue_scripts');
?>