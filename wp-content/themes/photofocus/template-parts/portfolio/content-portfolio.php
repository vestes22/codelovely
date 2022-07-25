<?php
/**
 * The template used for displaying projects on index view
 *
 * @package PhotoFocus
 */

$layout     = get_theme_mod( 'photofocus_portfolio_content_layout', 'layout-three' );
$no_thumb = trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/no-thumb-666x666.jpg';

global $post;

$categories_list = get_the_category();

$classes = 'grid-item';

foreach ( $categories_list as $cats ) {
	$classes .= ' ' . $cats->slug ;
}
?>

<article id="portfolio-post-<?php the_ID(); ?>" <?php post_class( esc_attr( $classes ) ); ?>>
	<div class="hentry-inner">
		<?php if( has_post_thumbnail() ) : 
			photofocus_post_thumbnail( 'photofocus-portfolio', 'html', true, true ); 
		else : ?>
			<div class="post-thumbnail">
				<a class="cover-link" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
					<img src="<?php echo esc_url( $no_thumb ); ?>">	
				</a>
			</div>	
		<?php endif; ?>		
		<div class="entry-container">
			<header class="entry-header">
				<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>

				<div class="entry-meta">
					<?php photofocus_posted_on(); ?>
				</div>
			</header>
		</div><!-- .entry-container -->
	</div><!-- .hentry-inner -->
</article>
