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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Order;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Orders Admin
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Admin_Orders {


	/**
	 * Initializes the voucher orders admin
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_resend_voucher_recipient_email' ) );

		// complete the order once all vouchers have been redeemed
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'maybe_complete_order' ), 15 );

		// hide voucher order item meta fields in the Edit Orders admin
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_itemmeta' ) );

		// display a link to the generated voucher
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'before_order_itemmmeta' ), 10, 2 );
	}


	/**
	 * Adds the "Voucher Recipient" Admin edit Order Actions dropdown
	 *
	 * @since 1.2.0
	 * @param array $available_emails available action email ids
	 * @return array available action email ids
	 */
	public function add_resend_voucher_recipient_email( $available_emails ) {

		$voucher_recipient = false;

		// order contains any recipient emails addresses?
		$order = isset( $_GET['post'] ) ? wc_get_order( $_GET['post'] ) : null;

		// bail if the order isn't set
		if ( ! $order instanceof \WC_Order ) {
			return $available_emails;
		}

		foreach ( MWC_Gift_Certificates_Order::get_vouchers( $order ) as $voucher ) {

			if ( $voucher->get_recipient_email() ) {
				$voucher_recipient = true;
				break;
			}
		}

		// add the action if there was a voucher recipient for the order
		if ( $voucher_recipient ) {
			$available_emails[] = 'wc_pdf_product_vouchers_voucher_recipient';
		}

		return $available_emails;
	}


	/**
	 * Marks the entire order as being redeemed if it contains all redeemed vouchers.
	 *
	 * @since 1.2.0
	 * @param int $post_id the order id
	 */
	public function maybe_complete_order( $post_id ) {

		$order = wc_get_order( $post_id );
		$voucher_count = 0;

		// if the order status is not completed, and the entire order has not already been marked as 'voucher redeemed'
		if ( ! MWC_Gift_Certificates_Order::vouchers_redeemed( $order ) ) {

			foreach ( MWC_Gift_Certificates_Order::get_vouchers( $order ) as $voucher ) {

				$voucher_count++;

				// an unredeemed voucher, bail
				if ( ! $voucher->has_status( 'redeemed' ) ) {
					return;
				}
			}

			if ( $voucher_count ) {
				// if we made it here, it means this order contains only voucher items, and they are all redeemed
				MWC_Gift_Certificates_Order::mark_vouchers_redeemed( $order, $voucher_count );
			}
		}
	}


	/**
	 * Hides voucher core meta data fields from the order admin
	 *
	 * @since 1.2.0
	 * @param array $hidden_fields array of item meta data field names to hide from the order admin
	 * @return array of item meta data field names to hide from the order admin
	 */
	public function hidden_order_itemmeta( $hidden_fields ) {
		return array_merge( $hidden_fields, array( '_voucher_id' ) );
	}


	/**
	 * Displays a link to the generated voucher for order items that have a voucher
	 *
	 * @since 3.0.0
	 * @param int $item_id order item identifier
	 * @param array $item array of order item data
	 */
	public function before_order_itemmmeta( $item_id, $item ) {

		$vouchers = MWC_Gift_Certificates_Order::get_order_item_vouchers( $item );

		if ( empty( $vouchers ) ) {
			return;
		}

		$links = array();

		foreach ( $vouchers as $voucher ) {
			$links[] = '<a href="' . esc_url( get_edit_post_link( $voucher->get_id() ) ) . '">' . esc_html( $voucher->get_voucher_number() ) . '</a>';
		}

		echo '<div class="wc-order-item-voucher"><strong>' . esc_html( _n( 'Gift Certificate:', 'Gift Certificates:', count( $links ), 'woocommerce-pdf-product-vouchers' ) ) . '</strong> ' . implode( ', ', $links ) . '</div>';
	}


}
