<?php
    // =============================================================================
    // Eenmalige omzettingen bij een core-update
    // =============================================================================
    // Draait één keer per site (op de website of in de admin, wat het eerst komt) en onthoudt
    // in de optie 'mkbase_migrations' wat al gedaan is.

    // Veldgroepen die het core thema zelf in PHP registreert (inc/acf/fields.php)
    function mkbase_core_field_group_keys() {
        return [
            'group_681c77cf42eb6', // Geavanceerd
            'group_68ff64b408f4e', // Meldingen
            'group_681afe6b4a111', // MK instellingen
            'group_69f0a9ec6e104', // 404
            'group_mk_loop_block',
            'group_mk_image_slider_block',
            'group_mk_kolommen_block',
            'group_mk_breadcrumbs_block',
        ];
    }

    add_action('init', function () {
        if (!function_exists('update_field')) return;

        $done = get_option('mkbase_migrations', []);
        if (!is_array($done)) $done = [];

        $migrations = [
            'meldingen_repeater_1_5_0'   => 'mkbase_migrate_notifications_to_repeater',
            'opruimen_veldgroepen_1_5_0' => 'mkbase_remove_disabled_core_field_groups',
        ];

        $changed = false;
        foreach ($migrations as $name => $callback) {
            if (in_array($name, $done, true)) continue;
            $callback();
            $done[]  = $name;
            $changed = true;
        }
        if ($changed) update_option('mkbase_migrations', $done, true);
    }, 20);

    // 1.5.0: de losse melding (tot 1.4.1) wordt de eerste rij van de repeater 'meldingen'.
    // De oude opties blijven staan, zodat er niets verloren gaat.
    function mkbase_migrate_notifications_to_repeater() {
        if (get_option('options_meldingen')) return; // Er staan al meldingen in de nieuwe lijst

        $tekst = get_option('options_tekst_melding');
        if ($tekst === false || trim(wp_strip_all_tags((string) $tekst)) === '') return;

        $begin = get_option('options_begindatum');
        $eind  = get_option('options_einddatum');
        $paginas = array_filter(array_map('intval', (array) get_option('options_waar_zichtbaar', [])));

        update_field('field_mk_meldingen', [[
            'field_mk_melding_actief'         => get_option('options_melding_tonen') === 'Ja' ? 1 : 0,
            'field_mk_melding_naam'           => 'Melding',
            'field_mk_melding_tekst'          => $tekst,
            'field_mk_melding_planning'       => get_option('options_data_opgeven') === 'ja' ? 1 : 0,
            'field_mk_melding_start'          => $begin ? date('Y-m-d 00:00:00', strtotime($begin)) : '',
            'field_mk_melding_eind'           => $eind ? date('Y-m-d 23:59:59', strtotime($eind)) : '',
            'field_mk_melding_stijl'          => 'eigen',
            'field_mk_melding_achtergrond'    => get_option('options_achtergrondkleur') ?: '#ff0000',
            'field_mk_melding_tekstkleur'     => get_option('options_tekstkleur') ?: '#ffffff',
            'field_mk_melding_positie'        => 'boven',
            'field_mk_melding_sluitbaar'      => 1,
            'field_mk_melding_opnieuw'        => 'sessie',
            'field_mk_melding_dagen'          => 7,
            'field_mk_melding_zichtbaarheid'  => $paginas ? 'alleen' : 'overal',
            'field_mk_melding_paginas'        => $paginas,
        ]], 'option');
    }

    // 1.5.0: uitgeschakelde database-kopieën van core-veldgroepen verwijderen (resten van vóór de
    // PHP-registratie). Actieve kopieën blijven staan; die meldt de Diagnose-pagina.
    function mkbase_remove_disabled_core_field_groups() {
        global $wpdb;

        $keys  = mkbase_core_field_group_keys();
        $in    = implode(',', array_fill(0, count($keys), '%s'));
        $ids   = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field-group' AND post_status = 'acf-disabled' AND post_name IN ($in)",
            $keys
        ));

        foreach ($ids as $group_id) {
            // Velden en subvelden van de groep (acf-field posts, genest via post_parent)
            $parents = [(int) $group_id];
            while ($parents) {
                $children = $wpdb->get_col(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field' AND post_parent IN (" . implode(',', $parents) . ')'
                );
                foreach ($children as $child_id) wp_delete_post((int) $child_id, true);
                $parents = array_map('intval', $children);
            }
            wp_delete_post((int) $group_id, true);
        }
    }

    // Actieve kopieën van core-veldgroepen: in de database of als JSON in het child theme.
    // Gebruikt door de Diagnose-pagina.
    function mkbase_core_field_group_copies() {
        global $wpdb;

        $keys   = mkbase_core_field_group_keys();
        $copies = [];

        $in   = implode(',', array_fill(0, count($keys), '%s'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT post_name, post_title FROM {$wpdb->posts} WHERE post_type = 'acf-field-group' AND post_status = 'publish' AND post_name IN ($in)",
            $keys
        ));
        foreach ($rows as $row) {
            $copies[] = ['title' => $row->post_title, 'where' => 'database'];
        }

        if (get_stylesheet_directory() !== get_template_directory()) {
            foreach (glob(get_stylesheet_directory() . '/acf-json/*.json') ?: [] as $file) {
                $json = json_decode((string) file_get_contents($file), true);
                if (isset($json['key']) && in_array($json['key'], $keys, true)) {
                    $copies[] = ['title' => $json['title'] ?? $json['key'], 'where' => 'acf-json/' . basename($file)];
                }
            }
        }
        return $copies;
    }
?>
