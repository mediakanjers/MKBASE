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

    // Namen van alle blokken die dit thema (en, indien aanwezig, het child thema) registreert.
    if (!mkbase_check_duplicate('mkbase_theme_block_names')):
    function mkbase_theme_block_names() {
        static $names = null;
        if ($names !== null) return $names;

        $dirs = [
            get_template_directory() . '/template-parts/blocks',
        ];
        if (get_template_directory() !== get_stylesheet_directory()) {
            $dirs[] = get_stylesheet_directory() . '/template-parts/blocks';
        }

        $names = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (scandir($dir) as $block) {
                if ($block === '.' || $block === '..') continue;
                $block_json = $dir . '/' . $block . '/block.json';
                if (file_exists($block_json)) {
                    $json = json_decode(file_get_contents($block_json), true);
                    $names[] = $json['name'] ?? $block;
                }
            }
        }

        return $names = array_values(array_unique($names));
    }
    endif;

    // Beperkt de blokkeneditor tot alleen de thema-blokken als de optie in "Geavanceerd" aanstaat.
    if (!mkbase_check_duplicate('mk_restrict_to_theme_blocks')):
    function mk_restrict_to_theme_blocks($allowed_blocks, $editor_context) {
        // Alleen de post/pagina-editor beperken — widgets- en navigatie-editor gebruiken
        // geen mk/-blokken en zouden anders leeg/onbruikbaar worden.
        if (empty($editor_context->name) || $editor_context->name !== 'core/edit-post') {
            return $allowed_blocks;
        }

        if (!function_exists('get_field') || !get_field('alleen_thema_blokken', 'option')) {
            return $allowed_blocks;
        }

        $extra_blocks = get_field('extra_zichtbare_blokken', 'option');
        $extra_blocks = is_array($extra_blocks) ? $extra_blocks : [];
        return array_values(array_unique(array_merge(mkbase_theme_block_names(), $extra_blocks)));
    }
    add_filter('allowed_block_types_all', 'mk_restrict_to_theme_blocks', 10, 2);
    endif;

    // Vergrendelt de paginastructuur (toevoegen/verwijderen/verplaatsen van blokken) sitebreed
    // als de optie "Volgorde vergrendeld" in Geavanceerd aanstaat — geldt voor iedereen, ongeacht
    // rol. De inhoud van bestaande blokken blijft gewoon bewerkbaar; alleen de structuur zit op slot.
    if (!mkbase_check_duplicate('mk_lock_page_structure')):
    function mk_lock_page_structure($settings, $editor_context) {
        if (empty($editor_context->post) || !function_exists('get_field')) {
            return $settings;
        }

        if (get_field('volgorde_vergrendeld', 'option')) {
            $settings['templateLock']   = 'all';
            // Voorkomt dat iemand via het per-blok "Lock"-dialoog (rechtermuisknop/toolbar)
            // movement/removal alsnog per blok expliciet aanzet — dat attribuut wint anders
            // altijd van de pagina-brede templateLock hierboven.
            $settings['canLockBlocks'] = false;
        }

        return $settings;
    }
    add_filter('block_editor_settings_all', 'mk_lock_page_structure', 10, 2);
    endif;
?>
