<?php
/**
 * Hero Content Options
 *
 * @package PhotoFocus
 */

/**
 * Add hero content options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_hero_content_options( $wp_customize ) {
	$wp_customize->add_section( 'photofocus_hero_content_options', array(
			'title' => esc_html__( 'Hero Content', 'photofocus' ),
			'panel' => 'photofocus_theme_options',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_hero_content_visibility',
			'default'           => 'disabled',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => photofocus_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'photofocus' ),
			'section'           => 'photofocus_hero_content_options',
			'type'              => 'select',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_hero_content',
			'default'           => '0',
			'sanitize_callback' => 'photofocus_sanitize_post',
			'active_callback'   => 'photofocus_is_hero_content_active',
			'label'             => esc_html__( 'Page', 'photofocus' ),
			'section'           => 'photofocus_hero_content_options',
			'type'              => 'dropdown-pages',
		)
	);
}
add_action( 'customize_register', 'photofocus_hero_content_options' );

/** Active Callback Functions **/
if ( ! function_exists( 'photofocus_is_hero_content_active' ) ) :
	/**
	* Return true if hero content is active
	*
	* @since PhotoFocus 0.1
	*/
	function photofocus_is_hero_content_active( $control ) {
		$enable = $control->manager->get_setting( 'photofocus_hero_content_visibility' )->value();

		return photofocus_check_section( $enable );
	}
endif;
