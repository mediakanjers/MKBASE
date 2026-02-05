<?php
    add_action('acf/init', function() {
        if (function_exists('acf_add_options_page')) {
            // Hoofdmenu
            acf_add_options_page(array(
                'page_title'    => 'Mediakanjers',
                'menu_title'    => 'Mediakanjers',
                'menu_slug'     => 'Mediakanjers',
                'capability'    => 'edit_posts',
                'redirect'      => true,
                'icon_url'      => get_template_directory_uri() . '/assets/images/mkicon.png'
            ));

            // Submenu 1 - Website instellingen
            acf_add_options_sub_page(array(
                'page_title'    => 'Website instellingen',
                'menu_title'    => 'Website instellingen',
                'parent_slug'   => 'Mediakanjers',
            ));

            // Submenu 2 - Geavanceerd
            acf_add_options_sub_page(array(
                'page_title'    => 'Geavanceerd',
                'menu_title'    => 'Geavanceerd',
                'parent_slug'   => 'Mediakanjers',
            ));
            
            // Submenu 3 - Meldingen
            acf_add_options_sub_page(array(
                'page_title'    => 'Meldingen',
                'menu_title'    => 'Meldingen',
                'parent_slug'   => 'Mediakanjers',
            ));
        }
    });
?>
