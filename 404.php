<?php get_header(); ?>

<?php
    $error_title         = get_field('titel' , 'options');
    $error_txt           = get_field('tekst' , 'options');
    $error_illustration  = get_field('afbeelding' , 'options');
?>

<div id="main-content" class="error-page error-page--404">
    <div class="error-page__container">
        <div class="error-page__content">
            <h1><?php echo esc_html($error_title);?></h1>
            <p><?php echo esc_html($error_txt);?></p>
            <a class="mk-button mk-button--primary" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Terug naar homepage', 'mk'); ?></a>
        </div>
        <div class="error-page__illustration">
            <img src="<?php echo esc_url($error_illustration);?>">
        </div>
    </div>
</div>

<?php get_footer(); ?>