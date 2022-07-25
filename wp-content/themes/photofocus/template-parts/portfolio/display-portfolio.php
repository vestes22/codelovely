<?php
/**
 * The template for displaying portfolio items
 *
 * @package PhotoFocus
 */
?>

<?php
$enable = get_theme_mod( 'photofocus_portfolio_option', 'disabled' );

if ( ! photofocus_check_section( $enable ) ) {
	// Bail if portfolio section is disabled.
	return;
}

$photofocus_title     = get_option( 'jetpack_portfolio_title', esc_html__( 'Projects', 'photofocus' ) );
$sub_title = get_option( 'jetpack_portfolio_content' );

$class = '';
if( !$photofocus_title && !$sub_title ) {
	$class = 'no-section-heading';
}

?>

<div id="portfolio-content-section" class="layout-three jetpack-portfolio section <?php echo esc_attr( $class ); ?>">
	<div class="wrapper">
		<?php if ( $photofocus_title || $sub_title ) : ?>
			<div class="section-heading-wrapper portfolio-section-headline">
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
			</div><!-- .section-heading-wrapper -->
		<?php endif; ?>

		<div class="section-content-wrapper portfolio-content-wrapper layout-three">
			<div class="grid">
				<?php
					get_template_part( 'template-parts/portfolio/post-types', 'portfolio' );
				?>
			</div>
		</div><!-- .section-content-wrap -->
	</div><!-- .wrapper -->
</div><!-- #portfolio-section -->
