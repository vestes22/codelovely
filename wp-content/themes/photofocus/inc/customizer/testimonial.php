<?php
/**
 * Add Testimonial Settings in Customizer
 *
 * @package PhotoFocus
*/

/**
 * Add testimonial options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_testimonial_options( $wp_customize ) {
	// Add note to Jetpack Testimonial Section
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_jetpack_testimonial_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'PhotoFocus_Note_Control',
			'label'             => sprintf( esc_html__( 'For Testimonial Options for PhotoFocus Theme, go %1$shere%2$s', 'photofocus' ),
				'<a href="javascript:wp.customize.section( \'photofocus_testimonials\' ).focus();">',
				 '</a>'
			),
		   'section'            => 'jetpack_testimonials',
			'type'              => 'description',
			'priority'          => 1,
		)
	);

	$wp_customize->add_section( 'photofocus_testimonials', array(
			'panel'    => 'photofocus_theme_options',
			'title'    => esc_html__( 'Testimonials', 'photofocus' ),
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
	        'name'              => 'photofocus_testimonial_jetpack_note',
	        'sanitize_callback' => 'sanitize_text_field',
	        'custom_control'    => 'Photofocus_Note_Control',
	        'active_callback'   => 'photofocus_is_ect_testimonial_inactive',
	        /* translators: 1: <a>/link tag start, 2: </a>/link tag close. */
	        'label'             => sprintf( esc_html__( 'For Testimonial, install %1$sEssential Content Types%2$s Plugin with testimonial Type Enabled', 'photofocus' ),
	            '<a target="_blank" href="' . esc_url( $install_url ) . '">',
	            '</a>'

	        ),
	       'section'            => 'photofocus_testimonials',
	        'type'              => 'description',
	        'priority'          => 1,
	    )
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_testimonial_option',
			'default'           => 'disabled',
			'active_callback'   => 'photofocus_is_ect_testimonial_active',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => photofocus_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'photofocus' ),
			'section'           => 'photofocus_testimonials',
			'type'              => 'select',
			'priority'          => 1,
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_testimonial_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'PhotoFocus_Note_Control',
			'active_callback'   => 'photofocus_is_testimonial_active',
			/* translators: 1: <a>/link tag start, 2: </a>/link tag close. */
			'label'             => sprintf( esc_html__( 'For CPT heading and sub-heading, go %1$shere%2$s', 'photofocus' ),
				'<a href="javascript:wp.customize.section( \'jetpack_testimonials\' ).focus();">',
				'</a>'
			),
			'section'           => 'photofocus_testimonials',
			'type'              => 'description',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_testimonial_number',
			'default'           => '3',
			'sanitize_callback' => 'photofocus_sanitize_number_range',
			'active_callback'   => 'photofocus_is_testimonial_active',
			'label'             => esc_html__( 'Number of items', 'photofocus' ),
			'section'           => 'photofocus_testimonials',
			'type'              => 'number',
			'input_attrs'       => array(
				'style'             => 'width: 100px;',
				'min'               => 0,
			),
		)
	);

	$number = get_theme_mod( 'photofocus_testimonial_number', 3 );

	for ( $i = 1; $i <= $number ; $i++ ) {

		//for CPT
		photofocus_register_option( $wp_customize, array(
				'name'              => 'photofocus_testimonial_cpt_' . $i,
				'sanitize_callback' => 'photofocus_sanitize_post',
				'active_callback'   => 'photofocus_is_testimonial_active',
				'label'             => esc_html__( 'Testimonial', 'photofocus' ) . ' ' . $i ,
				'section'           => 'photofocus_testimonials',
				'type'              => 'select',
				'choices'           => photofocus_generate_post_array( 'jetpack-testimonial' ),
			)
		);
	} // End for().
}
add_action( 'customize_register', 'photofocus_testimonial_options' );

/**
 * Active Callback Functions
 */
if ( ! function_exists( 'photofocus_is_testimonial_active' ) ) :
	/**
	* Return true if testimonial is active
	*
	* @since PhotoFocus Pro 1.0
	*/
	function photofocus_is_testimonial_active( $control ) {
		$enable = $control->manager->get_setting( 'photofocus_testimonial_option' )->value();

		//return true only if previwed page on customizer matches the type of content option selected
		return ( photofocus_is_ect_testimonial_active( $control ) &&  photofocus_check_section( $enable ) );
	}
endif;

if ( ! function_exists( 'photofocus_is_ect_testimonial_inactive' ) ) :
    /**
    *
    * @since Photofocus 1.0
    */
    function photofocus_is_ect_testimonial_inactive( $control ) {
        return ! ( class_exists( 'Essential_Content_Jetpack_testimonial' ) || class_exists( 'Essential_Content_Pro_Jetpack_testimonial' ) );
    }
endif;

if ( ! function_exists( 'photofocus_is_ect_testimonial_active' ) ) :
    /**
    *
    * @since Photofocus 1.0
    */
    function photofocus_is_ect_testimonial_active( $control ) {
        return ( class_exists( 'Essential_Content_Jetpack_testimonial' ) || class_exists( 'Essential_Content_Pro_Jetpack_testimonial' ) );
    }
endif;
