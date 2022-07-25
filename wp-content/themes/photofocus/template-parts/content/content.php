<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package PhotoFocus
 */

if( has_post_thumbnail() ) {
	$thumb = get_the_post_thumbnail_url( get_the_ID() );	
}
else {
	$thumb = trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/no-thumb-960x960.jpg';
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="post-wrapper hentry-inner">
		<div class="post-thumbnail" style="background-image: url( <?php echo esc_url( $thumb ); ?> )">
			<a class="cover-link" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">	
			</a>
		</div>

		<div class="entry-container">
			<header class="entry-header">
				<?php if ( is_sticky() ) { ?>
					<span class="sticky-post">
						<span><?php esc_html_e( 'Featured', 'photofocus' ); ?></span>
					</span>
				<?php } ?>

				<?php
				if ( is_singular() ) :
					the_title( '<h1 class="entry-title">', '</h1>' );
				else :
					the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
				endif;?>

				<?php if ( 'post' === get_post_type() ) : ?>
				<div class="entry-meta">
					<?php photofocus_by_line(); ?>
					<?php photofocus_posted_on(); ?>
				</div><!-- .entry-meta -->
				<?php
				endif; ?>
				
			</header><!-- .entry-header -->

			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div><!-- .entry-summary -->
		</div><!-- .entry-container -->
	</div><!-- .hentry-inner -->
</article><!-- #post-<?php the_ID(); ?> -->
