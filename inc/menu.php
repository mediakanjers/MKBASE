<?php
    // Menu-locaties registreren — draait bij elke pageload
    add_action('after_setup_theme', function() {
        register_nav_menus([
            'main_menu'   => __('Hoofdmenu', 'mk'),
            'footer_menu' => __('Footermenu', 'mk'),
        ]);
    });

    // Menu’s aanmaken indien niet aanwezig — alleen in de admin, niet op de frontend
    add_action('admin_init', function() {
        $menus    = wp_get_nav_menus();
        $existing = wp_list_pluck($menus, 'name');

        if (!in_array('Hoofdmenu', $existing)) {
            $id                     = wp_create_nav_menu('Hoofdmenu');
            $locations              = get_theme_mod('nav_menu_locations', []);
            $locations['main_menu'] = $id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        if (!in_array('Footermenu', $existing)) {
            $id                       = wp_create_nav_menu('Footermenu');
            $locations                = get_theme_mod('nav_menu_locations', []);
            $locations['footer_menu'] = $id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    });
?>