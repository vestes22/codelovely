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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin\MetaBoxes;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;
use WP_Post;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_adjust_date_by_timezone;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_validate_date;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Voucher Data Meta Box
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Meta_Box_Voucher_Data {


	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30 );
		add_action( 'wc_pdf_product_vouchers_process_voucher_meta', array( $this, 'save' ), 10, 2 );
	}


	/**
	 * Adds the meta box
	 *
	 * @since 3.0.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-pdf-product-vouchers-voucher-data', __( 'Gift Certificate Data', 'woocommerce-pdf-product-vouchers' ), array( $this, 'output' ), 'wc_voucher', 'normal', 'high' );
	}


	/**
	 * Outputs meta box contents
	 *
	 * @since 3.0.0
	 */
	public function output() {
		global $post, $voucher;

		include( wc_pdf_product_vouchers()->get_plugin_path() . '/src/admin/meta-boxes/views/html-voucher-data.php' );
	}


	/**
	 * Processes and saves meta box data.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id post identifier
	 * @param WP_Post $post the post object
	 */
	public function save( $post_id, WP_Post $post ) {
		global $wpdb;

		// update user input fields
		$user_input_fields = WC_Voucher_Template::get_voucher_user_input_fields();

		foreach ( $user_input_fields as $field => $data ) {

			$key = '_' . $field;

			if ( isset( $_POST[ $key ] ) ) {

				update_post_meta(
					$post->ID,
					$key,
					isset( $data['type'] ) && $data['type'] === 'textarea' ? sanitize_textarea_field( $_POST[ $key ] ) : wc_clean( $_POST[ $key ] )
				);
			}
		}

		// Update date
		if ( empty( $_POST['voucher_date'] ) ) {
			$date = current_time( 'timestamp' );
		} else {
			$date = strtotime( $_POST['voucher_date'] . ' ' . (int) $_POST['voucher_date_hour'] . ':' . (int) $_POST['voucher_date_minute'] . ':00' );
		}

		$date = date_i18n( 'Y-m-d H:i:s', $date );

		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->posts
			SET post_date = %s, post_date_gmt = %s
			WHERE ID = %s
		", $date, get_gmt_from_date( $date ), $post_id ) );

		$customer_changed = false;

		// update customer
		if ( isset( $_POST['customer_id'] ) && (int) $_POST['customer_id'] !== (int) get_post_meta( $post_id, '_customer_user', true ) ) {

			$customer_changed = true;

			wp_update_post( array( 'ID' => $post_id, 'post_author' => 1 ) );
			update_post_meta( $post_id, '_customer_user', $_POST['customer_id'] );
		}

		// update purchaser details
		if ( isset( $_POST['_purchaser_name'] ) ) {
			update_post_meta( $post->ID, '_purchaser_name', wc_clean( $_POST[ '_purchaser_name' ] ) );
		}

		if ( isset( $_POST['_purchaser_email'] ) ) {
			update_post_meta( $post->ID, '_purchaser_email', wc_clean( $_POST[ '_purchaser_email' ] ) );
		}

		// get a fresh copy of the voucher
		$voucher = wc_pdf_product_vouchers_get_voucher( $post->ID );

		// ensure voucher type is set
		if ( ! get_post_meta( $post->ID, '_voucher_type', true ) ) {

			$template = $voucher->get_template();
			$type     = $template ? $template->get_voucher_type() : 'multi';

			update_post_meta( $post->ID, '_voucher_type', $type );
		}

		// parse & update expiration date
		$timezone    = wc_timezone_string();
		$date_format = 'Y-m-d H:i:s';

		if ( ! empty( $_POST['expiration_date'] ) && ( $expiration_date_mysql = wc_pdf_product_vouchers_validate_date( $_POST['expiration_date'] . ' ' . (int) $_POST['expiration_date_hour'] . ':' . (int) $_POST['expiration_date_minute'] . ':00', 'mysql' ) ) ) {
			$expiration_date = date( $date_format, wc_pdf_product_vouchers_adjust_date_by_timezone( strtotime( $expiration_date_mysql ), 'timestamp', $timezone ) );
		} else {
			$expiration_date = '';
		}

		// get previous end date (UTC)
		$previous_expiration_date = $voucher->get_expiration_date( $date_format );

		if ( ! empty( $expiration_date ) && strtotime( $expiration_date ) <= current_time( 'timestamp', true ) ) {

			// loose check if new and old dates mismatch (expiration date has been updated)
			if ( $previous_expiration_date != $expiration_date ) {

				// if expiration date is now set to a past date,
				// automatically set status to expired
				$voucher->update_status( 'expired' );

			} elseif ( $voucher->has_status( array( 'active', 'redeemed' ) ) ) {

				// if the expiration date has not changed compared to previous,
				// but status has been changed to one of the active statuses,
				// remove the expiration date, so that it does not conflict with the status
				$expiration_date = '';
			}

		} elseif ( $voucher->has_status( 'expired' ) && ( '' === $expiration_date || strtotime( $expiration_date ) > current_time( 'timestamp' ) ) ) {

			// if the status was set to expired, but the date is off, set it
			$expiration_date = current_time( 'mysql', true );
		}

		// set the end date (UTC)
		$voucher->set_expiration_date( $expiration_date );

		// ensure that voucher key exists
		if ( ! $voucher->get_voucher_key() ) {
			$voucher->generate_key();
		}

		// recalculate taxes if needed
		if ( $customer_changed && $voucher->is_editable() && ! $voucher->has_redemptions() && ! $voucher->get_order_id() ) {

			$new_tax = $voucher->calculate_product_tax();
			$action  = isset( $_POST['wc_voucher_action'] ) ? $_POST['wc_voucher_action'] : null;

			if ( $new_tax != $voucher->get_product_tax() && 'calculate_product_tax' != $action ) {
				wc_pdf_product_vouchers()->get_message_handler()->add_error( __( 'Gift certificate customer has changed and is taxed differently from previous customer. You may need to recalculate gift certificate taxes.', 'woocommerce-pdf-product-vouchers' ) );
				// TODO: disabled automatic tax update for now, since I'm not convinced this isn't too obtrusive, we might reconsider this later {IT 2017-06-05}
				// update_post_meta( $post_id, '_product_tax', $new_tax );
			}
		}

		// update voucher image
		if ( isset( $_POST['_thumbnail_id'] ) ) {
			update_post_meta( $post->ID, '_thumbnail_id', (int) $_POST['_thumbnail_id'] );
		}
	}


}
