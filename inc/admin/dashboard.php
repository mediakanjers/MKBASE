<?php
    // Dashboard: welkomstblok van Mediakanjers met de startlijst. Bovenaan, over de volle breedte.

    add_action('wp_dashboard_setup', function () {
        wp_add_dashboard_widget('mkbase_docs_widget', 'Mediakanjers', 'mkbase_render_dashboard_widget');

        // Zet de widget bovenaan
        global $wp_meta_boxes;
        $widget = $wp_meta_boxes['dashboard']['normal']['core']['mkbase_docs_widget'] ?? null;
        if ($widget) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['mkbase_docs_widget']);
            $wp_meta_boxes['dashboard']['normal']['core'] = array_merge(
                ['mkbase_docs_widget' => $widget],
                $wp_meta_boxes['dashboard']['normal']['core']
            );
        }
    });

    // Startlijst: wat er op een nieuwe website ingevuld moet zijn. Afgevinkt op basis van wat er echt staat.
    function mkbase_startlijst_steps() {
        if (!function_exists('get_field')) return [];
        $settings = admin_url('admin.php?page=acf-options-website-instellingen');
        $filled   = function ($name) { return trim((string) get_field($name, 'options')) !== ''; };
        $menu     = wp_get_nav_menu_items('Hoofdmenu');

        $steps = [
            ['logo', 'Logo', 'Staat linksboven op elke pagina', (bool) get_field('logo', 'options'), $settings, 'Toevoegen'],
            ['favicon', 'Favicon', 'Het icoontje in het tabblad van de browser', (bool) get_field('favicon', 'options'), $settings, 'Toevoegen'],
            ['bedrijf', 'Bedrijfsgegevens', 'Naam, adres en telefoon of e-mail voor de footer',
                $filled('bedrijfsnaam') && $filled('adres') && $filled('postcode') && ($filled('telefoon') || $filled('email')), $settings . '#acf-group_mk_instellingen_bedrijf', 'Invullen'],
            ['socials', 'Sociale media', 'Links naar je kanalen in de footer', (bool) get_field('socials', 'options'), $settings . '#acf-group_mk_instellingen_social', 'Toevoegen'],
            ['menu', 'Hoofdmenu', 'Welke pagina\'s bovenaan in het menu staan', !empty($menu), current_user_can('edit_theme_options') ? admin_url('nav-menus.php') : '', 'Instellen'],
            ['404', '404-pagina', 'Wat bezoekers zien als een pagina niet bestaat', $filled('titel') || $filled('tekst'), admin_url('admin.php?page=acf-options-404'), 'Instellen'],
        ];
        return array_map(function ($s) {
            return array_combine(['id', 'title', 'hint', 'done', 'url', 'action'], $s);
        }, $steps);
    }

    function mkbase_startlijst_hidden() {
        return (bool) get_user_meta(get_current_user_id(), 'mkbase_startlijst_verborgen', true);
    }

    function mkbase_render_startlijst() {
        if (!current_user_can('edit_posts') || mkbase_startlijst_hidden()) return;
        $steps = mkbase_startlijst_steps();
        $done  = count(array_filter(array_column($steps, 'done')));
        $total = count($steps);
        if (!$total || $done === $total) return; // alles klaar: de lijst verdwijnt

        $left  = $total - $done;
        $circ  = 2 * M_PI * 30;
        $title = $done === 0 ? 'Maak je website compleet' : ($left <= 2 ? 'Je website is bijna compleet' : 'Je bent goed op weg');
        ?>
        <section class="mk-start" aria-labelledby="mk-start-title">
            <div class="mk-start__head">
                <div class="mk-start__ring">
                    <svg width="72" height="72" viewBox="0 0 72 72" aria-hidden="true">
                        <circle cx="36" cy="36" r="30" fill="none" stroke="#E7F1FB" stroke-width="8"/>
                        <circle cx="36" cy="36" r="30" fill="none" stroke="#0B6FB8" stroke-width="8" stroke-linecap="round" stroke-dasharray="<?php echo round($circ * $done / $total, 1) . ' ' . round($circ, 1); ?>" transform="rotate(-90 36 36)"/>
                    </svg>
                    <span><?php echo $done . '/' . $total; ?></span>
                </div>
                <div class="mk-start__intro">
                    <h2 id="mk-start-title"><?php echo esc_html($title); ?></h2>
                    <p><?php echo $left === 1 ? 'Nog 1 ding om in te vullen.' : 'Nog ' . $left . ' dingen om in te vullen.'; ?> Wat klaar is, vinken we automatisch af.</p>
                </div>
                <button type="button" class="mk-start__hide">Verbergen</button>
            </div>
            <ul class="mk-start__list">
                <?php foreach ($steps as $i => $s): ?>
                    <li class="mk-start__step<?php echo $s['done'] ? ' is-done' : ''; ?>">
                        <span class="mk-start__mark" aria-hidden="true"><?php echo $s['done'] ? '✓' : $i + 1; ?></span>
                        <div class="mk-start__text">
                            <strong><?php echo esc_html($s['title']); ?><span class="screen-reader-text"><?php echo $s['done'] ? ' (klaar)' : ' (nog te doen)'; ?></span></strong>
                            <span><?php echo esc_html($s['hint']); ?></span>
                        </div>
                        <?php if ($s['url']): ?>
                            <?php if ($s['done']): ?>
                                <a class="mk-start__edit" href="<?php echo esc_url($s['url']); ?>">Wijzigen<span class="screen-reader-text"> <?php echo esc_html($s['title']); ?></span></a>
                            <?php else: ?>
                                <a class="mk-start__btn" href="<?php echo esc_url($s['url']); ?>"><?php echo esc_html($s['action']); ?><span class="screen-reader-text"> <?php echo esc_html($s['title']); ?></span></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
    }

    function mkbase_render_dashboard_widget() {
        $url_docs      = admin_url('admin.php?page=mkbase-docs');
        $url_changelog = admin_url('admin.php?page=mkbase-changelog');

        $live = 0;
        if (function_exists('mkbase_get_notifications')) {
            foreach (mkbase_get_notifications() as $row) {
                if (mkbase_notification_status($row) === 'live') $live++;
            }
        }

        $tiles = [
            ['Meldingen', 'Een actie of mededeling bovenaan je website.', admin_url('admin.php?page=acf-options-meldingen'), $live ? $live . ' live' : '',
                'M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0'],
            ['Website instellingen', 'Logo, bedrijfsgegevens en sociale media.', admin_url('admin.php?page=acf-options-website-instellingen'), '',
                'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 13.7H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 7l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10.3 3V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z'],
            ['Uitleg', 'Alles over blokken, afbeeldingen en instellingen.', $url_docs, '',
                'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z'],
            ['Hulp nodig?', 'Neem contact op met Mediakanjers.', 'https://mediakanjers.nl/contact', '',
                'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
        ];
        ?>
        <div class="mk-dash">
            <section class="mk-welcome" aria-labelledby="mk-welcome-title">
                <div class="mk-welcome__head">
                    <svg width="48" height="48" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="16" fill="#29A8E0"/><text x="16" y="21" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-weight="800" font-size="13" fill="#fff">MK</text></svg>
                    <div class="mk-welcome__intro">
                        <h2 id="mk-welcome-title">Welkom bij je website</h2>
                        <p>Hier vind je de instellingen van je website, de uitleg en hulp van Mediakanjers.</p>
                    </div>
                    <div class="mk-welcome__actions">
                        <a class="mk-welcome__btn mk-welcome__btn--primary" href="<?php echo esc_url($url_docs); ?>">Bekijk de uitleg</a>
                        <a class="mk-welcome__btn" href="<?php echo esc_url($url_changelog); ?>">Wat is er nieuw</a>
                    </div>
                </div>
                <div class="mk-welcome__tiles">
                    <?php foreach ($tiles as [$title, $text, $url, $badge, $icon]):
                        $external = strpos($url, admin_url()) !== 0; ?>
                        <a class="mk-welcome__tile" href="<?php echo esc_url($url); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?>>
                            <span class="mk-welcome__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo esc_attr($icon); ?>"/></svg></span>
                            <strong><?php echo esc_html($title); ?></strong>
                            <span class="mk-welcome__text"><?php echo esc_html($text); ?><?php if ($external): ?><span class="screen-reader-text"> (opent in een nieuw tabblad)</span><?php endif; ?></span>
                            <?php if ($badge): ?><span class="mk-welcome__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php mkbase_render_startlijst(); ?>
        </div>
        <?php
    }

    // Startlijst verbergen (per gebruiker) of terughalen vanuit de Documentatie
    add_action('wp_ajax_mkbase_startlijst', function () {
        check_ajax_referer('mkbase_startlijst');
        if (!current_user_can('edit_posts')) wp_send_json_error(null, 403);
        update_user_meta(get_current_user_id(), 'mkbase_startlijst_verborgen', 1);
        wp_send_json_success();
    });

    add_action('admin_post_mkbase_startlijst_tonen', function () {
        check_admin_referer('mkbase_startlijst_tonen');
        delete_user_meta(get_current_user_id(), 'mkbase_startlijst_verborgen');
        wp_safe_redirect(admin_url());
        exit;
    });

    function mkbase_startlijst_restore_url() {
        return wp_nonce_url(admin_url('admin-post.php?action=mkbase_startlijst_tonen'), 'mkbase_startlijst_tonen');
    }

    // Widget boven de kolommen, over de volle breedte
    add_action('admin_footer', function () {
        if (get_current_screen()->id !== 'dashboard') return;
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var w=document.getElementById("mkbase_docs_widget"),h=document.querySelector(".metabox-holder");if(w&&h)h.insertBefore(w,h.firstChild);});</script>';
    });

    add_action('admin_enqueue_scripts', function ($hook) {
        if ($hook !== 'index.php') return;
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        wp_enqueue_style('mk-admin-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap', [], null);
        wp_enqueue_style('mk-admin-dashboard', $theme_uri . '/assets/css/admin-dashboard.css', ['mk-admin-fonts'], mkbase_asset_version($theme_dir . '/assets/css/admin-dashboard.css'));
        wp_enqueue_script('mk-admin-dashboard', $theme_uri . '/assets/js/admin-dashboard.js', [], mkbase_asset_version($theme_dir . '/assets/js/admin-dashboard.js'), true);
        wp_localize_script('mk-admin-dashboard', 'mkDash', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mkbase_startlijst'),
        ]);
    });
?>
