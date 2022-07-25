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
 * Misc functions
 *
 * @since 3.0.0
 */

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;


/**
 * Returns size information for all currently-registered image sizes
 *
 * @see https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 *
 * @since 3.0.0
 * @return array $sizes data for all currently-registered image sizes
 */
function wc_pdf_product_vouchers_get_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {

		if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {

			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );

		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}


/**
 * Gets size information for a specific image size
 *
 * @link https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 *
 * @since 3.0.0
 * @param string $size the image size for which to retrieve data.
 * @return bool|array $size size data about an image size or false if the size doesn't exist.
 */
function wc_pdf_product_vouchers_get_image_size( $size ) {

	$sizes = wc_pdf_product_vouchers_get_image_sizes();

	if ( isset( $sizes[ $size ] ) ) {
		return $sizes[ $size ];
	}

	return false;
}


/**
 * Gets the width of a specific image size
 *
 * @link https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 *
 * @since 3.0.0
 * @param string $size the image size for which to retrieve data.
 * @return bool|string $size width of an image size or false if the size doesn't exist.
 */
function wc_pdf_product_vouchers_get_image_width( $size ) {

	if ( ! $size = wc_pdf_product_vouchers_get_image_size( $size ) ) {
		return false;
	}

	if ( isset( $size['width'] ) ) {
		return $size['width'];
	}

	return false;
}


/**
 * Converts voucher file path to url
 *
 * In 3.0.0 moved from WC_Voucher to a global function.
 *
 * @since 2.2.1
 * @param string $path path to the voucher
 * @return string $url voucher download url
 */
function wc_pdf_product_vouchers_convert_path_to_url( $path ) {

	$wp_uploads     = wp_upload_dir();
	$wp_uploads_dir = $wp_uploads['basedir'];
	$wp_uploads_url = $wp_uploads['baseurl'];

	// replace uploads dir with uploads url
	$url = str_replace( $wp_uploads_dir, $wp_uploads_url, $path );

	return $url;
}


/**
 * Gets an array of available Google Fonts for PDF vouchers.
 *
 * @since 3.9.0
 *
 * @return string[]
 */
function wc_pdf_product_vouchers_get_available_google_fonts() : array {

	/**
	 * Filters the available font families and their URLs.
	 *
	 * Note: if these need to be translated at some point, make sure the keys are unchanged as they're used to enqueue font family styles.
	 *
	 * @since 3.9.0
	 *
	 * @param array $fonts the available Google font names
	 */
	$fonts = (array) apply_filters( 'wc_pdf_product_vouchers_available_google_fonts', [
		'Noto Sans',
		'Roboto',
		'Open Sans',
		'Lato',
		'Montserrat',
		'Playfair Display',
		'Archivo Narrow',
		'Poppins',
		'Raleway',
		'Merriweather',
	] );

	sort( $fonts );

	return array_unique( $fonts );
}
