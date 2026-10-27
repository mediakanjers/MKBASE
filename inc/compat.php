<?php
    /**
     * Centrale registratie van governance-bevindingen (child-theme/core-conflicten
     * e.d.). Logt altijd naar de PHP-errorlog — komt in wp-content/debug.log terecht
     * zodra WP_DEBUG_LOG in wp-config.php aan staat, ook als niemand de wp-admin
     * bezoekt — en verzamelt de bevinding voor de "MKBase Diagnose"-pagina
     * (inc/diagnostics.php), naast de gebruikelijke admin_notices-waarschuwing.
     */
    function mkbase_report_issue($message, $severity = 'warning', $notify = true) {
        error_log('[MKBase] ' . $message);

        global $mkbase_diagnostics_log;
        if (!isset($mkbase_diagnostics_log)) {
            $mkbase_diagnostics_log = [];
        }
        $mkbase_diagnostics_log[] = [
            'message'  => $message,
            'severity' => $severity,
        ];

        if (!$notify) {
            return;
        }

        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>MKBase waarschuwing:</strong> ' . wp_kses_post($message);
            echo '</p></div>';
        });
    }

    /**
     * Controleert of een functie al gedefinieerd is in het child thema.
     * Toont een admin notice als dat het geval is.
     * Geeft true terug als de functie al bestaat (duplicate), false als hij veilig gedefinieerd kan worden.
     */
    function mkbase_check_duplicate($function_name) {
        if (!function_exists($function_name)) {
            return false;
        }

        mkbase_report_issue(
            'De functie <code>' . esc_html($function_name) . '()</code> bestaat zowel in het core thema als in het child thema. '
            . 'Verwijder de versie uit het child thema — alleen de core versie wordt gebruikt.'
        );

        return true;
    }

    /**
     * Controleert of het actieve child thema een 'Requires MKBase: X.Y.Z'-regel
     * in zijn style.css heeft staan, en of de actieve core-versie daaraan voldoet.
     * WP_Theme::get() kent geen zelfbedachte headers, dus deze wordt rechtstreeks
     * uit het bestand gelezen met get_file_data(). Waarschuwt alleen, blokkeert niets.
     */
    function mkbase_check_version_requirement() {
        if (!is_child_theme()) {
            return;
        }

        $core_version = wp_get_theme(get_template())->get('Version');

        $child_style = get_stylesheet_directory() . '/style.css';
        $headers     = get_file_data($child_style, ['requires_mkbase' => 'Requires MKBase']);
        $required    = $headers['requires_mkbase'];

        if (!$required) {
            return;
        }

        if (version_compare($core_version, $required, '<')) {
            mkbase_report_issue(
                'Dit child-theme vereist MKBase ' . esc_html($required)
                . ', maar de actieve core-versie is ' . esc_html($core_version) . '. Werk het core-thema bij.'
            );
        }
    }
    add_action('after_setup_theme', 'mkbase_check_version_requirement');
?>
