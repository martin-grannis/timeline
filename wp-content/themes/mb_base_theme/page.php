<?php
/**
 * The template for displaying all pages.
 */

get_header(); ?>

	
	<div id="contentGrid">
<div id="content-grid">

		<?php while ( have_posts() ) : the_post(); ?>

			<?php get_template_part( 'content', 'page' ); ?>

		<?php endwhile; ?>

	</div>
	</div>

<?php get_footer(); ?>