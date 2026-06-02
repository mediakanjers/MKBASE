<?php
    /**
     * Controleert of een functie al gedefinieerd is in het child thema.
     * Toont een admin notice als dat het geval is.
     * Geeft true terug als de functie al bestaat (duplicate), false als hij veilig gedefinieerd kan worden.
     */
    function mkbase_check_duplicate($function_name) {
        if (!function_exists($function_name)) {
            return false;
        }

        add_action('admin_notices', function() use ($function_name) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>MKBase waarschuwing:</strong> De functie <code>' . esc_html($function_name) . '()</code> ';
            echo 'bestaat zowel in het core thema als in het child thema. ';
            echo 'Verwijder de versie uit het child thema — alleen de core versie wordt gebruikt.';
            echo '</p></div>';
        });

        return true;
    }
?>
