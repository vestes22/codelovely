<?php
/**
 * NextGen Media Download
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Media_Download
 *
 * @package NextGen
 * @author  GoDaddy
 */
class Media_Download {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts() {

		$default_asset_file = [
			'dependencies' => [ 'nextgen-nux-patterns' ],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/mediaDownload.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-media-download',
			GD_NEXTGEN_PLUGIN_URL . 'build/mediaDownload.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-media-download', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

	}

}
