<?php

namespace WPaaS;

use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Yoast_SEO {

	public function __construct() {

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], PHP_INT_MAX );

	}

	/**
	 * Initialize hooks.
	 *
	 * @action plugins_loaded
	 */
	public function plugins_loaded() {

		if ( ! defined( 'WPSEO_VERSION' ) ) {

			return;

		}

		$this->suppress_trash_notices();

		add_action( 'admin_init', [ $this, 'suppress_notification_center_notices' ], 20 );

		add_filter( 'option_wpseo', [ $this, 'suppress_admin_notices' ], PHP_INT_MAX );

	}

	/**
	 * Set the Yoast SEO accessible post types to empty.
	 *
	 * @action plugins_loaded
	 */
	public function suppress_trash_notices() {

		Plugin::force_remove_hook( 'wp_trash_post', 'WPSEO_Slug_Change_Watcher', 'detect_post_trash' );
		Plugin::force_remove_hook( 'before_delete_post', 'WPSEO_Slug_Change_Watcher', 'detect_post_delete' );
		Plugin::force_remove_hook( 'delete_term_taxonomy', 'WPSEO_Slug_Change_Watcher', 'detect_term_delete' );

	}

	/**
	 * Suppress admin notices when not applicable.
	 *
	 * @filter option_wpseo
	 */
	public function suppress_admin_notices( $value ) {

		if ( Plugin::is_temp_domain() ) {

			// Turn off the "Huge SEO Issue" message.
			$value['ignore_search_engines_discouraged_notice'] = true;

		}

		$value['ignore_indexation_warning'] = true;

		return $value;

	}

	/**
	 * Suppress notifications in the notification center.
	 */
	public function suppress_notification_center_notices() {

		if ( ! class_exists( 'Yoast_Notification_Center' ) ) {

			return;

		}

		$notification_center = \Yoast_Notification_Center::get();

		// Older versions of Yoast don't have this method.
		if ( ! is_callable( [ $notification_center, 'remove_notification_by_id' ] ) ) {

			return;

		}

		// Don't show the Woo Helper upsell if the customer went through our on-boarding.
		if ( Plugin::has_used_wpnux() ) {

			$notification_center->remove_notification_by_id( 'wpseo-suggested-plugin-yoast-woocommerce-seo' );

		}

		// Don't show the blocking robots notice if using a temp domain.
		if ( Plugin::is_temp_domain() ) {

			$notification_center->remove_notification_by_id( 'wpseo-dismiss-blog-public-notice' );

		}

	}

}
