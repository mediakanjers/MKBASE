<?php
    if (!mkbase_check_duplicate('mk_register_blocks')):
    function mk_register_blocks() {
        $core_dir  = get_template_directory() . '/template-parts/blocks';
        $child_dir = get_stylesheet_directory() . '/template-parts/blocks';
        $is_child  = get_template_directory() !== get_stylesheet_directory();

        $core_blocks  = [];
        $duplicates   = [];

        // Verzamel core blok-namen
        if (is_dir($core_dir)) {
            foreach (scandir($core_dir) as $block) {
                if ($block === '.' || $block === '..') continue;
                $block_path = $core_dir . '/' . $block;
                if (file_exists($block_path . '/block.json')) {
                    $json = json_decode(file_get_contents($block_path . '/block.json'), true);
                    $name = $json['name'] ?? $block;
                    $core_blocks[$name] = $block;
                    register_block_type($block_path);
                }
            }
        }

        // Controleer child theme op duplicaten en registreer unieke blokken
        if ($is_child && is_dir($child_dir)) {
            foreach (scandir($child_dir) as $block) {
                if ($block === '.' || $block === '..') continue;
                $block_path = $child_dir . '/' . $block;
                if (file_exists($block_path . '/block.json')) {
                    $json = json_decode(file_get_contents($block_path . '/block.json'), true);
                    $name = $json['name'] ?? $block;
                    if (isset($core_blocks[$name])) {
                        $duplicates[] = $name;
                    } else {
                        register_block_type($block_path);
                    }
                }
            }
        }

        // Admin notice bij duplicaten
        if (!empty($duplicates)) {
            add_action('admin_notices', function() use ($duplicates) {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>MKBase:</strong> De volgende blokken staan zowel in het core thema als het child thema. ';
                echo 'Verwijder ze uit het child thema — de core versie wordt gebruikt:<br>';
                foreach ($duplicates as $name) {
                    echo '<code>' . esc_html($name) . '</code> ';
                }
                echo '</p></div>';
            });
        }
    }
    add_action('init', 'mk_register_blocks');
    endif;

    if (!mkbase_check_duplicate('mk_block_categories')):
    function mk_block_categories($categories) {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'mediakanjers',
                    'title' => 'Mediakanjers',
                    'icon'  => 'star-filled',
                ],
            ]
        );
    }
    add_filter('block_categories_all', 'mk_block_categories');
    endif;
?>
