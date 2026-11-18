<?php
    // =============================================================================
    // Beveiliging (sinds 1.7.4)
    // =============================================================================
    // Gebruikersnamen niet publiek: WordPress laat ze standaard opvragen via /?author=1 (doorsturen naar
    // /author/<naam>/, meestal de inlognaam), de REST API (/wp-json/wp/v2/users) en de sitemap van
    // WordPress zelf (zonder Yoast). Met de inlognaam is de helft van de inloggegevens bekend.
    // Ingelogde gebruikers (o.a. de blokeditor) merken er niets van; het inlogscherm verandert niet.
    // Uitzetten per site: add_filter('mkbase_gebruikers_afschermen', '__return_false');

    function mkbase_gebruikers_afschermen() {
        return (bool) apply_filters('mkbase_gebruikers_afschermen', true);
    }

    // /?author=1: naar de voorpagina, vóór WordPress doorstuurt naar /author/<naam>/ (redirect_canonical, prioriteit 10)
    add_action('template_redirect', function () {
        if (!isset($_GET['author']) || is_user_logged_in() || !mkbase_gebruikers_afschermen()) return;
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }, 1);

    // REST API: de gebruikerslijst en losse gebruikers alleen voor ingelogde gebruikers
    add_filter('rest_endpoints', function ($endpoints) {
        if (is_user_logged_in() || !mkbase_gebruikers_afschermen()) return $endpoints;
        foreach (array_keys($endpoints) as $route) {
            if (strpos($route, '/wp/v2/users') === 0) unset($endpoints[$route]);
        }
        return $endpoints;
    });

    // Sitemap van WordPress zelf (wp-sitemap.xml, als Yoast niet actief is): geen lijst met gebruikers
    add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
        return $name === 'users' && mkbase_gebruikers_afschermen() ? false : $provider;
    }, 10, 2);
?>
