<?php
    // =============================================================================
    // Openingstijden ↔ Meldingen (sinds 1.7.0)
    // =============================================================================
    // 1. Openingstijden ín een melding (Inhoud → "Openingstijden in de melding").
    // 2. Een melding die een feestdag of afwijking volgt (Planning → "Volgens openingstijden"): de
    //    planning komt uit de bron, en de tekst kan invullers hebben ({naam} {datum} {tijden} {rooster}
    //    {toelichting}, zie mkbase_ot_invullers()).
    // 3. Automatische meldingen (Openingstijden → Algemeen): voor elke feestdag en afwijking zonder eigen
    //    melding een balk met de standaardtekst. Ze staan niet in de lijst bij Meldingen, maar worden hier
    //    gemaakt (mkbase_ot_auto_meldingen()) en door inc/notifications.php meegenomen.
    // Bronnen: mkbase_ot_bronnen() in data.php. Beheer: assets/js/admin-notifications.js en
    // assets/js/admin-openingstijden.js.

    // De melding zoals hij geldt: bij "Volgens openingstijden" de planning en tekst uit de bron.
    // Is de bron weg, dan 'mk_ontkoppeld' (de melding wordt dan niet getoond).
    function mkbase_ot_melding_effectief($row) {
        if (empty($row['melding_ot_koppelen']) || !mkbase_ot_actief()) return $row;
        $bron = mkbase_ot_ingevuld() ? mkbase_ot_bron($row['melding_ot_bron'] ?? '') : null;
        if (!$bron) {
            $row['mk_ontkoppeld'] = true;
            return $row;
        }
        $periode = mkbase_ot_bron_periode($bron, $row['melding_ot_vooraf'] ?? '');
        $row['melding_planning'] = 1;
        $row['melding_start']    = $periode['start'];
        $row['melding_eind']     = $periode['eind'];
        $row['melding_tekst']    = mkbase_ot_vul_in($row['melding_tekst'] ?? '', $bron, true);
        $row['mk_bron']          = $bron['sleutel'];
        return $row;
    }

    // Bronnen met een eigen melding (gekoppeld, ook als die uit staat): daar komt geen automatische bij
    function mkbase_ot_eigen_meldingen() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        if (function_exists('mkbase_prime_acf_repeater')) mkbase_prime_acf_repeater('meldingen', 'field_mk_meldingen');
        foreach ((array) (function_exists('get_field') ? get_field('meldingen', 'option') : []) as $i => $row) {
            if (!is_array($row) || empty($row['melding_ot_koppelen']) || empty($row['melding_ot_bron'])) continue;
            $cache[$row['melding_ot_bron']] = ['index' => $i, 'naam' => $row['melding_naam'] ?: __('Naamloze melding', 'mk'), 'row' => $row];
        }
        return $cache;
    }

    // Automatische meldingen: één balk per feestdag of afwijking (zonder eigen melding en zonder
    // "Geen melding"), zichtbaar vanaf het aantal dagen vooraf tot en met de laatste dag
    function mkbase_ot_auto_meldingen() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        if (!mkbase_ot_actief() || !mkbase_ot_ingevuld() || !get_field('mkbase_ot_auto', 'option')) return $cache;

        $eigen  = mkbase_ot_eigen_meldingen();
        $tekst  = mkbase_ot_standaard_tekst();
        $vandaag = mkbase_ot_vandaag();
        foreach (mkbase_ot_bronnen() as $sleutel => $bron) {
            if ($bron['geen_melding'] || isset($eigen[$sleutel]) || $bron['tot'] < $vandaag) continue;
            // Een gesloten feestdag op een dag dat alle roosters toch al dicht zijn: geen melding nodig
            if ($bron['soort'] === 'feestdag' && $bron['status'] === 'gesloten') {
                $open = false;
                foreach (mkbase_ot_roosters() as $id => $r) {
                    if (mkbase_ot_geldt($bron['voor'], $id) && $r['week'][(int) $bron['van']->format('N') - 1]['status'] !== 'gesloten') $open = true;
                }
                if (!$open) continue;
            }
            $periode = mkbase_ot_bron_periode($bron);
            $cache[] = [
                'melding_actief'        => 1,
                'melding_naam'          => $bron['naam'],
                'melding_tekst'         => '<p>' . esc_html(mkbase_ot_vul_in($tekst, $bron)) . '</p>',
                'melding_knop'          => null,
                'melding_planning'      => 1,
                'melding_start'         => $periode['start'],
                'melding_eind'          => $periode['eind'],
                'melding_stijl'         => get_field('mkbase_ot_auto_stijl', 'option') ?: 'waarschuwing',
                'melding_positie'       => get_field('mkbase_ot_auto_positie', 'option') ?: 'boven',
                'melding_tonen'         => 'direct',
                'melding_sluitbaar'     => 1,
                'melding_opnieuw'       => 'sessie',
                'melding_dagen'         => 7,
                'melding_zichtbaarheid' => 'overal',
                'mk_auto'               => true,
                'mk_bron'               => $sleutel,
            ];
        }
        return $cache;
    }

    // Openingstijden in een melding: dezelfde HTML als het blok
    function mkbase_ot_melding_html($row) {
        if (!function_exists('mkbase_openingstijden')) return '';
        return mkbase_openingstijden([
            'rooster'     => (array) ($row['melding_ot_roosters'] ?? []),
            'weergave'    => $row['melding_ot_weergave'] ?: 'week',
            'status'      => !isset($row['melding_ot_status']) || !empty($row['melding_ot_status']),
            'vandaag'     => true,
            'afwijkingen' => true,
            'wijziging'   => false, // de melding zelf is al de mededeling
        ]);
    }

    // Voor de beheerpagina's: per bron de gegevens voor de keuzelijst, de tijdlijn en de invullers
    function mkbase_ot_bronnen_admin() {
        $uit = [];
        foreach (mkbase_ot_bronnen() as $sleutel => $b) {
            $wanneer = $b['van'] == $b['tot'] ? mkbase_ot_datum_kort($b['van'])
                : sprintf(__('%1$s t/m %2$s', 'mk'), mkbase_ot_datum_kort($b['van']), mkbase_ot_datum_kort($b['tot']));
            $uit[$sleutel] = [
                'naam'      => $b['naam'],
                'soort'     => $b['soort'],
                'wanneer'   => $wanneer,
                'van'       => $b['van']->format('Y-m-d'),
                'tot'       => $b['tot']->format('Y-m-d'),
                'vast'      => $b['vast'],
                'invullers' => mkbase_ot_invullers($b),
                'label'     => $b['naam'] . ' · ' . $wanneer,
            ];
        }
        return $uit;
    }

    // Keuzelijst "Volgt" in een melding: afwijkingen en feestdagen, in groepen. Deze drie met prepare_field:
    // alleen als het formulier getoond wordt, niet bij elke get_field() op de website.
    add_filter('acf/prepare_field/key=field_mk_melding_ot_bron', function ($field) {
        if (!mkbase_ot_actief()) return $field;
        $groepen = [__('Afwijkingen', 'mk') => [], __('Feestdagen', 'mk') => []];
        foreach (mkbase_ot_bronnen_admin() as $sleutel => $b) {
            if (!$b['vast']) continue; // nog niet opgeslagen afwijking: nog geen vast kenmerk
            $groepen[$b['soort'] === 'afwijking' ? __('Afwijkingen', 'mk') : __('Feestdagen', 'mk')][$sleutel] = $b['label'];
        }
        // Een gekozen bron die er niet meer is, blijft zichtbaar (met de waarschuwing in de melding)
        $field['choices'] = array_filter($groepen);
        return $field;
    });

    add_filter('acf/prepare_field/key=field_mk_melding_ot_roosters', function ($field) {
        if (function_exists('mkbase_ot_rooster_keuzes')) $field['choices'] = mkbase_ot_rooster_keuzes();
        return $field;
    });

    add_filter('acf/prepare_field/key=field_mk_melding_ot_vooraf', function ($field) {
        if (mkbase_ot_actief()) $field['placeholder'] = (string) mkbase_ot_vooruit();
        return $field;
    });

    // Voorbeeld van de openingstijden in een melding (live voorbeeld bij Meldingen): dezelfde HTML als op de website
    add_action('wp_ajax_mkbase_ot_voorbeeld', function () {
        check_ajax_referer('mkbase_admin');
        if (!current_user_can('edit_posts') || !mkbase_ot_actief()) wp_send_json_error(null, 403);
        $roosters = array_map('sanitize_key', (array) ($_POST['roosters'] ?? []));
        $weergave = sanitize_key($_POST['weergave'] ?? 'week');
        wp_send_json_success(['html' => mkbase_ot_melding_html([
            'melding_ot_roosters' => $roosters,
            'melding_ot_weergave' => $weergave,
            'melding_ot_status'   => !empty($_POST['status']),
        ])]);
    });

    // Gegevens voor admin-notifications.js (Meldingen) en admin-openingstijden.js (Openingstijden)
    add_action('admin_enqueue_scripts', function () {
        if (!mkbase_ot_actief()) return;
        $page = $_GET['page'] ?? '';
        if ($page === 'acf-options-meldingen') {
            wp_localize_script('mk-admin-notifications', 'mkOtMeldingen', [
                'bronnen'   => mkbase_ot_bronnen_admin(),
                'vooruit'   => mkbase_ot_vooruit(),
                'tekst'     => mkbase_ot_standaard_tekst(),
                'invullers' => ['{naam}', '{datum}', '{tijden}', '{rooster}', '{toelichting}'],
                'roosters'  => function_exists('mkbase_ot_rooster_keuzes') ? mkbase_ot_rooster_keuzes() : [],
                'beheer'    => admin_url('admin.php?page=acf-options-openingstijden'),
                'ajax'      => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('mkbase_admin'),
            ]);
            // De openingstijden in het voorbeeld hebben de CSS en het script van de website nodig
            wp_enqueue_style('mk-openingstijden');
            wp_enqueue_script('mk-openingstijden');
        }
        if ($page === 'acf-options-openingstijden') {
            $eigen = [];
            foreach (mkbase_ot_eigen_meldingen() as $sleutel => $m) {
                $row = mkbase_ot_melding_effectief($m['row']);
                $eigen[$sleutel] = [
                    'naam'   => $m['naam'],
                    'status' => function_exists('mkbase_notification_status') ? mkbase_notification_status($row) : '',
                    'start'  => $row['melding_start'] ?? '',
                ];
            }
            wp_localize_script('mk-admin-openingstijden', 'mkOtKoppeling', [
                'eigen'     => $eigen,
                'auto'      => (bool) get_field('mkbase_ot_auto', 'option'),
                'meldingen' => admin_url('admin.php?page=acf-options-meldingen'),
            ]);
        }
    }, 30);
?>
