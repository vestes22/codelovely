<?php
/**
 * NextGen image categories
 *
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * NextGen Image Categories.
 */
class Layout_Selector {
	/**
	 * Reference to the image categories API.
	 *
	 * @access private
	 * @var null until assigned as $category_api_class instantiated class.
	 */
	private static $api;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param \WPaaS\StockPhotos\API $category_api_class Instantiated class with access to image categories API.
	 */
	public function __construct( $category_api_class = false ) {

		self::$api = $category_api_class;

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );
		add_action( 'init', [ $this, 'register_image_category_setting' ], 11 );

	}

	/**
	 * Enqueue the script.
	 */
	public function enqueue_scripts() {

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/layoutSelector.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-layout-selector',
			GD_NEXTGEN_PLUGIN_URL . 'build/layoutSelector.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-layout-selector', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		wp_enqueue_style(
			'nextgen-layout-selector',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-layoutSelector.css',
			[],
			$asset_file['version']
		);

		$choices = [];

		if ( self::$api ) {

			$choices = self::$api->get_d3_choices();

			if ( ! self::$api->is_d3_locale() || ! $choices ) {

				$choices = self::$api->get_d3_categories_fallback();

			}

			array_shift( $choices );

			$choices = [ 'generic' => __( 'Generic', 'nextgen' ) ] + $choices;

		}

		wp_localize_script(
			'nextgen-layout-selector',
			'nextgenImageCategories',
			$choices
		);
	}

	/**
	 * Register a core site setting for an image category
	 */
	public function register_image_category_setting() {

		// Get imported template value from the 'wpnux_export_data' option.
		$wpnux_export_data = json_decode( get_option( 'wpnux_export_data' ) );

		register_setting(
			'image_category',
			'image_category',
			[
				'show_in_rest'      => true,
				'default'           => isset( $wpnux_export_data->_meta->template ) ? $wpnux_export_data->_meta->template : '',
				'type'              => 'string',
				'description'       => __( 'Image category.', 'nextgen' ),
				'sanitize_callback' => null,
			]
		);
	}
}
