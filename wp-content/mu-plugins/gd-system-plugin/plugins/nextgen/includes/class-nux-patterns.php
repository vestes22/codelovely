<?php
/**
 * NextGen NUX Patterns
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * NUX_Patterns
 *
 * @package NextGen
 * @author  GoDaddy
 */
class NUX_Patterns {

	use Helper;

	const TRANSIENT = 'nextgen_nux_patterns';

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_init', [ $this, 'deregister_go_layouts' ] );
		add_action( 'block_editor_settings_all', [ $this, 'load_remote_patterns' ] );

		// block_editor_settings_all is needed for 5.8+ conditionally add deprecated action.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '5.8', '<' ) ) {
			add_action( 'block_editor_settings', [ $this, 'load_remote_patterns' ] );
		}
	}

	/**
	 * Filters the settings to pass to the block editor.
	 *
	 * @param array $settings Default editor settings.
	 */
	public function load_remote_patterns( $settings ) {

		$nux_api_endpoint = self::wpnux_api_base() . '/patterns';
		$locale           = determine_locale();
		$transient_key    = self::TRANSIENT . '-' . md5( $nux_api_endpoint . $locale );
		$patterns         = get_transient( $transient_key );

		if ( false === $patterns ) {
			$response = wp_remote_get( add_query_arg( 'lang', $locale, $nux_api_endpoint ) );

			// Do nothing if we've received an error.
			if ( is_wp_error( $response ) ) {
				return $settings;
			}

			// Do nothing if the response is not json.
			if ( false === strstr( wp_remote_retrieve_header( $response, 'content-type' ), 'application/json' ) ) {
				return $settings;
			}

			$patterns = wp_remote_retrieve_body( $response );
			set_transient( $transient_key, $patterns, DAY_IN_SECONDS );
		}

		$patterns = json_decode( $patterns );

		$settings['__experimentalBlockPatternCategories'] = $this->merge_block_pattern_categories(
			$settings['__experimentalBlockPatternCategories'],
			wp_list_pluck( $patterns, 'categories' )
		);

		$new_patterns = array_map(
			function( $pattern ) {
				return [
					'title'       => $pattern->title,
					'name'        => "nextgen/{$pattern->slug}",
					'content'     => $pattern->content,
					'description' => $pattern->description,
					'categories'  => $pattern->categories,
				];
			},
			$patterns
		);

		$settings['__experimentalBlockPatterns'] = array_merge(
			$settings['__experimentalBlockPatterns'],
			$new_patterns
		);

		return $settings;
	}

	/**
	 * Merge current and incoming categories.
	 *
	 * @param array $current The current block categories.
	 * @param array $incoming The new block categories to add.
	 *
	 * @return array
	 */
	private function merge_block_pattern_categories( $current, $incoming ) {
		$current_categories = wp_list_pluck( $current, 'label', 'name' );

		foreach ( $incoming as $categories ) {
			foreach ( $categories as $category ) {
				$category_slug = sanitize_title( $category );

				if ( ! array_key_exists( $category_slug, $current_categories ) ) {
					$current_categories[ $category_slug ] = ucwords( $category );
				}
			}
		}

		return array_map(
			function( $value, $key ) {
				return [
					'name'  => $key,
					'label' => $value,
				];
			},
			$current_categories,
			array_keys( $current_categories )
		);
	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts() {

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/nuxPatterns.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-nux-patterns',
			GD_NEXTGEN_PLUGIN_URL . 'build/nuxPatterns.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-nux-patterns', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		// Get imported template value from the 'wpnux_export_data' option.
		$wpnux_export_data = json_decode( get_option( 'wpnux_export_data' ) );

		wp_localize_script(
			'nextgen-nux-patterns',
			'nextgenNuxPatterns',
			[
				'currentLocale'  => determine_locale(),
				'nuxApiEndpoint' => self::wpnux_api_base(),
				'wpnuxTemplate'  => isset( $wpnux_export_data->_meta->template ) ? $wpnux_export_data->_meta->template : '',
			]
		);
	}

	/**
	 * Remove layouts registered by Go.
	 */
	public function deregister_go_layouts() {

		remove_filter( 'coblocks_layout_selector_layouts', 'go_coblocks_about_layouts' );
		remove_filter( 'coblocks_layout_selector_layouts', 'go_coblocks_contact_layouts' );
		remove_filter( 'coblocks_layout_selector_layouts', 'go_coblocks_home_layouts' );
		remove_filter( 'coblocks_layout_selector_layouts', 'go_coblocks_gallery_layouts' );

		/**
		 * `go_coblocks_portfolio_layouts` is present in Go v1.4.0 and earlier
		 */
		remove_filter( 'coblocks_layout_selector_layouts', 'go_coblocks_portfolio_layouts' );

	}
}
