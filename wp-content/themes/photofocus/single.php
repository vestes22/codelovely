<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package PhotoFocus
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<?php
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/content/content', 'single' );

				//the_post_navigation();

                the_post_navigation( array(
                	'prev_text' => '<span class="screen-reader-text">' . __( 'Previous Post', 'photofocus' ) . '</span><span aria-hidden="true" class="meta-nav">' . __( 'Prev Post', 'photofocus' ) . '</span> <span class="post-title">%title</span>',
                	'next_text' => '<span class="screen-reader-text">' . __( 'Next Post', 'photofocus' ) . '</span><span aria-hidden="true" class="meta-nav">' . __( 'Next Post', 'photofocus' ) . '</span> <span class="post-title">%title</span>',
                ) );


				get_template_part( 'template-parts/content/content', 'comment' );

			endwhile; // End of the loop.
			?>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
