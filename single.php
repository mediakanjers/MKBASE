<?php get_header(); ?>

	<div id="main-content" class="<?php echo mkbase_main_class(); ?>">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<div class="entry-content">
					<?php the_content(); ?>
				</div> <!-- .entry-content -->
			</article> <!-- #post -->
		<?php endwhile; ?>
	</div> <!-- #main-content -->

<?php get_footer(); ?>