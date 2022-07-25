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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Integrations;

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;

defined( 'ABSPATH' ) or exit;

 /**
  * PDF Product Vouchers integrations class.
  *
  * @since 3.7.1
  */
class Paytrail {


	/**
	 * Constructor.
	 *
	 * @since 3.7.1
	 */
	public function __construct() {

		add_filter( 'wc_paytrail_payment_params', [ $this, 'update_payment_order_items' ], 10, 2 );
	}


	/**
	 * Updates Paytrail payment order line items to include MPV discounts.
	 *
	 * @internal
	 *
	 * @since 3.7.1
	 *
	 * @see \WC_Paytrail_API_Request::set_payment_params()
	 *
	 * @param array $params payment order items
	 * @param \WC_Order $order the order object
	 * @return array
	 */
	public function update_payment_order_items( $params, $order ) {

		if ( $order instanceof \WC_Order && isset( $params['orderDetails']['products'] ) ) {

			$redemption_handler = wc_pdf_product_vouchers()->get_redemption_handler_instance();

			$discount_total = $redemption_handler->get_order_total_mpv_credit_used( $order );

			if ( $discount_total > 0 ) {

				$voucher_codes = [];

				foreach ( $redemption_handler->get_order_coupons( $order, 'multi_purpose_voucher' ) as $coupon ) {
					$voucher_codes[] = strtoupper( $coupon->get_code() );
				}

				/** This filter is documented in WooCommerce Paytrail Gateway in src/api/class-wc-paytrail-api-request.php */
				$vat_decimal_places = (int) apply_filters( 'wc_paytrail_vat_decimal_places', 2 );

				// no redirect to Paytrail should occur if the discount total is greater than the items total, but let's check anyway
				$items_total = array_sum( wp_list_pluck( $params['orderDetails']['products'], 'price' ) );
				$item_price  = -1 * ( $items_total < $discount_total ? $items_total : $discount_total );

				// add voucher discounts to Paytrail payment items
				$params['orderDetails']['products'][] = [
					'title'    => _n( 'Gift Certificate:', 'Gift Certificates:', count( $voucher_codes ), 'woocommerce-pdf-product-vouchers' ) . ' ' . implode( ', ', $voucher_codes ),
					'amount'   => 1,
					'price'    => wc_format_decimal( $item_price, 2 ),
					'vat'      => number_format( 0.0, $vat_decimal_places ),
					'discount' => '0.00',
					'type'     => 1,
				];
			}
		}

		return $params;
	}


}
