<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Configs {

	/**
	 * Array of config data.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$path = $this->find_readable_path(
			[
				ABSPATH . 'gd-config.php',
				ABSPATH . 'mt-config.php',
				WPMU_PLUGIN_DIR . '/bin/gd-config.php',
			]
		);

		if ( $path ) {

			require_once $path;

		}

		$this->load_ini_configs();

	}

	/**
	 * Return a config value.
	 *
	 * @param  string $config
	 *
	 * @return mixed|false
	 */
	public function get( $config ) {

		if ( ! isset( $this->data[ $config ] ) ) {

			return false;

		}

		if ( is_numeric( $this->data[ $config ] ) && (int) $this->data[ $config ] === $this->data[ $config ] ) {

			return (int) $this->data[ $config ];

		}

		return $this->data[ $config ];

	}

	/**
	 * Verify that configs exist.
	 *
	 * @return bool
	 */
	public function exist() {

		return ! empty( $this->data );

	}

	/**
	 * Load ini configs.
	 */
	private function load_ini_configs() {

		$defaults = $this->parse_ini(
			$this->find_readable_path(
				[
					'/web/conf/gd-wordpress.conf',
					WPMU_PLUGIN_DIR . '/bin/gd-wordpress.conf',
				]
			)
		);

		$resellers = $this->parse_ini(
			$this->find_readable_path(
				[
					'/web/conf/gd-resellers.conf',
					WPMU_PLUGIN_DIR . '/bin/gd-resellers.conf',
				]
			)
		);

		$reseller_id = Plugin::reseller_id();
		$reseller    = ( $reseller_id && isset( $resellers[ $reseller_id ] ) ) ? $resellers[ $reseller_id ] : [];
		$configs     = array_replace_recursive( $defaults, $reseller );

		ksort( $configs );

		foreach ( $configs as $config => $value ) {

			$this->data[ $config ] = $value;

		}

	}

	/**
	 * Return the first readable path from an array.
	 *
	 * @param  array $paths
	 *
	 * @return string|false
	 */
	private function find_readable_path( array $paths ) {

		foreach ( $paths as $path ) {

			if ( is_readable( $path ) ) {

				return $path;

			}

		}

		return false;

	}

	/**
	 * Return an array of parsed ini configs.
	 *
	 * @param  string $path
	 *
	 * @return array
	 */
	private function parse_ini( $path ) {

		return is_readable( $path ) ? (array) parse_ini_file( $path, true ) : [];

	}

	/**
	 * Return a valid JSON file or an empty array
	 *
	 * @param string $path
	 *
	 * @return array|boolean
	 */
	private function parse_json( $path ) {

		if ( ! is_readable( $path ) ) {

			return false;

		}

		$json = json_decode( file_get_contents( $path ) );

		return ( null === $json ) ? false : $json;

	}

}
