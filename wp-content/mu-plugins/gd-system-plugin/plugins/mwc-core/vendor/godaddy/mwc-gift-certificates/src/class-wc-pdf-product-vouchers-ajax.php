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

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * AJAX class
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_AJAX {


	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// voucher notes
		add_action( 'wp_ajax_wc_pdf_product_vouchers_add_voucher_note',              array( $this, 'add_voucher_note' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_delete_voucher_note',           array( $this, 'delete_voucher_note' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_redeem_voucher',                array( $this, 'redeem_voucher' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_void_voucher',                  array( $this, 'void_voucher' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_restore_voucher',               array( $this, 'restore_voucher' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_get_product_details',           array( $this, 'get_product_details' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_get_customer_details',          array( $this, 'get_customer_details' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_get_voucher_preview',           array( $this, 'get_voucher_preview' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_update_voucher_product',        array( $this, 'update_voucher_product' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_calculate_voucher_product_tax', array( $this, 'calculate_voucher_product_tax' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_load_voucher_redemptions',      array( $this, 'load_voucher_redemptions' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_save_voucher_redemptions',      array( $this, 'save_voucher_redemptions' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_delete_voucher_redemption',     array( $this, 'delete_voucher_redemption' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_barcode_redeem_voucher',        array( $this, 'barcode_redeem_voucher' ) );

		add_action( 'wp_ajax_wc_pdf_product_vouchers_list_redeem_voucher', array( $this, 'list_redeem_voucher' ) );
		add_action( 'wp_ajax_wc_pdf_product_vouchers_list_void_voucher',   array( $this, 'list_void_voucher' ) );

		// only return voucher products when appropriate
		add_filter( 'woocommerce_json_search_found_products', array( $this, 'filter_json_search_found_products' ) );

		add_action( 'wp_ajax_wc_pdf_product_vouchers_json_search_single_purpose_voucher_templates', array( $this, 'json_search_single_purpose_voucher_templates' ) );
	}


	/**
	 * Adds voucher note
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function add_voucher_note() {

		check_ajax_referer( 'add-voucher-note', 'security' );

		try {

			$voucher_id = isset( $_POST['voucher_id'] ) ? absint( $_POST['voucher_id'] ) : 0;
			$voucher    = $voucher_id > 0 ? wc_pdf_product_vouchers_get_voucher( $voucher_id ) : null;

			if ( ! $voucher ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Undefined gift certificate to add note to.', 'woocommerce-pdf-product-vouchers' ) );
			}

			$note_text = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );

			if ( '' === $note_text ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'A gift certificate note should not be empty.', 'woocommerce-pdf-product-vouchers' ) );
			}

			$comment_id = $voucher->add_note( $note_text );

			if ( ! $comment_id ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Please ensure the gift certificate has been created and saved before adding a note to it.', 'woocommerce-pdf-product-vouchers' ) );
			}

			// prepare variables to pass to templates
			$args = array(
				'voucher'    => $voucher,
				'comment_id' => $comment_id,
				'note'       => get_comment( $comment_id ),
			);

			/* This filter is documented in src/admin/meta-boxes/views/html-voucher-notes.php */
			$args['note_classes'] = apply_filters( 'wc_pdf_product_vouchers_voucher_note_class', array( 'note' ), $args['note'], $args['voucher'] );

			wp_send_json_success( array(
				'note_html' => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-note.php', $args ),
			) );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			wp_send_json_error( $e->getMessage() );
		}
	}


	/**
	 * Deletes voucher note
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function delete_voucher_note() {

		check_ajax_referer( 'delete-voucher-note', 'security' );

		$note_id = (int) $_POST['note_id'];

		if ( $note_id > 0 ) {
			wp_delete_comment( $note_id );
		}

		exit;
	}


	/**
	 * Sends product details
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function get_product_details() {

		check_ajax_referer( 'get-product-details', 'security' );

		$product_id = (int) $_GET['product_id'];

		if ( $product_id > 0 ) {

			$product = wc_get_product( $product_id );

			$data = array(
				'id'    => $product_id,
				'price' => wc_get_price_excluding_tax( $product ),
			);

			/**
			 * Filter the data found for a product with AJAX request
			 *
			 * @since 3.0.0
			 * @param array $data
			 * @param int $product_id
			 */
			$data = apply_filters( 'wc_pdf_product_vouchers_ajax_found_product_details', $data, $product_id );

			wp_send_json_success( $data );
		}

		die();
	}


	/**
	 * Sends customer details
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function get_customer_details() {

		check_ajax_referer( 'get-customer-details', 'security' );

		$user_id = (int) $_GET['user_id'];

		if ( $user_id > 0 ) {

			$data = array(
				'name'  => trim( sprintf( '%s %s', get_user_meta( $user_id, 'billing_first_name', true ), get_user_meta( $user_id, 'billing_last_name', true ) ) ),
				'email' => get_user_meta( $user_id, 'billing_email', true ),
			);

			/**
			 * Filter the data found for a customer with AJAX request
			 *
			 * @since 3.0.0
			 * @param array $data
			 * @param int $user_id
			 */
			$data = apply_filters( 'wc_pdf_product_vouchers_ajax_found_customer_details', $data, $user_id );

			wp_send_json_success( $data );

		}

		die();
	}


	/**
	 * Sends voucher preview
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function get_voucher_preview() {

		check_ajax_referer( 'get-voucher-preview', 'security' );

		$voucher_id = (int) $_GET['voucher_id'];
		$image_id   = (int) $_GET['image_id'];

		if ( $voucher_id > 0 && $image_id > 0 ) {

			$data = array(
				'preview_html' => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-preview.php', array(
					'voucher'         => wc_pdf_product_vouchers_get_voucher( $voucher_id ) ,
					'thumbnail_id'    => $image_id,
					'preview_image'   => wp_get_attachment_image( $image_id, 'medium_large' ),
					'unsaved_preview' => true,
				) ),
			);

			wp_send_json_success( $data );
		}

		die();
	}


	/**
	 * Updates voucher product
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function update_voucher_product() {

		check_ajax_referer( 'update-voucher-product', 'security' );

		$voucher_id    = (int) $_POST['voucher_id'];
		$product_id    = (int) $_POST['product_id'];
		$product_price = wc_format_decimal( $_POST['product_price'] );

		if ( $voucher_id > 0 && $product_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );;
			}

			if ( ! $voucher->is_editable() || $voucher->has_redemptions() ) {
				wp_send_json_error( array( 'message' => __( 'Could not change gift certificate product: gift certificate is not editable or has redemptions', 'woocommerce-pdf-product-vouchers' ) ) );;
			}

			$previous_product_id  = get_post_meta( $voucher_id, '_product_id', true );
			$previous_template_id = get_post_meta( $previous_product_id, '_voucher_template_id', true );
			$new_template_id      = get_post_meta( $product_id, '_voucher_template_id', true );
			$template_changed     = (int) $previous_template_id !== (int) $new_template_id;

			// update voucher product data
			update_post_meta( $voucher_id, '_product_id', $product_id );
			update_post_meta( $voucher_id, '_product_price', $product_price );

			// recalculate & update taxes
			$new_tax = $voucher->calculate_product_tax();

			if ( $new_tax != $voucher->get_product_tax() ) {
				update_post_meta( $voucher_id, '_product_tax', $new_tax );
			}

			$voucher->calculate_remaining_value();

			$data = array(
				'status'          => $voucher->get_status(),
				'remaining_value' => $voucher->get_remaining_value(),
				'balance_html'    => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-balance.php', array( 'voucher' => $voucher ) ),
			);

			if ( $template_changed ) {

				$voucher->set_template_id( $new_template_id );

				if ( $template = $voucher->get_template() ) {
					$voucher->set_image_id( $template->get_image_id() );
				}

				try {
					$voucher->generate_pdf();
				} catch( Framework\SV_WC_Plugin_Exception $e ) {
					// simply log exceptions here, as PDF regeneration is not crucial for this action
					/* translators: %s - error message */
					wc_pdf_product_vouchers()->log( sprintf( __( 'Could not generate gift certificate PDF: %s', 'woocommerce-pdf-product-vouchers' ), $e->getMessage() ) );
				}

				// re-render voucher preview if template has changed
				$data['preview_html'] = $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-preview.php', array(
					'voucher'         => $voucher,
					'thumbnail_id'    => $voucher->get_image_id(),
					'preview_image'   => $voucher->has_preview_image() ? $voucher->get_preview_image( 'medium_large' ) : $voucher->get_image( 'medium_large' ),
					'unsaved_preview' => false,
				) );
			}

			wp_send_json_success( $data );
		}

		exit;
	}


	/**
	 * Recalculates voucher product tax
	 *
	 * @internal
	 *
	 * @since 3.1.0
	 */
	public function calculate_voucher_product_tax() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			} elseif ( ! $voucher->is_editable() || $voucher->has_redemptions() ) {
				wp_send_json_error( array( 'message' => __( 'Could not recalculate gift certificate product tax: gift certificate is not editable or has redemptions', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			$tax = $voucher->calculate_product_tax();

			update_post_meta( $voucher_id, '_product_tax', $tax );

			$data = array(
				'status'          => $voucher->get_status(),
				'remaining_value' => $voucher->get_remaining_value(),
				'balance_html'    => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-balance.php', array( 'voucher' => $voucher ) ),
			);

			try {
				$voucher->generate_pdf();
			} catch( Framework\SV_WC_Plugin_Exception $e ) {
				// simply log exceptions here, as PDF regeneration is not crucial for this action
				/* translators: %s - error message */
				wc_pdf_product_vouchers()->log( sprintf( __( 'Could not generate gift certificate PDF: %s', 'woocommerce-pdf-product-vouchers' ), $e->getMessage() ) );
			}

			wp_send_json_success( $data );
		}

		exit;
	}


	/**
	 * Loads voucher redemptions
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function load_voucher_redemptions() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			// render voucher redemptions
			$args = array( 'voucher' => wc_pdf_product_vouchers_get_voucher( $voucher_id ) );

			wp_send_json_success( array(
				'redemptions_html' => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-redemptions.php', $args ),
			) );
		}

		exit;
	}


	/**
	 * Saves voucher redemptions
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function save_voucher_redemptions() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			if ( ! $voucher->is_editable() ) {
				wp_send_json_error( array( 'message' => __( 'Gift certificate is not editable', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			// parse the serialized string into an array
			$data = array();
			parse_str( $_POST['data'], $data );

			$redemptions = $data['_redemptions'];

			// sanitize redemptions
			foreach ( $redemptions as $i => $redemption ) {

				// use the sanitized (non-formatted) amount for storage
				$redemptions[ $i ]['amount'] = wc_format_decimal( $redemption['amount'] );
				$redemptions[ $i ]['notes']  = sanitize_text_field( $redemption['notes'] );
			}

			try {

				$voucher->set_redemptions( $redemptions );

			} catch( Framework\SV_WC_Plugin_Exception $e ) {

				wp_send_json_error( array(
					'message' => $e->getMessage(),
				) );
			}

			// send voucher balance
			$this->send_balance_json_success( $voucher );
		}

		exit;
	}


	/**
	 * Deletes a voucher redemption
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function delete_voucher_redemption() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id = (int) $_POST['voucher_id'];
		$key        = (int) $_POST['redemption_key'];

		if ( $voucher_id > 0 && $key >= 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			if ( ! $voucher->is_editable() ) {
				wp_send_json_error( array( 'message' => __( 'Gift certificate is not editable', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			// remove the redemption
			$redemptions = $voucher->get_redemptions();

			unset( $redemptions[ $key ] );

			// TODO: instead of storing all redemptions in a single meta field perhaps
			// we should store each redemption in a separate field (add_post_meta), so that each redemption has
			// a meta_id, which would be a bit more reliable than just using the array key...?
			// The only drawback is that neither wp_update_meta or wp_delete_meta support
			// passing in the meta_id, which is a shame... {IT 2017-01-24}
			$redemptions = array_values( $redemptions );

			try {

				$voucher->set_redemptions( $redemptions );

			} catch( Framework\SV_WC_Plugin_Exception $e ) {

				wp_send_json_error( array(
					'message' => $e->getMessage(),
				) );
			}

			// send voucher balance
			$this->send_balance_json_success( $voucher );
		}

		exit;
	}


	/**
	 * Redeems a voucher
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function redeem_voucher() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id  = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			if ( ! $voucher->is_editable() ) {
				wp_send_json_error( array( 'message' => __( 'Gift certificate is not editable', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			try {

				$amount        = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
				$decimal_sep   = wc_get_price_decimal_separator();
				$thousands_sep = wc_get_price_thousand_separator();

				// if both separators are used in the same string, strip the thousands separator from input
				if ( false !== strpos( $amount, $thousands_sep ) && false !== strpos( $amount, $decimal_sep ) ) {
					$amount = str_replace( $thousands_sep, '', $amount );
				}

				$voucher->redeem( array(
					'amount'  => (float) wc_format_decimal( $amount ),
					'notes'   => isset( $_POST['notes'] )  ? sanitize_text_field( $_POST['notes'] )        : '',
					'user_id' => get_current_user_id()
				) );

				// send voucher balance
				$this->send_balance_json_success( $voucher );

			} catch ( Framework\SV_WC_Plugin_Exception $e ) {

				wp_send_json_error( array(
					/* translators: Placeholders: %1$s - voucher number, %2$s - error message */
					'message' => sprintf( __( 'Could not redeem gift certificate %1$s: %2$s', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number(), $e->getMessage() ),
				) );
			}
		}

		exit;
	}


	/**
	 * Voids a voucher
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function void_voucher() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id  = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			if ( ! $voucher->is_editable() ) {
				wp_send_json_error( array( 'message' => __( 'Gift certificate is not editable', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			$reason = sanitize_text_field( $_POST['reason'] );

			$args = array(
				'reason'  => $reason,
				'user_id' => get_current_user_id(),
			);

			$voucher->void( $args );

			// send voucher balance
			$this->send_balance_json_success( $voucher );
		}

		exit;
	}


	/**
	 * Restores a voided voucher
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function restore_voucher() {

		check_ajax_referer( 'voucher-balance', 'security' );

		$voucher_id  = (int) $_POST['voucher_id'];

		if ( $voucher_id > 0 ) {

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( ! $voucher ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gift certificate', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			if ( ! $voucher->has_status( 'voided' ) ) {
				wp_send_json_error( array( 'message' => __( 'Gift certificate is not voided', 'woocommerce-pdf-product-vouchers' ) ) );
			}

			$voucher->restore();

			// send voucher balance
			$this->send_balance_json_success( $voucher );
		}

		exit;
	}


	/**
	 * Redeems a voucher from the voucher list view.
	 *
	 * Not really an AJAX method, but rather a quick way to handle
	 * the list action, similar to how WC handles order actions in list view.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function list_redeem_voucher() {

		if ( current_user_can( 'manage_woocommerce' ) && check_admin_referer( 'vouchers-list-redeem-voucher' ) ) {

			$voucher_id = absint( $_GET['voucher_id'] );
			$voucher    = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( $voucher && $voucher->is_editable() ) {

				try {

					$voucher->redeem( array(
						'amount'  => wc_format_decimal( $_GET['amount'] ),
						'notes'   => isset( $_GET['notes'] ) ? sanitize_text_field( $_GET['notes'] ) : '',
						'user_id' => get_current_user_id()
					)  );

					/* translators: %s - voucher number */
					wc_pdf_product_vouchers()->get_message_handler()->add_message( sprintf( __( 'Gift certificate %s redeemed.', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number() ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					/* translators: %1$s - voucher number, %2$s - error message */
					wc_pdf_product_vouchers()->get_message_handler()->add_error( sprintf( __( 'Could not redeem gift certificate %1$s: %2$s', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number(), $e->getMessage() ) );
				}
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=wc_voucher' ) );
		exit;
	}


	/**
	 * Voids a voucher from the voucher list view.
	 *
	 * Not really an AJAX method, but rather a quick way to handle
	 * the list action, similar to how WC handles order actions in list view.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function list_void_voucher() {

		if ( current_user_can( 'manage_woocommerce' ) && check_admin_referer( 'vouchers-list-void-voucher' ) ) {

			$reason     = isset( $_GET['reason'] ) ? sanitize_text_field( $_GET['reason'] ) : '';
			$voucher_id = absint( $_GET['voucher_id'] );

			$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

			if ( $voucher && $voucher->is_editable() ) {

				$args = array(
					'reason'  => $reason,
					'user_id' => get_current_user_id(),
				);

				$voucher->void( $args );

				/* translators: %s - voucher number */
				wc_pdf_product_vouchers()->get_message_handler()->add_message( sprintf( __( 'Gift certificate %s voided.', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number() ) );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=wc_voucher' ) );
		exit;
	}


	/**
	 * Removes non-voucher products from json search results
	 *
	 * @since 3.0.0
	 * @param array $products
	 * @return array $products
	 */
	public function filter_json_search_found_products( $products ) {

		// remove non-voucher products
		if ( isset( $_REQUEST['exclude'] ) && 'wc_pdf_product_vouchers_non_voucher_products' === $_REQUEST['exclude'] ) {
			foreach( $products as $id => $title ) {

				if ( 'yes' !== get_post_meta( $id, '_has_voucher', true ) || ! get_post_meta( $id, '_voucher_template_id', true ) ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}


	/**
	 * Searches for single purpose voucher templates and echoes JSON data.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 */
	public function json_search_single_purpose_voucher_templates() {
		check_ajax_referer( 'search-voucher-templates', 'security' );

		$term = (string) wc_clean( stripslashes( Framework\SV_WC_Helper::get_requested_value( 'term' ) ) );

		if ( empty( $term ) ) {
			die();
		}

		if ( is_numeric( $term ) ) {

			$args = array(
				'post_type'      => 'wc_voucher_template',
				'post_status'    => 'private',
				'posts_per_page' => -1,
				'post__in'       => array( 0, $term ),
				'fields'         => 'ids'
			);

		} else {

			$args = array(
				'post_type'      => 'wc_voucher_template',
				'post_status'    => 'private',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids'
			);
		}

		$posts = get_posts( $args );

		$voucher_templates = array();

		if ( $posts ) {

			foreach ( $posts as $post ) {

				$voucher_template = wc_pdf_product_vouchers_get_voucher_template( $post );

				if ( $voucher_template && 'single' === $voucher_template->get_voucher_type() ) {

					$allow_online_redemptions = get_post_meta( $voucher_template->get_id(), '_allow_online_redemptions', true );

					if ( $allow_online_redemptions ) {

						$voucher_templates[ $post ] = $voucher_template->get_name();
					}
				}
			}
		}

		/**
		 * Filters single-purpose voucher templates found for JSON (AJAX) search.
		 *
		 * @since 3.4.0
		 *
		 * @param array $voucher_templates associative array of the found voucher template ids and names
		 */
		$voucher_templates = apply_filters( 'wc_pdf_product_vouchers_json_search_single_purpose_voucher_templates_results', $voucher_templates );

		wp_send_json( $voucher_templates );
	}


	/**
	 * Sends json success message after updating voucher balance
	 *
	 * @since 3.0.0
	 * @param WC_Voucher $voucher the voucher object
	 */
	private function send_balance_json_success( WC_Voucher $voucher ) {

		$data = array(
			'status'          => $voucher->get_status(),
			'remaining_value' => $voucher->get_remaining_value(),
			'balance_html'    => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-balance.php', array( 'voucher' => $voucher ) ),
			'notes_html'      => $this->render_html_fragment( 'src/admin/meta-boxes/views/html-voucher-notes.php',   array( 'voucher' => $voucher ) ),
		);

		wp_send_json_success( $data );
	}


	/**
	 * Renders a HTML fragment for the voucher admin screen
	 *
	 * @since 3.0.0
	 * @param string $path path to the HTML file to render
	 * @param array $args associative array of variables to pass to the HTML template
	 * @return string $html
	 */
	private function render_html_fragment( $path, $args ) {

		extract( $args );

		ob_start();

		include( wc_pdf_product_vouchers()->get_plugin_path() . '/' . $path );

		return ob_get_clean();
	}


	/**
	 * Handle AJAX to redeem voucher from barcode.
	 *
	 * @internal
	 *
	 * @since 3.5.0
	 */
	public function barcode_redeem_voucher() {

		check_ajax_referer( 'barcode-redeem-voucher', 'security' );

		$barcode_value = sanitize_text_field( $_POST['barcode_value'] );
		$voucher       = $this->get_voucher_from_barcode( $barcode_value );

		try {

			/**
			 * Filter whether voucher should be redeemed.
			 *
			 * @since 3.5.0
			 *
			 * @param bool                  Voucher should be redeemed, defaults to true.
			 * @param WC_Voucher $voucher  Current instance of WC_Voucher.
			 * @param string $barcode_value Barcode value.
			 */
			$should_redeem = apply_filters( 'wc_pdf_product_vouchers_should_redeem_scanned_voucher', true, $voucher, $barcode_value );

			if ( true !== $should_redeem ) {
				return;
			}

			$this->is_redeemable( $voucher );

			switch ( $voucher->get_voucher_type() ) {

				case 'single':
					$redemption_handler = new MWC_Gift_Certificates_Redeem_Single();
					$amount             = $voucher->get_product_price();
				break;

				case 'multi':
					$redemption_handler = new MWC_Gift_Certificates_Redeem_Multi();
					$amount             = isset( $_POST['amount'] ) ? (float) $_POST['amount'] : null;
				break;

				default:
					/* translators: Unknown voucher type. Placeholders: %1$s - voucher type */
					$message = sprintf( esc_html__( "Gift Certificate type '%s' wasn't recognized.", 'woocommerce-pdf-product-vouchers' ) , $voucher->get_voucher_type() );
					wp_send_json_error( array( 'message'   => $message, ) );
				break;
			}

			$response = $redemption_handler->redeem( $voucher, $amount );

			wp_send_json_success( $response->to_array() );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}
	}


	/**
	 * Get voucher based on barcode value using meta query with key _barcode_value
	 *
	 * @since 3.5.0
	 *
	 * @param $barcode_value
	 * @return bool|WC_Voucher
	 */
	private function get_voucher_from_barcode( $barcode_value ) {

		if ( empty( $barcode_value ) ) {
			return false;
		}

		$voucher = false;
		$args    = array(
			'post_type'              => 'wc_voucher',
			'post_status'            => array_keys( wc_pdf_product_vouchers_get_voucher_statuses() ),
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'meta_key'               => '_barcode_value',
			'meta_value'             => $barcode_value,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
		);

		$found_posts = get_posts( $args );

		if ( ! empty( $found_posts ) ) {
			$voucher = wc_pdf_product_vouchers_get_voucher( $found_posts[0] );
		}

		return $voucher;
	}


	/**
	 * Validates whether the voucher is redeemable or not.
	 *
	 * @since 3.5.0
	 *
	 * @param WC_Voucher $voucher
	 *
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function is_redeemable( $voucher ) {

		if ( ! $voucher ) {

			throw new Framework\SV_WC_Plugin_Exception(
				/* translators: Voucher was not found */
				esc_html__( 'Gift certificate was not found!', 'woocommerce-pdf-product-vouchers' )
			);

		}

		if ( ! $voucher->is_editable() ) {

			/* translators: Not redeemable voucher notice. Placeholders: %1$s - voucher number, %2$s - <a>, %3$s - </a> tag */
			$message = __( 'Gift certificate %s is not redeemable! %sClick Here%s to view the gift certificate!', 'woocommerce-pdf-product-vouchers' );

			if ( 'redeemed' === $voucher->get_status() ) {

				/* translators: Already redeemed voucher notice. Placeholders: %1$s - voucher number, %2$s - <a>, %3$s - </a> tag */
				$message = __( 'Gift certificate %s was already redeemed! %sClick Here%s to view the gift certificate!', 'woocommerce-pdf-product-vouchers' );

			}

			$message = wp_kses_post( sprintf( $message, $voucher->get_voucher_number(), '<a target="_blank" href="' . get_edit_post_link( $voucher->get_id() ) . '">', '</a>' ) );

			throw new Framework\SV_WC_Plugin_Exception( $message );
		}
	}


}
