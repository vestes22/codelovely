<?php
/**
 * NextGen Admin Modal
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Modal
 *
 * @package NextGen
 * @author  GoDaddy
 */
class Wp_Admin {

	use Helper;

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'admin_footer-themes.php', [ $this, 'admin_themes_optout_modal' ] );
		add_action( 'admin_init', [ $this, 'register_scripts' ] );
		add_filter( 'go_default_copyright', [ $this, 'filter_copyright_text' ] );

	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function register_scripts() {
		global $pagenow;

		if ( 'themes.php' !== $pagenow ) {

			return;

		}

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/wpAdmin.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-wp-admin',
			GD_NEXTGEN_PLUGIN_URL . 'build/wpAdmin.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_enqueue_style(
			'nextgen-wp-admin-style',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-wpAdmin.css',
			[],
			$asset_file['version']
		);

	}

	/**
	 *  Output React binding Div.
	 */
	public function admin_themes_optout_modal() {
		// Add Admin Modal backdrop to the DOM.
		?>

		<div id="nextgen-themes-optout-modal"></div>

		<?php
	}

	/**
	 * Filter the default Go copyright text.
	 */
	public function filter_copyright_text() {

		return sprintf(
			/* translators: %s: GoDaddy. */
			__( 'Go by %s', 'nextgen' ),
			'GoDaddy'
		);

	}

}
