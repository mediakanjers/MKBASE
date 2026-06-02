<?php
    define('MKBASE_UPDATER_URL', 'https://wordpress-1253021-5957184.cloudwaysapps.com/updater/mkbase-update.json');

    require_once get_template_directory() . '/inc/compat.php';

    require_once get_template_directory() . '/inc/enqueue/scripts.php';
    require_once get_template_directory() . '/inc/enqueue/styles.php';

    require_once get_template_directory() . '/inc/acf/acf.php';
    require_once get_template_directory() . '/inc/acf/fields.php';
    require_once get_template_directory() . '/inc/acf/json.php';
    require_once get_template_directory() . '/inc/acf/register-blocks.php';

    require_once get_template_directory() . '/inc/breadcrumbs.php';
    require_once get_template_directory() . '/inc/menu.php';
    require_once get_template_directory() . '/inc/changelog.php';
    require_once get_template_directory() . '/inc/docs.php';
    require_once get_template_directory() . '/inc/setup.php';
    require_once get_template_directory() . '/inc/shortcodes.php';

    require_once get_template_directory() . '/updater/updater.php';
?>