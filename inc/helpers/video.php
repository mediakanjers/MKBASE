<?php
    /**
     * Toont een video (de HTML van een ACF oEmbed-veld) pas na een klik. Tot die tijd staat er
     * alleen een voorbeeldafbeelding met afspeelknop, zodat YouTube en Vimeo geen cookies en
     * zware scripts laden voordat de bezoeker zelf op afspelen klikt.
     * YouTube speelt af via youtube-nocookie.com, Vimeo met dnt=1 (niet volgen).
     *
     * @param string $embed      HTML van het oEmbed-veld, bijv. get_field('video').
     * @param bool   $is_preview In de editor direct de video tonen (zonder afspeelknop).
     */
    function mkbase_video($embed, $is_preview = false) {
        if (!$embed || !preg_match('/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1/i', $embed, $m)) {
            return $embed; // Geen iframe (ander soort embed): ongewijzigd tonen
        }

        $src    = html_entity_decode($m[2]);
        $poster = '';

        if (preg_match('#youtube(?:-nocookie)?\.com/embed/([\w-]{11})#', $src, $yt)) {
            $src    = preg_replace('#//(www\.)?youtube\.com/embed/#', '//www.youtube-nocookie.com/embed/', $src);
            $poster = 'https://i.ytimg.com/vi/' . $yt[1] . '/hqdefault.jpg';
        } elseif (str_contains($src, 'player.vimeo.com')) {
            $src = add_query_arg('dnt', 1, $src);
        }

        // In de editor de video direct tonen; op de site start hij na de klik meteen met afspelen
        if (!$is_preview) {
            $src = add_query_arg('autoplay', 1, $src);
        }

        $iframe = preg_replace_callback('/(<iframe\b[^>]*\bsrc=)(["\'])(.*?)\2/i', function ($a) use ($src) {
            return $a[1] . '"' . esc_url($src) . '"';
        }, $embed, 1);

        if ($is_preview) return $iframe;

        wp_enqueue_script('mk-video');

        $title = preg_match('/\btitle=(["\'])(.*?)\1/i', $embed, $t) ? html_entity_decode($t[2]) : '';
        $label = $title
            /* translators: %s: titel van de video */
            ? sprintf(__('Video afspelen: %s', 'mk'), $title)
            : __('Video afspelen', 'mk');

        $html  = '<div class="mk-video">';
        $html .= '<button type="button" class="mk-video__play" aria-label="' . esc_attr($label) . '">';
        if ($poster) {
            $html .= '<img class="mk-video__poster" src="' . esc_url($poster) . '" alt="" loading="lazy" decoding="async">';
        }
        $html .= '<span class="mk-video__icon" aria-hidden="true"></span>';
        $html .= '</button>';
        $html .= '<template>' . $iframe . '</template>';
        $html .= '</div>';

        return $html;
    }
?>
