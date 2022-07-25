<?php
/**
 * Header Media Options
 *
 * @package PhotoFocus
 */

/**
 * Add Header Media options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_header_media_options( $wp_customize ) {
	$wp_customize->get_section( 'header_image' )->description = esc_html__( 'If you add video, it will only show up on Homepage/FrontPage. Other Pages will use Header/Post/Page Image depending on your selection of option. Header Image will be used as a fallback while the video loads ', 'photofocus' );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_option',
			'default'           => 'entire-site',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => array(
				'homepage'               => esc_html__( 'Homepage / Frontpage', 'photofocus' ),
				'entire-site'            => esc_html__( 'Entire Site', 'photofocus' ),
				'disable'                => esc_html__( 'Disabled', 'photofocus' ),
			),
			'label'             => esc_html__( 'Enable on', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'select',
			'priority'          => 1,
		)
	);

	/* Scroll Down option */
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_scroll_down',
			'sanitize_callback' => 'photofocus_sanitize_checkbox',
			'default'           => 1,
			'label'             => esc_html__( 'Scroll Down Button', 'photofocus' ),
			'section'           => 'header_image',
			'custom_control'    => 'PhotoFocus_Toggle_Control',
		)
	);

	/*Overlay Option for Header Media*/
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_image_opacity',
			'default'           => '0',
			'sanitize_callback' => 'photofocus_sanitize_number_range',
			'label'             => esc_html__( 'Header Media Overlay', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'number',
			'input_attrs'       => array(
				'style' => 'width: 60px;',
				'min'   => 0,
				'max'   => 100,
			),
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_text_alignment',
			'default'           => 'text-align-left',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => array(
				'text-align-center' => esc_html__( 'Center', 'photofocus' ),
				'text-align-right'  => esc_html__( 'Right', 'photofocus' ),
				'text-align-left'   => esc_html__( 'Left', 'photofocus' ),
			),
			'label'             => esc_html__( 'Text Alignment', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'radio',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_content_alignment',
			'default'           => 'content-align-right',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => array(
				'content-align-center' => esc_html__( 'Center', 'photofocus' ),
				'content-align-right'  => esc_html__( 'Right', 'photofocus' ),
				'content-align-left'   => esc_html__( 'Left', 'photofocus' ),
			),
			'label'             => esc_html__( 'Content Alignment', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'radio',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_logo',
			'sanitize_callback' => 'esc_url_raw',
			'custom_control'    => 'WP_Customize_Image_Control',
			'label'             => esc_html__( 'Header Media Logo', 'photofocus' ),
			'section'           => 'header_image',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_logo_option',
			'default'           => 'homepage',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'active_callback'   => 'photofocus_is_header_media_logo_active',
			'choices'           => array(
				'homepage'               => esc_html__( 'Homepage / Frontpage', 'photofocus' ),
				'entire-site'            => esc_html__( 'Entire Site', 'photofocus' ) ),
			'label'             => esc_html__( 'Enable Header Media logo on', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'select',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_sub_title',
			'sanitize_callback' => 'wp_kses_post',
			'label'             => esc_html__( 'Header Media Tagline', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'text',
		)
	);

    photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_title',
			'sanitize_callback' => 'wp_kses_post',
			'label'             => esc_html__( 'Header Media Title', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'text',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_text',
			'sanitize_callback' => 'wp_kses_post',
			'label'             => esc_html__( 'Site Header Text', 'photofocus' ),
			'section'           => 'header_image',
			'type'              => 'textarea',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_url',
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
			'label'             => esc_html__( 'Header Media Url', 'photofocus' ),
			'section'           => 'header_image',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_media_url_text',
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Header Media Url Text', 'photofocus' ),
			'section'           => 'header_image',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_header_url_target',
			'sanitize_callback' => 'photofocus_sanitize_checkbox',
			'label'             => esc_html__( 'Open Link in New Window/Tab', 'photofocus' ),
			'section'           => 'header_image',
			'custom_control'    => 'PhotoFocus_Toggle_Control',
		)
	);
}
add_action( 'customize_register', 'photofocus_header_media_options' );

/** Active Callback Functions */

if ( ! function_exists( 'photofocus_is_header_media_logo_active' ) ) :
	/**
	* Return true if header logo is active
	*
	* @since PhotoFocus 0.1
	*/
	function photofocus_is_header_media_logo_active( $control ) {
		$logo = $control->manager->get_setting( 'photofocus_header_media_logo' )->value();
		if ( '' != $logo ) {
			return true;
		} else {
			return false;
		}
	}
endif;
