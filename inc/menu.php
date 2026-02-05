<?php
    /* Registreer menu's en maak ze automatisch aan indien nodig */
    add_action('after_setup_theme', function() {

        // 1. Menu-locaties registreren
        register_nav_menus([
            'main_menu'   => __('Hoofdmenu', 'textdomain'),
            'footer_menu' => __('Footermenu', 'textdomain'),
        ]);

        // 2. Controleren of de menu’s al bestaan
        $menus = wp_get_nav_menus();
        $existing = wp_list_pluck($menus, 'name');

        // 3. Hoofdmenu aanmaken indien niet aanwezig
        if (!in_array('Hoofdmenu', $existing)) {
            $main_menu_id = wp_create_nav_menu('Hoofdmenu');
            $locations = get_theme_mod('nav_menu_locations');
            $locations['main_menu'] = $main_menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        // 4. Footermenu aanmaken indien niet aanwezig
        if (!in_array('Footermenu', $existing)) {
            $footer_menu_id = wp_create_nav_menu('Footermenu');
            $locations = get_theme_mod('nav_menu_locations');
            $locations['footer_menu'] = $footer_menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    });
?>