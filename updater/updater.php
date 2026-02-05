<?php
    add_filter('site_transient_update_themes', 'mkbase_check_for_update');

    function mkbase_check_for_update($transient) {
        if (empty($transient->checked)) return $transient;

        $remote = wp_remote_get('https://wordpress-1253021-5488963.cloudwaysapps.com/updater/mkbase-update.json', [
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
            // Controleer of het zipbestand bestaat en een geldig .zip-bestand is
            $zip_check = wp_remote_head($response->download_url);

            if (
                !is_wp_error($zip_check) &&
                wp_remote_retrieve_response_code($zip_check) === 200 &&
                strpos(wp_remote_retrieve_header($zip_check, 'content-type'), 'zip') !== false
            ) {
                $transient->response[$theme_slug] = [
                    'theme' => $theme_slug,
                    'new_version' => $response->version,
                    'url' => $response->details_url ?? '',
                    'package' => $response->download_url,
                ];
            }
        }

        return $transient;
    }
?>
