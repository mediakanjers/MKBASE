<?php
    // =============================================================================
    // Enqueue Styles (Block editor — styling voor mk/-blokken)
    // =============================================================================

    function mkbase_enqueue_editor_assets() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        wp_enqueue_style('mk-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', [], null);
        wp_enqueue_style('mk-editor-blocks', $theme_uri . '/assets/css/editor-blocks.css', [], filemtime($theme_dir . '/assets/css/editor-blocks.css'));
        wp_enqueue_script('mk-editor-blocks', $theme_uri . '/assets/js/editor-blocks.js', ['jquery', 'acf-input', 'wp-blocks'], filemtime($theme_dir . '/assets/js/editor-blocks.js'), true);
    }
    add_action('enqueue_block_editor_assets', 'mkbase_enqueue_editor_assets');
?>
