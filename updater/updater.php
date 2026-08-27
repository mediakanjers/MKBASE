<?php
    add_filter('pre_set_site_transient_update_themes', 'mkbase_check_for_update');
    // site_transient_update_themes
    function mkbase_check_for_update($transient) {
        if (empty($transient->checked)) return $transient;

        $remote = wp_remote_get(MKBASE_UPDATER_URL, [
            'timeout' => 30,
            'headers' => ['Accept' => 'application/json']
        ]);

        if (is_wp_error($remote) || wp_remote_retrieve_response_code($remote) !== 200) {
            return $transient;
        }

        $response = json_decode(wp_remote_retrieve_body($remote));
        if (!$response) return $transient;

        $theme_slug = get_template();
        $current_version = $transient->checked[$theme_slug] ?? null;

        if (
            isset($response->version, $response->download_url) &&
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
                $theme_uri = get_template_directory_uri();
                wp_enqueue_style('mk-admin-shared', $theme_uri . '/assets/css/admin-shared.css', [], filemtime(get_template_directory() . '/assets/css/admin-shared.css'));
                wp_enqueue_style('mk-admin-changelog', $theme_uri . '/assets/css/admin-changelog.css', ['mk-admin-shared'], filemtime(get_template_directory() . '/assets/css/admin-changelog.css'));
                wp_print_styles(['mk-admin-shared', 'mk-admin-changelog']);

                echo '<div class="mkbase-pending-changelog" style="margin:16px;">';
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