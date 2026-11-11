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

    // "Bekijk op website" (knop bij elke melding in de admin): ?mk_melding_voorbeeld=N toont alleen
    // melding N, ongeacht status, planning en zichtbaarheid. Alleen voor wie meldingen mag bewerken,
    // met een geldige nonce. Geeft de index terug, of null.
    function mkbase_notification_preview_index() {
        static $index = false;
        if ($index !== false) return $index;
        $index = null;

        if (is_admin() || !isset($_GET['mk_melding_voorbeeld']) || !current_user_can('edit_posts')) return $index;
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_mkvb'] ?? '')), 'mkbase_melding_voorbeeld')) return $index;

        $i = (int) $_GET['mk_melding_voorbeeld'];
        if (isset(mkbase_get_notifications()[$i])) $index = $i;
        return $index;
    }

    function mkbase_notification_preview_url() {
        return add_query_arg('_mkvb', wp_create_nonce('mkbase_melding_voorbeeld'), home_url('/'));
    }

    // Plek die een melding inneemt. Balken mogen onder elkaar staan (null); een pop-up en elke plek
    // van een zwevend blok zijn er maar één tegelijk.
    function mkbase_notification_slot($row) {
        $positie = $row['melding_positie'] ?? 'boven';
        if ($positie === 'popup') return 'popup';
        if ($positie === 'blok') return 'blok_' . ($row['melding_blok_plek'] ?: 'rechts_onder');
        return null;
    }

    function mkbase_notification_slot_label($slot) {
        $plekken = [
            'popup'              => 'de pop-up in het midden',
            'blok_links_boven'   => 'het blok linksboven',
            'blok_links_midden'  => 'het blok links in het midden',
            'blok_links_onder'   => 'het blok linksonder',
            'blok_rechts_boven'  => 'het blok rechtsboven',
            'blok_rechts_midden' => 'het blok rechts in het midden',
            'blok_rechts_onder'  => 'het blok rechtsonder',
        ];
        return $plekken[$slot] ?? $slot;
    }

    // Kunnen twee meldingen tegelijk zichtbaar zijn? Zonder planning: altijd.
    function mkbase_notifications_overlap($a, $b) {
        $van = function ($r) { return !empty($r['melding_planning']) && !empty($r['melding_start']) ? $r['melding_start'] : '0000'; };
        $tot = function ($r) { return !empty($r['melding_planning']) && !empty($r['melding_eind']) ? $r['melding_eind'] : '9999'; };
        return $van($a) <= $tot($b) && $van($b) <= $tot($a);
    }

    // Opslaan tegenhouden als twee meldingen dezelfde plek in dezelfde periode willen
    add_filter('acf/validate_value/key=field_mk_meldingen', function ($valid, $value) {
        if ($valid !== true || !is_array($value)) return $valid;
        $k = [
            'melding_actief' => 'field_mk_melding_actief', 'melding_naam' => 'field_mk_melding_naam',
            'melding_positie' => 'field_mk_melding_positie', 'melding_blok_plek' => 'field_mk_melding_blok_plek',
            'melding_planning' => 'field_mk_melding_planning', 'melding_start' => 'field_mk_melding_start', 'melding_eind' => 'field_mk_melding_eind',
        ];
        $rows = [];
        foreach ($value as $raw) {
            if (!is_array($raw)) continue;
            $row = [];
            foreach ($k as $name => $key) $row[$name] = $raw[$key] ?? '';
            if (empty($row['melding_actief']) || !mkbase_notification_slot($row)) continue;
            foreach ($rows as $other) {
                if (mkbase_notification_slot($other) === mkbase_notification_slot($row) && mkbase_notifications_overlap($other, $row)) {
                    return sprintf(
                        'Op %s staan twee meldingen tegelijk: "%s" en "%s". Kies voor één van beide een andere plek, of plan ze in verschillende periodes.',
                        mkbase_notification_slot_label(mkbase_notification_slot($row)),
                        $other['melding_naam'] ?: 'Naamloze melding',
                        $row['melding_naam'] ?: 'Naamloze melding'
                    );
                }
            }
            $rows[] = $row;
        }
        return $valid;
    }, 10, 2);

    // Meldingen die op deze pagina zichtbaar zijn (één keer per request bepaald)
    function mkbase_visible_notifications() {
        static $visible = null;
        if ($visible !== null) return $visible;

        $visible = [];
        if (is_admin()) return $visible;

        $preview = mkbase_notification_preview_index();
        if ($preview !== null) {
            $row = mkbase_get_notifications()[$preview];
            $row['mk_voorbeeld'] = true;
            return $visible = [$row];
        }

        $bezet = []; // vangnet: per plek alleen de eerste (opslaan houdt dubbele al tegen)
        foreach (mkbase_get_notifications() as $row) {
            if (mkbase_notification_status($row) !== 'live' || !mkbase_notification_on_this_page($row)) continue;
            $slot = mkbase_notification_slot($row);
            if ($slot && isset($bezet[$slot])) continue;
            if ($slot) $bezet[$slot] = true;
            $visible[] = $row;
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


    // Voorbeeld niet in de cache, en een zwevende balk zodat duidelijk is dat alleen jij dit ziet
    add_action('template_redirect', function () {
        if (mkbase_notification_preview_index() === null) return;
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        nocache_headers();
    });

    add_action('wp_footer', function () {
        $i = mkbase_notification_preview_index();
        if ($i === null) return;
        $row    = mkbase_get_notifications()[$i];
        $status = mkbase_notification_status($row);
        $labels = [
            'live'     => 'Nu live',
            'gepland'  => 'Gepland vanaf ' . mysql2date('j M H:i', $row['melding_start'] ?? ''),
            'verlopen' => 'Verlopen',
            'uit'      => 'Staat uit',
            'leeg'     => 'Nog zonder tekst',
        ];
        ?>
        <div class="mk-voorbeeldbalk" role="region" aria-label="Voorbeeld van een melding">
            <svg width="32" height="32" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="16" fill="#29A8E0"/><text x="16" y="21" text-anchor="middle" font-family="system-ui, sans-serif" font-weight="800" font-size="13" fill="#fff">MK</text></svg>
            <span class="mk-voorbeeldbalk__txt">
                <strong>Voorbeeld: “<?php echo esc_html($row['melding_naam'] ?: 'Naamloze melding'); ?>”</strong>
                <span><?php echo esc_html($labels[$status] ?? ''); ?> · alleen jij ziet dit</span>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=acf-options-meldingen')); ?>">Terug naar Meldingen</a>
        </div>
        <style>
            .mk-voorbeeldbalk { position: fixed; z-index: 2147483000; left: 50%; bottom: calc(24px + var(--mk-notification-bottom-height, 0px)); transform: translateX(-50%); display: flex; align-items: center; gap: 14px; max-width: calc(100vw - 32px); box-sizing: border-box; padding: 10px 10px 10px 14px; border-radius: 16px; background: #0F1B2D; color: #fff; box-shadow: 0 24px 60px rgba(15, 27, 45, 0.35); font: 14px/1.35 system-ui, -apple-system, 'Segoe UI', sans-serif; }
            .mk-voorbeeldbalk svg { flex-shrink: 0; }
            .mk-voorbeeldbalk__txt { display: flex; flex-direction: column; min-width: 0; }
            .mk-voorbeeldbalk__txt span { color: #9AA9BD; font-size: 12.5px; }
            .mk-voorbeeldbalk a { flex-shrink: 0; padding: 10px 14px; border-radius: 10px; background: #0B6FB8; color: #fff; font-weight: 700; text-decoration: none; }
            .mk-voorbeeldbalk a:hover, .mk-voorbeeldbalk a:focus-visible { background: #08568F; color: #fff; outline: 2px solid #7CC6F2; outline-offset: 2px; }
        </style>
        <?php
    }, 100);


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

        // De MK-stijl en -balk komen uit inc/admin/ui.php (mk-admin); dit is alleen het meldingen-deel
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_style('mk-admin-notifications', $theme_uri . '/assets/css/admin-notifications.css', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/css/admin-notifications.css'));
        wp_enqueue_script('mk-admin-notifications', $theme_uri . '/assets/js/admin-notifications.js', ['acf-input', 'mk-admin'], mkbase_asset_version($theme_dir . '/assets/js/admin-notifications.js'), true);
        wp_localize_script('mk-admin-notifications', 'mkNotificationsAdmin', [
            'now'    => current_time('Y-m-d H:i:s'),
            'styles' => mkbase_notification_styles(),
            'host'   => wp_parse_url(home_url(), PHP_URL_HOST),
            'view'   => mkbase_notification_preview_url(),
        ]);
    });

    // Eenvoudige werkbalk voor de meldingtekst: een melding is één zin, geen opgemaakt artikel
    add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
        $toolbars['mk_melding'] = [1 => ['bold', 'italic', 'link', 'unlink']];
        // Tekst in het Kolommen-blok: alleen wat een klant nodig heeft
        $toolbars['mk_tekst'] = [1 => ['bold', 'italic', 'bullist', 'numlist', 'link', 'unlink']];
        return $toolbars;
    });

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

    // Statuslabels naast de paginatitel. Het script werkt de aantallen bij terwijl je wijzigt.
    function mkbase_notifications_overview() {
        $counts = array_fill_keys(['live', 'gepland', 'verlopen', 'uit', 'leeg'], 0);
        foreach (mkbase_get_notifications() as $row) $counts[mkbase_notification_status($row)]++;

        $html = '<div class="mk-stats" aria-label="Status van de meldingen">';
        foreach (['live', 'gepland', 'verlopen', 'uit'] as $status) {
            $html .= '<span class="mk-pill mk-pill--' . $status . '" data-status="' . $status . '"><b>' . (int) $counts[$status] . '</b> '
                . esc_html(mkbase_notification_status_label($status)) . '</span>';
        }
        $html .= '</div>';

        if ($counts['leeg']) {
            $html .= '<p class="mk-stats__hint">' . (int) $counts['leeg'] . ' melding(en) staan aan maar hebben nog geen tekst of knop, en worden daarom niet getoond.</p>';
        }
        return $html;
    }
?>
