<?php
    $bgcolor          = get_field('achtergrondkleur' , 'options') ?: '#ff0000';
    $txtcolor         = get_field('tekstkleur' , 'options') ?: '#ffffff';
    $txt              = get_field('tekst_melding' , 'options');
    $shownotification = get_field('melding_tonen' , 'options');

    // DATA
    $showdate         = get_field('data_opgeven' , 'options');
    $begindate        = get_field('begindatum' , 'options');
    $enddate          = get_field('einddatum' , 'options');

    // SHOW ON
    $pages = get_field('waar_zichtbaar' , 'options');
    $current_id = get_queried_object_id();
    $show_on_page = false;

    if ($pages) {
        if (!is_array($pages)) {
            $pages = [$pages];
        }
        $page_ids = array_map(function ($page) {
            return is_object($page) ? $page->ID : (int) $page;
        }, $pages);
        if (in_array($current_id, $page_ids, true)) {
            $show_on_page = true;
        }
    } else {
        $show_on_page = true;
    }

?>

<?php if ($shownotification === 'Ja' && $show_on_page) { ?>
    <div
        style="--mk-notification-bg: <?= esc_attr($bgcolor); ?>; --mk-notification-color: <?= esc_attr($txtcolor); ?>;"
        class="mk-notification"
        <?php if ($begindate) echo 'data-begindatum="' . esc_attr($begindate) . '"'; ?>
        <?php if ($enddate) echo 'data-einddatum="' . esc_attr($enddate) . '"'; ?>
        <?php if ($showdate) echo 'data-opgeven="' . esc_attr($showdate) . '"'; ?>>
        <div class="mk-notification__inner">
            <div class="mk-notification__inner__closex">
                <span class="mk-notification__inner__closex__lineone"></span>
                <span class="mk-notification__inner__closex__linetwo"></span>
            </div>
            <div class="mk-notification__inner__txt">
                <?php echo wp_kses_post($txt);?>
            </div>
        </div>
    </div>
<?php } ?>