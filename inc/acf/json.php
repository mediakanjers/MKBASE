<?php
    // Core thema velden laden vanuit acf-json/
    add_filter('acf/settings/load_json', function($paths) {
        $paths[] = get_template_directory() . '/acf-json';

        // Child thema velden laden als er een child thema actief is
        if (get_stylesheet_directory() !== get_template_directory()) {
            $child_path = get_stylesheet_directory() . '/acf-json';
            if (is_dir($child_path)) {
                $paths[] = $child_path;
            }
        }

        return $paths;
    });

    // Nieuwe en bewerkte veldgroepen opslaan in het actieve thema
    add_filter('acf/settings/save_json', function() {
        if (get_stylesheet_directory() !== get_template_directory()) {
            return get_stylesheet_directory() . '/acf-json';
        }

        return get_template_directory() . '/acf-json';
    });
?>
