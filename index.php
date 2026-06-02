<?php get_header(); ?>
	<div id="main-content" class="<?php echo mkbase_main_class(); ?>">
		<div class="clearfix">
			<?php while ( have_posts() ) : the_post(); ?>
			<?php endwhile; ?>
		</div>
	</div> <!-- #main-content -->
<?php get_footer(); ?>