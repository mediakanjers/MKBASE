<?php
    $query_loop      = get_field('query_loop') ?: 'post';
    $bericht_object  = get_field('bericht_object');
    $number_of_posts = (int)(get_field('number_of_posts') ?: 6);
    $grid            = get_field('grid') ?: '3';
    $breedte         = get_field('breedte') ?: 'contained';
    $image_size      = get_field('afbeeldingsformaat') ?: 'large';
    $toon_knop       = get_field('toon_knop');
    $knop_tekst      = get_field('knop_tekst') ?: __('Meer lezen', 'mk');

    if (!empty($bericht_object)) {
        $posts = array_filter(array_map(function($item) {
            return is_object($item) ? $item : get_post((int) $item);
        }, (array) $bericht_object));
    } else {
        $posts = get_posts([
            'post_type'      => $query_loop,
            'posts_per_page' => $number_of_posts,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
    }

    if (!$posts) return;

    $classes = [
        'mk-loop',
        'mk-loop--type-' . sanitize_html_class($query_loop),
        $breedte === 'full' ? 'mk-loop--full' : '',
    ];
?>

<div<?php echo mkbase_block_attrs($block, $classes); ?>>
    <div class="mk-loop__grid" data-grid="<?php echo esc_attr($grid); ?>">
        <?php foreach ($posts as $post):
            get_template_part('template-parts/content/card', null, [
                'post'       => $post,
                'image_size' => $image_size,
                'knop_tekst' => $toon_knop ? $knop_tekst : '',
                'is_preview' => $is_preview,
            ]);
        endforeach; ?>
    </div>
</div>
