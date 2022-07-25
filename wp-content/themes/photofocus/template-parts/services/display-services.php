<?php
/**
 * The template for displaying services content
 *
 * @package PhotoFocus
 */
?>

<?php
$enable_content = get_theme_mod( 'photofocus_service_option', 'disabled' );

if ( ! photofocus_check_section( $enable_content ) ) {
	// Bail if services content is disabled.
	return;
}

$photofocus_title    = get_option( 'ect_service_title', esc_html__( 'Services', 'photofocus' ) );
$sub_title = get_option( 'ect_service_content' );

$classes[] = 'services-section';
$classes[] = 'section';

if ( ! $photofocus_title && ! $sub_title ) {
	$classes[] = 'no-section-heading';
}
?>

<div id="services-section" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="wrapper">
		<?php if ( $photofocus_title || $sub_title ) : ?>
			<div class="section-heading-wrapper">
				<?php if ( $photofocus_title ) : ?>
					<div class="section-title-wrapper">
						<h2 class="section-title"><?php echo wp_kses_post( $photofocus_title ); ?></h2>
					</div><!-- .page-title-wrapper -->
				<?php endif; ?>

				<?php if ( $sub_title ) : ?>
					<div class="section-description-wrapper section-subtitle">
						<p><?php echo wp_kses_post( $sub_title ); ?></p>
					</div><!-- .section-description -->
				<?php endif; ?>
			</div><!-- .section-heading-wrapper -->
		<?php endif; ?>

		<div class="section-content-wrapper services-content-wrapper layout-three">
			<?php get_template_part( 'template-parts/services/post-types-services' ); ?>
		</div><!-- .services-wrapper -->
	</div><!-- .wrapper -->
</div><!-- #services-section -->
