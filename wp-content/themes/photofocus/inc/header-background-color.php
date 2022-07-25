<?php
/**
 * Customizer functionality
 *
 * @package PhotoFocus
 */

/**
 * Sets up the WordPress core custom header and custom background features.
 *
 * @since PhotoFocus 0.1
 *
 * @see photofocus_header_style()
 */
function photofocus_custom_header_and_background() {

	$default_background_color = '#000000';
				
	$default_text_color       = '#ffffff';

	/**
	 * Filter the arguments used when adding 'custom-background' support in PhotoFocus.
	 *
	 * @since PhotoFocus 0.1
	 *
	 * @param array $args {
	 *     An array of custom-background support arguments.
	 *
	 *     @type string $default-color Default color of the background.
	 * }
	 */
	add_theme_support( 'custom-background', apply_filters( 'photofocus_custom_background_args', array(
		'default-color' => $default_background_color,
	) ) );

	/**
	 * Filter the arguments used when adding 'custom-header' support in PhotoFocus.
	 *
	 * @since PhotoFocus 0.1
	 *
	 * @param array $args {
	 *     An array of custom-header support arguments.
	 *
	 *     @type string $default-text-color Default color of the header text.
	 *     @type int      $width            Width in pixels of the custom header image. Default 1200.
	 *     @type int      $height           Height in pixels of the custom header image. Default 280.
	 *     @type bool     $flex-height      Whether to allow flexible-height header images. Default true.
	 *     @type callable $wp-head-callback Callback function used to style the header image and text
	 *                                      displayed on the blog.
	 * }
	 */
	add_theme_support( 'custom-header', apply_filters( 'photofocus_custom_header_args', array(
		'default-image'      	 => get_parent_theme_file_uri( '/assets/images/header-image.jpg' ),
		'default-text-color'     => $default_text_color,
		'width'                  => 1920,
		'height'                 => 1080,
		'flex-height'            => true,
		'flex-width'            => true,
		'wp-head-callback'       => 'photofocus_header_style',
		'video'                  => true,
	) ) );

	register_default_headers( array(
		'default-image' => array(
			'url'           => '%s/assets/images/header-image.jpg',
			'thumbnail_url' => '%s/assets/images/header-image-275x155.jpg',
			'description'   => esc_html__( 'Default Header Image', 'photofocus' ),
		),
	) );
}
add_action( 'after_setup_theme', 'photofocus_custom_header_and_background' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @since PhotoFocus 0.1
 * @see photofocus_customize_register()
 *
 * @return void
 */
function photofocus_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @since PhotoFocus 0.1
 * @see photofocus_customize_register()
 *
 * @return void
 */
function photofocus_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds the JS listener to make Customizer color_scheme control.
 *
 * Passes color scheme data as colorScheme global.
 *
 * @since PhotoFocus 0.1
 */
function photofocus_customize_control_js() {
	
	wp_enqueue_style( 'photofocus-custom-controls-css', trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/css/customizer.css' );

}
add_action( 'customize_controls_enqueue_scripts', 'photofocus_customize_control_js' );
