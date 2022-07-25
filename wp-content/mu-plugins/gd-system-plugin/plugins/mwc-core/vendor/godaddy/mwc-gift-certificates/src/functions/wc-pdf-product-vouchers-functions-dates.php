<?php
/**
 * MWC Gift Certificates
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade MWC Gift Certificates to newer
 * versions in the future. If you wish to customize MWC Gift Certificates for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GiftCertificates;

defined( 'ABSPATH' ) or exit;

/**
 * Date functions
 *
 * @since 3.0.0
 */

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;


/**
 * Loosely parses a date for PDF Vouchers date usages
 *
 * (This is not an absolutely fool-proof check due to PHP 5.2 compatibility constraints)
 *
 * @since 3.0.0
 * @param string|int $date a date in timestamp or string format
 * @param string $format (optional) format to validate: either 'mysql' (default) or 'timestamp'
 * @return false|string|int the date parsed in the chosen format or false if not a valid date
 */
function wc_pdf_product_vouchers_validate_date( $date, $format = 'mysql' ) {

	$parsed_date  = false;
	$is_timestamp = 'timestamp' === $format;

	if ( $is_timestamp && is_numeric( $date ) && (int) $date > 0 ) {

		$parsed_date = (int) $date;

	} elseif ( ! $is_timestamp && is_string( $date ) ) {

		$date = strtotime( $date );

		if ( $date > 0 ) {

			$format      = 'mysql' === $format ? 'Y-m-d H:i:s' : $format;
			$parsed_date = date( $format, $date );
		}
	}

	return $parsed_date;
}


/**
 * Formats date in a requested format
 *
 * @since 3.0.0
 * @param string|int $date date string, in 'mysql' format, or timestamp
 * @param string $format (optional) format to use: 'mysql' (default), 'timestamp' or valid PHP date format
 * @return string|int formatted date as a timestamp or mysql format
 */
function wc_pdf_product_vouchers_format_date( $date, $format = 'mysql' ) {

	switch ( $format ) {

		case 'mysql':
			return is_numeric( $date ) ? date( 'Y-m-d H:i:s', $date ) : $date;

		case 'timestamp':
			return is_numeric( $date ) ? (int) $date : strtotime( $date );

		default:
			return date( $format, is_numeric( $date ) ? (int) $date : strtotime( $date ) );

	}
}


/**
 * Adjusts dates in UTC format
 *
 * Converts a UTC date to the corresponding date in another timezone.
 *
 * @since 3.0.0
 * @param int|string $date date in string or timestamp format
 * @param string $format format to use in output
 * @param string $timezone timezone to convert from
 * @return int|string
 */
function wc_pdf_product_vouchers_adjust_date_by_timezone( $date, $format = 'mysql', $timezone = 'UTC' ) {

	if ( is_numeric( $date ) ) {
		$src_date = date( 'Y-m-d H:i:s', $date );
	} else {
		$src_date = (int) $date;
	}

	if ( 'mysql' === $format ) {
		$format = 'Y-m-d H:i:s';
	}

	if ( 'UTC' === $timezone ) {
		$from_timezone = 'UTC';
		$to_timezone   = wc_timezone_string();
	} else {
		$from_timezone = $timezone;
		$to_timezone   = 'UTC';
	}

	try {

		$from_date = new \DateTime( $src_date, new \DateTimeZone( $from_timezone ) );
		$to_date   = new \DateTimeZone( $to_timezone );
		$offset    = $to_date->getOffset( $from_date );

		// getTimestamp method not used here for PHP 5.2 compatibility
		$timestamp = (int) $from_date->format( 'U' );

	} catch ( \Exception $e ) {

		// in case of DateTime errors, just return the date as is but issue an error
		trigger_error( sprintf( 'Failed to parse date "%1$s" to get timezone offset: %2$s.', $date, $e->getMessage() ), E_USER_WARNING );

		$timestamp = is_numeric( $date ) ? (int) $date : strtotime( $date );
		$offset    = 0;
	}

	return 'timestamp' === $format ? $timestamp + $offset : date( $format, $timestamp + $offset );
}
