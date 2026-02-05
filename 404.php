<?php get_header(); ?>

	<div id="main-content" class="<?php if(is_front_page()) { echo "voorpagina"; } else { echo "vervolgpagina"; } ?>">
		<p>De pagina die u zocht kon niet gevonden worden. Probeer uw zoekopdracht te verfijnen of gebruik de bovenstaande navigatie om deze post te vinden.</p>
	</div> <!-- #main-content -->

<?php get_footer(); ?>