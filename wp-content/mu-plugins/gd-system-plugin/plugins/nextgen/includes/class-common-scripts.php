<?php
/**
 * NextGen Common Scripts
 *
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Main Logo_Menu class
 *
 * @package NextGen
 */
class Common_Scripts {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Expose registering of common styles.
	 */
	public function register_styles() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_styles' ] );

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
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/common.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-common',
			GD_NEXTGEN_PLUGIN_URL . 'build/common.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_localize_script(
			'nextgen-common',
			'nextgenCommonData',
			[
				'baseApiUrl' => REST_API::API_NAMESPACE['v1'],
			]
		);

		wp_set_script_translations( 'nextgen-common', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

	}

	/**
	 * Enqueue the styles.
	 */
	public function enqueue_styles() {

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/common.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_style(
			'nextgen-common',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-common.css',
			[],
			$asset_file['version']
		);

	}
}

