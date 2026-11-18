<?php
    // Alleen downloads uit de eigen GitHub-releases worden geïnstalleerd, ook als het manifest ooit iets
    // anders zou bevatten
    define('MKBASE_UPDATER_DOWNLOAD_PREFIX', 'https://github.com/mediakanjers/MKBASE/releases/download/');

    // Het manifest (mkbase-update.json op GitHub). WordPress slaat de update-gegevens bij één controle
    // meerdere keren op, en elke keer liep dit filter: zonder cache waren dat twee verzoeken naar GitHub
    // van elk max. 30 seconden. Nu: één keer per request, 6 uur bewaard (een mislukte poging 30 minuten),
    // en opnieuw bij "Opnieuw controleren" onder Dashboard → Updates.
    function mkbase_update_manifest() {
        static $manifest = false;
        if ($manifest !== false) return $manifest;

        $opnieuw = is_admin() && isset($_GET['force-check']) && current_user_can('update_themes');
        $cache   = $opnieuw ? false : get_site_transient('mkbase_update_manifest');
        if (is_array($cache)) return $manifest = $cache['data'];

        $remote = wp_remote_get(MKBASE_UPDATER_URL, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);
        $data = null;
        if (!is_wp_error($remote) && wp_remote_retrieve_response_code($remote) === 200) {
            $json = json_decode(wp_remote_retrieve_body($remote));
            if (is_object($json)) $data = $json;
        }
        set_site_transient('mkbase_update_manifest', ['data' => $data], $data ? 6 * HOUR_IN_SECONDS : 30 * MINUTE_IN_SECONDS);
        return $manifest = $data;
    }

    add_filter('pre_set_site_transient_update_themes', 'mkbase_check_for_update');
    // site_transient_update_themes
    function mkbase_check_for_update($transient) {
        if (empty($transient->checked)) return $transient;

        $response = mkbase_update_manifest();
        if (!$response) return $transient;

        $theme_slug = get_template();
        $current_version = $transient->checked[$theme_slug] ?? null;

        if (
            isset($response->version, $response->download_url) &&
            is_string($response->download_url) &&
            strpos($response->download_url, MKBASE_UPDATER_DOWNLOAD_PREFIX) === 0 &&
            !empty($current_version) &&
            version_compare($response->version, $current_version, '>')
        ) {
            $transient->response[$theme_slug] = [
                'theme' => $theme_slug,
                'new_version' => $response->version,
                // Wijst naar onze eigen "wat is er nieuw"-pagina i.p.v. de GitHub releases-
                // pagina rechtstreeks: WordPress laadt deze URL in een iframe-thickbox
                // (zie wp_theme_update_row()), en GitHub blokkeert embedding in iframes
                // (X-Frame-Options) — die popup bleef daardoor altijd leeg.
                'url' => admin_url('admin.php?page=mkbase-update-details'),
                'package' => $response->download_url,
                // Eigen sleutel, wordt door WP core genegeerd maar door onszelf gebruikt
                // om de changelog-tekst van deze update te tonen (zie inc/changelog.php)
                // vóórdat de klant 'm daadwerkelijk binnenhaalt.
                'mkbase_changelog' => $response->changelog ?? '',
            ];
        }

        return $transient;
    }

    // Verborgen pagina, alleen bedoeld als doel van de "View version details"-thickbox-
    // link hierboven — toont de changelog-tekst van de update die nog niet binnengehaald
    // is. Rendert met iframe_header()/iframe_footer() (dezelfde aanpak als WP's eigen
    // plugin-informatie-popup) zodat het los van de normale admin-chrome netjes in de
    // iframe past.
    add_action('admin_menu', function() {
        add_submenu_page(null, 'MKBase update', 'MKBase update', 'update_themes', 'mkbase-update-details', function() {
            if (!current_user_can('update_themes')) {
                wp_die('Geen toegang.');
            }

            $theme_slug  = get_template();
            $theme       = wp_get_theme($theme_slug);
            $update_data = get_site_transient('update_themes');
            $update      = $update_data->response[$theme_slug] ?? null;

            iframe_header(sprintf('%s %s', $theme->get('Name'), $update['new_version'] ?? ''));

            if (!$update) {
                echo '<p>Geen updategegevens beschikbaar.</p>';
            } else {
                // Zelfde stijl als Mediakanjers → Changelog (tokens hangen aan body.mk-admin)
                $theme_uri = get_template_directory_uri();
                $theme_dir = get_template_directory();
                wp_enqueue_style('mk-admin', $theme_uri . '/assets/css/mk-admin.css', [], mkbase_asset_version($theme_dir . '/assets/css/mk-admin.css'));
                wp_enqueue_style('mk-admin-changelog', $theme_uri . '/assets/css/admin-changelog.css', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/css/admin-changelog.css'));
                wp_print_styles(['mk-admin', 'mk-admin-changelog']);
                echo '<script>document.body.classList.add("mk-admin");</script>';

                echo '<div class="mk-cl__pending" style="margin:16px;">';
                echo !empty($update['mkbase_changelog'])
                    ? mkbase_render_changelog_markdown($update['mkbase_changelog'])
                    : '<p>Geen changelog beschikbaar voor deze versie.</p>';
                echo '</div>';
            }

            iframe_footer();
            exit;
        });
    });
?>