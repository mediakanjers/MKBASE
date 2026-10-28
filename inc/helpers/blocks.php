<?php
    /**
     * id- en class-attribuut voor de buitenste <div> van een blok: het HTML-anker, de
     * "Extra CSS-klasse(n)" uit het tabblad Geavanceerd van de editor en "Volledige breedte"
     * uit de blokwerkbalk (class alignfull), plus de eigen classes van het blok.
     * Gebruik: <div<?php echo mkbase_block_attrs($block, 'mk-team'); ?>>
     *
     * @param array        $block   Het $block dat ACF aan render.php meegeeft.
     * @param array|string $classes Eigen classes van het blok.
     */
    function mkbase_block_attrs($block, $classes = []) {
        $classes = array_merge(
            (array) $classes,
            preg_split('/\s+/', $block['className'] ?? '', -1, PREG_SPLIT_NO_EMPTY)
        );

        if (($block['align'] ?? '') === 'full') {
            $classes[] = 'alignfull';
            wp_enqueue_style('mk-align');
        }

        $html = ' class="' . esc_attr(implode(' ', array_filter($classes))) . '"';
        if (!empty($block['anchor'])) {
            $html = ' id="' . esc_attr($block['anchor']) . '"' . $html;
        }
        return $html;
    }

    // Melding voor de redacteur, alleen bedoeld voor de editor-preview ($is_preview)
    function mkbase_block_notice($text) {
        return '<p class="mk-block-notice">' . esc_html($text) . '</p>';
    }
?>
