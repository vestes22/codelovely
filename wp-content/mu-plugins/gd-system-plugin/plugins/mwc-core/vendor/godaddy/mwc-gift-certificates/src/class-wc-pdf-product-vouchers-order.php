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

use WC_Order;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Order handler/helper class
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Order {


	/**
	 * Returns any vouchers attached to an order
	 *
	 * @since 1.2.0
	 * @param WC_Order $order the order object
	 * @return WC_Voucher[]
	 */
	public static function get_vouchers( $order ) {

		$vouchers = array();

		$order_items = $order instanceof WC_Order ? $order->get_items() : array();

		if ( count( $order_items ) > 0 ) {

			foreach ( $order_items as $order_item_id => $item ) {

				$vouchers = array_merge( $vouchers, self::get_order_item_vouchers( $item ) );
			}
		}

		return $vouchers;
	}


	/**
	 * Returns any vouchers associated with an order item.
	 *
	 * @since 3.0.0
	 * @param \WC_Order_Item_Product|array $item order item
	 * @return WC_Voucher[]
	 */
	public static function get_order_item_vouchers( $item ) {

		$vouchers = array();

		$voucher_meta = $item->get_meta( '_voucher_id', false );

		if ( ! empty( $voucher_meta ) ) {

			foreach ( $voucher_meta as $meta ) {

				if ( $voucher = wc_pdf_product_vouchers_get_voucher( $meta->value ) ) {
					$vouchers[] = $voucher;
				}
			}
		}

		return $vouchers;
	}


	/**
	 * Returns true if an order has been marked as fully redeemed.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Order $order the order object
	 * @return boolean true if the order is marked as redeemed
	 */
	public static function vouchers_redeemed( WC_Order $order ) {
		return (bool) $order->get_meta( '_voucher_redeemed' );
	}


	/**
	 * Marks an order as having all vouchers redeemed
	 *
	 * @since 1.2.0
	 * @param WC_Order $order the order object
	 * @param int $voucher_count the number of redeemed vouchers
	 */
	public static function mark_vouchers_redeemed( WC_Order $order, $voucher_count = 1 ) {

		$order->add_order_note( _n( 'Gift certificate redeemed.', 'All gift certificates redeemed.', $voucher_count, 'woocommerce-pdf-product-vouchers' ) );

		$order->update_meta_data( '_voucher_redeemed', true );
		$order->save_meta_data();

		/**
		 * Fires after all vouchers for an order have been marked redeemed
		 *
		 * @since 1.2.0
		 * @param WC_Order $order the order object
		 */
		do_action( 'wc_pdf_product_vouchers_order_redeemed', $order );
	}


}
