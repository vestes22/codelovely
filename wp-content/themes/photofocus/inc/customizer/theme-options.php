<?php
/**
 * Theme Options
 *
 * @package PhotoFocus
 */

/**
 * Add theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function photofocus_theme_options( $wp_customize ) {
	$wp_customize->add_panel( 'photofocus_theme_options', array(
		'title'    => esc_html__( 'Theme Options', 'photofocus' ),
		'priority' => 130,
	) );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_latest_posts_title',
			'default'           => esc_html__( 'News', 'photofocus' ),
			'sanitize_callback' => 'wp_kses_post',
			'label'             => esc_html__( 'Latest Posts Title', 'photofocus' ),
			'section'           => 'photofocus_theme_options',
		)
	);

	// Layout Options
	$wp_customize->add_section( 'photofocus_layout_options', array(
		'title' => esc_html__( 'Layout Options', 'photofocus' ),
		'panel' => 'photofocus_theme_options',
		)
	);

	/* Default Layout */
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_default_layout',
			'default'           => 'right-sidebar',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'label'             => esc_html__( 'Default Layout', 'photofocus' ),
			'section'           => 'photofocus_layout_options',
			'type'              => 'radio',
			'choices'           => array(
				'right-sidebar'         => esc_html__( 'Right Sidebar ( Content, Primary Sidebar )', 'photofocus' ),
				'no-sidebar-full-width' => esc_html__( 'No Sidebar: Full Width', 'photofocus' ),
			),
		)
	);

	/* Homepage/Archive Layout */
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_homepage_archive_layout',
			'default'           => 'no-sidebar-full-width',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'label'             => esc_html__( 'Homepage/Archive Layout', 'photofocus' ),
			'section'           => 'photofocus_layout_options',
			'type'              => 'radio',
			'choices'           => array(
				'right-sidebar'         => esc_html__( 'Right Sidebar ( Content, Primary Sidebar )', 'photofocus' ),
				'no-sidebar-full-width' => esc_html__( 'No Sidebar: Full Width', 'photofocus' ),
			),
		)
	);

	/* Single Page/Post Image */
	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_single_layout',
			'default'           => 'disabled',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'label'             => esc_html__( 'Single Page/Post Image', 'photofocus' ),
			'section'           => 'photofocus_layout_options',
			'type'              => 'radio',
			'choices'           => array(
				'disabled'              => esc_html__( 'Disabled', 'photofocus' ),
				'post-thumbnail'        => esc_html__( 'Post Thumbnail', 'photofocus' ),
			),
		)
	);

	// Excerpt Options.
	$wp_customize->add_section( 'photofocus_excerpt_options', array(
		'panel'     => 'photofocus_theme_options',
		'title'     => esc_html__( 'Excerpt Options', 'photofocus' ),
	) );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_excerpt_length',
			'default'           => '20',
			'sanitize_callback' => 'absint',
			'input_attrs' => array(
				'min'   => 10,
				'max'   => 200,
				'step'  => 5,
				'style' => 'width: 60px;',
			),
			'label'    => esc_html__( 'Excerpt Length (words)', 'photofocus' ),
			'section'  => 'photofocus_excerpt_options',
			'type'     => 'number',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_excerpt_more_text',
			'default'           => esc_html__( 'Continue reading...', 'photofocus' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Read More Text', 'photofocus' ),
			'section'           => 'photofocus_excerpt_options',
			'type'              => 'text',
		)
	);

	// Excerpt Options.
	$wp_customize->add_section( 'photofocus_search_options', array(
		'panel'     => 'photofocus_theme_options',
		'title'     => esc_html__( 'Search Options', 'photofocus' ),
	) );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_search_text',
			'default'           => esc_html__( 'Search', 'photofocus' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Search Text', 'photofocus' ),
			'section'           => 'photofocus_search_options',
			'type'              => 'text',
		)
	);
	
	// Homepage / Frontpage Options.
	$wp_customize->add_section( 'photofocus_homepage_options', array(
		'description' => esc_html__( 'Only posts that belong to the categories selected here will be displayed on the front page', 'photofocus' ),
		'panel'       => 'photofocus_theme_options',
		'title'       => esc_html__( 'Homepage / Frontpage Options', 'photofocus' ),
	) );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_recent_posts_heading',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => esc_html__( 'News', 'photofocus' ),
			'label'             => esc_html__( 'Recent Posts Heading', 'photofocus' ),
			'section'           => 'photofocus_homepage_options',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_static_page_heading',
			'sanitize_callback' => 'sanitize_text_field',
			'active_callback'	=> 'photofocus_is_static_page_enabled',
			'default'           => esc_html__( 'Archives', 'photofocus' ),
			'label'             => esc_html__( 'Posts Page Header Text', 'photofocus' ),
			'section'           => 'photofocus_homepage_options',
		)
	);

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_front_page_category',
			'sanitize_callback' => 'photofocus_sanitize_category_list',
			'custom_control'    => 'PhotoFocus_Multi_Cat',
			'label'             => esc_html__( 'Categories', 'photofocus' ),
			'section'           => 'photofocus_homepage_options',
			'type'              => 'dropdown-categories',
		)
	);

	// Pagination Options.
	$pagination_type = get_theme_mod( 'photofocus_pagination_type', 'default' );

	$nav_desc = '';

	/**
	* Check if navigation type is Jetpack Infinite Scroll and if it is enabled
	*/
	$nav_desc = sprintf(
		wp_kses(
			__( 'For infinite scrolling, use %1$sCatch Infinite Scroll Plugin%2$s with Infinite Scroll module Enabled.', 'photofocus' ),
			array(
				'a' => array(
					'href' => array(),
					'target' => array(),
				),
				'br'=> array()
			)
		),
		'<a target="_blank" href="https://wordpress.org/plugins/catch-infinite-scroll/">',
		'</a>'
	);

	$wp_customize->add_section( 'photofocus_pagination_options', array(
		'description'     => $nav_desc,
		'panel'           => 'photofocus_theme_options',
		'title'           => esc_html__( 'Pagination Options', 'photofocus' ),
		'active_callback' => 'photofocus_scroll_plugins_inactive'
	) );

	photofocus_register_option( $wp_customize, array(
			'name'              => 'photofocus_pagination_type',
			'default'           => 'default',
			'sanitize_callback' => 'photofocus_sanitize_select',
			'choices'           => photofocus_get_pagination_types(),
			'label'             => esc_html__( 'Pagination type', 'photofocus' ),
			'section'           => 'photofocus_pagination_options',
			'type'              => 'select',
		)
	);

	/* Scrollup Options */
	$wp_customize->add_section( 'photofocus_scrollup', array(
		'panel'    => 'photofocus_theme_options',
		'title'    => esc_html__( 'Scrollup Options', 'photofocus' ),
	) );

	$action = 'install-plugin';
	$slug   = 'to-top';

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

	// Add note to Scroll up Section
    photofocus_register_option( $wp_customize, array(
            'name'              => 'photofocus_to_top_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'PhotoFocus_Note_Control',
            'active_callback'   => 'photofocus_is_to_top_inactive',
            /* translators: 1: <a>/link tag start, 2: </a>/link tag close. */
            'label'             => sprintf( esc_html__( 'For Scroll Up, install %1$sTo Top%2$s Plugin', 'photofocus' ),
                '<a target="_blank" href="' . esc_url( $install_url ) . '">',
                '</a>'

            ),
           'section'            => 'photofocus_scrollup',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

    photofocus_register_option( $wp_customize, array(
            'name'              => 'photofocus_to_top_option_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'PhotoFocus_Note_Control',
            'active_callback'   => 'photofocus_is_to_top_active',
            /* translators: 1: <a>/link tag start, 2: </a>/link tag close. */
			'label'             => sprintf( esc_html__( 'For Scroll Up Options, go %1$shere%2$s', 'photofocus'  ),
                 '<a href="javascript:wp.customize.panel( \'to_top_panel\' ).focus();">',
                 '</a>'
            ),
            'section'           => 'photofocus_scrollup',
            'type'              => 'description',
        )
    );
}
add_action( 'customize_register', 'photofocus_theme_options' );

/** Active Callback Functions */
if ( ! function_exists( 'photofocus_scroll_plugins_inactive' ) ) :
	/**
	* Return true if infinite scroll functionality exists
	*
	* @since PhotoFocus 0.1
	*/
	function photofocus_scroll_plugins_inactive( $control ) {
		if ( ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'infinite-scroll' ) ) || class_exists( 'Catch_Infinite_Scroll' ) ) {
			// Support infinite scroll plugins.
			return false;
		}

		return true;
	}
endif;

if ( ! function_exists( 'photofocus_is_static_page_enabled' ) ) :
	/**
	* Return true if A Static Page is enabled
	*
	* @since PhotoFocus 1.1.2
	*/
	function photofocus_is_static_page_enabled( $control ) {
		$enable = $control->manager->get_setting( 'show_on_front' )->value();
		
		if ( 'page' === $enable ) {
			return true;
		}

		return false;
	}
endif;

if ( ! function_exists( 'photofocus_is_to_top_inactive' ) ) :
    /**
    * Return true if To Top is active
    *
    * @since PhotoFocus 0.1
    */
    function photofocus_is_to_top_inactive( $control ) {
        return ! ( class_exists( 'To_Top' ) );
    }
endif;

if ( ! function_exists( 'photofocus_is_to_top_active' ) ) :
    /**
    * Return true if To Top is active
    *
    * @since PhotoFocus 0.1
    */
    function photofocus_is_to_top_active( $control ) {
        return ( class_exists( 'To_Top' ) );
    }
endif;
