<?php
/**
 * NextGen Helpers
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

trait Helper {

	/**
	 * Get the base url for WPNUX API.
	 *
	 * @return string URL.
	 */
	public static function wpnux_api_base() {
		$api_urls = [
			'local' => 'https://wpnux.test/api',
			'dev'   => 'https://wpnux.dev-godaddy.com/v2/api',
			'test'  => 'https://wpnux.test-godaddy.com/v2/api',
			'prod'  => 'https://wpnux.godaddy.com/v2/api',
		];

		$env = getenv( 'SERVER_ENV', true );

		$api_url = ! empty( $api_urls[ $env ] ) ? $api_urls[ $env ] : $api_urls['prod'];

		return untrailingslashit( (string) apply_filters( 'nextgen_wpnux_api_url', $api_url ) );
	}

	/**
	 * Given an interface name. Find all the classes from the class
	 *
	 * @param array  $classlist array of classes to check.
	 * @param string $interface the interface we are looking for.
	 * @param string $namespace the namespace to check against.
	 *
	 * @return array of classes that implements the interface.
	 */
	public function find_classes_by_interface( $classlist = [], $interface = '', $namespace = __NAMESPACE__ ) {

		return array_filter(
			$classlist,
			function( $classname ) use ( $interface, $namespace ) {

				// Move to str_starts_with when we move to php 8.
				if ( ! ! $namespace && strpos( $classname, $namespace ) !== 0 ) {
					return false;
				}

				return in_array( $interface, class_implements( $classname ), true );

			}
		);

	}

}
