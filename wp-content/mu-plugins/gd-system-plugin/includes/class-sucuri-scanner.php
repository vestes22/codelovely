<?php

namespace WPaaS;

use SucuriScanCache;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Sucuri_Scanner {

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	const BASENAME = 'sucuri-scanner/sucuri.php';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ], PHP_INT_MAX );

	}

	/**
	 * Special behavior to run at the very end of `init`.
	 *
	 * @action init - PHP_INT_MAX
	 */
	public function init() {

		add_filter( 'sucuriscan_option_defaults', function ( $options ) {

			// Disable all notifications by default.
			foreach ( $options as $option => &$value ) {

				$value = ( 0 === strpos( $option, 'sucuriscan_notify_' ) ) ? 'disabled' : $value;

			}

			$options['sucuriscan_use_wpmail'] = 'enabled';
			$options['sucuriscan_diff_utility'] = 'enabled';
			$options['sucuriscan_lastlogin_redirection'] = 'enabled';

			return $options;

		} );

		add_action( 'current_screen', function ( $current_screen ) {

			if ( ! class_exists( 'SucuriScanCache' ) || empty( $current_screen->id ) || 'toplevel_page_sucuriscan' !== $current_screen->id ) {

				return;

			}

			$cache = new SucuriScanCache( 'integrity' );

			$whitelist = [
				'gd-config.php'                 => 'added',
				'wp-admin/install.php'          => 'removed',
				'wp-admin/includes/upgrade.php' => 'modified',
			];

			// Prevent our files from affecting integrity checks.
			foreach ( $whitelist as $file_path => $file_status ) {

				$key = md5( $file_path );

				if ( ! $cache->exists( $key ) ) {

					$cache->add( $key, [ 'file_path' => $file_path, 'file_status' => $file_status, 'ignored_at' => time() ] );

				}

			}

		} );

	}

}
