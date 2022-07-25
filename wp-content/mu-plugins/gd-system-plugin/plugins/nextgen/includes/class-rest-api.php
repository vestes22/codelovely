<?php
/**
 * Rest API class.
 *
 * @since NEXT
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class REST_API
 *
 * @package GoDaddy\WordPress\Plugins\NextGen
 */
class REST_API {

	use Helper;

	/**
	 * Array of REST API namespaces.
	 *
	 * @var array
	 */
	const API_NAMESPACE = [
		'v1' => 'nextgen/v1',
	];

	const USER_CAP = 'edit_theme_options';

	/**
	 * REST_API constructor.
	 */
	public function __construct() {

		add_action( 'rest_api_init', [ $this, 'settings_endpoint' ] );
		add_action( 'rest_api_init', [ $this, 'customer_data_settings' ] );
		add_action( 'rest_api_init', [ $this, 'handle_custom_authorization_header' ], 9 );
		add_filter( 'rest_allowed_cors_headers', [ $this, 'allow_custom_authorization_header' ] );

	}

	/**
	 * GET nextgen/v1/settings route.
	 */
	public function settings_endpoint() {

		register_rest_route(
			self::API_NAMESPACE['v1'],
			'settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => function() {
					// See https://wordpress.org/support/article/roles-and-capabilities/#edit_theme_options .
					return current_user_can( self::USER_CAP );
				},
				'callback'            => [ $this, 'settings_callback' ],
			]
		);

	}

	/**
	 * Register GD Cid into the customer database.
	 */
	public function customer_data_settings() {

		register_setting(
			'nextgen_customer_data',
			'nextgen_customer_id',
			[
				'show_in_rest'      => true,
				'default'           => '',
				'type'              => 'string',
				'description'       => 'JWT CID',
				'sanitize_callback' => function( $value ) {
					return wp_is_uuid( $value ) ? $value : '';
				},
			]
		);

	}

	// phpcs:disable
	/**
	 * Set up Gutenberg editor settings.
	 * This is mostly copied from core.
	 *
	 * @return Array
	 */
	public function get_editor_settings() {
		// This is copied from core.
		global $editor_styles, $post;

		$align_wide    = get_theme_support( 'align-wide' );
		$color_palette = current( (array) get_theme_support( 'editor-color-palette' ) );
		$font_sizes    = current( (array) get_theme_support( 'editor-font-sizes' ) );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		// Editor Styles.
		$styles = array(
			array(
				'css' => file_get_contents(
					ABSPATH . WPINC . '/css/dist/editor/editor-styles.css'
				),
			),
		);

		$locale_font_family = esc_html_x( 'Noto Serif', 'CSS Font Family for Editor Font' );
		$styles[]           = [
			'css' => "body { font-family: '$locale_font_family' }",
		];

		if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
			foreach ( $editor_styles as $style ) {
				if ( preg_match( '~^(https?:)?//~', $style ) ) {
					$response = wp_remote_get( $style );
					if ( ! is_wp_error( $response ) ) {
						$styles[] = array(
							'css' => wp_remote_retrieve_body( $response ),
						);
					}
				} else {
					$file = get_theme_file_path( $style );
					if ( is_file( $file ) ) {
						$styles[] = array(
							'css'     => file_get_contents( $file ),
							'baseURL' => get_theme_file_uri( $style ),
						);
					}
				}
			}
		}

		$image_size_names = apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail' ),
				'medium'    => __( 'Medium' ),
				'large'     => __( 'Large' ),
				'full'      => __( 'Full Size' ),
			)
		);

		$available_image_sizes = array();
		foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
			$available_image_sizes[] = array(
				'slug' => $image_size_slug,
				'name' => $image_size_name,
			);
		}

		/**
		 * @psalm-suppress TooManyArguments
		 */
		$body_placeholder = apply_filters( 'write_your_story', __( 'Start writing or type / to choose a block' ), $post );
		// Filter used since 5.8
		$allowed_block_types = apply_filters( 'allowed_block_types_all', true, $post );;
		
		// Prior to 5.8 we need to apply the filter `allowed_block_types`
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '5.8', '<' ) ) {
			$allowed_block_types = apply_filters( 'allowed_block_types', true, $post );
		}

		$post_id = get_option( 'page_on_front' );

		if ( ! $post_id || ! is_numeric( $post_id ) ) {
			// Create a draft to load.
			if ( ! function_exists( 'get_default_post_to_edit' ) ) {
				require ABSPATH . '/wp-admin/includes/post.php';
			}
			$post_id = get_default_post_to_edit( 'page', true )->ID;
		}

		/**
		 * @psalm-suppress TooManyArguments
		 */
		$editor_settings = array(
			'initialPostId'          => (int) $post_id, // Heisenberg specific.
			'alignWide'              => $align_wide,
			'disableCustomColors'    => get_theme_support( 'disable-custom-colors' ),
			'disableCustomFontSizes' => get_theme_support( 'disable-custom-font-sizes' ),
			'disablePostFormats'     => ! current_theme_supports( 'post-formats' ),
			/** This filter is documented in wp-admin/edit-form-advanced.php */
			'titlePlaceholder'       => apply_filters( 'enter_title_here', __( 'Add title' ), $post ),
			'bodyPlaceholder'        => $body_placeholder,
			'isRTL'                  => is_rtl(),
			'autosaveInterval'       => AUTOSAVE_INTERVAL,
			'maxUploadFileSize'      => $max_upload_size,
			'allowedMimeTypes'       => [],
			'styles'                 => $styles,
			'imageSizes'             => $available_image_sizes,
			'richEditingEnabled'     => user_can_richedit(),
			'codeEditingEnabled'     => false,
			'allowedBlockTypes'      => $allowed_block_types,
			'__experimentalCanUserUseUnfilteredHTML' => false,
			'__experimentalBlockPatterns' => [],
			'__experimentalBlockPatternCategories' => [],
		);

		if ( false !== $color_palette ) {
			$editor_settings['colors'] = $color_palette;
		}

		if ( false !== $font_sizes ) {
			$editor_settings['fontSizes'] = $font_sizes;
		}

		// block_editor_settings_all is needed for 5.8+ conditionally return deprecated filter.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '5.8', '<' ) ) {
			return apply_filters( 'block_editor_settings', $editor_settings, $post );
		}

		return apply_filters( 'block_editor_settings_all', $editor_settings, $post );
	}

	/**
	 * Set up the Gutenberg REST API and preloaded data
	 * This is mostly copied from core.
	 */
	public function setup_rest_api() {
		global $post;

		$post_type = 'page';

		// Preload common data.
		$preload_paths = array(
			'/',
			'/wp/v2/types?context=edit',
			'/wp/v2/taxonomies?per_page=-1&context=edit',
			'/wp/v2/themes?status=active',
			sprintf( '/wp/v2/types/%s?context=edit', $post_type ),
			sprintf( '/wp/v2/users/me?post_type=%s&context=edit', $post_type ),
			array( '/wp/v2/media', 'OPTIONS' ),
			array( '/wp/v2/blocks', 'OPTIONS' ),
		);

		// Filter used since 5.8
		$preload_paths = apply_filters( 'block_editor_rest_api_preload_paths', $preload_paths, $post );

		// Prior to 5.8 we need to apply the filter `block_editor_preload_paths`
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '5.8', '<' ) ) {
			$preload_paths = apply_filters( 'block_editor_preload_paths', $preload_paths, $post );
		}
		

		return array_reduce( $preload_paths, 'rest_preload_api_request', array() );
	}
	// phpcs:enable

	/**
	 * Callback that outputs data for the settings_endpoint method.
	 */
	public function settings_callback() {

		if ( ! function_exists( 'get_current_screen' ) ) {
			require ABSPATH . '/wp-admin/includes/class-wp-screen.php';
			require ABSPATH . '/wp-admin/includes/screen.php';
			set_current_screen( 'edit-post' );
		}

		// Typical action that triggers wp_localize_scripts calls.
		// @codingStandardsIgnoreStart
		do_action( 'enqueue_block_editor_assets' );
		do_action( 'admin_enqueue_scripts' );
		do_action( 'wp_enqueue_scripts' );
		// @codingStandardsIgnoreEnd

		$settings = WP_Scripts::$localized_data;

		$settings['nextgenEditorPreload']  = $this->setup_rest_api();
		$settings['nextgenEditorSettings'] = $this->get_editor_settings();

		return $settings;

	}

	/**
	 * Populates the Basic Auth server details from the X-NextGen-Authorization header.
	 */
	public function handle_custom_authorization_header() {
		// If we don't have anything to pull from, return early.
		if ( ! isset( $_SERVER['HTTP_X_NEXTGEN_AUTHORIZATION'] ) ) {
			return;
		}

		// If either PHP_AUTH key is already set, do nothing.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) || isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return;
		}

		// Test to make sure the pattern matches expected.
		if ( ! preg_match( '%^Basic [a-z\d/+]*={0,2}$%i', $_SERVER['HTTP_X_NEXTGEN_AUTHORIZATION'] ) ) {
			return;
		}

		// Removing `Basic ` the token would start six characters in.
		$token    = substr( $_SERVER['HTTP_X_NEXTGEN_AUTHORIZATION'], 6 );
		$userpass = base64_decode( $token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		list( $user, $pass ) = explode( ':', $userpass );

		// Now shove them in the proper keys where we're expecting later on.
		$_SERVER['PHP_AUTH_USER'] = $user;
		$_SERVER['PHP_AUTH_PW']   = $pass;
	}

	/**
	 * Filters the list of request headers that are allowed for REST API CORS requests.
	 *
	 * @param string[] $allow_headers The list of request headers to allow.
	 */
	public function allow_custom_authorization_header( $allow_headers ) {
		return array_merge( $allow_headers, [ 'X-NextGen-Authorization' ] );
	}

}
