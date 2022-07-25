<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Gravity_Forms {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! defined( 'GRAVITY_MANAGER_URL' ) && ! Plugin::is_env( 'prod' ) ) {

			define( 'GRAVITY_MANAGER_URL', 'http://dev.gravityhelp.com/wp-content/plugins/gravitymanager' );

		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 0 );

		add_filter( 'http_request_args', [ $this, 'http_request_args' ], PHP_INT_MAX, 2 );

	}

	/**
	 * Special behavior to run early on `plugins_loaded`.
	 *
	 * @action plugins_loaded - 0
	 */
	public function plugins_loaded() {

		if ( ! class_exists( 'GFForms' ) || ! defined( 'GD_GF_LICENSE_KEY' ) || defined( 'GF_LICENSE_KEY' ) || ! get_option( 'gform_pending_installation', true ) ) {

			return;

		}

		define( 'GF_LICENSE_KEY', GD_GF_LICENSE_KEY );

		add_action( 'enqueue_block_editor_assets', [ $this, 'coblocks_gforms_callout' ] );

	}

	/**
	 * Enqueue the gravity forms callout script
	 */
	public function coblocks_gforms_callout() {

		if ( ! class_exists( 'CoBlocks' ) ) {

			return;

		}

		wp_enqueue_script(
			'wpaas-coblocks-gform-callout',
			Plugin::assets_url( 'js/wpaas-coblocks-gform-callout.min.js' ),
			[ 'wp-i18n', 'wp-element', 'wp-plugins', 'wp-components' ],
			Helpers::version()
		);

	}

	/**
	 * Add our header to API calls.
	 *
	 * @filter http_request_args - PHP_INT_MAX
	 */
	public function http_request_args( $args, $url ) {

		if ( defined( 'GRAVITY_MANAGER_URL' ) && 0 === strpos( $url, GRAVITY_MANAGER_URL ) ) {

			$args['headers']['X-Gravity-Client'] = base64_encode( Plugin::config( 'gf_client_key' ) );

		}

		return $args;

	}

}
