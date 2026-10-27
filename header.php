<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if (is_singular() && pings_open()) : ?>
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php endif; ?>
	<?php wp_head(); ?>

</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page_container">

<header>


</header> <!-- #full-header -->