<?php
    add_action('admin_enqueue_scripts', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-changelog') return;
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-admin-changelog', $theme_uri . '/assets/css/admin-changelog.css', [], filemtime($theme_dir . '/assets/css/admin-changelog.css'));
        wp_enqueue_script('mk-admin-changelog', $theme_uri . '/assets/js/admin-changelog.js', [], filemtime($theme_dir . '/assets/js/admin-changelog.js'), true);
    });

    add_action('admin_footer', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'mkbase-changelog') return;
        echo '<div id="mkbase-changelog-content" style="display:none;">';
        echo mkbase_get_changelog_html();
        echo '</div>';
    });

    function mkbase_admin_page_nav($active) {
        $pages = [
            'mkbase-changelog' => ['label' => 'Changelog',     'icon' => 'dashicons-backup'],
            'mkbase-docs'      => ['label' => 'Documentatie',  'icon' => 'dashicons-book'],
        ];
        $html  = '<nav class="mkbase-page-nav">';
        foreach ($pages as $slug => $page) {
            $is_active = $active === $slug ? ' is-active' : '';
            $url       = esc_url(admin_url('admin.php?page=' . $slug));
            $html .= '<a href="' . $url . '" class="mkbase-page-nav__tab' . $is_active . '">';
            $html .= '<span class="dashicons ' . esc_attr($page['icon']) . '"></span>';
            $html .= esc_html($page['label']);
            $html .= '</a>';
        }
        $html .= '</nav>';
        return $html;
    }

    function mkbase_format_date($date_str) {
        $months = ['01'=>'januari','02'=>'februari','03'=>'maart','04'=>'april','05'=>'mei','06'=>'juni','07'=>'juli','08'=>'augustus','09'=>'september','10'=>'oktober','11'=>'november','12'=>'december'];
        $parts = explode('-', $date_str);
        if (count($parts) !== 3) return $date_str;
        return intval($parts[2]) . ' ' . ($months[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
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

        $lines             = explode("\n", $content);
        $html              = '';
        $in_list           = false;
        $in_code           = false;
        $code_buffer       = '';
        $version_count     = 0;
        $latest_date       = '';
        $is_first          = true;
        $versions_rendered = 0;
        $older_opened      = false;

        $installed_version = wp_get_theme(get_template())->get('Version');

        $theme_slug  = get_template();
        $update_data = get_site_transient('update_themes');
        $new_version = $update_data->response[$theme_slug]['new_version'] ?? null;
        $update_url  = wp_nonce_url(
            admin_url('update.php?action=upgrade-theme&theme=' . $theme_slug),
            'upgrade-theme_' . $theme_slug
        );

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '## ')) {
                $version_count++;
                if ($version_count === 1 && preg_match('/—\s*(\d{4}-\d{2}-\d{2})/', $line, $m)) {
                    $latest_date = $m[1];
                }
            }
        }

        $html .= mkbase_admin_page_nav('mkbase-changelog');

        $html .= '<div id="mkbase-changelog-summary">';
        $html .= '<div class="mkbase-summary-stat"><span class="mkbase-summary-number">' . esc_html($installed_version) . '</span><span class="mkbase-summary-label">geïnstalleerd</span></div>';
        $html .= '<div class="mkbase-summary-stat"><span class="mkbase-summary-number">' . $version_count . '</span><span class="mkbase-summary-label">release' . ($version_count !== 1 ? 's' : '') . '</span></div>';
        if ($latest_date) {
            $html .= '<div class="mkbase-summary-stat"><span class="mkbase-summary-number">' . mkbase_format_date($latest_date) . '</span><span class="mkbase-summary-label">laatste update</span></div>';
        }
        if ($new_version && version_compare($new_version, $installed_version, '>')) {
            $html .= '<div class="mkbase-summary-update">';
            $html .= '<span class="mkbase-update-text"><span class="dashicons dashicons-update"></span> Versie <strong>' . esc_html($new_version) . '</strong> beschikbaar</span>';
            $html .= '<a href="' . esc_url($update_url) . '" class="mkbase-update-btn">Nu bijwerken</a>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $filters = [
            'all'         => 'Alles',
            'new'         => 'Nieuw',
            'fix'         => 'Fix',
            'improvement' => 'Verbetering',
            'change'      => 'Wijziging',
            'update'      => 'Update',
        ];
        $html .= '<div id="mkbase-changelog-filters">';
        foreach ($filters as $key => $label) {
            $html .= '<button class="mkbase-filter-tab' . ($key === 'all' ? ' is-active' : '') . '" data-filter="' . $key . '">' . $label . '</button>';
        }
        $html .= '</div>';

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
                if ($version_body_open) { $html .= '</div></div>'; $version_body_open = false; }

                preg_match('/##\s+(.+?)(?:\s+—\s+(\d{4}-\d{2}-\d{2}))?$/', $raw, $matches);
                $version = htmlspecialchars(trim($matches[1] ?? substr($raw, 3)), ENT_QUOTES, 'UTF-8');
                $date    = isset($matches[2]) ? mkbase_format_date($matches[2]) : '';

                $versions_rendered++;

                if ($versions_rendered === 2) {
                    $older_opened = true;
                    $html .= '<button id="mkbase-older-toggle" type="button">';
                    $html .= '<span class="dashicons dashicons-arrow-down-alt2"></span>';
                    $html .= '<span class="mkbase-older-label">Bekijk oudere versies</span>';
                    $html .= '</button>';
                    $html .= '<div id="mkbase-changelog-older" style="display:none;">';
                }

                $is_open = $versions_rendered === 1;
                $html .= '<div class="mkbase-version-block">';
                $html .= '<div class="mkbase-version-header">';
                $html .= '<button class="mkbase-version-toggle" type="button" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
                $html .= '<span class="mkbase-version-title">' . $version . '</span>';

                if ($is_first) {
                    if ($new_version && version_compare($new_version, $installed_version, '>')) {
                        $html .= '<span class="mkbase-badge-outdated">Update beschikbaar</span>';
                    } else {
                        $html .= '<span class="mkbase-badge-latest">Geïnstalleerd</span>';
                    }
                    $is_first = false;
                }

                if ($date) $html .= '<span class="mkbase-version-date">' . $date . '</span>';
                $html .= '<span class="mkbase-version-toggle-icon dashicons ' . ($is_open ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2') . '"></span>';
                $html .= '</button>';
                $html .= '</div>';
                $html .= '<div class="mkbase-version-body"' . ($is_open ? '' : ' style="display:none;"') . '>';
                $version_body_open = true;

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
        if ($version_body_open) $html .= '</div></div>';
        if ($older_opened) $html .= '</div>';
        return $html;
    }
?>
