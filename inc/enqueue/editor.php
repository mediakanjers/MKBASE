<?php
    // =============================================================================
    // Enqueue Styles (Block editor — styling voor mk/-blokken)
    // =============================================================================

    function mkbase_enqueue_editor_assets() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        wp_enqueue_style('mk-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', [], null);
        wp_enqueue_style('mk-editor-blocks', $theme_uri . '/assets/css/editor-blocks.css', [], mkbase_asset_version($theme_dir . '/assets/css/editor-blocks.css'));
        wp_enqueue_script('mk-editor-blocks', $theme_uri . '/assets/js/editor-blocks.js', ['jquery', 'acf-input', 'wp-blocks', 'wp-data'], mkbase_asset_version($theme_dir . '/assets/js/editor-blocks.js'), true);

        // Huisstijlkleuren (theme.json van het child theme) bovenaan elke kleurkiezer, plus wit en zwart
        $palette = [];
        if (function_exists('wp_get_global_settings')) {
            foreach ((array) wp_get_global_settings(['color', 'palette', 'theme']) as $color) {
                if (!empty($color['color'])) $palette[] = $color['color'];
            }
        }
        $palette = array_values(array_unique(array_merge($palette, ['#FFFFFF', '#000000'])));

        wp_localize_script('mk-editor-blocks', 'mkEditor', [
            'palette' => $palette,
            // Welk stuk uit de Documentatie de link "uitleg" in het blokvenster opent
            'docs'    => [
                'mk/kolommen'     => 'blokken/blok-kolommen',
                'mk/loop'         => 'blokken/blok-loop',
                'mk/image-slider' => 'blokken/blok-slider',
                'mk/breadcrumbs'  => 'blokken/blok-breadcrumbs',
            ],
        ]);
    }
    add_action('enqueue_block_editor_assets', 'mkbase_enqueue_editor_assets');
?>
