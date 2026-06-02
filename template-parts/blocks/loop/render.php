<?php
    $query_loop      = get_field('query_loop') ?: 'post';
    $bericht_object  = get_field('bericht_object');
    $number_of_posts = (int)(get_field('number_of_posts') ?: 6);
    $grid            = get_field('grid') ?: '3';
    $breedte         = get_field('breedte') ?: 'contained';
    $toon_knop       = get_field('toon_knop');
    $knop_tekst      = get_field('knop_tekst') ?: 'Meer lezen';

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

    $classes = trim('mk-loop'
        . ' mk-loop--type-' . sanitize_html_class($query_loop)
        . ($breedte === 'full' ? ' mk-loop--full' : '')
    );
?>

<div class="<?php echo esc_attr($classes); ?>">
    <div class="mk-loop__grid" data-grid="<?php echo esc_attr($grid); ?>">
        <?php foreach ($posts as $post):
            $id      = $post->ID;
            $title   = get_the_title($id);
            $link    = get_permalink($id);
            $excerpt = get_the_excerpt($id);
            $img_id  = get_post_thumbnail_id($id);
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : '';
            $img_alt = $img_id ? get_post_meta($img_id, '_wp_attachment_image_alt', true) : $title;
            $terms   = get_the_terms($id, 'category');
            $term    = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';
        ?>
            <a href="<?php echo esc_url($link); ?>" class="mk-loop__card">
                <?php if ($img_url): ?>
                    <div class="mk-loop__image">
                        <img loading="lazy" src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                    </div>
                <?php endif; ?>
                <div class="mk-loop__content">
                    <?php if ($term): ?>
                        <span class="mk-loop__category"><?php echo esc_html($term); ?></span>
                    <?php endif; ?>
                    <h3 class="mk-loop__title"><?php echo esc_html($title); ?></h3>
                    <?php if ($excerpt): ?>
                        <p class="mk-loop__excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($toon_knop): ?>
                        <span class="mk-button mk-button--primary"><?php echo esc_html($knop_tekst); ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
