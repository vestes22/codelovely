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
 * Template functions
 *
 * @since 1.2.0
 */

use WC_Product;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;


if ( ! function_exists( '\GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_render_product_voucher_fields' ) ) {

	/**
	 * Pluggable function to render the frontend product page voucher fields
	 *
	 * @since 1.2.0
	 * @param WC_Product $product the voucher product
	 */
	function wc_pdf_product_vouchers_render_product_voucher_fields( WC_Product $product ) {

		// don't remove this starting array for PHP 7.1+
		$products = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_product_id ) {
				$products[] = wc_get_product( $variation_product_id );
			}
		} else {
			$products[] = $product;
		}

		foreach ( $products as $product ) {

			$voucher_template = MWC_Gift_Certificates_Product::get_voucher_template( $product );

			if ( $voucher_template ) {

				$fields = $voucher_template->get_user_input_voucher_fields();
				$images = $voucher_template->get_image_urls();

				if ( $fields || $images ) {

					reset( $images );

					$product_id = $product->get_id();

					// load the template file
					wc_get_template(
						'single-product/product-voucher.php',
						array(
							'product'          => $product,
							'product_id'       => $product_id,
							'voucher_template' => $voucher_template,
							'fields'           => $fields,
							'images'           => $images,
							'selected_image'   => isset( $_POST[ 'voucher_image' ] ) && ! empty( $_POST[ 'voucher_image' ][ $product_id ] ) ? $_POST[ 'voucher_image' ][ $product_id ] : key( $images ),
						),
						'',
						wc_pdf_product_vouchers()->get_plugin_path() . '/templates/'
					);
				}
			}
		}
	}

}


if ( ! function_exists( '\GoDaddy\WordPress\MWC\GiftCertificates\wc_vouchers_locate_voucher_preview_template' ) ) {

	/**
	 * Locates the voucher preview template file, in this plugin's templates directory
	 *
	 * @since 1.0.0
	 * @param string $locate locate path
	 * @return string the location path for the voucher preview file
	 */
	function wc_vouchers_locate_voucher_preview_template( $locate ) {

		$post_type = get_query_var( 'post_type' );
		$preview   = get_query_var( 'preview' );

		if ( 'wc_voucher_template' == $post_type && 'true' === $preview ) {
			$locate = wc_pdf_product_vouchers()->get_plugin_path() . '/templates/single-wc_voucher_template.php';
		}

		// voucher template needs to be loaded for both preview and when viewing the voucher,
		// so that PDF generation from HTML works
		if ( 'wc_voucher' == $post_type ) {
			$locate = wc_pdf_product_vouchers()->get_plugin_path() . '/templates/single-wc_voucher.php';
		}

		return $locate;
	}

}

if ( ! function_exists( '\GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_account_vouchers' ) ) {

	/**
	 * Renders My Account > Vouchers template
	 *
	 * @since 3.0.0
	 */
	function wc_pdf_product_vouchers_account_vouchers() {
		wc_get_template( 'myaccount/vouchers.php' );
	}
}


/**
 * Returns My Account > Vouchers columns
 *
 * @since 3.0.0
 * @return array associative array of column-id => label
 */
function wc_pdf_product_vouchers_get_account_vouchers_columns() {

	/**
	 * Filter My Account > Vouchers columns.
	 *
	 * @since 3.0.0
	 * @param array $columns
	 */
	return apply_filters( 'wc_pdf_product_vouchers_account_vouchers_columns', array(
		'voucher-number'    => __( 'Number', 'woocommerce-pdf-product-vouchers' ),
		'voucher-expires'   => __( 'Expires', 'woocommerce-pdf-product-vouchers' ),
		'voucher-remaining' => __( 'Remaining Value', 'woocommerce-pdf-product-vouchers' ),
		'voucher-actions'   => '&nbsp;',
	) );
}

