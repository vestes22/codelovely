<?php
/**
 * Plugin autoloader
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(

	function( $resource ) { // @codingStandardsIgnoreLine

		if ( 0 !== strpos( $resource, __NAMESPACE__ ) ) {

			return;

		}

		$resource = strtolower(
			str_replace(
				[ __NAMESPACE__ . '\\', '_' ],
				[ '', '-' ],
				$resource
			)
		);

		$parts = explode( '\\', $resource );
		$name  = array_pop( $parts );
		$files = str_replace( '//', '/', glob( sprintf( '%s/%s/*-%s.php', __DIR__, implode( '/', $parts ), $name ) ) );

		if ( isset( $files[0] ) && is_readable( $files[0] ) ) {

			require_once $files[0];

		}

	}
);
