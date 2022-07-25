<?php
/**
 * NextGen Site Design
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

use WP_Error;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Site_Design
 *
 * @package NextGen
 * @author  GoDaddy
 */
class Site_Design {

	use Helper;

	const USER_CAP             = 'edit_theme_options';
	const EDITOR_WRAPPER_CLASS = 'editor-styles-wrapper';
	const API_ROUTE            = 'design';

	const NEXTGEN_ONLY_FONTS = [
		[
			'Cardo'  => [
				'700',
			],
			'Roboto' => [
				'400',
			],
		],
		[
			'Yeseva One' => [
				'400',
			],
			'Vollkorn'   => [
				'400',
			],
		],
		[
			'Anton'         => [
				'400',
			],
			'IBM Plex Sans' => [
				'400',
			],
		],
		[
			'IBM Plex Mono_heading' => [
				'600',
				'400',
			],
			'IBM Plex Mono_body'    => [
				'400',
			],
		],
		[
			'Recursive' => [
				'900',
			],
			'Open Sans' => [
				'400',
			],
		],
	];

	/**
	 * The requests params set for the ajax callback.
	 *
	 * @var array
	 */
	private $request_params;

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_site_design_update_design_style', [ $this, 'update_design_style' ] );
		add_action( 'rest_api_init', [ $this, 'design_endpoint' ] );

		/**
		 * Remove Go theme inline editor styles
		 */
		add_action(
			'wp_loaded',
			function() {
				remove_action( 'admin_init', 'Go\Core\editor_styles' );
			}
		);

		/**
		 * Add the shared styles to the editor
		 */
		add_action(
			'admin_init',
			function() {
				$suffix = SCRIPT_DEBUG ? '' : '.min';
				$rtl    = ! is_rtl() ? '' : '-rtl';
				// Enqueue  shared editor styles.
				add_editor_style(
					"dist/css/style-editor{$rtl}{$suffix}.css"
				);
			}
		);

		add_action(
			'admin_head',
			function() {
				printf( '<style id="site-design-styles">%s</style>', esc_html( $this->get_editor_styles() ) );
			}
		);

	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts() {

		if ( ! current_user_can( self::USER_CAP ) ) {

			return;

		}

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// short-circuit.
		$active_theme = wp_get_theme();
		if ( 'Go' !== $active_theme->get( 'Name' ) ) {
			return;
		}

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/siteDesign.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-site-design',
			GD_NEXTGEN_PLUGIN_URL . 'build/siteDesign.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-site-design', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		$current_design_style = \Go\Core\get_design_style();
		$fonts                = self::get_go_fonts();

		$data = [
			'apiRoute'               => self::API_ROUTE,
			'editorClass'            => self::EDITOR_WRAPPER_CLASS,
			'availableDesignStyles'  => \Go\Core\get_available_design_styles(),
			'currentDesignStyle'     => get_theme_mod( 'design_style', \Go\Core\get_default_design_style() ),
			'currentColorScheme'     => get_theme_mod( 'color_scheme', \Go\Core\get_default_color_scheme() ),
			'currentColors'          => [
				'primary'    => get_theme_mod( 'primary_color' ),
				'secondary'  => get_theme_mod( 'secondary_color' ),
				'tertiary'   => get_theme_mod( 'tertiary_color' ),
				'background' => get_theme_mod( 'background_color' ),
			],
			'isAdvancedFontsEnabled' => false,
		];

		if ( version_compare( GO_VERSION, '1.3.9', '>' ) ) {
			$data = array_merge(
				$data,
				[ 'isAdvancedFontsEnabled' => true ]
			);
		}

		if ( version_compare( GO_VERSION, '1.3.6', '>=' ) ) {
			$data = array_merge(
				$data,
				[
					'currentFonts' => get_theme_mod( 'fonts', $current_design_style['fonts'] ),
					'fontSize'     => get_theme_mod( 'font_size', $current_design_style['font_size'] ),
					'typeRatio'    => get_theme_mod( 'type_ratio', $current_design_style['type_ratio'] ),
					'fonts'        => $fonts,
				]
			);
		}

		wp_localize_script(
			'nextgen-site-design',
			'nextgenSiteDesign',
			$data
		);

		wp_enqueue_style(
			'nextgen-site-design',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-siteDesign.css',
			[],
			$asset_file['version']
		);

		wp_add_inline_style(
			'nextgen-site-design',
			'.editor-post-title__input { height: auto !important; scrollbar-width: none; }'
		);
	}

	/**
	 * Register a design endpoint to deal with site design.
	 */
	public function design_endpoint() {
		register_rest_route(
			REST_API::API_NAMESPACE['v1'],
			self::API_ROUTE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => function() {
					// See https://wordpress.org/support/article/roles-and-capabilities/#edit_theme_options.
					return current_user_can( self::USER_CAP );
				},
				'callback'            => [ $this, 'update_design_style' ],
			]
		);
	}

	/**
	 * Return array of Go fonts.
	 *
	 * @return array
	 */
	private static function get_go_fonts() {
		$design_styles = \Go\Core\get_available_design_styles();

		$fonts = array_values(
			array_map(
				function( $design_style ) {
					return isset( $design_style['fonts'] ) ? $design_style['fonts'] : [];
				},
				$design_styles
			)
		);

		if ( version_compare( GO_VERSION, '1.3.9', '>' ) ) {
			$fonts = array_merge( $fonts, self::NEXTGEN_ONLY_FONTS );
		}

		return $fonts;
	}

	/**
	 * Set's the request params used in the ajax callback.
	 *
	 * @param array $args The parameters.
	 */
	public function set_request_params( array $args ) {
		$this->request_params = $this->sanitize_request_params( $args );
	}

	/**
	 * Get the sanitized request params.
	 *
	 * @return array
	 */
	public function get_request_params() {
		return $this->sanitize_request_params( $this->request_params );
	}

	/**
	 * Returns the sanitized request params.
	 *
	 * Uses INPUT_POST if params are not passed as an argument.
	 *
	 * @param array $request_params The passed params.
	 *
	 * @return array
	 */
	protected function sanitize_request_params( $request_params = null ) {
		$args = [
			'design_style'     => FILTER_SANITIZE_STRING,
			'color_palette'    => FILTER_SANITIZE_STRING,
			'fonts'            => FILTER_SANITIZE_STRING,
			'font_size'        => FILTER_SANITIZE_STRING,
			'type_ratio'       => FILTER_VALIDATE_FLOAT,
			'should_update'    => FILTER_VALIDATE_BOOLEAN,
			'initial_load'     => FILTER_VALIDATE_BOOLEAN,
			'primary_color'    => FILTER_SANITIZE_STRING,
			'secondary_color'  => FILTER_SANITIZE_STRING,
			'tertiary_color'   => FILTER_SANITIZE_STRING,
			'background_color' => FILTER_SANITIZE_STRING,
		];

		return is_array( $request_params )
			? filter_var_array( $request_params, $args )
			: filter_input_array( INPUT_POST, $args );
	}

	/**
	 * Get the stylesheet content of current design style
	 *
	 * @param null $design_style design style object.
	 *
	 * @return string|string[]
	 */
	private function get_editor_styles( $design_style = null ) {
		if ( ! $design_style ) {
			$design_style = \Go\Core\get_design_style();
		}

		// @codingStandardsIgnoreStart
		$stylesheet = file_get_contents(
			sprintf(
				'%1$s/go/%2$s',
				get_theme_root(),
				str_replace( '.min', '', $design_style['editor_style'] )
			)
		);
		// @codingStandardsIgnoreEnd

		return str_replace(
			'../../../dist/images/',
			'/wp-content/themes/go/dist/images/',
			str_replace( ':root', '.' . self::EDITOR_WRAPPER_CLASS, $stylesheet )
		);
	}

	/**
	 * Retreive the selected design style styles and return them for injection into the DOM
	 */
	public function update_design_style() {

		$request_params = $this->get_request_params();

		if ( ! $request_params ) {
			return new WP_Error(
				'rest_invalid',
				__( 'Error processing your request.', 'nextgen' ),
				[ 'status' => 500 ]
			);
		}

		$selected_style = $request_params['design_style'];
		$color_palette  = $request_params['color_palette'];
		$fonts          = $request_params['fonts'];
		$font_size      = $request_params['font_size'];
		$type_ratio     = $request_params['type_ratio'];
		$should_update  = $request_params['should_update'];
		$initial_load   = $request_params['initial_load'];

		$custom_colors = [
			'primary_color'    => $request_params['primary_color'],
			'secondary_color'  => $request_params['secondary_color'],
			'tertiary_color'   => $request_params['tertiary_color'],
			'background_color' => $request_params['background_color'],
		];

		if ( ! $selected_style ) {

			return new WP_Error(
				'rest_selected_style_invalid',
				__( 'A design_style must be passed.', 'nextgen' ),
				[ 'status' => 404 ]
			);

		}

		$design_styles = \Go\Core\get_available_design_styles();

		if ( ! isset( $design_styles[ $selected_style ] ) ) {

			return new WP_Error(
				'rest_design_style_invalid',
				__( 'An available design_style must be passed.', 'nextgen' ),
				[ 'status' => 404 ]
			);

		}

		$design_style  = $design_styles[ $selected_style ];
		$default_fonts = $this->get_go_fonts();

		if ( $should_update ) {

			set_theme_mod( 'design_style', $selected_style );
			set_theme_mod( 'color_scheme', $color_palette );

			if ( isset( $fonts, $font_size, $type_ratio ) ) {
				$fonts = json_decode( html_entity_decode( $fonts ), true );

				if ( is_array( $fonts ) && count( $fonts ) <= 3 ) {
					$avail_font_keys = str_replace( [ '_heading', '_body' ], '', array_keys( array_merge( ...$default_fonts ) ) ); // flatten array.
					$font_keys       = str_replace( [ '_heading', '_body' ], '', array_keys( $fonts ) );

					// Make sure the font pack selected are in the available list of fonts.
					if ( ! array_diff( $font_keys, $avail_font_keys ) ) {
						set_theme_mod( 'fonts', $fonts );
					}
				}

				set_theme_mod( 'font_size', $font_size );
				set_theme_mod( 'type_ratio', $type_ratio );
			}

			foreach ( $custom_colors as $theme_mod => $color ) {
				$theme_mod_string = str_replace( '_color', '', $theme_mod );
				$color            = ! empty( $color ) ? $color : $design_style['color_schemes'][ $color_palette ][ $theme_mod_string ];

				set_theme_mod( $theme_mod, $color );
			}
		}

		$fonts_string = [];

		foreach ( $default_fonts as $fonts ) {
			foreach ( $fonts as $font_name => $font_weights ) {
				$fonts_string[] = sprintf( '%1$s:%2$s', str_replace( [ '_heading', '_body' ], '', $font_name ), implode( ',', $font_weights ) );
			}
		}

		// Fonts take too long to load. So we are only fetching on first page load.
		$font_styles = $initial_load ? file_get_contents( // @codingStandardsIgnoreLine
			esc_url_raw(
				add_query_arg(
					[
						'family'  => rawurlencode( implode( '|', $fonts_string ) ),
						'subset'  => rawurlencode( 'latin,latin-ext' ),
						'display' => 'swap',
					],
					'https://fonts.googleapis.com/css'
				)
			)
		) : false;

		return [
			'stylesheet' => $this->get_editor_styles( $design_style ),
			'fontStyles' => $font_styles,
		];

	}
}
