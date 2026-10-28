<?php get_header(); ?>

<?php
    global $wp_query;
    $search_query = get_search_query();
?>

<div id="main-content" class="search-page">
    <div class="search-page__container">
        <h1 class="search-page__title">
            <?php
                if ($search_query) {
                    printf(esc_html__('Zoekresultaten voor "%s"', 'mk'), esc_html($search_query));
                } else {
                    esc_html_e('Zoeken', 'mk');
                }
            ?>
        </h1>

        <?php get_search_form(); ?>

        <?php if (have_posts()): ?>
            <p class="search-page__count">
                <?php printf(esc_html(_n('%s resultaat gevonden', '%s resultaten gevonden', $wp_query->found_posts, 'mk')), esc_html(number_format_i18n($wp_query->found_posts))); ?>
            </p>

            <ul class="search-page__results">
                <?php while (have_posts()): the_post(); ?>
                    <li class="search-page__result">
                        <h2 class="search-page__result-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <?php $excerpt = get_the_excerpt(); ?>
                        <?php if ($excerpt): ?>
                            <p class="search-page__excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>

            <?php the_posts_pagination([
                'prev_text' => esc_html__('Vorige', 'mk'),
                'next_text' => esc_html__('Volgende', 'mk'),
            ]); ?>
        <?php else: ?>
            <p class="search-page__empty"><?php esc_html_e('Er zijn geen resultaten gevonden. Probeer een andere zoekterm.', 'mk'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
