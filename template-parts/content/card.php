<?php
    // Berichtkaart, gebruikt door het Loop-blok en archive.php.
    // get_template_part('template-parts/content/card', null, ['post' => $post, ...]);
    // Niet $post gebruiken: load_template() maakt dat de globale $post van de pagina.
    $card_post  = $args['post'];
    $image_size = $args['image_size'] ?? 'large';
    $knop_tekst = $args['knop_tekst'] ?? '';
    $is_preview = !empty($args['is_preview']);

    $id    = $card_post->ID;
    $img   = mkbase_image(get_post_thumbnail_id($id), $image_size);
    $terms = get_the_terms($id, 'category');
    $term  = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';
    $excerpt = get_the_excerpt($id);

    // In de block-editor (canvas én inserter-voorbeelden) mag de kaart geen
    // echte link zijn: klikken navigeert dan de editor-iframe zelf weg naar de
    // link-URL, wat de post die je aan het bewerken bent onbereikbaar maakt en
    // een SecurityError in Gutenberg's cleanup veroorzaakt (cross-origin na
    // navigatie). Op de website blijft het gewoon een <a>.
    $card_tag = $is_preview ? 'div' : 'a';
?>
<<?php echo $card_tag; ?><?php echo $is_preview ? '' : ' href="' . esc_url(get_permalink($id)) . '"'; ?> class="mk-loop__card">
    <?php if ($img): ?>
        <div class="mk-loop__image">
            <?php echo $img; ?>
        </div>
    <?php endif; ?>
    <div class="mk-loop__content">
        <?php if ($term): ?>
            <span class="mk-loop__category"><?php echo esc_html($term); ?></span>
        <?php endif; ?>
        <h3 class="mk-loop__title"><?php echo esc_html(get_the_title($id)); ?></h3>
        <?php if ($excerpt): ?>
            <p class="mk-loop__excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>
        <?php if ($knop_tekst): ?>
            <span class="mk-button mk-button--primary"><?php echo esc_html($knop_tekst); ?></span>
        <?php endif; ?>
    </div>
</<?php echo $card_tag; ?>>
