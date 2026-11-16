<?php
    /**
     * Cache-busting versienummer voor een asset-bestand, op basis van filemtime().
     * Bewust niet gecached (bijv. in een transient): dan zouden bezoekers na een
     * thema-update nog tot de cache verloopt de oude CSS/JS krijgen, en een transient
     * kost zonder object cache een databasequery — duurder dan filemtime() zelf, dat
     * PHP binnen een request al in de stat-cache houdt.
     */
    function mkbase_asset_version($path) {
        if (!file_exists($path)) return false;

        return filemtime($path);
    }
?>
