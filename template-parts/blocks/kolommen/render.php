<?php
    $verhouding           = get_field('verhouding') ?: '50-50';
    $kolommen             = get_field('kolommen') ?: [];
    $verticale_uitlijning = get_field('verticale_uitlijning') ?: 'boven';
    $achtergrond_breed    = get_field('achtergrond_breed');
    $achtergrond_grid     = get_field('achtergrond_grid');
    $tekstkleur           = get_field('tekstkleur');
    $padding              = get_field('padding') ?: 'geen';
    $blok_afgerond        = !empty(get_field('blok_afgerond'));

    $kolom_aantal = count(explode('-', $verhouding));

    if (!$kolommen) return;

    // Volgorde waarin meerdere gekozen types binnen één kolom gerenderd worden.
    $type_volgorde = ['titel', 'tekst', 'afbeelding', 'video', 'knop', 'icoon_tekst', 'cijfer'];

    $outer_classes = ['mk-kolommen-outer'];
    if ($achtergrond_breed) {
        $outer_classes[] = 'mk-kolommen-outer--breed';
    }
    $outer_style = $achtergrond_breed ? ' style="--mk-kolommen-bg-breed: ' . esc_attr($achtergrond_breed) . ';"' : '';

    $inner_style_parts = [];
    if ($achtergrond_grid) $inner_style_parts[] = '--mk-kolommen-bg-grid: ' . esc_attr($achtergrond_grid) . ';';
    if ($tekstkleur)       $inner_style_parts[] = '--mk-kolommen-color: ' . esc_attr($tekstkleur) . ';';
    $inner_style = $inner_style_parts ? ' style="' . implode(' ', $inner_style_parts) . '"' : '';
?>

<div class="<?php echo esc_attr(implode(' ', $outer_classes)); ?>" data-padding="<?php echo esc_attr($padding); ?>"<?php echo $outer_style; ?>>
    <div class="mk-kolommen<?php echo $blok_afgerond ? ' mk-kolommen--rounded' : ''; ?>"
        data-verhouding="<?php echo esc_attr($verhouding); ?>"
        data-uitlijning="<?php echo esc_attr($verticale_uitlijning); ?>"
        data-padding="<?php echo esc_attr($padding); ?>"<?php echo $inner_style; ?>>
        <?php foreach ($kolommen as $i => $kolom):
            if ($i >= $kolom_aantal) break;
            $types = !empty($kolom['type']) ? (array) $kolom['type'] : ['tekst'];

            $kolom_classes = ['mk-kolommen__kolom'];
            foreach ($types as $t) $kolom_classes[] = 'mk-kolommen__kolom--' . sanitize_html_class($t);

            $kolom_achtergrond = $kolom['kolom_achtergrond'] ?? '';
            $kolom_tekstkleur  = $kolom['kolom_tekstkleur'] ?? '';
            $kolom_afgerond    = !empty($kolom['kolom_afgerond']);
            $kolom_padding     = $kolom['kolom_padding'] ?? 'geen';
            $kolom_uitlijning  = $kolom['kolom_uitlijning'] ?? 'boven';

            if ($kolom_achtergrond || $kolom_tekstkleur || $kolom_afgerond) {
                $kolom_classes[] = 'mk-kolommen__kolom--card';
            }
            if ($kolom_afgerond) {
                $kolom_classes[] = 'mk-kolommen__kolom--rounded';
            }

            $kolom_style_parts = [];
            if ($kolom_achtergrond) $kolom_style_parts[] = '--mk-kolom-bg: ' . esc_attr($kolom_achtergrond) . ';';
            if ($kolom_tekstkleur)  $kolom_style_parts[] = '--mk-kolom-color: ' . esc_attr($kolom_tekstkleur) . ';';
            $kolom_style = $kolom_style_parts ? ' style="' . implode(' ', $kolom_style_parts) . '"' : '';
        ?>
            <div class="<?php echo esc_attr(implode(' ', $kolom_classes)); ?>" data-padding="<?php echo esc_attr($kolom_padding); ?>" data-content-uitlijning="<?php echo esc_attr($kolom_uitlijning); ?>"<?php echo $kolom_style; ?>>
                <?php foreach ($type_volgorde as $type):
                    if (!in_array($type, $types, true)) continue;
                ?>

                    <?php if ($type === 'titel' && $kolom['titel_tekst']):
                        $titel_niveau = in_array($kolom['titel_niveau'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $kolom['titel_niveau'] : 'h2';
                    ?>
                        <<?php echo $titel_niveau; ?> class="mk-kolommen__titel"><?php echo esc_html($kolom['titel_tekst']); ?></<?php echo $titel_niveau; ?>>
                    <?php endif; ?>

                    <?php if ($type === 'tekst' && $kolom['tekst']): ?>
                        <div class="mk-kolommen__tekst"><?php echo wp_kses_post($kolom['tekst']); ?></div>
                    <?php endif; ?>

                    <?php if ($type === 'afbeelding' && $kolom['afbeelding']): ?>
                        <img class="mk-kolommen__afbeelding" loading="lazy" src="<?php echo esc_url($kolom['afbeelding']['url']); ?>" alt="<?php echo esc_attr($kolom['afbeelding']['alt']); ?>">
                    <?php endif; ?>

                    <?php if ($type === 'video' && $kolom['video']): ?>
                        <div class="mk-kolommen__video"><?php echo $kolom['video']; ?></div>
                    <?php endif; ?>

                    <?php if ($type === 'knop' && $kolom['knop']):
                        $knop   = $kolom['knop'];
                        $stijl  = $kolom['knop_stijl'] ?: 'primair';
                        $target = $knop['target'] ?: '_self';
                        $rel    = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
                        // In de block-editor mag dit geen echte link zijn — klikken navigeert
                        // anders de editor-iframe zelf weg (zie mk/loop render.php voor de uitleg).
                        $knop_tag = $is_preview ? 'span' : 'a';
                    ?>
                        <<?php echo $knop_tag; ?> class="mk-button mk-button--<?php echo esc_attr($stijl); ?>"<?php echo $is_preview ? '' : ' href="' . esc_url($knop['url']) . '" target="' . esc_attr($target) . '"' . $rel; ?>>
                            <?php echo esc_html($knop['title']); ?>
                        </<?php echo $knop_tag; ?>>
                    <?php endif; ?>

                    <?php if ($type === 'icoon_tekst'): ?>
                        <div class="mk-kolommen__icoon-tekst">
                            <?php if ($kolom['icoon']): ?>
                                <img class="mk-kolommen__icoon" loading="lazy" src="<?php echo esc_url($kolom['icoon']['url']); ?>" alt="">
                            <?php endif; ?>
                            <?php if ($kolom['icoon_tekst']): ?>
                                <span><?php echo esc_html($kolom['icoon_tekst']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($type === 'cijfer'): ?>
                        <div class="mk-kolommen__cijfer">
                            <?php if ($kolom['cijfer_waarde']): ?>
                                <span class="mk-kolommen__cijfer-waarde"><?php echo esc_html($kolom['cijfer_waarde']); ?></span>
                            <?php endif; ?>
                            <?php if ($kolom['cijfer_label']): ?>
                                <span class="mk-kolommen__cijfer-label"><?php echo esc_html($kolom['cijfer_label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
