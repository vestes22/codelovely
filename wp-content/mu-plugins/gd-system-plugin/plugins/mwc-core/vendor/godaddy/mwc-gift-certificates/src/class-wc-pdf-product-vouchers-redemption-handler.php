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

use WC_Order_Refund;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * The PDF Product Vouchers online redemptions handler
 *
 * @since 3.4.0
 */
class MWC_Gift_Certificates_Redemption_Handler {


	/** @var array associative array of order ids and their WC_Discount instances, used for memoization */
	private $order_discounts = array();


	/**
	 * Sets up the online redemptions handler.
	 *
	 * @since 3.4.0
	 */
	public function __construct() {

		// TODO: consider adding support for subscriptions {IT 2017-03-29}

		// voucher coupon data handling
		add_filter( 'woocommerce_coupon_discount_types',                   [ $this, 'add_voucher_coupon_types' ] );
		add_filter( 'woocommerce_get_shop_coupon_data',                    [ $this, 'load_voucher_coupon_data' ], 10, 2 );
		add_filter( 'woocommerce_order_recalculate_coupons_coupon_object', [ $this, 'load_voucher_coupon_data_on_recalculation' ], 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid',                         [ $this, 'is_voucher_coupon_valid' ], 10, 3 );
		add_filter( 'woocommerce_apply_with_individual_use_coupon',        [ $this, 'apply_voucher_on_top_of_individual_use_coupon' ], 10, 2 );
		add_filter( 'woocommerce_apply_individual_use_coupon',             [ $this, 'apply_individual_use_coupon_on_top_of_vouchers' ], 10, 3 );

		// voucher coupon display/messaging
		add_filter( 'woocommerce_coupon_message', array( $this, 'voucher_coupon_message' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'voucher_coupon_label' ), 10, 2 );

		// order voucher coupon / redemption  handling
		// TODO: should we handle situations where the coupon item discount is changed on an order? {IT 2018-04-12}
		add_action( 'woocommerce_new_order_item', array( $this, 'redeem_order_item_coupon_voucher' ), 10, 2 );
		add_action( 'woocommerce_before_delete_order_item', array( $this, 'remove_order_item_coupon_voucher' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_voucher_redemptions' ), 10, 3 );

		// MPV "discount" handling (acts like store credit)
		add_filter( 'woocommerce_calculated_total', array( $this, 'apply_multi_purpose_vouchers_to_cart' ), 1100, 2 );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'apply_multi_purpose_vouchers_to_order_total' ), 10, 2 );

		// MPV "discount" display handling
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_order_totals_mpv_details_row' ), 10, 2 );
		add_action( 'woocommerce_admin_order_totals_after_tax', array( $this, 'output_admin_order_totals_mpv_details' ) );

		// may hide the tax label on the cart totals
		add_filter( 'woocommerce_cart_totals_order_total_html', [ $this, 'maybe_remove_tax_label' ] );
	}


	/** Voucher coupon data handling ******************************************************/


	/**
	 * Adds multi-purpose-voucher as a valid coupon type to WC.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param array $coupon_types an array of coupon types
	 * @return array
	 */
	public function add_voucher_coupon_types( $coupon_types ) {

		$coupon_types['multi_purpose_voucher'] = __( 'Multi-purpose gift certificate', 'woocommerce-pdf-product-vouchers' );

		return $coupon_types;
	}


	/**
	 * Loads coupon data from a voucher when redeeming online.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param false|array $coupon_data an array of coupon data to load
	 * @param string|int $coupon_code the coupon code
	 * @return array an array of coupon data to load
	 */
	public function load_voucher_coupon_data( $coupon_data, $coupon_code ) {

		if ( empty( $coupon_data ) && $voucher = wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon_code ) ) {

			// prevent online redemption of a single purpose voucher with no redeemable products. WooCommerce would otherwise allow this to be redeemed for any product
			if ( 'single' === $voucher->get_voucher_type() && empty( $voucher->get_redeemable_products() )  ) {
				return $coupon_data;
			}

			$coupon_data = array(
				'discount_type'          => 'single' === $voucher->get_voucher_type() ? 'percent' : 'multi_purpose_voucher',
				'amount'                 => 'single' === $voucher->get_voucher_type() ? 100 : $voucher->get_remaining_value(),
				'individual_use'         => false,
				'product_ids'            => 'single' === $voucher->get_voucher_type() ? $voucher->get_redeemable_products() : array(),
				'usage_limit'            => 'single' === $voucher->get_voucher_type() ? $voucher->get_product_quantity() : 0,
				// if this voucher has been partially redeemed, limit items to the number of remaining redemptions
				'limit_usage_to_x_items' => 'single' === $voucher->get_voucher_type() ? absint( $voucher->get_product_quantity() - $voucher->get_used_quantity() ) : 0,
				'usage_count'            => $voucher->get_used_quantity(),
				'date_expires'           => $voucher->get_expiration_date(),
				'free_shipping'          => false,
				'exclude_sale_items'     => false
			);
		}

		return $coupon_data;
	}


	/**
	 * Loads coupon data from a voucher when recalculating coupons for an order.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Coupon $coupon the coupon object
	 * @param string $coupon_code the coupon code
	 * @return \WC_Coupon
	 */
	public function load_voucher_coupon_data_on_recalculation( $coupon, $coupon_code ) {

		if ( wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon_code ) ) {

			$data = $this->load_voucher_coupon_data( array(), $coupon_code );

			$coupon->read_manual_coupon( $coupon_code, $data );
		}

		return $coupon;
	}


	/**
	 * Adjusts whether a voucher coupon is valid.
	 *
	 * Note that these checks are separate from voucher data loading so that the data can still be loaded in admin
	 * even after the voucher has been redeemed or has become invalid.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param bool $valid whether the coupon is valid for use or not
	 * @param \WC_Coupon $coupon the coupon instance
	 * @param \WC_Discounts $discounts the discounts class instance
	 * @throws Framework\SV_WC_Plugin_Exception
	 * @return bool
	 */
	public function is_voucher_coupon_valid( $valid, $coupon, $discounts ) {

		if ( $valid && $this->is_voucher_coupon( $coupon ) && $voucher = wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon->get_code() ) ) {

			// only proceed for vouchers that are active *and* redeemable
			if ( ! $voucher->has_status( 'active' ) || ! $voucher->is_redeemable_online() ) {
				return false;
			}

			// currency only affects MPVs, as SPVs will redeem a single product's full amount
			if ( 'multi' === $voucher->get_voucher_type() ) {

				// get the object from the items
				$items = $discounts->get_items();
				$item  = reset( $items )->object;

				// ensure voucher currency matches
				if ( $item instanceof \WC_Order_item && ( $order = $item->get_order() ) ) {
					$currency = $order->get_currency();
				} else {
					$currency = get_woocommerce_currency();
				}

				if ( $voucher->get_voucher_currency() !== $currency ) {
					throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Sorry, this gift certificate is only valid for the %s currency.', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_currency() ), 100 );
				}
			}
		}

		return $valid;
	}


	/** Voucher coupon display/messaging ******************************************************/


	/**
	 * Overrides coupon added/removed messages for vouchers.
	 *
	 * TODO: this does not currently handle the "Coupon has been removed" message from WC_AJAX {IT 2018-03-29}
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param string $msg the original message
	 * @param int $msg_code the message code
	 * @param \WC_Coupon $coupon the coupon instance
	 * @return string
	 */
	public function voucher_coupon_message( $msg, $msg_code, $coupon ) {

		if ( $this->is_voucher_coupon( $coupon ) ) {
			switch ( $msg_code ) {
				case \WC_Coupon::WC_COUPON_SUCCESS :
					$msg = __( 'Gift certificate applied successfully.', 'woocommerce-pdf-product-vouchers' );
				break;

				case \WC_Coupon::WC_COUPON_REMOVED :
					$msg = __( 'Gift certificate removed successfully.', 'woocommerce-pdf-product-vouchers' );
				break;
			}
		}

		return $msg;
	}


	/**
	 * Overrides the voucher coupon label in cart and order review.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param string $label the label
	 * @param \WC_Coupon $coupon the coupon instance
	 * @return string
	 */
	public function voucher_coupon_label( $label, $coupon ) {

		if ( $this->is_voucher_coupon( $coupon ) ) {
			$label = sprintf( __( 'Gift Certificate: %s', 'woocommerce-pdf-product-vouchers' ), strtoupper( $coupon->get_code() ) );
		}

		return $label;
	}


	/** Voucher coupon redemption handling ******************************************************/


	/**
	 * Redeems a voucher when it's added to an order as a coupon.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param int $item_id the order item id
	 * @param \WC_Order_Item $item the order item
	 */
	public function redeem_order_item_coupon_voucher( $item_id, $item ) {

		if ( ! $item->is_type( 'coupon' ) ) {
			return;
		}

		if ( $order = wc_get_order( $item->get_order_id() ) ) {
			$this->redeem_order_voucher( $order, $item );
		}
	}


	/**
	 * Removes a voucher redemption when it's coupon is removed from an order.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param int $item_id the order item id
	 * @param \WC_Order_Item $item the order item
	 */
	public function remove_order_item_coupon_voucher( $item_id ) {

		$item = \WC_Order_Factory::get_order_item( $item_id );

		if ( ! $item || ! $item->is_type( 'coupon' ) ) {
			return;
		}

		if ( $order = wc_get_order( $item->get_order_id() ) ) {
			$this->remove_order_voucher_redemption( $item, $order );
		}
	}


	/**
	 * Updates voucher redemptions when an order status changes.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param int $order_id the order id
	 * @param string $from_status previous status
	 * @param string $to_status new status
	 */
	public function update_voucher_redemptions( $order_id, $from_status, $to_status ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( in_array( $to_status, array( 'failed', 'cancelled', 'refunded' ), true ) ) {
			// remove voucher redemptions when an order fails, is cancelled or refunded, so that the voucher can be used again
			$this->remove_order_voucher_redemptions( $order );
		} else {
			// In all other cases, mark vouchers redeemed - note that we don't check if the order is paid for or not, as
			// we want to avoid a situation where someone can use the same voucher code to create multiple on-hold orders.
			$this->redeem_order_vouchers( $order );
		}
	}


	/**
	 * Redeems vouchers used on an order.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order object
	 */
	private function redeem_order_vouchers( $order ) {

		$coupon_items = $order->get_items( 'coupon' );

		if ( ! empty( $coupon_items ) ) {
			foreach( $coupon_items as $coupon_item ) {
				$this->redeem_order_voucher( $order, $coupon_item );
			}
		}
	}


	/**
	 * Redeems a single voucher used as a coupon on an order.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order instance
	 * @param \WC_Order_Item_Coupon $coupon_item the coupon item instance
	 */
	private function redeem_order_voucher( $order, $coupon_item ) {

		$coupon = $this->get_coupon_from_item( $coupon_item, $order );

		if ( ! $coupon instanceof \WC_Coupon || ! $this->is_voucher_coupon( $coupon ) ) {
			return;
		}

		// check if this voucher coupon has already been redeemed
		if ( $coupon_item->get_meta( '_wc_pdf_product_vouchers_is_redeemed' ) ) {
			return;
		}

		// get coupon discount amount (voucher redemption amount
		$amount = $coupon_item->get_discount();

		// if not set, use whatever the coupon value (amount) is and store it on teh coupon item as well
		if ( ! $amount ) {
			$amount = $coupon->get_amount();
			$coupon_item->set_discount( $amount );
		}

		$voucher = wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon->get_code() );

		// sanity check - does the voucher exist?
		if ( ! $voucher instanceof WC_Voucher ) {
			return;
		}

		$args = array(
			'amount'   => $amount,
			'order_id' => $order->get_id(),
			'user_id'  => $order->get_user_id(),
		);

		// SPV - do our best to determine which products the coupon applies to
		// here we need to check how the coupons were used - ie quantity, etc.
		if ( $coupon->is_type( 'percent' ) ) {

			$discounts    = $this->get_coupon_discounts_by_item( $order, $coupon->get_code() );
			$redeemed_qty = 0;
			$amount_left  = $amount;

			if ( ! empty( $discounts ) ) {
				foreach ( $discounts as $order_item_id => $discount ) {
					$item = $order->get_item( $order_item_id );

					$item_price    = $item->get_subtotal() / $item->get_quantity();
					$redeemed_qty += round( $discount / $item_price );
					$amount_left  -= $discount;

					if ( ! $amount_left ) {
						break;
					}
				}
			}

			// if we cannot determine the qty from items, assume qty of 1
			if ( ! $redeemed_qty ) {
				$redeemed_qty = 1;
			}

			$args['amount']   = $voucher->get_product_price() * $redeemed_qty;
			$args['quantity'] = $redeemed_qty;
		}

		try {

			$voucher->redeem( $args );

			$coupon_item->update_meta_data( '_wc_pdf_product_vouchers_is_redeemed', true );
			$coupon_item->save();

			/**
			 * Fires upon redeeming a voucher.
			 *
			 * @since 3.5.0
			 *
			 * @param WC_Voucher $voucher the redeemed voucher
			 */
			do_action( 'wc_pdf_product_vouchers_voucher_redeemed', $voucher );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			/* translators: %1$s - voucher number, %2$s - error message */
			$order->add_order_note( sprintf( __( 'Could not redeem gift certificate %1$s: %2$s', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number(), $e->getMessage() ) );
		}
	}


	/**
	 * Removes the redemptions for the vouchers used on the order.
	 *
	 * Undoes redemptions when an order is failed, cancelled or refunded.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order object
	 */
	private function remove_order_voucher_redemptions( $order ) {

		$coupon_items = $order->get_items( 'coupon' );

		if ( ! empty( $coupon_items ) ) {
			foreach ( $coupon_items as $coupon_item ) {
				$this->remove_order_voucher_redemption( $coupon_item, $order );
			}
		}
	}


	/**
	 * Removes the redemption for a voucher used on an order.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order instance
	 * @param \WC_Order_Item_Coupon $coupon_item the coupon item instance
	 */
	private function remove_order_voucher_redemption( $coupon_item, $order ) {

		$coupon = $this->get_coupon_from_item( $coupon_item, $order );

		if ( ! $coupon instanceof \WC_Coupon || ! $this->is_voucher_coupon( $coupon ) ) {
			return;
		}

		// check if this voucher coupon has already been redeemed
		if ( ! $coupon_item->get_meta( '_wc_pdf_product_vouchers_is_redeemed' ) ) {
			return;
		}

		$voucher = wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon->get_code() );

		// sanity check - does the voucher exist?
		if ( ! $voucher instanceof WC_Voucher ) {
			return;
		}

		// sanity check - maybe the redemptions have been already manually removed?
		if ( ! $voucher->has_redemptions() ) {
			return;
		}

		$redemptions = $voucher->get_redemptions();
		$changed     = false;

		foreach ( $redemptions as $i => $redemption ) {
			if ( (int) $redemption['order_id'] === (int) $order->get_id() ) {
				unset( $redemptions[ $i ] );
				$changed = true;
			}
		}

		$coupon_item->delete_meta_data( '_wc_pdf_product_vouchers_is_redeemed' );
		$coupon_item->save();

		if ( $changed ) {
			try {
				$voucher->set_redemptions( $redemptions );
			} catch ( Framework\SV_WC_Plugin_Exception $e ) {
				/* translators: %1$s - voucher number, %2$s - error message */
				$order->add_order_note( sprintf( __( 'Could not delete redemptions for gift certificate %1$s: %2$s', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number(), $e->getMessage() ) );
			}
		}
	}


	/** MPV discount/credit handling ******************************************************/


	/**
	 * Applies multi-purpose voucher coupon discounts (credit) to cart total.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param float|int $total
	 * @param \WC_Cart $cart cart instance
	 * @return float $total
	 */
	public function apply_multi_purpose_vouchers_to_cart( $total = 0, $cart = null ) {

		if ( ! $cart instanceof \WC_Cart || empty( $total ) ) {
			return $total;
		}

		$applied_coupons = $cart->get_applied_coupons();

		if ( ! empty( $applied_coupons ) ) {

			foreach ( $applied_coupons as $code ) {

				$coupon = new \WC_Coupon( $code );

				if ( ! $coupon instanceof \WC_Coupon || ! $coupon->is_type( 'multi_purpose_voucher' ) || ! $coupon->is_valid() ) {
					continue;
				}

				// the discount from total is the coupon amount
				$discount = $coupon->get_amount();

				if ( $total < $discount ) {
					$discount = $total;
					$total = 0;
				} else {
					$total -= $discount;
				}

				$this->add_mpv_discount_to_cart_coupon_discount_totals( $code, $discount );
			}
		}

		return $total;
	}


	/**
	 * Adds multi-purpose voucher coupon discount (credit) to cart discount totals.
	 *
	 * @since 3.4.0
	 *
	 * @param string $coupon_code the coupon code
	 * @param int|float $discount the discount amount
	 */
	private function add_mpv_discount_to_cart_coupon_discount_totals( $coupon_code, $discount = 0 ) {

		$coupon_discount_totals = WC()->cart->get_coupon_discount_totals();

		if ( empty( $coupon_discount_totals ) || ! is_array( $coupon_discount_totals ) ) {
			$coupon_discount_totals = array();
		}

		if ( empty( $coupon_discount_totals[ $coupon_code ] ) ) {
			$coupon_discount_totals[ $coupon_code ] = $discount;
		} else {
			$coupon_discount_totals[ $coupon_code ] += $discount;
		}

		WC()->cart->set_coupon_discount_totals( $coupon_discount_totals );
	}


	/**
	 * Applies multi-purpose voucher coupon discounts (credit) to order total.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param bool $and_taxes unused
	 * @param \WC_Order $order the order instance
	 */
	public function apply_multi_purpose_vouchers_to_order_total( $and_taxes, $order ) {

		$coupon_items = $order->get_items( 'coupon' );
		$total        = $order->get_total();
		$save_order   = false;

		if ( ! empty( $coupon_items ) ) {
			foreach ( $coupon_items as $item_id => $coupon_item ) {

				$coupon = $this->get_coupon_from_item( $coupon_item, $order );

				if ( ! $coupon instanceof \WC_Coupon ) {
					continue;
				}

				$discount = $coupon_item->get_discount();

				if ( ! $coupon->is_type( 'multi_purpose_voucher' ) ) {
					continue;
				}

				if ( $total < $discount ) {
					$total = 0;
				} else {
					$total -= $discount;
				}

				$order->set_total( $total );

				$save_order = true;
			}

			// save once
			if ( $save_order ) {
				$order->save();
			}
		}
	}


	/** MPV discount/credit display handling ******************************************************/


	/**
	 * Adds used multi-purpose voucher credit to order total rows in frontend.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param array $total_rows order total rows
	 * @param \WC_Order $order the order instance
	 * @return array
	 */
	public function add_order_totals_mpv_details_row( $total_rows, $order = null ) {

		$total_credit_used = $this->get_order_total_mpv_credit_used( $order );

		if ( $total_credit_used > 0 && ( $order instanceof \WC_Order || $order instanceof WC_Order_Refund ) ) {

			$mpv_details = array(
				'mpv_details' => array(
					'label' => __( 'Gift certificate credit used:', 'woocommerce-pdf-product-vouchers' ),
					'value' => wc_price( '-' . $total_credit_used, array( 'currency' => $order->get_currency() ) ),
				)
			);

			// insert after tax row if possible, otherwise before payment method or at least before order total
			if ( array_key_exists( 'tax', $total_rows ) ) {
				$total_rows = Framework\SV_WC_Helper::array_insert_after( $total_rows, 'tax', $mpv_details );
			} elseif ( array_key_exists( 'payment_method', $total_rows ) ) {
				$total_rows = $this->array_insert_before( $total_rows, 'payment_method', $mpv_details );
			} else {
				$total_rows = $this->array_insert_before( $total_rows, 'order_total', $mpv_details );
			}
		}

		return $total_rows;
	}


	/**
	 * Displays used multi-purpose voucher credit in admin order edit screen.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param int $order_id the order id
	 */
	public function output_admin_order_totals_mpv_details( $order_id = 0 ) {

		$order             = ! empty( $order_id ) ? wc_get_order( $order_id ) : null;
		$total_credit_used = $order ? $this->get_order_total_mpv_credit_used( $order ) : null;

		if ( ! empty( $total_credit_used ) ) :

			?>
			<tr>
				<td class="label"><?php esc_html_e( 'Gift certificate credit used:', 'woocommerce-pdf-product-vouchers' ); ?> <span class="tips" data-tip="<?php esc_attr_e( 'This is the total credit used from multi-purpose-gift certificates.', 'woocommerce-pdf-product-vouchers' ); ?>">[?]</span>:</td>
				<td width="1%"></td>
				<td class="total"><?php echo wc_price( '-' . $total_credit_used, array( 'currency' => $order->get_currency() ) ); ?></td>
			</tr>
			<?php

		endif;
	}


	/** Total display handling ******************************************************/


	/**
	 * Maybe removes the tax label from cart total.
	 *
	 * @internal
	 *
	 * @since 3.8.2
	 *
	 * @param string $order_total_html WC order total HTML
	 * @return string filtered order total HTML that may have a tax label hidden
	 */
	public function maybe_remove_tax_label( $order_total_html ) {

		if ( $this->should_hide_tax_label() ) {

			// prevents maybe_remove_tax_label to be called again inside wc_cart_totals_order_total_html()
			remove_filter( 'woocommerce_cart_totals_order_total_html', [ $this, 'maybe_remove_tax_label' ], 10, 1 );

			// will remove the tax label on the next wc_cart_totals_order_total_html() call
			add_filter( 'woocommerce_cart_display_prices_including_tax', [ $this, 'disable_cart_display_prices_including_tax' ], 999 );

			// rebuilds the order total html with display_prices_including_tax set to false
			$order_total_html = wc_cart_totals_order_total_html();

			// restore display_prices_including_tax setting so it won't affect the rest of the cart section
			remove_filter( 'woocommerce_cart_display_prices_including_tax', [ $this, 'disable_cart_display_prices_including_tax' ], 999 );
		}

		return $order_total_html;
	}


	/**
	 * Determines whether the tax label must be hidden or not from cart total.
	 *
	 * @since 3.8.2
	 *
	 * @return bool true if the tax label must be hidden from cart total
	 */
	private function should_hide_tax_label() {

		$cart_handler = wc_pdf_product_vouchers()->get_cart_instance();
		$cart         = WC()->cart;

		$should_hide_tax_label = 0.0 === floatval( $cart->get_cart_contents_total() ) && $cart_handler->cart_has_pdf_voucher();

		/**
		 * Filters the flag that may hide the tax label from total regardless of WooCommerce display tax setting.
		 *
		 * @since 3.8.2
		 *
		 * @param bool $should_hide_tax_label determines whether the tax label must be hidden or not
		 * @param MWC_Gift_Certificates_Redemption_Handler $handler the PDF Product Vouchers redemption handler instance
		 */
		return (bool) apply_filters( 'wc_pdf_product_vouchers_should_hide_tax_label_from_cart_total', $should_hide_tax_label, $this );
	}


	/**
	 * Callback to disable WooCommerce displaying prices including tax setting on cart temporarily.
	 *
	 * A single method to return false is created to allow this callback to be removed.
	 *
	 * @internal
	 *
	 * @since 3.8.2
	 *
	 * @return false
	 */
	public function disable_cart_display_prices_including_tax() {

		return false;
	}


	/** Helper methods ******************************************************/


	/**
	 * Gets the total multi-purpose voucher credit used on the order.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order
	 * @return float $total_credit_used
	 */
	public function get_order_total_mpv_credit_used( $order ) {

		if ( ! $order instanceof \WC_Order ) {
			return 0;
		}

		$total_credit_used = 0;
		$coupon_items      = $order->get_items( 'coupon' );

		if ( ! empty( $coupon_items ) ) {
			foreach ( $coupon_items as $coupon_item ) {

				$coupon = $this->get_coupon_from_item( $coupon_item, $order );

				if ( $coupon instanceof \WC_Coupon && $coupon->is_type( 'multi_purpose_voucher' ) ) {
					$total_credit_used += $coupon_item->get_discount();
				}
			}
		}

		return $total_credit_used;
	}


	/**
	 * Gets the coupon instance for an order coupon item.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order_Item_Coupon coupon order item
	 * @param \WC_Order the order instance
	 * @return \WC_Coupon
	 */
	private function get_coupon_from_item( $coupon_item, $order ) {

		// construct coupon from code
		$coupon_code   = $coupon_item->get_code();
		$coupon_object = new \WC_Coupon( $coupon_code );

		// note: the following code is almost a 100% duplicate from \WC_Order::recalculate_coupons()

		// if we do not have a coupon discount type (was it virtual? has it been deleted?) we must create a temporary coupon using what data WC has stored during checkout
		if ( ! $coupon_object->get_discount_type() ) {

			$coupon_object = new \WC_Coupon();
			$coupon_object->set_props( (array) $coupon_item->get_meta( 'coupon_data', true ) );
			$coupon_object->set_code( $coupon_code );
			$coupon_object->set_virtual( true );

			// if there is no coupon amount (maybe dynamic?), set it to the given **discount** amount so the coupon's same value is applied.
			if ( ! $coupon_object->get_amount() ) {

				// if the order originally had prices including tax, remove the discount + discount tax.
				if ( $order->get_prices_include_tax() ) {
					$coupon_object->set_amount( $coupon_item->get_discount() + $coupon_item->get_discount_tax() );
				} else {
					$coupon_object->set_amount( $coupon_item->get_discount() );
				}

				$coupon_object->set_discount_type( 'fixed_cart' );
			}
		}

		/** this filter is documented in \WC_Order::recalculate_coupons() **/
		return apply_filters( 'woocommerce_order_recalculate_coupons_coupon_object', $coupon_object, $coupon_code, $coupon_item, $order );
	}


	/**
	 * Gets the coupons instances for an order.
	 *
	 * In 3.7.1 added optional $type parameter.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order object
	 * @param string|null $type optional coupon type to filter results by
	 * @return \WC_Coupon[]
	 */
	public function get_order_coupons( $order, $type = null ) {

		$coupons = array();

		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {

			$coupon = $this->get_coupon_from_item( $coupon_item, $order );

			if ( ! $coupon instanceof \WC_Coupon ) {
				continue;
			}

			if ( null === $type || $coupon->is_type( $type ) ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}


	/**
	 * Gets the WC_Discounts instance for a given order.
	 *
	 * Unfortunately, WC itself does not have a way to get the discounts class instance
	 * for an order, so it needs to be recreated and recalculated every time it's used. This method
	 * memoizes the instance, so that if used within a loop, the discounts are not recalculated every time.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Order $order the order object
	 * @return \WC_Discounts instance
	 */
	private function get_order_discounts( $order ) {

		if ( ! isset( $this->order_discounts[ $order->get_id() ] ) ) {

			$discounts = new \WC_Discounts( $order );

			foreach ( $this->get_order_coupons( $order ) as $coupon ) {
				$discounts->apply_coupon( $coupon, false );
			}

			$this->order_discounts[ $order->get_id() ] = $discounts;
		}

		return $this->order_discounts[ $order->get_id() ];
	}


	/**
	 * Gets coupon discount amounts by order item for a given order.
	 *
	 * Used for checking which products a SPV coupon discounted.
	 *
	 * @param \WC_order $order the order object
	 * @param string $coupon_code the coupon code
	 * @return array
	 */
	private function get_coupon_discounts_by_item( $order, $coupon_code ) {

		$discounts = array();

		if ( $order_discounts = $this->get_order_discounts( $order ) ) {
			$all_discounts = $order_discounts->get_discounts();

			if ( isset( $all_discounts[ $coupon_code ] ) ) {
				$discounts = array_filter( $all_discounts[ $coupon_code ] );
			}
		}

		return $discounts;
	}


	/**
	 * Checks whether the given coupon is a PDF Vouchers coupon or not.
	 *
	 * @since 3.4.0
	 *
	 * @param \WC_Coupon $coupon the coupon instance
	 * @return bool
	 */
	private function is_voucher_coupon( $coupon ) {
		return $coupon->is_type( 'multi_purpose_voucher' ) || wc_pdf_product_vouchers_get_voucher_by_voucher_number( $coupon->get_code() );
	}


	/**
	 * Inserts the given element _before_ the given key in the array.
	 *
	 * Sample usage:
	 *
	 * given
	 *
	 * array( 'item_1' => 'foo', 'item_2' => 'bar' )
	 *
	 * array_insert_before( $array, 'item_2', array( 'item_1.5' => 'w00t' ) )
	 *
	 * becomes
	 *
	 * array( 'item_1' => 'foo', 'item_1.5' => 'w00t', 'item_2' => 'bar' )
	 *
	 * @since 3.4.0
	 *
	 * @param array $array array to insert the given element into
	 * @param string $insert_key key to insert given element after
	 * @param array $element element to insert into array
	 * @return array
	 */
	private function array_insert_before( $array, $insert_key, $element ) {

		$new_array = array();

		foreach ( $array as $key => $value ) {

			if ( $insert_key === $key ) {

				foreach ( $element as $k => $v ) {
					$new_array[ $k ] = $v;
				}
			}

			$new_array[ $key ] = $value;
		}

		return $new_array;
	}


	/**
	 * Applies vouchers on top of individual-use coupons.
	 *
	 * @internal
	 *
	 * @since 3.5.7
	 *
	 * @param bool $apply_coupon whether coupon should be applied
	 * @param \WC_Coupon $coupon the coupon object
	 * @return bool
	 */
	public function apply_voucher_on_top_of_individual_use_coupon( $apply_coupon, $coupon ) {

		return $this->is_voucher_coupon( $coupon ) ? true : $apply_coupon;
	}


	/**
	 * Retains applied vouchers when a single-use coupon is applied on top of them to the cart.
	 *
	 * @internal
	 *
	 * @since 3.5.7
	 *
	 * @param string[] $keep_vouchers array of vouchers to keep (from being removed)
	 * @param \WC_Coupon $single_use_coupon the single-use coupon being applied that would remove other coupons and vouchers
	 * @param string[] $applied_coupons codes of coupons already applied (may include vouchers)
	 * @return string[]
	 */
	public function apply_individual_use_coupon_on_top_of_vouchers( $keep_vouchers, $single_use_coupon, $applied_coupons ) {

		if ( is_array( $keep_vouchers ) ) {

			foreach ( $applied_coupons as $coupon_code ) {

				$applied_coupon = new \WC_Coupon( $coupon_code );

				if ( ! in_array( $coupon_code, $keep_vouchers, false ) && $this->is_voucher_coupon( $applied_coupon ) ) {
					$keep_vouchers[] = $coupon_code;
				}
			}
		}

		return $keep_vouchers;
	}


}
