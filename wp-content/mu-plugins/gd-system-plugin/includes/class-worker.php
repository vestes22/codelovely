<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Worker {

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	const BASENAME = 'worker/init.php';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'muplugins_loaded', [ $this, 'muplugins_loaded' ], -PHP_INT_MAX );

		add_action( 'init', [ $this, 'init' ], PHP_INT_MAX );

		add_filter( 'plugin_install_action_links', [ $this, 'filter_search_results_action_links' ], PHP_INT_MAX, 2 );

		if ( filter_input( INPUT_GET, 'showWorker' ) ) {

			add_filter( 'all_plugins', [ $this, 'show_in_plugins_list' ], PHP_INT_MAX );

			add_filter( 'option_active_plugins', [ $this, 'filter_active_plugins' ], PHP_INT_MAX );

			add_action( 'plugin_action_links_' . self::BASENAME, [ $this, 'remove_action_links' ], PHP_INT_MAX );

			add_action( 'admin_print_styles', [ $this, 'hide_invalid_plugin_notice' ] );

		}

	}

	/**
	 * Special behavior to run early on `muplugins_loaded`.
	 *
	 * @action muplugins_loaded - -PHP_INT_MAX
	 */
	public function muplugins_loaded() {

		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . '0-worker.php';

		if ( is_readable( $mu_plugin_file ) ) {

			@unlink( $mu_plugin_file );

		}

		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . self::BASENAME;

		if ( is_readable( $plugin_file ) ) {

			$this->uninstall( $plugin_file );

		}

	}

	/**
	 * Special behavior to run at the very end of `init`.
	 *
	 * @action init - PHP_INT_MAX
	 */
	public function init() {

		$mmb_core = function_exists( 'mwp_core' ) ? mwp_core() : null;

		if ( is_a( $mmb_core, 'MMB_Core' ) ) {

			$this->remove_hook(
				[ 'admin_notices', [ $mmb_core, 'admin_notice' ] ],
				[ 'network_admin_notices', [ $mmb_core, 'network_admin_notice' ] ] // Multisite.
			);

		}

	}

	/**
	 * Show the worker plugin as installed and active in the WordPress.org plugin search results
	 *
	 * @filter plugin_install_action_links - PHP_INT_MAX
	 *
	 * @param  array action_links Plugin action links array.
	 * @param  array $plugin      Plugin data array.
	 *
	 * @return array Filtered array of plugin action links
	 */
	public function filter_search_results_action_links( $action_links, $plugin ) {

		if ( dirname( self::BASENAME ) !== $plugin['slug'] ) {

			return $action_links;

		}

		$action_links[2] = $action_links[1];

		$action_links[0] = sprintf(
			'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
			_x( 'Active', 'plugin' )
		);

		$action_links[1] = sprintf(
			'<a href="javascript:void(0);" class="wpaas-tip worker" data-tooltip="%1$s" data-tooltip-direction="%2$s"><span class="dashicons-before dashicons-editor-help">Required</span></a>',
			esc_attr__( 'This is already included as a must-use plugin on your site and is required for backups and other important hosting platform benefits to function properly. It cannot be removed or deactivated. It should not be installed as a normal plugin and will be automatically removed if uploaded manually.', 'gd-system-plugin' ),
			esc_attr( wp_is_mobile() ? 'top' : ( is_rtl() ? 'right' : 'left' ) )
		);

		return $action_links;

	}

	/**
	 * Show plugin in the admin list view.
	 *
	 * @filter all_plugins - PHP_INT_MAX
	 *
	 * @param  array $plugins
	 *
	 * @return array
	 */
	public function show_in_plugins_list( $plugins ) {

		$plugins[ self::BASENAME ] = get_plugin_data( Plugin::base_dir() . 'plugins/' . self::BASENAME );

		return $plugins;

	}

	/**
	 * Add the worker plugin to the list of active plugins
	 *
	 * @filter option_active_plugins - PHP_INT_MAX
	 *
	 * @param  array $active_plugins Active plugins option.
	 *
	 * @return array Filtered array of active plugins
	 */
	public function filter_active_plugins( $active_plugins ) {

		$active_plugins[] = self::BASENAME;

		return $active_plugins;

	}

	/**
	 * Remove the deactivate action link from the worker plugin
	 *
	 * @action plugin_action_links_worker/init.php - PHP_INT_MAX
	 *
	 * @param array $action_links The worker plugin action links.
	 *
	 * @return array Filtered array of worker plugin action links
	 */
	public function remove_action_links( $action_links ) {

		unset( $action_links['deactivate'] );

		return $action_links;

	}

	/**
	 * Hide the plugin deactivation error message
	 *
	 * @action admin_print_styles/init.php - PHP_INT_MAX
	 */
	public function hide_invalid_plugin_notice() {

		?>

		<style type="text/css">
		#message.error {
			display: none;
		}
		</style>

		<?php

	}

	/**
	 * Remove one or more hooked action or filter.
	 *
	 * @param array $... Variable list of param arrays to pass through `remove_filter()`.
	 */
	protected function remove_hook( $array ) {

		foreach ( func_get_args() as $params ) {

			if ( isset( $params[1] ) && is_callable( $params[1] ) ) {

				remove_filter( ...$params );

			}

		}

	}

	/**
	 * Ensure the plugin is deactivated and deleted.
	 *
	 * @param string $plugin_file
	 */
	private function uninstall( $plugin_file ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		if ( is_plugin_active( plugin_basename( $plugin_file ) ) ) {

			deactivate_plugins( $plugin_file, true ); // Skip deactivation hooks.

		}

		if ( ! class_exists( 'WP_Filesystem' ) ) {

			require_once ABSPATH . 'wp-admin/includes/file.php';

		}

		$plugin_dir = escapeshellarg( dirname( $plugin_file ) );

		exec( "rm -rf {$plugin_dir} > /dev/null 2>/dev/null &" ); // Non-blocking.

	}

}
