<?php
    $slides       = get_field('slides');
    $itemsDesktop = get_field('items_desktop') ?: 4;
    $itemsTablet  = get_field('items_tablet') ?: 2;
    $autoplay     = get_field('autoplay');
    $aspectRatio  = get_field('aspect_ratio') ?: '';
    $lightbox     = get_field('lightbox');
    $imageSize    = get_field('afbeeldingsformaat') ?: 'full';
    $galleryId    = wp_unique_id('image-slider-');

    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();
    wp_enqueue_style('mk-image-slider', $theme_uri . '/template-parts/blocks/image-slider/image-slider.css', ['swiper-v14-css'], mkbase_asset_version($theme_dir . '/template-parts/blocks/image-slider/image-slider.css'));
    wp_register_script('mk-image-slider', $theme_uri . '/template-parts/blocks/image-slider/image-slider.js', ['swiper-v14-js'], mkbase_asset_version($theme_dir . '/template-parts/blocks/image-slider/image-slider.js'), true);
    wp_enqueue_script('mk-image-slider');

    if ($lightbox) {
        wp_enqueue_script('mk-lightbox');
    }
?>

<?php if ($slides): ?>
<div<?php echo mkbase_block_attrs($block, 'image-slider'); ?>
    data-items-desktop="<?php echo esc_attr($itemsDesktop); ?>"
    data-items-tablet="<?php echo esc_attr($itemsTablet); ?>"
    data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">
    <div class="image-slider__track swiper">
        <div class="swiper-wrapper">
            <?php foreach ($slides as $slide):
                $afbeelding = $slide['afbeelding'];
            ?>
                <?php if ($afbeelding):
                    $style = $aspectRatio ? 'aspect-ratio:' . esc_attr($aspectRatio) . ';' : '';
                ?>
                    <div class="image-slider__item swiper-slide" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
                        <?php if ($lightbox): ?>
                            <a class="image-slider__link" href="<?php echo esc_url($afbeelding['url']); ?>" data-mk-lightbox="<?php echo esc_attr($galleryId); ?>">
                                <?php echo mkbase_image($afbeelding, $imageSize); ?>
                            </a>
                        <?php else: ?>
                            <?php echo mkbase_image($afbeelding, $imageSize); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
