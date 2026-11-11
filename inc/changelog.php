<?php
    add_action('admin_enqueue_scripts', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-changelog') return;
        // MK-stijl en -balk komen uit inc/admin/ui.php (mk-admin); dit is alleen het changelog-deel
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-admin-changelog', $theme_uri . '/assets/css/admin-changelog.css', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/css/admin-changelog.css'));
        wp_enqueue_script('mk-admin-changelog', $theme_uri . '/assets/js/admin-changelog.js', ['mk-admin'], mkbase_asset_version($theme_dir . '/assets/js/admin-changelog.js'), true);
    });

    add_action('admin_footer', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-changelog') return;
        echo '<div id="mkbase-changelog-content" style="display:none;">';
        echo mkbase_get_changelog_html();
        echo '</div>';
    });

    function mkbase_format_date($date_str) {
        $parts = explode('-', trim($date_str));
        if (count($parts) !== 3) return $date_str;
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    function mkbase_get_item_badge($text) {
        $lower = strtolower($text);
        if (preg_match('/\b(toegevoegd|aangemaakt|nieuw|geïntroduceerd)\b/', $lower))
            return '<span class="mkbase-type-badge mkbase-type-new">Nieuw</span>';
        if (preg_match('/\b(opgelost|gerepareerd|fix|hersteld)\b/', $lower))
            return '<span class="mkbase-type-badge mkbase-type-fix">Fix</span>';
        if (preg_match('/\b(verbeterd|verbetering|geoptimaliseerd|mooier|gelikter)\b/', $lower))
            return '<span class="mkbase-type-badge mkbase-type-improvement">Verbetering</span>';
        if (preg_match('/\b(verplaatst|verwijderd|omgebouwd|hernoemd|vervangen)\b/', $lower))
            return '<span class="mkbase-type-badge mkbase-type-change">Wijziging</span>';
        if (preg_match('/\b(verhoogd|bijgewerkt|getest|geüpdatet|geupdated|uitgebreid)\b/', $lower))
            return '<span class="mkbase-type-badge mkbase-type-update">Update</span>';
        return '';
    }

    // Soort van een changelog-kopje (### Nieuw, ### Bugfix, …), of null als het geen bekende soort is
    function mkbase_changelog_section_type($heading) {
        $h = strtolower(trim($heading));
        if (str_starts_with($h, 'nieuw')) return 'new';
        if (str_contains($h, 'fix') || str_contains($h, 'opgelost')) return 'fix';
        if (str_starts_with($h, 'verbeter')) return 'improvement';
        if (str_starts_with($h, 'wijzig')) return 'change';
        if (str_starts_with($h, 'update')) return 'update';
        return null;
    }

    function mkbase_changelog_badge($type) {
        $labels = ['new' => 'Nieuw', 'fix' => 'Fix', 'improvement' => 'Verbetering', 'change' => 'Wijziging', 'update' => 'Update'];
        return '<span class="mkbase-type-badge mkbase-type-' . $type . '">' . $labels[$type] . '</span>';
    }

    // Simpele, losstaande markdown-naar-HTML-omzetting voor één changelog-sectie
    // (bijv. de tekst uit mkbase-update.json voor een nog niet geïnstalleerde
    // update) — geen versie-blokken/inklap-UI zoals mkbase_get_changelog_html(),
    // gewoon een leesbare weergave van koppen, lijsten en badges.
    function mkbase_render_changelog_markdown($markdown) {
        $lines       = explode("\n", str_replace("\r\n", "\n", (string) $markdown));
        $html        = '';
        $in_list     = false;
        $in_code     = false;
        $code_buffer = '';

        foreach ($lines as $raw) {
            $line = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
            $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
            $line = preg_replace('/`(.+?)`/', '<code>$1</code>', $line);

            if (str_starts_with($raw, '```')) {
                if (!$in_code) {
                    if ($in_list) { $html .= '</ul>'; $in_list = false; }
                    $in_code     = true;
                    $code_buffer = '';
                } else {
                    $in_code = false;
                    $html .= '<pre><code>' . htmlspecialchars($code_buffer, ENT_QUOTES, 'UTF-8') . '</code></pre>';
                }
                continue;
            }
            if ($in_code) { $code_buffer .= $raw . "\n"; continue; }

            if (str_starts_with($raw, '## ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                preg_match('/##\s+(.+?)(?:\s+—\s+(\d{4}-\d{2}-\d{2}))?$/', $raw, $m);
                $version = htmlspecialchars(trim($m[1] ?? substr($raw, 3)), ENT_QUOTES, 'UTF-8');
                $date    = isset($m[2]) ? mkbase_format_date($m[2]) : '';
                $html .= '<div class="mkbase-version-title">' . $version;
                if ($date) $html .= ' <span class="mkbase-version-date">' . $date . '</span>';
                $html .= '</div>';
            } elseif (str_starts_with($raw, '### ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $html .= '<h3>' . substr($line, 4) . '</h3>';
            } elseif (str_starts_with($raw, '# ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
            } elseif (str_starts_with($raw, '- ')) {
                if (!$in_list) { $html .= '<ul>'; $in_list = true; }
                $badge = mkbase_get_item_badge($raw);
                $html .= '<li>' . $badge . substr($line, 2) . '</li>';
            } elseif (str_starts_with($raw, '> ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $notice = htmlspecialchars(substr($raw, 2), ENT_QUOTES, 'UTF-8');
                $notice = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $notice);
                $notice = preg_replace('/`(.+?)`/', '<code>$1</code>', $notice);
                $html .= '<div class="mkbase-notice"><span class="dashicons dashicons-warning"></span>' . $notice . '</div>';
            } elseif (trim($raw) === '') {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
            } else {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $html .= '<p>' . $line . '</p>';
            }
        }

        if ($in_list) $html .= '</ul>';
        return $html;
    }

    function mkbase_get_changelog_html() {
        $theme_dir = get_template_directory();
        $file      = $theme_dir . '/CHANGELOG.md';
        if (!file_exists($file)) return '<p>CHANGELOG.md niet gevonden.</p>';

        $content = file_get_contents($file);
        $archive_dir = $theme_dir . '/changelog-archive';
        if (is_dir($archive_dir)) {
            $archive_files = glob($archive_dir . '/*.md');
            if ($archive_files) {
                usort($archive_files, function($a, $b) {
                    return version_compare(basename($b, '.md'), basename($a, '.md'));
                });
                foreach ($archive_files as $archive_file) {
                    $content .= "\n" . file_get_contents($archive_file);
                }
            }
        }

        // Normaliseert CRLF (Windows-regeleinden) naar LF — anders blijft er een \r
        // achter aan het eind van elke regel, wat de $-ankering in de datum-regex
        // hieronder breekt (de datum valt dan in de versietitel i.p.v. in het aparte,
        // Nederlands-opgemaakte datum-badge).
        $content = str_replace("\r\n", "\n", $content);
        $lines             = explode("\n", $content);
        $html              = '';
        $in_list           = false;
        $in_code           = false;
        $code_buffer       = '';
        $versions_rendered = 0;
        $section           = null;

        $installed_version = wp_get_theme(get_template())->get('Version');

        $theme_slug  = get_template();
        $update_data = get_site_transient('update_themes');
        $new_version = $update_data->response[$theme_slug]['new_version'] ?? null;
        $update_url  = wp_nonce_url(
            admin_url('update.php?action=upgrade-theme&theme=' . $theme_slug),
            'upgrade-theme_' . $theme_slug
        );

        // Eerst alle versies en datums verzamelen, voor de lijst rechts
        $versions = [];
        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.+?)(?:\s+—\s+(\d{4}-\d{2}-\d{2}))?\s*$/', $line, $m)) {
                $versions[] = ['version' => trim($m[1]), 'date' => isset($m[2]) ? mkbase_format_date($m[2]) : ''];
            }
        }
        $has_update = $new_version && version_compare($new_version, $installed_version, '>');

        $html .= '<div class="mk-cl">';
        $html .= '<div class="mk-cl__main">';

        // Filter op soort en zoeken
        $filters = [
            'all'         => 'Alles',
            'new'         => 'Nieuw',
            'fix'         => 'Fix',
            'improvement' => 'Verbetering',
            'change'      => 'Wijziging',
            'update'      => 'Update',
        ];
        $html .= '<div class="mk-cl__tools">';
        $html .= '<label class="mk-search"><span class="screen-reader-text">Zoeken in de changelog</span><input type="search" id="mkbase-changelog-search" placeholder="Zoeken, bijv. meldingen of lightbox" autocomplete="off"></label>';
        $html .= '<div id="mkbase-changelog-filters" class="mk-segmented" role="group" aria-label="Filter op soort">';
        foreach ($filters as $key => $label) {
            $html .= '<button type="button" class="mkbase-filter-tab' . ($key === 'all' ? ' is-active' : '') . '" data-filter="' . $key . '" aria-pressed="' . ($key === 'all' ? 'true' : 'false') . '">' . $label . '</button>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p class="mk-cl__empty" hidden>Geen wijzigingen gevonden. Probeer een ander woord of filter.</p>';

        // Wat de beschikbare update brengt
        if ($has_update) {
            $pending_changelog = $update_data->response[$theme_slug]['mkbase_changelog'] ?? '';
            if ($pending_changelog) {
                $html .= '<section class="mk-cl__pending"><h2>Nieuw in versie ' . esc_html($new_version) . '</h2>';
                $html .= mkbase_render_changelog_markdown($pending_changelog);
                $html .= '</section>';
            }
        }

        $version_body_open = false;

        foreach ($lines as $line) {
            $raw  = $line;
            $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
            $line = preg_replace('/`(.+?)`/', '<code>$1</code>', $line);

            if (str_starts_with($raw, '```')) {
                if (!$in_code) {
                    if ($in_list) { $html .= '</ul>'; $in_list = false; }
                    $in_code     = true;
                    $code_buffer = '';
                } else {
                    $in_code = false;
                    $html .= '<pre><code>' . htmlspecialchars($code_buffer, ENT_QUOTES, 'UTF-8') . '</code></pre>';
                    $code_buffer = '';
                }
                continue;
            }

            if ($in_code) {
                $code_buffer .= $raw . "\n";
                continue;
            }

            if (str_starts_with($raw, '## ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                if ($version_body_open) { $html .= '</div></section>'; $version_body_open = false; }

                preg_match('/##\s+(.+?)(?:\s+—\s+(\d{4}-\d{2}-\d{2}))?$/', $raw, $matches);
                $version = htmlspecialchars(trim($matches[1] ?? substr($raw, 3)), ENT_QUOTES, 'UTF-8');
                $date    = isset($matches[2]) ? mkbase_format_date($matches[2]) : '';

                $versions_rendered++;
                $section = null;

                // De eerste twee versies staan open, oudere zijn ingeklapt
                $is_open    = $versions_rendered <= 2;
                $is_current = ltrim($version, 'vV') === $installed_version;
                $html .= '<section class="mkbase-version-block" id="versie-' . esc_attr(sanitize_title($version)) . '">';
                $html .= '<h2 class="mkbase-version-header">';
                $html .= '<button class="mkbase-version-toggle" type="button" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
                $html .= '<span class="mkbase-version-title">' . $version . '</span>';
                if ($is_current) $html .= '<span class="mk-cl__current">Jouw versie</span>';
                if ($date) $html .= '<span class="mkbase-version-date">' . $date . '</span>';
                $html .= '<span class="mk-cl__chevron" aria-hidden="true"></span>';
                $html .= '</button>';
                $html .= '</h2>';
                $html .= '<div class="mkbase-version-body"' . ($is_open ? '' : ' hidden') . '>';
                $version_body_open = true;

            } elseif (str_starts_with($raw, '### ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $html .= '<h3>' . substr($line, 4) . '</h3>';
                // Het kopje (Nieuw, Verbetering, …) bepaalt het label van de punten eronder
                $section = mkbase_changelog_section_type(substr($raw, 4));

            } elseif (str_starts_with($raw, '# ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }

            } elseif (str_starts_with($raw, '- ')) {
                if (!$in_list) { $html .= '<ul>'; $in_list = true; }
                $badge = $section ? mkbase_changelog_badge($section) : mkbase_get_item_badge($raw);
                $html .= '<li>' . $badge . '<span class="mk-cl__txt">' . substr($line, 2) . '</span></li>';

            } elseif (str_starts_with($raw, '> ')) {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $notice = htmlspecialchars(substr($raw, 2), ENT_QUOTES, 'UTF-8');
                $notice = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $notice);
                $notice = preg_replace('/`(.+?)`/', '<code>$1</code>', $notice);
                $html .= '<div class="mkbase-notice"><span class="dashicons dashicons-warning"></span>' . $notice . '</div>';

            } elseif (trim($raw) === '') {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }

            } else {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $html .= '<p>' . $line . '</p>';
            }
        }

        if ($in_list) $html .= '</ul>';
        if ($version_body_open) $html .= '</div></section>';
        $html .= '</div>'; // .mk-cl__main

        // Rechts: updatestatus en de lijst met versies
        $html .= '<aside class="mk-cl__aside">';
        $html .= '<div class="mk-cl__status' . ($has_update ? ' is-update' : '') . '">';
        $html .= '<div class="mk-cl__status-head"><span class="mk-cl__status-icon" aria-hidden="true"></span><div>';
        if ($has_update) {
            $html .= '<strong>Update beschikbaar</strong><span>Versie ' . esc_html($new_version) . ' staat klaar (jij hebt ' . esc_html($installed_version) . ')</span>';
        } else {
            $html .= '<strong>Up-to-date</strong><span>Versie ' . esc_html($installed_version) . ' is geïnstalleerd</span>';
        }
        $html .= '</div></div>';
        if ($has_update) {
            $html .= '<a href="' . esc_url($update_url) . '" class="mk-btn">Nu bijwerken</a>';
        } else {
            $html .= '<p>Updates verschijnen bij Dashboard → Updates zodra Mediakanjers een nieuwe versie uitbrengt.</p>';
        }
        $html .= '</div>';

        $html .= '<nav class="mk-cl__versions" aria-label="Versies"><div class="mk-cl__versions-title">Versies</div>';
        foreach ($versions as $v) {
            $current = ltrim($v['version'], 'vV') === $installed_version;
            $html .= '<a href="#versie-' . esc_attr(sanitize_title($v['version'])) . '"' . ($current ? ' aria-current="true"' : '') . '>'
                . '<span>' . esc_html($v['version']) . '</span><span>' . esc_html($v['date']) . '</span></a>';
        }
        $html .= '</nav>';
        $html .= '</aside>';
        $html .= '</div>'; // .mk-cl
        return $html;
    }
?>
