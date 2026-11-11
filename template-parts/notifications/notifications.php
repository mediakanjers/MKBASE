<?php
    // Meldingen — logica in inc/notifications.php. Aangeroepen vanuit footer.php van het child theme.
    // De classes __inner, __inner__txt en __inner__closex zijn gelijk aan vóór 1.5.0, voor bestaande eigen CSS.
    // Vormen: balk (boven / boven_scrollt / onder, onder elkaar), pop-up (<dialog>, één tegelijk)
    // en zwevend blok (per plek één). Verschijnen en verdwijnen regelt assets/js/notifications.js.
    $meldingen = mkbase_visible_notifications();
    if (!$meldingen) return;

    $balken  = ['boven' => [], 'boven_scrollt' => [], 'onder' => []];
    $popup   = null;
    $blokken = [];
    foreach ($meldingen as $melding) {
        $positie = $melding['melding_positie'] ?? 'boven';
        if ($positie === 'popup') $popup = $melding;
        elseif ($positie === 'blok') $blokken[$melding['melding_blok_plek'] ?: 'rechts_onder'] = $melding;
        else $balken[isset($balken[$positie]) ? $positie : 'boven'][] = $melding;
    }
    $eigen_css = get_field('css_overschrijven', 'option') === 'Ja';

    // Eén melding. $tag: 'div' (balk, blok) of 'dialog' (pop-up)
    $render = function ($melding, $vorm, $tag = 'div') {
        [$bg, $color] = mkbase_notification_colors($melding);
        $stijl     = $melding['melding_stijl'] ?? 'info';
        // Een pop-up is altijd te sluiten (in de admin staat dan geen keuze) en volgt "Daarna weer tonen"
        $sluitbaar = !empty($melding['melding_sluitbaar']) || $vorm === 'popup';
        $kruisje   = $sluitbaar;
        $knop      = $melding['melding_knop'] ?? null;
        $voorbeeld = !empty($melding['mk_voorbeeld']);
        $tonen     = $voorbeeld ? 'direct' : ($melding['melding_tonen'] ?? 'direct');

        $attr = [
            'class'      => 'mk-notification mk-notification--' . $stijl . ' mk-notification--' . $vorm,
            'aria-label' => __('Melding', 'mk'),
            'data-id'    => mkbase_notification_id($melding) . ($voorbeeld ? '-voorbeeld' : ''),
            'style'      => '--mk-notification-bg: ' . $bg . '; --mk-notification-color: ' . $color . ';',
        ];
        if ($tag === 'div') $attr['role'] = 'region';
        if ($sluitbaar) {
            $attr['data-opnieuw'] = $melding['melding_opnieuw'] ?: 'sessie';
            $attr['data-dagen']   = (int) ($melding['melding_dagen'] ?: 7);
        }
        if ($tonen === 'tijd')   $attr['data-tonen-sec'] = max(1, (int) ($melding['melding_tonen_sec'] ?: 10));
        if ($tonen === 'scroll') $attr['data-tonen-px']  = max(1, (int) ($melding['melding_tonen_px'] ?: 600));
        if ($tonen !== 'direct' && $tag === 'div') $attr['hidden'] = 'hidden';
        if (!empty($melding['melding_auto'])) $attr['data-auto'] = max(2, (int) ($melding['melding_auto_sec'] ?: 8));
        if ($vorm === 'popup') {
            $attr['data-backdrop']    = !empty($melding['melding_backdrop']) || !isset($melding['melding_backdrop']) ? '1' : '0';
            $attr['data-scroll-blok'] = !empty($melding['melding_scroll_blok']) || !isset($melding['melding_scroll_blok']) ? '1' : '0';
        }
        ?>
        <<?php echo $tag; foreach ($attr as $name => $value) echo ' ' . $name . '="' . esc_attr($value) . '"'; ?>>
            <div class="mk-notification__inner">
                <div class="mk-notification__inner__txt"><?php echo wp_kses_post($melding['melding_tekst']); ?></div>

                <?php if (!empty($knop['url'])):
                    $target = $knop['target'] ?: '_self';
                ?>
                    <a class="mk-notification__button" href="<?php echo esc_url($knop['url']); ?>" target="<?php echo esc_attr($target); ?>"<?php echo $target === '_blank' ? ' rel="noopener noreferrer"' : ''; ?>>
                        <?php echo esc_html($knop['title'] ?: __('Lees meer', 'mk')); ?>
                    </a>
                <?php endif; ?>

                <?php if ($kruisje): ?>
                    <button type="button" class="mk-notification__close mk-notification__inner__closex" aria-label="<?php esc_attr_e('Melding sluiten', 'mk'); ?>">
                        <span class="mk-notification__inner__closex__lineone" aria-hidden="true"></span>
                        <span class="mk-notification__inner__closex__linetwo" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($attr['data-auto'])): ?>
                <span class="mk-notification__timer" aria-hidden="true"></span>
            <?php endif; ?>
        </<?php echo $tag; ?>>
        <?php
    };
?>
<?php foreach ($balken as $positie => $items):
    if (!$items) continue;
?>
    <div class="mk-notifications mk-notifications--<?php echo esc_attr(str_replace('_', '-', $positie)); ?>"<?php echo $eigen_css ? ' data-eigen-css' : ''; ?>>
        <?php foreach ($items as $melding) $render($melding, 'balk'); ?>
    </div>
<?php endforeach; ?>

<?php if ($blokken): ?>
    <div class="mk-notification-blokken"<?php echo $eigen_css ? ' data-eigen-css' : ''; ?>>
        <?php foreach ($blokken as $plek => $melding): ?>
            <div class="mk-notifications mk-notifications--blok mk-notifications--<?php echo esc_attr(str_replace('_', '-', $plek)); ?>" style="--mk-notification-width: <?php echo (int) ($melding['melding_blok_breedte'] ?: 360); ?>px;">
                <?php $render($melding, 'blok'); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($popup) $render($popup, 'popup', 'dialog'); ?>
