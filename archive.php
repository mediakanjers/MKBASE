<?php get_header(); ?>

<?php
    // Titel zonder voorvoegsel ("Categorie:", "Tag:"), alleen de naam
    add_filter('get_the_archive_title_prefix', '__return_empty_string');
?>

<div id="main-content" class="archive-page">
    <?php // Zelfde kaart en grid als het Loop-blok, zodat de bestaande opmaak daarvan ook hier geldt ?>
    <div class="mk-loop archive-page__loop">
        <header class="archive-page__header">
            <h1 class="archive-page__title"><?php echo wp_kses_post(get_the_archive_title()); ?></h1>
            <?php the_archive_description('<div class="archive-page__description">', '</div>'); ?>
        </header>

        <?php if (have_posts()): ?>
            <div class="mk-loop__grid" data-grid="3">
                <?php while (have_posts()): the_post();
                    get_template_part('template-parts/content/card', null, ['post' => get_post()]);
                endwhile; ?>
            </div>

            <?php the_posts_pagination([
                'prev_text' => esc_html__('Vorige', 'mk'),
                'next_text' => esc_html__('Volgende', 'mk'),
            ]); ?>
        <?php else: ?>
            <p class="archive-page__empty"><?php esc_html_e('Er zijn hier nog geen berichten.', 'mk'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
