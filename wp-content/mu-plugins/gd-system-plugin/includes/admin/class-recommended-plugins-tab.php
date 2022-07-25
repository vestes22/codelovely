<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Recommended_Plugins_Tab {

	/**
	 * Tab slug name.
	 *
	 * @var string
	 */
	const SLUG = 'gd-recommended';

	/**
	 * URL for fetching JSON plugin data.
	 *
	 * @var string
	 */
	const URL = 'https://raw.githubusercontent.com/godaddy-wordpress/recommended-plugins/master/recommended-plugins.min.json';

	/**
	 * Recommended plugin data from GitHub
	 *
	 * @var array
	 */
	private $recommended_plugins;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! Plugin::is_gd() || version_compare( PHP_VERSION, '7.0', '<' ) || ( defined( 'GD_RECOMMENDED_PLUGINS' ) && ! GD_RECOMMENDED_PLUGINS ) ) {

			return;

		}

		add_action( 'admin_init', [ $this, 'redirect' ], -PHP_INT_MAX );

		add_action( 'admin_init', [ $this, 'get_recommended_plugins' ], -PHP_INT_MAX );

	}

	/**
	 * Redirect the default page to our tab.
	 */
	public function redirect() {

		global $pagenow;

		if ( 'plugin-install.php' === $pagenow && ! filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {

			wp_safe_redirect( add_query_arg( 'tab', self::SLUG, admin_url( 'plugin-install.php' ) ) );

			exit;

		}

	}

	/**
	 * Setup the recommended plugins data retrieved from GitHub and remaining actions
	 */
	public function get_recommended_plugins() {

		global $pagenow;

		if ( 'plugin-install.php' !== $pagenow ) {

			return;

		}

		$this->recommended_plugins = get_transient( 'wpaas_recommended_plugins' );

		if ( WP_DEBUG || false === $this->recommended_plugins ) {

			$response = wp_remote_get( add_query_arg( 'ver', time(), esc_url_raw( self::URL ) ) );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) || is_wp_error( $response ) ) {

				return $response;

			}

			$plugins = (object) json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $plugins ) ) {

				return;

			}

			$this->recommended_plugins = $plugins;

			set_transient( 'wpaas_recommended_plugins', $plugins, DAY_IN_SECONDS );

		}

		add_filter( 'install_plugins_tabs', [ $this, 'install_plugins_tabs' ], -PHP_INT_MAX );

		add_action( 'install_plugins_' . self::SLUG, [ $this, 'recommended_plugin_list' ] );

		add_filter( 'admin_body_class', [ $this, 'recommended_plugin_list_class' ] );

	}

	/**
	 * Setup our install plugins tabs
	 *
	 * @param  array $tabs List of available tabs.
	 *
	 * @return array Filtered list of plugin install tabs.
	 */
	public function install_plugins_tabs( $tabs ) {

		$unset_tabs = [
			'beta',
			'featured',
			'recommended',
		];

		foreach ( $unset_tabs as $tab ) {

			if ( ! isset( $tabs[ $tab ] ) ) {

				continue;

			}

			unset( $tabs[ $tab ] );

		}

		$tabs = [ self::SLUG => __( 'GoDaddy Recommended', 'gd-system-plugin' ) ] + $tabs;

		return $tabs;

	}

	/**
	 * Render the recommended plugin table
	 *
	 * @return null
	 */
	public function recommended_plugin_list() {

		$list_table = new Recommended_Plugins_List_Table( $this->recommended_plugins );

		?>

		<form id="plugin-filter" method="post">

			<?php $list_table->display(); ?>

		</form>

		<?php

	}

	/**
	 * Add the admin body class on the tab.
	 *
	 * @return string Admin body classes
	 */
	public function recommended_plugin_list_class( $classes ) {

		global $pagenow;

		if ( 'plugin-install.php' === $pagenow ) {

			$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

			$classes = ( ! $tab || self::SLUG === $tab ) ? $classes . ' gd-recommended-plugins' : $classes;

		}

		return $classes;

	}

}
