<?php
/**
 * Display Header Media
 *
 * @package PhotoFocus
 */

if ( 'disable' === get_theme_mod( 'photofocus_header_media_option', 'entire-site' ) ) {
	return;
}

$header_image = photofocus_featured_overall_image();

$header_media_text = photofocus_has_header_media_text();

if ( ( ( is_header_video_active() && has_header_video() ) || 'disable' !== $header_image ) || $header_media_text ) :
?>
<div class="custom-header header-media">
	<div class="wrapper">
		<div class="custom-header-media">
			<?php
			if ( is_header_video_active() && has_header_video() ) {
				the_custom_header_markup();
			} elseif ( 'disable' !== $header_image ) {
				echo '<div id="wp-custom-header" class="wp-custom-header"><img src="' . esc_url( $header_image ) . '"/></div>	';
			}
			?>
			<?php photofocus_header_media_text(); ?>

			<?php if ( get_theme_mod( 'photofocus_header_media_scroll_down', 1 ) ) : ?>
					<div class="scroll-down">
						<span><?php esc_html_e( 'Scroll', 'photofocus' ); ?></span>
						<?php echo photofocus_get_svg( array( 'icon' => 'arrow-right' ) ); ?>
					</div><!-- .scroll-down -->
			<?php endif; ?>

		</div>
	</div><!-- .wrapper -->
	<div class="custom-header-overlay"></div><!-- .custom-header-overlay -->
</div><!-- .custom-header -->
<?php endif; ?>
