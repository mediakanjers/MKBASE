<?php
    // Meldingen — logica in inc/notifications.php. Aangeroepen vanuit footer.php van het child theme.
    // De classes __inner, __inner__txt en __inner__closex zijn gelijk aan vóór 1.5.0, voor bestaande eigen CSS.
    $meldingen = mkbase_visible_notifications();
    if (!$meldingen) return;

    $groepen = ['boven' => [], 'boven_scrollt' => [], 'onder' => []];
    foreach ($meldingen as $melding) {
        $positie = $melding['melding_positie'] ?? 'boven';
        $groepen[isset($groepen[$positie]) ? $positie : 'boven'][] = $melding;
    }
    $eigen_css = get_field('css_overschrijven', 'option') === 'Ja';
?>
<?php foreach ($groepen as $positie => $items):
    if (!$items) continue;
?>
    <div class="mk-notifications mk-notifications--<?php echo esc_attr(str_replace('_', '-', $positie)); ?>"<?php echo $eigen_css ? ' data-eigen-css' : ''; ?>>
        <?php foreach ($items as $melding):
            [$bg, $color] = mkbase_notification_colors($melding);
            $stijl     = $melding['melding_stijl'] ?? 'info';
            $sluitbaar = !empty($melding['melding_sluitbaar']);
            $knop      = $melding['melding_knop'] ?? null;
        ?>
            <div class="mk-notification mk-notification--<?php echo esc_attr($stijl); ?>"
                role="region" aria-label="<?php esc_attr_e('Melding', 'mk'); ?>"
                data-id="<?php echo esc_attr(mkbase_notification_id($melding)); ?>"
                <?php if ($sluitbaar): ?>
                    data-opnieuw="<?php echo esc_attr($melding['melding_opnieuw'] ?: 'sessie'); ?>"
                    data-dagen="<?php echo (int) ($melding['melding_dagen'] ?: 7); ?>"
                <?php endif; ?>
                style="--mk-notification-bg: <?php echo esc_attr($bg); ?>; --mk-notification-color: <?php echo esc_attr($color); ?>;">
                <div class="mk-notification__inner">
                    <div class="mk-notification__inner__txt"><?php echo wp_kses_post($melding['melding_tekst']); ?></div>

                    <?php if (!empty($knop['url'])):
                        $target = $knop['target'] ?: '_self';
                    ?>
                        <a class="mk-notification__button" href="<?php echo esc_url($knop['url']); ?>" target="<?php echo esc_attr($target); ?>"<?php echo $target === '_blank' ? ' rel="noopener noreferrer"' : ''; ?>>
                            <?php echo esc_html($knop['title'] ?: __('Lees meer', 'mk')); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($sluitbaar): ?>
                        <button type="button" class="mk-notification__close mk-notification__inner__closex" aria-label="<?php esc_attr_e('Melding sluiten', 'mk'); ?>">
                            <span class="mk-notification__inner__closex__lineone" aria-hidden="true"></span>
                            <span class="mk-notification__inner__closex__linetwo" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
