<?php
    add_action('admin_enqueue_scripts', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-diagnose') return;
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', [], null);
        wp_enqueue_style('mk-admin-shared', $theme_uri . '/assets/css/admin-shared.css', [], mkbase_asset_version($theme_dir . '/assets/css/admin-shared.css'));
        wp_enqueue_style('mk-admin-diagnostics', $theme_uri . '/assets/css/admin-diagnostics.css', ['mk-admin-shared'], mkbase_asset_version($theme_dir . '/assets/css/admin-diagnostics.css'));
        wp_enqueue_script('mk-admin-diagnostics', $theme_uri . '/assets/js/admin-diagnostics.js', [], mkbase_asset_version($theme_dir . '/assets/js/admin-diagnostics.js'), true);
    });

    add_action('admin_footer', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-diagnose') return;
        echo '<div id="mkbase-diagnose-content" style="display:none;">';
        echo mkbase_get_diagnostics_html();
        echo '</div>';
    });

    function mkbase_diagnostics_status_row($label, $value) {
        return '<div class="mkbase-diag-status__row"><span class="mkbase-diag-status__label">' . esc_html($label) . '</span>'
            . '<span class="mkbase-diag-status__value">' . $value . '</span></div>';
    }

    function mkbase_diagnostics_badge($ok, $label) {
        $class = $ok ? 'mkbase-diag-badge--ok' : 'mkbase-diag-badge--warning';
        return '<span class="mkbase-diag-badge ' . $class . '">' . esc_html($label) . '</span>';
    }

    // Voor instellingen/toggles: aan/uit is geen goed/fout, dus geen groen/amber.
    function mkbase_diagnostics_toggle($enabled) {
        $class = $enabled ? 'mkbase-diag-badge--info' : 'mkbase-diag-badge--neutral';
        return '<span class="mkbase-diag-badge ' . $class . '">' . ($enabled ? 'aan' : 'uit') . '</span>';
    }

    // Zet PHP-ini-notatie ("256M", "1G") om naar bytes, voor de memory_limit-check.
    function mkbase_parse_size($size) {
        $size = trim($size);
        if ($size === '' || $size === '-1') {
            return -1; // geen limiet
        }
        $unit  = strtoupper(substr($size, -1));
        $value = (int) $size;
        switch ($unit) {
            case 'G': $value *= 1024;
            case 'M': $value *= 1024;
            case 'K': $value *= 1024;
        }
        return $value;
    }

    // Checkt of debug.log publiek via de browser te benaderen is. Resultaat 1 uur
    // gecached in een transient, zodat dit geen herhaalde externe requests oplevert —
    // draait bewust alleen wanneer de Diagnose-pagina zelf geladen wordt, niet globaal
    // op elke pagina-load van de site.
    function mkbase_check_debug_log_exposure() {
        $log_path = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($log_path)) {
            return;
        }

        $exposed = get_transient('mkbase_debug_log_exposed');
        if ($exposed === false) {
            $response = wp_remote_get(content_url('debug.log'), ['timeout' => 5]);
            $exposed  = (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) ? '1' : '0';
            set_transient('mkbase_debug_log_exposed', $exposed, HOUR_IN_SECONDS);
        }

        if ($exposed === '1') {
            mkbase_report_issue(
                'Het bestand <code>debug.log</code> is publiek benaderbaar via de browser ('
                . esc_html(content_url('debug.log')) . '). Dit kan gevoelige foutmeldingen/paden lekken — '
                . 'blokkeer toegang via .htaccess of verwijder het bestand.',
                'warning',
                true
            );
        }
    }

    function mkbase_get_diagnostics_html() {
        global $mkbase_diagnostics_log;

        if (strpos(home_url(), 'https://') !== 0) {
            mkbase_report_issue('Deze site draait niet via HTTPS (<code>' . esc_html(home_url()) . '</code>). Schakel SSL in.', 'warning', true);
        }

        mkbase_check_debug_log_exposure();

        if (get_option('blog_public') === '0') {
            mkbase_report_issue('Deze site staat ingesteld om zoekmachines te ontmoedigen (Instellingen → Lezen → "Vraag zoekmachines deze site niet te tonen"). Zet dit uit zodra de site live mag zijn.', 'warning', true);
        }

        if (function_exists('get_field')) {
            $favicon = get_field('favicon', 'option');
            if (empty($favicon)) {
                mkbase_report_issue('Er is geen favicon ingesteld (Mediakanjers → Website instellingen).', 'info', false);
            }

            if (!get_field('titel', 'options') && !get_field('tekst', 'options')) {
                mkbase_report_issue('De 404-pagina heeft geen titel of tekst ingesteld (Mediakanjers → 404).', 'info', false);
            }
        }

        global $mkbase_legacy_theme_block_names;
        if (!empty($mkbase_legacy_theme_block_names)) {
            mkbase_report_issue(
                'Dit thema/child-thema gebruikt nog legacy ACF-blokken zonder <code>block.json</code>: <code>'
                . esc_html(implode('</code>, <code>', $mkbase_legacy_theme_block_names))
                . '</code>. Overweeg te migreren naar block.json-registratie.',
                'info',
                false
            );
        }

        if (!has_nav_menu('main_menu')) {
            mkbase_report_issue('De menu-locatie "Hoofdmenu" heeft geen menu gekoppeld.', 'info', false);
        }
        if (!has_nav_menu('footer_menu')) {
            mkbase_report_issue('De menu-locatie "Footermenu" heeft geen menu gekoppeld.', 'info', false);
        }

        foreach (mkbase_get_notifications() as $melding) {
            $status = mkbase_notification_status($melding);
            $naam   = esc_html($melding['melding_naam'] ?: 'Naamloos');
            if ($status === 'verlopen') {
                mkbase_report_issue('Melding "' . $naam . '" staat aan, maar de eindtijd is voorbij. Zet hem uit of verwijder hem bij Mediakanjers → Meldingen.', 'info', false);
            } elseif ($status === 'leeg') {
                mkbase_report_issue('Melding "' . $naam . '" staat aan, maar heeft geen tekst of knop en wordt daarom niet getoond.', 'info', false);
            }
        }

        foreach (mkbase_core_field_group_copies() as $kopie) {
            $waar = $kopie['where'] === 'database'
                ? 'in de database (verwijder hem bij ACF → Veldgroepen)'
                : 'als <code>' . esc_html($kopie['where']) . '</code> in het child theme (verwijder dat bestand)';
            mkbase_report_issue(
                'De ACF-veldgroep "' . esc_html($kopie['title']) . '" staat ' . $waar . ', terwijl het core thema deze groep zelf levert. '
                . 'Zo komen wijzigingen uit core-updates mogelijk niet door. De ingevulde waarden blijven bewaard bij het verwijderen.',
                'warning',
                false
            );
        }

        $update_data = get_site_transient('update_themes');
        $pending     = $update_data->response[get_template()] ?? null;
        if (!empty($pending['new_version'])) {
            mkbase_report_issue('Er is een nieuwe MKBase-versie beschikbaar: ' . esc_html($pending['new_version']) . '.', 'info', false);
        }

        if (get_option('permalink_structure') === '') {
            mkbase_report_issue('De permalink-structuur staat op "Gewoon" (<code>?p=123</code>-URL\'s). Kies een nettere structuur bij Instellingen → Permalinks.', 'warning', true);
        }

        if (function_exists('get_field') && get_field('css_overschrijven', 'option') === 'Ja') {
            mkbase_report_issue('"CSS overschrijven" bij Meldingen (tabblad Technisch) staat op "Ja" — dit is bedoeld voor Mediakanjers-medewerkers, niet voor permanent gebruik op een klantsite.', 'info', false);
        }

        if (!wp_is_writable(wp_upload_dir()['basedir'])) {
            mkbase_report_issue('De uploads-map is niet schrijfbaar. Media-uploads werken hierdoor niet.', 'warning', true);
        }
        if (!wp_is_writable(WP_CONTENT_DIR)) {
            mkbase_report_issue('De map <code>wp-content</code> is niet schrijfbaar.', 'warning', true);
        }

        $free_space_check = @disk_free_space(ABSPATH);
        if ($free_space_check !== false && $free_space_check < 500 * 1024 * 1024) {
            mkbase_report_issue('Er is minder dan 500 MB vrije schijfruimte over (' . esc_html(size_format($free_space_check)) . ').', 'warning', true);
        }

        $issues = $mkbase_diagnostics_log ?? [];

        $core_version  = wp_get_theme(get_template())->get('Version');
        $debug_on      = defined('WP_DEBUG') && WP_DEBUG;
        $debug_log_on  = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;

        $html  = '<div class="mk-cover">';
        $html .= '<div class="mk-cover__eyebrow">MKBase Theme &nbsp;·&nbsp; Mediakanjers</div>';
        $html .= '<div class="mk-cover__title">Diagnose</div>';
        $html .= '<div class="mk-cover__sub">Overzicht van bevindingen op deze site — child-theme/core-conflicten, versie-eisen en andere signalen. Bedoeld voor de developer.</div>';
        $html .= '<div class="mk-cover__tags">';
        $html .= '<span class="mk-cover__tag">Core-versie ' . esc_html($core_version) . '</span>';
        if (!empty($issues)) {
            $html .= '<span class="mk-cover__tag mk-cover__tag--alert">' . count($issues) . ' bevinding(en)</span>';
        } else {
            $html .= '<span class="mk-cover__tag">Alles in orde</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= mkbase_admin_page_nav('mkbase-diagnose');

        $html .= '<div id="mkbase-diag-layout">';

        // ── Omgeving ─────────────────────────────────────────────
        $core_theme    = wp_get_theme(get_template());
        $requires_wp   = $core_theme->get('RequiresWP');
        $requires_php  = $core_theme->get('RequiresPHP');
        $wp_version    = get_bloginfo('version');
        $wp_ok         = !$requires_wp || version_compare($wp_version, $requires_wp, '>=');
        $php_ok        = !$requires_php || version_compare(PHP_VERSION, $requires_php, '>=');
        $memory_bytes  = mkbase_parse_size(ini_get('memory_limit'));
        $memory_ok     = $memory_bytes === -1 || $memory_bytes >= 128 * 1024 * 1024;

        $html .= '<div class="mkbase-diag-status">';
        $html .= '<h2>Omgeving</h2>';
        $html .= mkbase_diagnostics_status_row('MKBase core-versie', esc_html($core_version));

        if (is_child_theme()) {
            $child_theme = wp_get_theme();
            $headers     = get_file_data(get_stylesheet_directory() . '/style.css', ['requires_mkbase' => 'Requires MKBase']);
            $requires    = $headers['requires_mkbase'] ?: '—';
            $html .= mkbase_diagnostics_status_row('Actief child-theme', esc_html($child_theme->get('Name')) . ' (' . esc_html($child_theme->get('Version')) . ')');
            $html .= mkbase_diagnostics_status_row('Vereiste MKBase-versie', esc_html($requires));
        } else {
            $html .= mkbase_diagnostics_status_row('Actief child-theme', '— (mkbase draait als hoofdthema)');
        }

        $wp_value = esc_html($wp_version) . ($requires_wp ? ' (vereist: ' . esc_html($requires_wp) . ') ' : ' ') . mkbase_diagnostics_badge($wp_ok, $wp_ok ? 'OK' : 'te oud');
        $html .= mkbase_diagnostics_status_row('WordPress-versie', $wp_value);

        $php_value = esc_html(PHP_VERSION) . ($requires_php ? ' (vereist: ' . esc_html($requires_php) . ') ' : ' ') . mkbase_diagnostics_badge($php_ok, $php_ok ? 'OK' : 'te oud');
        $html .= mkbase_diagnostics_status_row('PHP-versie', $php_value);

        $memory_value = esc_html(ini_get('memory_limit')) . ' ' . mkbase_diagnostics_badge($memory_ok, $memory_ok ? 'OK' : 'laag');
        $html .= mkbase_diagnostics_status_row('PHP memory_limit', $memory_value);

        $html .= mkbase_diagnostics_status_row('WP_DEBUG', mkbase_diagnostics_badge($debug_on, $debug_on ? 'aan' : 'uit'));
        $html .= mkbase_diagnostics_status_row('WP_DEBUG_LOG', mkbase_diagnostics_badge($debug_log_on, $debug_log_on ? 'aan' : 'uit'));

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins    = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $active_names   = [];
        foreach ($active_plugins as $plugin_file) {
            if (isset($all_plugins[$plugin_file])) {
                $active_names[] = $all_plugins[$plugin_file]['Name'];
            }
        }
        $html .= mkbase_diagnostics_status_row('Actieve plugins (' . count($active_names) . ')', esc_html(implode(', ', $active_names)));

        global $wpdb;
        $html .= mkbase_diagnostics_status_row('Database table-prefix', esc_html($wpdb->prefix) . ' ' . ($wpdb->prefix === 'wp_' ? mkbase_diagnostics_toggle(false) : mkbase_diagnostics_toggle(true)));
        $html .= mkbase_diagnostics_status_row('SEO-plugin (Yoast)', mkbase_diagnostics_toggle(defined('WPSEO_VERSION')));
        $wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $html .= mkbase_diagnostics_status_row('WP-Cron', mkbase_diagnostics_toggle(!$wp_cron_disabled) . ($wp_cron_disabled ? ' (uitgeschakeld — vaak bewust, servercron actief)' : ''));

        $html .= '</div>';

        if (!$debug_log_on) {
            $html .= '<p class="mkbase-diag-hint">Bevindingen worden altijd naar de PHP-errorlog geschreven, maar landen pas in <code>wp-content/debug.log</code> als <code>WP_DEBUG_LOG</code> in <code>wp-config.php</code> op <code>true</code> staat. Handig om aan te zetten als de wp-admin niet bereikbaar is.</p>';
        }

        // ── Thema-instellingen ───────────────────────────────────
        $only_theme_blocks = function_exists('get_field') && get_field('alleen_thema_blokken', 'option');

        $html .= '<div class="mkbase-diag-status">';
        $html .= '<h2>Thema-instellingen</h2>';
        $alleen_blokken_value = mkbase_diagnostics_toggle($only_theme_blocks);
        if ($only_theme_blocks) {
            $extra_blocks = get_field('extra_zichtbare_blokken', 'option');
            $extra_blocks = is_array($extra_blocks) ? $extra_blocks : [];
            $alleen_blokken_value .= $extra_blocks ? ' + extra: ' . esc_html(implode(', ', $extra_blocks)) : '';
        }
        $html .= mkbase_diagnostics_status_row('Alleen thema-blokken', $alleen_blokken_value);
        $html .= mkbase_diagnostics_status_row('Paginastructuur vergrendeld', mkbase_diagnostics_toggle(function_exists('get_field') && get_field('volgorde_vergrendeld', 'option')));
        $html .= mkbase_diagnostics_status_row('Bestandseditor (Mediakanjers)', mkbase_diagnostics_toggle(function_exists('get_field') && get_field('bestandseditor_mediakanjers', 'option')));

        if (function_exists('get_field')) {
            $injection_points = [
                'in_de_head'                => 'In de head',
                'direct_na_body'            => 'Direct na body',
                'voor_de_sluitende_body'    => 'Voor sluitende body',
                'alleen_op_de_homepage'     => 'Alleen homepage',
                'alleen_in_de_footer'       => 'Alleen footer',
            ];
            $active_injections = [];
            foreach ($injection_points as $field_name => $label) {
                if (get_field($field_name, 'option')) {
                    $active_injections[] = $label;
                }
            }
            $html .= mkbase_diagnostics_status_row(
                'Code-injectie (Geavanceerd)',
                $active_injections ? mkbase_diagnostics_toggle(true) . ' ' . esc_html(implode(', ', $active_injections)) : mkbase_diagnostics_toggle(false)
            );
        }

        $html .= '</div>';

        // ── Performance ──────────────────────────────────────────
        global $wpdb;
        // Sinds WP 6.6 kent autoload meer waarden dan 'yes' (o.a. 'on', 'auto', 'auto-on')
        $autoload_values = wp_autoload_values_to_autoload();
        $placeholders    = implode(', ', array_fill(0, count($autoload_values), '%s'));
        $autoload_bytes  = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ($placeholders)", $autoload_values));
        $autoload_ok    = $autoload_bytes < 1024 * 1024;

        $debug_log_path = WP_CONTENT_DIR . '/debug.log';
        $debug_log_size = file_exists($debug_log_path) ? filesize($debug_log_path) : 0;
        $debug_log_size_ok = $debug_log_size < 10 * 1024 * 1024;

        $html .= '<div class="mkbase-diag-status">';
        $html .= '<h2>Performance</h2>';
        $html .= mkbase_diagnostics_status_row('Autoload-opties (wp_options)', size_format($autoload_bytes) . ' ' . mkbase_diagnostics_badge($autoload_ok, $autoload_ok ? 'OK' : 'groot'));
        $html .= mkbase_diagnostics_status_row('Grootte debug.log', ($debug_log_size > 0 ? size_format($debug_log_size) : '— (bestaat niet)') . ' ' . mkbase_diagnostics_badge($debug_log_size_ok, $debug_log_size_ok ? 'OK' : 'groot'));
        $html .= mkbase_diagnostics_status_row('Persistent object cache', mkbase_diagnostics_toggle(wp_using_ext_object_cache()));
        $html .= '</div>';

        if (!$autoload_ok) {
            $html .= '<p class="mkbase-diag-hint">Autoload-opties worden bij élke pagina-load in het geheugen geladen. Een grote hoeveelheid (vaak veroorzaakt door een plugin) kan de site vertragen.</p>';
        }
        if (!$debug_log_size_ok) {
            $html .= '<p class="mkbase-diag-hint"><code>debug.log</code> is groot geworden — overweeg het bestand op te ruimen.</p>';
        }

        // ── Server ───────────────────────────────────────────────
        $uploads_dir      = wp_upload_dir();
        $uploads_writable = wp_is_writable($uploads_dir['basedir']);
        $content_writable = wp_is_writable(WP_CONTENT_DIR);
        $htaccess_exists  = file_exists(ABSPATH . '.htaccess');
        $free_space       = @disk_free_space(ABSPATH);
        $free_space_ok    = $free_space === false || $free_space >= 500 * 1024 * 1024;
        $acf_json_files   = glob(get_template_directory() . '/acf-json/*.json') ?: [];

        $extensions = ['curl' => true, 'mbstring' => true, 'gd' => false, 'imagick' => false];

        $html .= '<div class="mkbase-diag-status">';
        $html .= '<h2>Server</h2>';
        $html .= mkbase_diagnostics_status_row('Uploads-map schrijfbaar', mkbase_diagnostics_badge($uploads_writable, $uploads_writable ? 'OK' : 'niet schrijfbaar'));
        $html .= mkbase_diagnostics_status_row('wp-content schrijfbaar', mkbase_diagnostics_badge($content_writable, $content_writable ? 'OK' : 'niet schrijfbaar'));
        $html .= mkbase_diagnostics_status_row('.htaccess aanwezig', mkbase_diagnostics_toggle($htaccess_exists));
        $html .= mkbase_diagnostics_status_row('Vrije schijfruimte', $free_space === false ? '— (onbekend)' : size_format($free_space) . ' ' . mkbase_diagnostics_badge($free_space_ok, $free_space_ok ? 'OK' : 'laag'));
        $html .= mkbase_diagnostics_status_row('ACF-JSON sync-map', $acf_json_files ? mkbase_diagnostics_toggle(true) . ' (' . count($acf_json_files) . ' bestand(en))' : mkbase_diagnostics_toggle(false) . ' (bewust — velden zijn PHP-local geregistreerd)');

        foreach ($extensions as $ext => $required) {
            $loaded = extension_loaded($ext);
            $badge  = $required ? mkbase_diagnostics_badge($loaded, $loaded ? 'OK' : 'ontbreekt') : mkbase_diagnostics_toggle($loaded);
            $html  .= mkbase_diagnostics_status_row('PHP-extensie: ' . $ext, $badge);
        }
        $html .= '</div>';

        if (!$uploads_writable || !$content_writable) {
            $html .= '<p class="mkbase-diag-hint">Een niet-schrijfbare map breekt media-uploads en/of thema-/pluginfunctionaliteit die bestanden probeert weg te schrijven.</p>';
        }

        // ── Bevindingen ──────────────────────────────────────────
        $html .= '<div class="mkbase-diag-findings">';
        $html .= '<h2>Bevindingen</h2>';

        if (empty($issues)) {
            $html .= '<p class="mkbase-diag-empty">' . mkbase_diagnostics_badge(true, 'OK') . ' Geen problemen gevonden tijdens dit paginabezoek.</p>';
        } else {
            foreach ($issues as $issue) {
                $severity   = $issue['severity'] ?? 'warning';
                $badgeClass = $severity === 'info' ? 'mkbase-diag-badge--info' : 'mkbase-diag-badge--warning';
                $badgeLabel = $severity === 'info' ? 'Info' : 'Waarschuwing';
                $html .= '<div class="mkbase-diag-issue mkbase-diag-issue--' . esc_attr($severity) . '">';
                $html .= '<span class="mkbase-diag-badge ' . $badgeClass . '">' . esc_html($badgeLabel) . '</span>';
                $html .= '<p>' . wp_kses_post($issue['message']) . '</p>';
                $html .= '<p class="mkbase-diag-issue__hint">Deze melding staat ook in de PHP-errorlog (<code>debug.log</code> als <code>WP_DEBUG_LOG</code> aan staat) — ook zichtbaar als de wp-admin niet bereikbaar is.</p>';
                $html .= '</div>';
            }
        }

        $html .= '</div>'; // .mkbase-diag-findings
        $html .= '</div>'; // #mkbase-diag-layout

        return $html;
    }
?>
