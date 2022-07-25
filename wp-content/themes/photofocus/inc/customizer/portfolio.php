<?php
/**
 * Add Portfolio Settings in Customizer
 *
 * @package PhotoFocus
 */

/**
 * Add portfolio options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_portfolio_options( $wp_customize ) {
	// Add note to Jetpack Portfolio Section
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_jetpack_portfolio_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'PhotoFocus_Note_Control',
			'label'             => sprintf( esc_html__( 'For Portfolio Options for PhotoFocus Theme, go %1$shere%2$s', 'photofocus' ),
				 '<a href="javascript:wp.customize.section( \'photofocus_portfolio\' ).focus();">',
				 '</a>'
			),
			'section'           => 'jetpack_portfolio',
			'type'              => 'description',
			'priority'          => 1,
		)
	);

	$wp_customize->add_section( 'photofocus_portfolio', array(
			'panel'    => 'photofocus_theme_options',
			'title'    => esc_html__( 'Portfolio', 'photofocus' ),
		)
	);

	$action = 'install-plugin';
	$slug   = 'essential-content-types';

	$install_url = wp_nonce_url(
	    add_query_arg(
	        array(
	            'action' => $action,
	            'plugin' => $slug
	        ),
	        admin_url( 'update.php' )
	    ),
	    $action . '_' . $slug
	);

	photofocus_register_option( $wp_customize, array(
	        'name'              => 'photofocus_portfolio_jetpack_note',
	        'sanitize_callback' => 'sanitize_text_field',
	        'custom_control'    => 'Photofocus_Note_Control',
	        'active_callback'   => 'photofocus_is_ect_portfolio_inactive',
	        /* translators: 1: <a>/link tag start, 2: </a>/link tag close. */
	        'label'             => sprintf( esc_html__( 'For Portfolio, install %1$sEssential Content Types%2$s Plugin with Portfolio Type Enabled', 'photofocus' ),
	            '<a target="_blank" href="' . esc_url( $install_url ) . '">',
	            '</a>'

	        ),
	       'section'            => 'photofocus_portfolio',
	        'type'              => 'description',
	        'priority'          => 1,
	    )
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_portfolio_option',
			'default'           => 'disabled',
			'active_callback'   => 'photofocus_is_ect_portfolio_active',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => photofocus_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'photofocus' ),
			'section'           => 'photofocus_portfolio',
			'type'              => 'select',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_portfolio_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'PhotoFocus_Note_Control',
			'active_callback'   => 'photofocus_is_portfolio_active',
			'label'             => sprintf( esc_html__( 'For CPT heading and sub-heading, go %1$shere%2$s', 'photofocus' ),
				 '<a href="javascript:wp.customize.control( \'jetpack_portfolio_title\' ).focus();">',
				 '</a>'
			),
			'section'           => 'photofocus_portfolio',
			'type'              => 'description',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_portfolio_number',
			'default'           => 6,
			'sanitize_callback' => 'photofocus_sanitize_number_range',
			'active_callback'   => 'photofocus_is_portfolio_active',
			'label'             => esc_html__( 'Number of items to show', 'photofocus' ),
			'section'           => 'photofocus_portfolio',
			'type'              => 'number',
			'input_attrs'       => array(
				'style'             => 'width: 100px;',
				'min'               => 0,
			),
		)
	);

	$number = get_theme_mod( 'photofocus_portfolio_number', 6 );

	for ( $i = 1; $i <= $number ; $i++ ) {

		//for CPT
		photofocus_register_option( $wp_customize, array(
				'name'              => 'photofocus_portfolio_cpt_' . $i,
				'sanitize_callback' => 'photofocus_sanitize_post',
				'active_callback'   => 'photofocus_is_portfolio_active',
				'label'             => esc_html__( 'Portfolio', 'photofocus' ) . ' ' . $i ,
				'section'           => 'photofocus_portfolio',
				'type'              => 'select',
				'choices'           => photofocus_generate_post_array( 'jetpack-portfolio' ),
			)
		);
	} // End for().
}
add_action( 'customize_register', 'photofocus_portfolio_options' );

/**
 * Active Callback Functions
 */
if ( ! function_exists( 'photofocus_is_portfolio_active' ) ) :
	/**
	* Return true if portfolio is active
	*
	* @since PhotoFocus 0.1
	*/
	function photofocus_is_portfolio_active( $control ) {
		$enable = $control->manager->get_setting( 'photofocus_portfolio_option' )->value();

		//return true only if previwed page on customizer matches the type of content option selected
		return ( photofocus_is_ect_portfolio_active( $control ) &&  photofocus_check_section( $enable ) );
	}
endif;

if ( ! function_exists( 'photofocus_is_ect_portfolio_inactive' ) ) :
    /**
    *
    * @since Photofocus 1.0
    */
    function photofocus_is_ect_portfolio_inactive( $control ) {
        return ! ( class_exists( 'Essential_Content_Jetpack_Portfolio' ) || class_exists( 'Essential_Content_Pro_Jetpack_Portfolio' ) );
    }
endif;

if ( ! function_exists( 'photofocus_is_ect_portfolio_active' ) ) :
    /**
    *
    * @since Photofocus 1.0
    */
    function photofocus_is_ect_portfolio_active( $control ) {
        return ( class_exists( 'Essential_Content_Jetpack_Portfolio' ) || class_exists( 'Essential_Content_Pro_Jetpack_Portfolio' ) );
    }
endif;
