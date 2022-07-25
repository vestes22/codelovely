<?php
/**
 * The template used for displaying testimonial on front page
 *
 * @package PhotoFocus
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="hentry-inner">
		<div class="entry-container">
			<div class="entry-content">
				<?php the_excerpt(); ?>
			</div>

			<?php $position = get_post_meta( get_the_id(), 'ect_testimonial_position', true ); ?>	
		</div><!-- .entry-container -->	
		<?php photofocus_post_thumbnail( 'photofocus-testimonial', 'html', true, false ); ?>
		<?php if ( $position ) : ?>
				<header class="entry-header">
					<?php the_title( '<h2 class="entry-title">', '</h2>' );	?>
					<?php if ( $position ) : ?>
						<p class="entry-meta"><span class="position">
							<?php echo esc_html( $position ); ?></span>
						</p>
					<?php endif; ?>
				</header>
		<?php endif;?>
	</div><!-- .hentry-inner -->
</article>
