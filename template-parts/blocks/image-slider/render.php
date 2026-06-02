<?php
    $slides       = get_field('slides');
    $itemsDesktop = get_field('items_desktop') ?: 4;
    $itemsTablet  = get_field('items_tablet') ?: 2;
    $autoplay     = get_field('autoplay');
    $aspectRatio  = get_field('aspect_ratio') ?: '';

    wp_enqueue_script('owl-carousel-js');

    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();
    wp_register_script('mk-image-slider', $theme_uri . '/template-parts/blocks/image-slider/image-slider.js', ['jquery', 'owl-carousel-js'], filemtime($theme_dir . '/template-parts/blocks/image-slider/image-slider.js'), true);
    wp_enqueue_script('mk-image-slider');
?>

<?php if ($slides): ?>
<div class="image-slider"
    data-items-desktop="<?php echo esc_attr($itemsDesktop); ?>"
    data-items-tablet="<?php echo esc_attr($itemsTablet); ?>"
    data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">
    <div class="image-slider__track owl-carousel">
        <?php foreach ($slides as $slide):
            $afbeelding = $slide['afbeelding'];
        ?>
            <?php if ($afbeelding):
                $style = $aspectRatio ? 'aspect-ratio:' . esc_attr($aspectRatio) . ';' : '';
            ?>
                <div class="image-slider__item" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
                    <img loading="lazy" src="<?php echo esc_url($afbeelding['url']); ?>" alt="<?php echo esc_attr($afbeelding['alt']); ?>">
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
