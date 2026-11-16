<?php
    // De tijden zelf staan bij Mediakanjers → Openingstijden; dit blok kiest alleen wat het toont.
    // Zie inc/openingstijden/output.php.
    if (!function_exists('mkbase_openingstijden')) return;

    $aan = function ($naam) {
        $waarde = get_field($naam);
        return $waarde === null ? true : (bool) $waarde; // nog nooit opgeslagen: de standaard (aan)
    };

    $html = mkbase_openingstijden([
        'rooster'     => (array) get_field('rooster'), // één of meer roosters; leeg = het eerste
        'weergave'    => get_field('weergave') ?: 'week',
        'titel'       => trim((string) get_field('titel')),
        'status'      => $aan('toon_status'),
        'vandaag'     => $aan('vandaag_uitlichten'),
        'samenvoegen' => $aan('samenvoegen'),
        'gesloten'    => $aan('gesloten_tonen'),
        'afwijkingen' => $aan('afwijkingen_tonen'),
        'wijziging'   => $aan('wijziging_uitlichten'),
        'indeling'    => get_field('indeling') ?: 'naast',
        'link'        => get_field('link') ?: null,
        'voorbeeld'   => $is_preview,
    ]);
    if ($html === '') return;
?>
<?php
    // Breedte: Inhoudsbreedte (zoals de andere blokken, gecentreerd) of de volle breedte van de plek
    // waar het blok staat, bijv. in een kolom (mk-openingstijden--vol; opmaak in het child theme)
    $classes = ['mk-openingstijden'];
    if (get_field('breedte') === 'vol') $classes[] = 'mk-openingstijden--vol';
?>
<div<?php echo mkbase_block_attrs($block, $classes); ?>>
    <?php echo $html; ?>
    <?php if ($is_preview && current_user_can('edit_posts')): ?>
        <p class="mk-ot__beheer"><a href="<?php echo esc_url(admin_url('admin.php?page=acf-options-openingstijden#week')); ?>" target="_blank" rel="noopener"><?php esc_html_e('Tijden aanpassen →', 'mk'); ?></a></p>
    <?php endif; ?>
</div>
