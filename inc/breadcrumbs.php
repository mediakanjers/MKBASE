<?php
    if (!mkbase_check_duplicate('mk_breadcrumbs')):
    function mk_breadcrumbs($is_preview = false) {
        if (is_front_page()) return;

        $sep = '<span class="mk-breadcrumbs-sep">›</span>';

        // In de block-editor mogen dit geen echte links zijn — klikken navigeert anders
        // de editor-iframe zelf weg (zie mk/loop render.php voor de uitleg).
        $tag = $is_preview ? 'span' : 'a';

        echo '<nav class="mk-breadcrumbs">';
        echo '<' . $tag . ($is_preview ? '' : ' href="' . esc_url(home_url()) . '"') . '>Home</' . $tag . '>';

        if (is_singular()) {
            $post_type     = get_post_type();
            $post_type_obj = get_post_type_object($post_type);

            if ($post_type === 'page') {
                global $post;
                $ancestors = array_reverse(get_post_ancestors($post->ID));
                foreach ($ancestors as $ancestor) {
                    echo $sep . '<' . $tag . ($is_preview ? '' : ' href="' . esc_url(get_permalink($ancestor)) . '"') . '>' . esc_html(get_the_title($ancestor)) . '</' . $tag . '>';
                }
            } elseif ($post_type === 'post') {
                $category = get_the_category();
                if (!empty($category)) {
                    echo $sep . '<' . $tag . ($is_preview ? '' : ' href="' . esc_url(get_category_link($category[0]->term_id)) . '"') . '>' . esc_html($category[0]->name) . '</' . $tag . '>';
                }
            } elseif ($post_type_obj && $post_type_obj->has_archive) {
                $archive_url = get_post_type_archive_link($post_type);
                if ($archive_url) {
                    echo $sep . '<' . $tag . ($is_preview ? '' : ' href="' . esc_url($archive_url) . '"') . '>' . esc_html($post_type_obj->labels->name) . '</' . $tag . '>';
                }
            }

            echo $sep . '<strong>' . esc_html(get_the_title()) . '</strong>';

        } elseif (is_home()) {
            echo $sep . '<span>Blog</span>';

        } elseif (is_category() || is_tax()) {
            echo $sep . '<span>' . esc_html(single_term_title('', false)) . '</span>';

        } elseif (is_search()) {
            echo $sep . '<span>Zoekresultaten voor &ldquo;' . esc_html(get_search_query()) . '&rdquo;</span>';

        } elseif (is_404()) {
            echo $sep . '<span>404</span>';
        }

        echo '</nav>';
    }

    endif;

    add_action('acf/init', function() {
        if (!function_exists('acf_add_local_field_group')) return;
        acf_add_local_field_group([
            'key'      => 'group_mk_breadcrumbs_block',
            'title'    => 'Breadcrumbs blok',
            'fields'   => [
                [
                    'key'     => 'field_mk_breadcrumbs_info',
                    'label'   => '',
                    'name'    => '',
                    'type'    => 'message',
                    'message' => '<strong>ℹ️ Breadcrumbs blok</strong><br>Breadcrumbs worden automatisch gegenereerd op basis van de paginastructuur.<br>Op de homepage worden geen breadcrumbs weergegeven.',
                ],
            ],
            'location' => [
                [[
                    'param'    => 'block',
                    'operator' => '==',
                    'value'    => 'mk/breadcrumbs',
                ]],
            ],
            'active' => true,
        ]);
    });
?>
