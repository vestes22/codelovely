<?php
/**
 * Featured Slider Options
 *
 * @package PhotoFocus
 */

/**
 * Add hero content options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_slider_options( $wp_customize ) {
	$wp_customize->add_section( 'photofocus_featured_slider', array(
			'panel' => 'photofocus_theme_options',
			'title' => esc_html__( 'Featured Slider', 'photofocus' ),
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_slider_option',
			'default'           => 'disabled',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => photofocus_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'photofocus' ),
			'section'           => 'photofocus_featured_slider',
			'type'              => 'select',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_slider_number',
			'default'           => '4',
			'sanitize_callback' => 'photofocus_sanitize_number_range',

			'active_callback'   => 'photofocus_is_slider_active',
			'description'       => esc_html__( 'Save and refresh the page if No. of Slides is changed (Max no of slides is 20)', 'photofocus' ),
			'input_attrs'       => array(
				'style' => 'width: 100px;',
				'min'   => 0,
				'max'   => 20,
				'step'  => 1,
			),
			'label'             => esc_html__( 'No of Slides', 'photofocus' ),
			'section'           => 'photofocus_featured_slider',
			'type'              => 'number',
		)
	);

	$slider_number = get_theme_mod( 'photofocus_slider_number', 4 );

	for ( $i = 1; $i <= $slider_number ; $i++ ) {

		// Page Sliders
		photofocus_register_option( $wp_customize, array(
				'name'              => 'photofocus_slider_page_' . $i,
				'sanitize_callback' => 'photofocus_sanitize_post',
				'active_callback'   => 'photofocus_is_slider_active',
				'label'             => esc_html__( 'Page', 'photofocus' ) . ' # ' . $i,
				'section'           => 'photofocus_featured_slider',
				'type'              => 'dropdown-pages',
			)
		);
	} // End for().
}
add_action( 'customize_register', 'photofocus_slider_options' );

/** Active Callback Functions */

if ( ! function_exists( 'photofocus_is_slider_active' ) ) :
	/**
	* Return true if slider is active
	*
	* @since PhotoFocus 0.1
	*/
	function photofocus_is_slider_active( $control ) {
		$enable = $control->manager->get_setting( 'photofocus_slider_option' )->value();

		//return true only if previwed page on customizer matches the type option selected
		return photofocus_check_section( $enable );
	}
endif;
