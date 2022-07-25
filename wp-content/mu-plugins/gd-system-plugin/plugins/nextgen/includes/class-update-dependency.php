<?php
/**
 * NextGen force update when loading into editor.
 *
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * Auto Updates Class.
 */
class Update_Dependency {
	/**
	 * Whether or not plugins and themes should force updates.
	 *
	 * @access private
	 * @var false until set true upon determining updates are available.
	 */
	private $force_update = false;

	/**
	 * Plugin and theme dependencies have been updates and a snackbar should be shown.
	 *
	 * @access private
	 * @var false until set true upon determining updates were forced.
	 */
	private $update_was_forced = false;

	/**
	 * Source of truth of trigger query arg.
	 *
	 * @access private
	 * @var String
	 */
	private $forced_update_query_arg = 'nextgen-update-forced';

	/**
	 * Source of truth whether user is allowed to update.
	 *
	 * @access private
	 * @var Function
	 * @return Boolean True if user is allowed to update dependencies.
	 */
	private function user_allowed_to_update() {

		return current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' );

	}

	/**
	 * Class constructor
	 */
	public function __construct() {

		// Check latest versions from wp.org and set update_plugins and update_themes transients.
		wp_update_plugins();
		wp_update_themes();

		$update_plugins = get_site_transient( 'update_plugins' );
		$update_themes  = get_site_transient( 'update_themes' );

		if ( ! empty( $update_plugins->response ) && isset( $update_plugins->response['coblocks/class-coblocks.php'] ) ) {

			$this->force_update = true;

		}

		if ( ! empty( $update_themes->response ) && isset( $update_themes->response['go'] ) ) {

			$this->force_update = true;

		}

		$this->update_was_forced = filter_input( INPUT_GET, $this->forced_update_query_arg, FILTER_VALIDATE_BOOLEAN );

		add_filter(
			'removable_query_args',
			function( $removable_query_args ) {
				$removable_query_args[] = $this->forced_update_query_arg;
				return $removable_query_args;
			}
		);

		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Enqueue the scripts.
	 *
	 * @access public
	 */
	public function enqueue_scripts() {

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/updateDependency.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-update-dependency',
			GD_NEXTGEN_PLUGIN_URL . 'build/updateDependency.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-update-dependency', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		wp_enqueue_style(
			'nextgen-update-dependency',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-updateDependency.css',
			[],
			$asset_file['version']
		);

		wp_localize_script(
			'nextgen-update-dependency',
			'nextgenForceUpdates',
			[
				'updatesAvailable'  => $this->force_update ? 'true' : 'false',
				'updatesWereForced' => $this->update_was_forced ? 'true' : 'false',
			]
		);

		if ( $this->force_update ) {

			wp_add_inline_style( 'nextgen-update-dependency', '.components-modal__screen-overlay:not(.nextgen-update-dependency-modal-overlay) { display: none; }' );

		}

	}

	/**
	 * Fire off background process for updating dependency plugins and themes.
	 *
	 * @access public
	 * @return WP_REST_Response A '200' response status is expected after updates have completed.
	 */
	public function run_update() {

		$skin            = new \WP_Ajax_Upgrader_Skin();
		$theme_upgrader  = new \Theme_Upgrader( $skin );
		$plugin_upgrader = new \Plugin_Upgrader( $skin );
		$theme_result    = $theme_upgrader->bulk_upgrade( array( 'go' ) );
		$plugin_result   = $plugin_upgrader->bulk_upgrade( array( 'coblocks/class-coblocks.php' ) );

		$response = new \WP_REST_Response( [] );
		$response->set_status( 200 );
		return $response;

	}

	/**
	 * Update database transient for 'update_plugins' and 'update_themes' and use the values to return a WP_REST_Response.
	 *
	 * @access public
	 * @return WP_REST_Response Response contains the boolean value of whether or not updates are available.
	 */
	public function get_dep_update() {

		wp_update_plugins();
		wp_update_themes();

		$update_plugins    = get_site_transient( 'update_plugins' );
		$update_themes     = get_site_transient( 'update_themes' );
		$has_plugin_update = false;
		$has_theme_update  = false;

		if ( ! empty( $update_plugins->response ) && isset( $update_plugins->response['coblocks/class-coblocks.php'] ) ) {

			$has_plugin_update = true;

		}

		if ( ! empty( $update_themes->response ) && isset( $update_themes->response['go'] ) ) {

			$has_theme_update = true;

		}

		$data     = [ $has_theme_update || $has_plugin_update ];
		$response = new \WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;

	}

	/**
	 * Register rest routes for use with the updateDependency script.
	 *
	 * @access public
	 */
	public function register_rest_endpoints() {

		register_rest_route(
			'nextgen',
			'do/update',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'run_update' ],
				'permission_callback' => function() {
					return $this->user_allowed_to_update();
				},
			)
		);

		register_rest_route(
			'nextgen',
			'/get/update',
			array(
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_dep_update' ],
				'permission_callback' => function() {
					return $this->user_allowed_to_update();
				},
			)
		);

	}

}
