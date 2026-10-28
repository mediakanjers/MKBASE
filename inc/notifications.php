<?php
    // =============================================================================
    // Meldingen (notificatiebalk)
    // =============================================================================
    // Velden:  inc/acf/fields.php (Mediakanjers → Meldingen, repeater 'meldingen')
    // HTML:    template-parts/notifications/notifications.php (aangeroepen vanuit footer.php)
    // Site:    assets/css/components/notifications.css + assets/js/notifications.js
    // Admin:   assets/css/admin-notifications.css + assets/js/admin-notifications.js

    // Kleuren van de vaste stijlen [achtergrond, tekst]; 'eigen' gebruikt de kleurvelden
    function mkbase_notification_styles() {
        return [
            'info'         => ['#2563eb', '#ffffff'],
            'succes'       => ['#15803d', '#ffffff'],
            'waarschuwing' => ['#f59e0b', '#111827'],
            'actie'        => ['#e11d48', '#ffffff'],
        ];
    }

    function mkbase_get_notifications() {
        if (!function_exists('get_field')) return [];
        $rows = get_field('meldingen', 'option');
        return is_array($rows) ? $rows : [];
    }

    // 'uit' | 'leeg' | 'gepland' | 'verlopen' | 'live'
    function mkbase_notification_status($row, $now = null) {
        if (empty($row['melding_actief'])) return 'uit';
        if (trim(wp_strip_all_tags($row['melding_tekst'] ?? '')) === '' && empty($row['melding_knop']['url'])) return 'leeg';

        if (!empty($row['melding_planning'])) {
            $now = $now ?? current_time('mysql'); // Tijdzone van WordPress, zelfde notatie als de velden
            if (!empty($row['melding_start']) && $now < $row['melding_start']) return 'gepland';
            if (!empty($row['melding_eind']) && $now > $row['melding_eind']) return 'verlopen';
        }
        return 'live';
    }

    function mkbase_notification_on_this_page($row) {
        $mode = $row['melding_zichtbaarheid'] ?? 'overal';
        if ($mode !== 'alleen' && $mode !== 'behalve') return true;

        $ids  = array_map('intval', (array) ($row['melding_paginas'] ?: []));
        $obj  = get_queried_object();
        $here = $obj instanceof WP_Post && in_array($obj->ID, $ids, true);

        return $mode === 'alleen' ? $here : !$here;
    }

    // Meldingen die op deze pagina zichtbaar zijn (één keer per request bepaald)
    function mkbase_visible_notifications() {
        static $visible = null;
        if ($visible !== null) return $visible;

        $visible = [];
        if (is_admin()) return $visible;

        foreach (mkbase_get_notifications() as $row) {
            if (mkbase_notification_status($row) === 'live' && mkbase_notification_on_this_page($row)) {
                $visible[] = $row;
            }
        }
        return $visible;
    }

    function mkbase_notification_colors($row) {
        $styles = mkbase_notification_styles();
        $stijl  = $row['melding_stijl'] ?? 'info';
        if (isset($styles[$stijl])) return $styles[$stijl];
        return [$row['melding_achtergrond'] ?: '#ff0000', $row['melding_tekstkleur'] ?: '#ffffff'];
    }

    // Verandert als de inhoud of planning verandert: wie een melding wegklikte, ziet een gewijzigde weer
    function mkbase_notification_id($row) {
        return substr(md5(wp_json_encode([
            $row['melding_tekst'] ?? '',
            $row['melding_knop'] ?? '',
            $row['melding_start'] ?? '',
            $row['melding_eind'] ?? '',
        ])), 0, 10);
    }

    // CSS en JS alleen op pagina's met een zichtbare melding. Prioriteit 9: vóór style-main.css,
    // zodat het child theme de opmaak kan overschrijven.
    add_action('wp_enqueue_scripts', function () {
        if (!mkbase_visible_notifications()) return;

        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();

        if (get_field('css_overschrijven', 'option') !== 'Ja') {
            wp_enqueue_style('mk-notifications', $theme_uri . '/assets/css/components/notifications.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/notifications.css'));
        }
        wp_enqueue_script('mk-notifications', $theme_uri . '/assets/js/notifications.js', [], mkbase_asset_version($theme_dir . '/assets/js/notifications.js'), true);
    }, 9);


    // =============================================================================
    // Cache: WP Rocket legen bij opslaan en op elk begin- en eindmoment
    // =============================================================================
    // Anders blijft een gecachete pagina een geplande melding missen, of een verlopen melding tonen.

    function mkbase_notifications_purge_cache() {
        if (function_exists('rocket_clean_domain')) rocket_clean_domain();
    }
    add_action('mkbase_notifications_purge_cache', 'mkbase_notifications_purge_cache');

    add_action('acf/save_post', function ($post_id) {
        if ($post_id !== 'options' || ($_GET['page'] ?? '') !== 'acf-options-meldingen') return;

        wp_clear_scheduled_hook('mkbase_notifications_purge_cache');
        $tz = wp_timezone();
        foreach (mkbase_get_notifications() as $row) {
            if (empty($row['melding_planning'])) continue;
            foreach (['melding_start', 'melding_eind'] as $key) {
                if (empty($row[$key])) continue;
                $moment = date_create_immutable($row[$key], $tz);
                if (!$moment) continue;
                // Eind = "tot en met", dus één seconde later legen
                $ts = $moment->getTimestamp() + ($key === 'melding_eind' ? 1 : 0);
                if ($ts > time()) wp_schedule_single_event($ts, 'mkbase_notifications_purge_cache', [$ts]);
            }
        }
        mkbase_notifications_purge_cache();
    }, 20);


    // =============================================================================
    // Admin: Mediakanjers → Meldingen
    // =============================================================================

    function mkbase_is_notifications_screen() {
        return is_admin() && ($_GET['page'] ?? '') === 'acf-options-meldingen';
    }

    add_action('admin_enqueue_scripts', function () {
        if (!mkbase_is_notifications_screen()) return;

        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_style('mk-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', [], null);
        wp_enqueue_style('mk-admin-shared', $theme_uri . '/assets/css/admin-shared.css', [], mkbase_asset_version($theme_dir . '/assets/css/admin-shared.css'));
        wp_enqueue_style('mk-admin-notifications', $theme_uri . '/assets/css/admin-notifications.css', ['mk-admin-shared'], mkbase_asset_version($theme_dir . '/assets/css/admin-notifications.css'));
        wp_enqueue_script('mk-admin-notifications', $theme_uri . '/assets/js/admin-notifications.js', ['acf-input'], mkbase_asset_version($theme_dir . '/assets/js/admin-notifications.js'), true);
        wp_localize_script('mk-admin-notifications', 'mkNotificationsAdmin', [
            'now'    => current_time('Y-m-d H:i:s'),
            'styles' => mkbase_notification_styles(),
        ]);
    });

    // Kop boven het formulier, in de stijl van Changelog / Documentatie / Diagnose
    add_action('admin_notices', function () {
        if (!mkbase_is_notifications_screen()) return;
        $docs = admin_url('admin.php?page=mkbase-docs');
        ?>
        <div class="mk-notif-head">
            <div class="mk-notif-toolbar">
                <a class="mk-notif-toolbar__link" href="<?php echo esc_url($docs); ?>"><span class="dashicons dashicons-book"></span> Uitleg</a>
                <button id="mk-dark-toggle" class="mk-dark-toggle" type="button" title="Wissel dark/light mode">🌙 Dark</button>
            </div>
            <div class="mk-cover">
                <div class="mk-cover__eyebrow">Mediakanjers</div>
                <div class="mk-cover__title">Meldingen</div>
                <p class="mk-cover__sub">Toon een balk boven- of onderaan de website, bijvoorbeeld voor een actie, gewijzigde openingstijden of een storing. Meerdere meldingen tegelijk kan, en je kunt ze vooraf inplannen.</p>
            </div>
        </div>
        <?php
    }, 1);

    // Overzicht met de status van elke melding (bovenaan het formulier)
    add_filter('acf/load_field/key=field_mk_meldingen_overzicht', function ($field) {
        if (!mkbase_is_notifications_screen()) return $field;
        $field['message'] = mkbase_notifications_overview();
        return $field;
    });

    function mkbase_notification_status_label($status) {
        return [
            'live'     => 'Live',
            'gepland'  => 'Gepland',
            'verlopen' => 'Verlopen',
            'uit'      => 'Uit',
            'leeg'     => 'Geen tekst',
        ][$status] ?? $status;
    }

    function mkbase_notifications_overview() {
        $rows = mkbase_get_notifications();
        if (!$rows) {
            return '<div class="mk-notif-overview mk-notif-overview--empty">Er zijn nog geen meldingen. Klik op <strong>Melding toevoegen</strong> om er een te maken.</div>';
        }

        $counts = array_fill_keys(['live', 'gepland', 'verlopen', 'uit', 'leeg'], 0);
        foreach ($rows as $row) $counts[mkbase_notification_status($row)]++;

        $html = '<div class="mk-notif-overview">';
        foreach (['live', 'gepland', 'verlopen', 'uit'] as $status) {
            $html .= '<div class="mk-notif-stat mk-notif-stat--' . $status . '">'
                . '<span class="mk-notif-stat__count">' . (int) $counts[$status] . '</span>'
                . '<span class="mk-notif-stat__label">' . esc_html(mkbase_notification_status_label($status)) . '</span>'
                . '</div>';
        }
        $html .= '</div>';

        if ($counts['leeg']) {
            $html .= '<p class="mk-notif-overview__hint">' . (int) $counts['leeg'] . ' melding(en) staan aan maar hebben nog geen tekst of knop, en worden daarom niet getoond.</p>';
        }
        return $html;
    }
?>
