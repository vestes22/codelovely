<?php
/**
 * The template for displaying featured content
 *
 * @package PhotoFocus
 */
?>

<?php
$enable_content = get_theme_mod( 'photofocus_featured_content_option', 'disabled' );

if ( ! photofocus_check_section( $enable_content ) ) {
	// Bail if featured content is disabled.
	return;
}

$photofocus_title = get_option( 'featured_content_title', esc_html__( 'Contents', 'photofocus' ) );
$sub_title        = get_option( 'featured_content_content' );

$class = '';
if( !$photofocus_title && !$sub_title ) {
	$class = 'no-section-heading';
}

?>

<div id="featured-content-section" class="layout-three featured-content section <?php echo esc_attr( $class ); ?>">
	<div class="wrapper">
		<?php if ( $photofocus_title || $sub_title ) : ?>
			<div class="section-heading-wrapper featured-section-headline">
				<?php if ( $photofocus_title ) : ?>
					<div class="section-title-wrapper">
						<h2 class="section-title"><?php echo wp_kses_post( $photofocus_title ); ?></h2>
					</div><!-- .section-title-wrapper -->
				<?php endif; ?>

				<?php if ( $sub_title ) : ?>
					<div class="section-description-wrapper section-subtitle">
						<p><?php echo wp_kses_post( $sub_title ); ?></p>
					</div><!-- .section-description-wrapper -->
				<?php endif; ?>
			</div><!-- .section-heading-wrap -->
		<?php endif; ?>

		<div class="section-content-wrapper featured-content-wrapper layout-three">
			<?php get_template_part( 'template-parts/featured-content/post-types-featured' ); ?>
		</div><!-- .section-content-wrap -->
	</div><!-- .wrapper -->
</div><!-- #featured-content-section -->
