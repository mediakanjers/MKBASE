<?php
    /**
     * Laadt alle subvelden van een herhalende lijst (repeater) op een Mediakanjers-optiepagina in één
     * query, vóór get_field() ze een voor een opvraagt (sinds 1.7.4).
     *
     * De opgeslagen waarden laden al mee met autoload (inc/acf/acf.php), maar een subveld dat nooit is
     * opgeslagen (bijv. de tijden van een dag die op Gesloten staat, of een veld dat door een voorwaarde
     * verborgen was) zoekt WordPress anders elk met een eigen query op. Hiermee onthoudt WordPress in
     * één keer welke er bestaan en welke niet.
     *
     * @param string $name      Naam van de repeater, bijv. 'meldingen'
     * @param string $field_key Sleutel van de repeater, bijv. 'field_mk_meldingen'
     */
    function mkbase_prime_acf_repeater($name, $field_key) {
        static $gedaan = [];
        if (isset($gedaan[$name]) || !function_exists('acf_get_field') || !function_exists('wp_prime_option_caches')) return;
        $gedaan[$name] = true;

        $post_id = acf_get_valid_post_id('options'); // 'options', met WPML bijv. 'options_en'
        $rijen   = (int) get_option("{$post_id}_{$name}");
        $field   = acf_get_field($field_key);
        if ($rijen < 1 || empty($field['sub_fields'])) return;

        $namen = [];
        for ($i = 0; $i < $rijen; $i++) {
            foreach ($field['sub_fields'] as $sub) {
                if (!empty($sub['name'])) $namen[] = "{$post_id}_{$name}_{$i}_{$sub['name']}";
            }
        }
        wp_prime_option_caches($namen);
    }
?>
