<?php

namespace WPaaS\Admin;

use \WPaaS\Cache;
use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Bar {

	/**
	 * Admin bar object.
	 *
	 * @var WP_Admin_Bar
	 */
	private $admin_bar;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

		/**
		 * Initialize NextGen filters.
		 */
		add_filter( 'nextgen_admin_links', [ $this, 'filter_site_link' ], 10, 1 );

	}

	/**
	 * Filter the admin links used for NextGen Logo-Menu.
	 *
	 * @param array $links associative array of name => 'link'.
	 *
	 * @return array
	 */
	public function filter_site_link( $links ) {

		return array_merge(
			$links,
			array(
				'overview'     => esc_url( Plugin::account_url( 'overview' ) ),
				'settings'     => esc_url( Plugin::account_url( 'settings' ) ),
				'changeDomain' => esc_url( Plugin::account_url( 'changedomain' ) ),
				'flush'        => current_user_can( Cache::$cap ) ? Cache::get_flush_url() : '',
			)
		);

	}

	/**
	 * Initialize the script.
	 *
	 * @action init
	 */
	public function init() {

		/**
		 * Filter the user cap required to view the admin bar menu.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$cap = (string) apply_filters( 'wpaas_admin_bar_cap', 'activate_plugins' );

		if ( ! current_user_can( $cap ) ) {

			return;

		}

		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Admin bar menu.
	 *
	 * @action admin_bar_menu
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function admin_bar_menu( \WP_Admin_Bar $admin_bar ) {

		$this->admin_bar = $admin_bar;

		$menus = [
			'gd' => 'gd_menu',
			'mt' => 'mt_menu',
		];

		$menu = Plugin::use_brand_value( $menus, 'reseller_menu' );

		if ( is_callable( [ $this, $menu ] ) ) {

			$this->$menu();

		}

	}

	/**
	 * Enqueue styles.
	 *
	 * @action admin_enqueue_scripts
	 * @action wp_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$rtl    = ! is_rtl() ? '' : '-rtl';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-admin-bar', Plugin::assets_url( "css/admin-bar{$rtl}{$suffix}.css" ), [], Plugin::version() );

	}

	/**
	 * Return the subdomain to use for the help docs
	 *
	 * @return string Help documents subdomain
	 */
	private function get_help_docs_url() {

		$language  = get_option( 'WPLANG', 'www' );
		$parts     = explode( '_', $language );
		$subdomain = ! empty( $parts[1] ) ? strtolower( $parts[1] ) : strtolower( $language );

		// Overrides
		switch ( $subdomain ) {

			case '':
				$subdomain = 'www'; // Default
				break;

			case 'uk':
				$subdomain = 'ua'; // Ukrainian (Українська)
				break;

			case 'el':
				$subdomain = 'gr'; // Greek (Ελληνικά)
				break;

			case 'gb':
				$subdomain = 'uk'; // United Kingdom
				break;

		}

		/**
		 * Filter the help documentation URL.
		 *
		 * @since 3.11.2
		 *
		 * @var string
		 */
		$url = (string) apply_filters( 'wpaas_help_docs_url', "https://${subdomain}.godaddy.com/help/wordpress-1000047", $subdomain, $language );

		return esc_url( $url );

	}

	/**
	 * GoDaddy admin menu.
	 */
	private function gd_menu() {

		$this->cleanup_admin_bar();
		$this->hosting_overview_menu_item();
		$this->hosting_settings_menu_item();
		$this->help_and_support_menu_item();
		$this->flush_cache_menu_item();
		$this->pro_connection_key_menu_item();

		global $submenu;

		if ( empty( $submenu['godaddy'] ) ) {

			return;

		}

		foreach ( $submenu['godaddy'] as $item ) {

			parse_str( $item[2], $var );

			$this->admin_bar->add_menu(
				[
					'parent' => 'wpaas',
					'id'     => 'wpaas-' . sanitize_title( ! empty( $var['tab'] ) ? $var['tab'] : $item[2] ),
					'href'   => $item[2],
					'title'  => $item[0],
				]
			);

		}

	}

	/**
	 * Media Temple admin menu.
	 */
	private function mt_menu() {

		$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'admin-generic' );
		$this->hosting_settings_menu_item();
		$this->flush_cache_menu_item();

	}

	/**
	 * Reseller admin menu.
	 */
	private function reseller_menu() {

		$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'admin-generic' );
		$this->hosting_overview_menu_item();
		$this->hosting_settings_menu_item();
		$this->flush_cache_menu_item();
		$this->pro_connection_key_menu_item();

	}

	/**
	 * Top-level menu item.
	 *
	 * @param string $label
	 * @param string $icon
	 */
	private function top_level_menu_item( $label, $icon ) {

		$managed_wordpress = [
			'id'     => 'wpaas',
			'title'  => sprintf(
				'<span class="ab-icon dashicons dashicons-%s"></span><span class="ab-label">%s</span>',
				esc_attr( $icon ),
				esc_html( $label )
			),
			'parent' => 'top-secondary',
		];

		if ( ! Plugin::use_nextgen() ) {

			unset( $managed_wordpress['parent'] );

		}

		$this->admin_bar->add_menu( $managed_wordpress );

	}

	/**
	 * Flush Cache menu item.
	 */
	private function flush_cache_menu_item() {

		if ( ! current_user_can( Cache::$cap ) ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-flush-cache',
				'title'  => __( 'Flush Cache', 'gd-system-plugin' ),
				'href'   => Cache::get_flush_url(),
			]
		);

	}

	/**
	 * Hosting Overview menu item.
	 */
	private function hosting_overview_menu_item() {

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-overview',
				'href'   => esc_url( Plugin::account_url( 'overview' ) ),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					__( 'Hosting Overview', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Hosting Settings menu item.
	 */
	private function hosting_settings_menu_item() {

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-settings',
				'href'   => esc_url( Plugin::account_url( 'settings' ) ),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					__( 'Hosting Settings', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Connection Management API key modal menu item.
	 */
	private function pro_connection_key_menu_item() {

		if ( ! is_admin() || filter_input( INPUT_GET, 'showWorker' ) || ! function_exists( 'mwp_get_potential_key' ) ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-pro-connection-key',
				'href'   => '#',
				'title'  => sprintf(
					'<span id="mwp-view-connection-key" class="wp-admin-bar-wpaas-pro-connection-key">%s</span>',
					__( 'Connection Management', 'gd-system-plugin' )
				),
			]
		);

	}

	/**
	 * Help & Support menu item
	 */
	private function help_and_support_menu_item() {

		if ( ! is_admin() ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-help-and-support',
				'href'   => $this->get_help_docs_url(),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					__( 'Help &amp; Support', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Cleanup the admin bar of excessive items.
	 */
	private function cleanup_admin_bar() {

		if ( ! Plugin::use_nextgen() ) {

			$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'admin-generic' );

			return;

		}

		$nodes          = $this->admin_bar->get_nodes();
		$customize_node = $this->admin_bar->get_node( 'customize' );

		$this->admin_bar->remove_node( 'wp-logo' );
		$this->admin_bar->remove_node( 'updates' );
		$this->admin_bar->remove_node( 'comments' );
		$this->admin_bar->remove_node( 'customize' );
		$this->admin_bar->remove_node( 'search' );
		$this->admin_bar->remove_node( 'archive' );

		$admin_bar_blacklist = [
			'wpseo-menu', // Yoast SEO
			'my-account',
			'new-media',
			'new-user',
			'new-shop_coupon',
			'new-shop_order',
		];

		foreach ( $nodes as $node_id => $node_data ) {

			if (
				'my-account' === $node_data->parent ||
				'site-name' === $node_data->parent ||
				in_array( $node_data->id, $admin_bar_blacklist, true )
			) {

				$this->admin_bar->remove_node( $node_id );

			}

		}

		$site_name_node        = $this->admin_bar->get_node( 'site-name' );
		$site_name_node->title = is_admin() ? __( 'My Site', 'gd-system-plugin' ) : __( 'My Dashboard', 'gd-system-plugin' );
		$site_name_node->href  = is_admin() ? esc_url( site_url() ) : esc_url( admin_url() );
		$this->admin_bar->add_menu( $site_name_node );

		if ( is_admin() ) {

			$this->admin_bar->add_menu(
				[
					'id'     => 'wpaas-admin-bar-my-account',
					'title'  => __( 'Logout' ),
					'parent' => 'top-secondary',
					'href'   => wp_logout_url(),
				]
			);

		}

		$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'admin-generic' );

		if ( empty( $customize_node ) ) {

			$this->admin_bar->add_menu(
				[
					'id'     => 'customize',
					'title'  => __( 'Design Editor', 'gd-system-plugin' ),
					'parent' => 'top-secondary',
					'href'   => admin_url( 'customize.php?autofocus[section]=colors' ),
					'meta'   => [
						'class' => 'hide-if-no-customize',
					],
				]
			);

		} else {

			$customize_node->parent = 'top-secondary';
			$customize_node->title  = __( 'Design Editor', 'gd-system-plugin' );
			$customize_node->href   = admin_url( 'customize.php?autofocus[section]=colors' );

			$this->admin_bar->add_menu( $customize_node );

		}

	}

}
