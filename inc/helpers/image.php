<?php
    /**
     * Geeft een <img> voor een afbeelding uit de mediabibliotheek: de passende maat (srcset),
     * breedte/hoogte, lazy loading en de alt-tekst uit de mediabibliotheek. Gebruik dit in
     * render.php's en templates in plaats van zelf een <img> te schrijven.
     *
     * @param array|int|string $image ACF-afbeeldingsveld (array, ID of URL) of attachment-ID. Leeg geeft ''.
     * @param string           $size  WordPress-afbeeldingsmaat: 'thumbnail', 'medium', 'large', 'full'.
     * @param array            $attr  Extra attributen, bijv. ['class' => 'mk-blok__afbeelding'] of ['alt' => ''].
     */
    function mkbase_image($image, $size = 'large', $attr = []) {
        if (is_array($image)) {
            $id = (int) ($image['ID'] ?? $image['id'] ?? 0);
        } elseif (is_numeric($image)) {
            $id = (int) $image;
        } elseif (is_string($image) && $image !== '') {
            $id = attachment_url_to_postid($image);
            if (!$id) {
                // Geen bestand uit de mediabibliotheek: gewone <img>
                return mkbase_image_tag($image, $attr);
            }
        } else {
            return '';
        }

        if (!$id) return '';

        // SVG's niet via WordPress: die kent hun maten niet (zonder plugin geen <img>, met
        // SVG Support een vaste 1024×1024). Breedte/hoogte komen uit het SVG-bestand zelf.
        if (get_post_mime_type($id) !== 'image/svg+xml') {
            $html = wp_get_attachment_image($id, $size, false, $attr);
            if ($html) return $html;
        }

        $url = wp_get_attachment_url($id);
        if (!$url) return '';

        // SVG niet breder dan het gekozen formaat (bijv. 'medium' = max. 300px), verhouding blijft gelijk
        $dims = mkbase_svg_dimensions(get_attached_file($id));
        $max  = is_string($size) && $size !== 'full' ? (int) get_option($size . '_size_w') : 0;
        if ($dims && $max && $dims['width'] > $max) {
            $dims = ['width' => $max, 'height' => (int) round($dims['height'] * $max / $dims['width'])];
        }

        return mkbase_image_tag($url, $attr + ['alt' => get_post_meta($id, '_wp_attachment_image_alt', true)] + $dims);
    }

    /**
     * ACF-keuzeveld "Afbeeldingsformaat" voor in een blok. Geef de waarde door aan mkbase_image():
     * mkbase_image($afbeelding, get_field('afbeeldingsformaat') ?: 'large').
     *
     * @param string $key     Unieke ACF-key, bijv. 'field_mk_team_afbeeldingsformaat'.
     * @param string $default Formaat als er niets gekozen is.
     * @param string $name    Veldnaam voor get_field().
     */
    function mkbase_image_size_field($key, $default = 'large', $name = 'afbeeldingsformaat') {
        $choices = [];
        foreach (['thumbnail' => 'Klein', 'medium' => 'Middel', 'large' => 'Groot'] as $size => $label) {
            $width = (int) get_option($size . '_size_w');
            $choices[$size] = $width ? $label . ' (max. ' . $width . 'px)' : $label;
        }
        $choices['full'] = 'Origineel';

        return [
            'key'           => $key,
            'label'         => 'Afbeeldingsformaat',
            'name'          => $name,
            'type'          => 'select',
            'instructions'  => 'Groter is scherper, maar laadt trager. Kies het formaat dat past bij hoe breed de afbeelding op de pagina staat. Telefoons krijgen automatisch een kleinere versie.',
            'choices'       => $choices,
            'default_value' => $default,
            'allow_null'    => 0,
            'wrapper'       => ['width' => '50'],
        ];
    }

    // Breedte en hoogte van een SVG-bestand, uit width/height of anders de viewBox. Onbekend geeft [].
    function mkbase_svg_dimensions($file) {
        if (!$file || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg' || !is_readable($file)) return [];

        $head = file_get_contents($file, false, null, 0, 4096);
        if (!$head || !preg_match('/<svg\b[^>]*>/i', $head, $tag)) return [];

        if (preg_match('/\swidth=["\']([\d.]+)(?:px)?["\']/i', $tag[0], $w) && preg_match('/\sheight=["\']([\d.]+)(?:px)?["\']/i', $tag[0], $h)) {
            $width = (float) $w[1]; $height = (float) $h[1];
        } elseif (preg_match('/\sviewBox=["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)\s*["\']/i', $tag[0], $vb)) {
            $width = (float) $vb[1]; $height = (float) $vb[2];
        } else {
            return [];
        }

        if ($width <= 0 || $height <= 0) return [];
        return ['width' => (int) round($width), 'height' => (int) round($height)];
    }

    // Eenvoudige <img> voor als WordPress er zelf geen kan maken (SVG of externe URL)
    function mkbase_image_tag($src, $attr = []) {
        $attr = array_merge(['alt' => '', 'loading' => 'lazy', 'decoding' => 'async'], $attr);

        $html = '<img src="' . esc_url($src) . '"';
        foreach ($attr as $name => $value) {
            $html .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }
        return $html . '>';
    }
?>
