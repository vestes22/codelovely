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
 * Voucher functions
 *
 * @since 3.0.0
 */

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;


/**
 * Returns a voucher template
 *
 * @since 3.0.0
 * @param int|\WP_Post|WC_Voucher_Template $post (optional) post object or post id of the voucher template, defaults to global $post object
 * @return WC_Voucher_Template|false
 */
function wc_pdf_product_vouchers_get_voucher_template( $post = null ) {
	return wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_template( $post );
}


/**
 * Returns a voucher
 *
 * @since 3.0.0
 * @param int|\WP_Post|WC_Voucher $post (optional) post object or post id of the voucher, defaults to global $post object
 * @return WC_Voucher|false
 */
function wc_pdf_product_vouchers_get_voucher( $post = null ) {
	return wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher( $post );
}


/**
 * Gets a voucher based on the voucher number.
 *
 * @since 3.9.8
 *
 * @param string $voucher_number the voucher number
 * @return WC_Voucher|false
 */
function wc_pdf_product_vouchers_get_voucher_by_voucher_number( $voucher_number ) {
	return wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_by_voucher_number( $voucher_number );
}


/**
 * Returns all voucher statuses
 *
 * @since 3.0.0
 * @return array
 */
function wc_pdf_product_vouchers_get_voucher_statuses() {
	return wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_statuses();
}


/**
 * Generates a unique voucher number
 *
 * @since 3.0.0
 * @param int $order_id (optional) order ID the voucher is related to
 * @return string generated voucher number
 */
function wc_pdf_product_vouchers_generate_voucher_number( $order_id = null ) {
	return wc_pdf_product_vouchers()->get_voucher_handler_instance()->generate_voucher_number( $order_id );
}


/**
 * Returns the nice name for a voucher status
 *
 * @since 3.0.0
 * @param string $status voucher status slug
 * @return string voucher status name (label)
 */
function wc_pdf_product_vouchers_get_voucher_status_name( $status ) {

	$statuses = wc_pdf_product_vouchers_get_voucher_statuses();
	$status   = 0 === strpos( $status, 'wcpdf-' ) ? substr( $status, 6 ) : $status;
	$status   = isset( $statuses[ 'wcpdf-' . $status ] ) ? $statuses[ 'wcpdf-' . $status ] : $status;

	return is_array( $status ) && isset( $status['label'] ) ? $status['label'] : $status;
}

/**
 * Creates a new voucher
 *
 * Returns a new voucher object on success which can then be used to add additional data,
 * but will return WP_Error on failure.
 *
 * @since 3.0.0
 *
 * @param array $args {
 *     array of arguments
 *
 *     @type string $number (optional) voucher number, defaults to a unqiue, generated voucher number
 *     @type int $voucher_id (optional) voucher identifier - if provided, will perform an update instead of insert
 *     @type int $voucher_template_id voucher template identifier
 *     @type int $voucher_image_id (optional) voucher image identifier, defaults to the primary voucher image of the voucher template
 *     @type string $voucher_type (optional) voucher type, defaults to the type of voucher template
 *     @type string $currency (optional) voucher currency, defaults to woocommerce currency
 *     @type int $user_id (optional) voucher customer identifier
 *     @type int $product_id (optional) voucher product identifier
 *     @type int $product_price (optional) voucher product price, defaults to the order item or the product price
 *     @type int $product_quantity (optional) voucher product quantity, defaults to 1
 *     @type int $order_item_id (optional) order item id this voucher is related to
 *     @type string $date (optional) voucher date in mysql format, defaults to now
 * }
 * @return WC_Voucher|\WP_Error
 */
function wc_pdf_product_vouchers_create_voucher( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number'              => null,
		'voucher_id'          => 0,
		'voucher_template_id' => 0,
		'voucher_image_id'    => 0,
		'voucher_type'        => null,
		'currency'            => null,
		'user_id'             => 0,
		'order_id'            => 0,
		'product_id'          => 0,
		'product_price'       => null,
		'product_tax'         => null,
		'product_quantity'    => 1,
		'order_item_id'       => 0,
		'date'                => null // date in UTC
	) );

	// TODO: make sure this method actually supports both updating and creating, currently the
	// voucher number is generated in both cases, which is not what we want {IT 2017-02-21}

	if ( ! $args['number'] ) {
		$args['number'] = wc_pdf_product_vouchers_generate_voucher_number( $args['order_id'] );
	}

	$new_voucher_data = array(
		'post_title'    => $args['number'],
		'post_parent'   => (int) $args['voucher_template_id'],
		'post_author'   => 1,
		'post_date_gmt' => $args['date'],
		'post_type'     => 'wc_voucher',
		'post_status'   => 'wcpdf-pending',
	);

	$updating = false;

	if ( (int) $args['voucher_id'] > 0 ) {
		$updating               = true;
		$new_voucher_data['ID'] = (int) $args['voucher_id'];
	}

	/**
	 * Filters new voucher data
	 *
	 * @since 3.0.0
	 * @param array $data
	 * @param array $args {
	 *     array of voucher arguments
	 *
	 *     @type int $voucher_template_id the voucher template to be used for generating the PDF
	 *     @type int $order_id (optional) the order id that this voucher was generated from
	 *     @type int $user_id (optional) the user id the voucher is assigned to
	 * }
	 */
	$new_post_data = apply_filters( 'wc_pdf_product_vouchers_new_voucher_data', $new_voucher_data, array(
		'voucher_template_id' => (int) $args['voucher_template_id'],
		'order_id'            => (int) $args['order_id'],
		'user_id'             => (int) $args['user_id'],
	) );

	if ( $updating ) {
		$voucher_id = wp_update_post( $new_post_data );
	} else {
		$voucher_id = wp_insert_post( $new_post_data );
	}

	// bail out on error
	// TODO: should we throw here instead? {IT 2017-01-07}
	if ( is_wp_error( $voucher_id ) ) {
		return $voucher_id;
	}

	// get the voucher object to set properties on
	$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

	/**
	 * Filter the voucher args so they can be adjusted by 3rd parties before saving meta.
	 *
	 * @since 3.2.2
	 *
	 * @param string[] $args voucher args
	 * @param WC_Voucher $voucher the voucher object
	 * @param bool $updating true if the voucher is being updated, false if newly created
	 */
	$args = apply_filters( 'wc_pdf_product_vouchers_new_voucher_data_before_save', $args, $voucher, $updating );

	// save/update the customer id that this voucher was purchased by
	if ( (int) $args['user_id'] > 0 ) {
		$voucher->set_customer_id( $args['user_id'] );
	}

	// save/update the order id that this voucher was purchased from
	if ( (int) $args['order_id'] > 0 ) {
		$voucher->set_order_id( $args['order_id'] );
	}

	// save/update the product id that this voucher was purchased for
	if ( (int) $args['product_id'] > 0 ) {
		$voucher->set_product_id( $args['product_id'] );
	}

	// save/update the order item id that this voucher was purchased with
	if ( (int) $args['order_item_id'] > 0 ) {
		$voucher->set_order_item_id( $args['order_item_id'] );
	}

	// save/update the selected voucher image id
	if ( (int) $args['voucher_image_id'] > 0 ) {

		$voucher->set_image_id( $args['voucher_image_id'] );

	} elseif ( ! $updating ) {

		$voucher_template = $voucher->get_template();

		// if we don't have a template or image, we can't create a voucher
		if ( ! $voucher_template || ! is_callable( array( $voucher_template, 'get_image_id' ) ) ) {
			return new \WP_Error( 'no-template', 'No template available for this voucher' );
		}

		// if creating a new voucher and no image id was provided,
		// default to the primary image from the template
		$voucher->set_image_id( $voucher_template->get_image_id() );
	}

	// save/update the selected voucher type
	$voucher_type = $args['voucher_type'];

	if ( ! $voucher_type && ! $updating ) {

		$voucher_template = $voucher->get_template();
		$voucher_type     = 'single';

		// get the voucher type from the template if available or default to single voucher
		if ( $voucher_template && is_callable( array( $voucher_template, 'get_voucher_type' ) ) ) {
			$voucher_type = $voucher_template->get_voucher_type();
		}
	}

	if ( $voucher_type ) {
		update_post_meta( $voucher->get_id(), '_voucher_type', $voucher_type );
	}

	if ( ! $updating ) {

		// generate a secret key that allows the pdf generator access the voucher's HTML
		$voucher->generate_key();

		// set voucher currency
		if ( empty( $args['currency'] ) ) {
			$args['currency'] = get_woocommerce_currency();
		}

		update_post_meta( $voucher->get_id(), '_voucher_currency', $args['currency'] );

		// ensure product quantity is at least 1
		if ( empty( $args['product_quantity'] ) || $args['product_quantity'] < 1 ) {
			$args['product_quantity'] = 1;
		}

		$order = $voucher->get_order();
		$item  = $order ? $voucher->get_order_item() : null;

		// try to determine voucher product price if not provided
		if ( null === $args['product_price'] ) {

			if ( $order && $item ) {
				$args['product_price'] = $order->get_item_subtotal( $item );
			} elseif ( $product = $voucher->get_product() ) {
				$args['product_price'] = wc_get_price_excluding_tax( $product );
			}
		}

		// try to determine voucher product tax if not provided
		if ( null === $args['product_tax'] ) {

			if ( $order && $item ) {
				$args['product_tax'] = $order->get_item_tax( $item );
			} elseif ( $product = $voucher->get_product() ) {
				$args['product_tax'] = $voucher->calculate_product_tax( $args['product_price'] );
			}
		}

		// set voucher product price, quantity and remaining value
		update_post_meta( $voucher->get_id(), '_product_price',    $args['product_price'] );
		update_post_meta( $voucher->get_id(), '_product_tax',      $args['product_tax'] );
		update_post_meta( $voucher->get_id(), '_product_quantity', $args['product_quantity'] );
		update_post_meta( $voucher->get_id(), '_remaining_value',  (float) $args['product_price'] * (float) $args['product_quantity'] );
	}

	/**
	 * Fires after a voucher has been created
	 *
	 * @since 3.0.0
	 * @param WC_Voucher $voucher the voucher that was created
	 * @param string[] $args voucher creation data
	 */
	do_action( 'wc_pdf_product_vouchers_voucher_created', $voucher, $args );

	return $voucher;
}


/**
 * Returns available vouchers for a customer
 *
 * @since 3.0.0
 * @param int $customer_id (optional) user identifier, defaults to current user
 * @return WC_Voucher[]|bool
 */
function wc_pdf_product_vouchers_get_customer_available_vouchers( $customer_id = null ) {

	if ( ! $customer_id ) {
		$customer_id = get_current_user_id();
	}

	if ( ! $customer_id ) {
		return false;
	}

	$vouchers = array();

	$voucher_posts = get_posts( array(
		'post_type'   => 'wc_voucher',
		'post_status' => 'wcpdf-active',
		'nopaging'    => true,
		'meta_key'    => '_customer_user',
		'meta_value'  => $customer_id,
	) );

	if ( ! empty( $voucher_posts ) ) {
		foreach ( $voucher_posts as $post ) {
			$vouchers[] = wc_pdf_product_vouchers_get_voucher( $post );
		}
	}

	/**
	 * Filters vouchers available for a customer
	 *
	 * @since 3.0.0
	 * @param WC_Voucher[] $vouchers
	 * @param int $customer_id
	 */
	return apply_filters( 'wc_pdf_product_vouchers_customer_available_vouchers', $vouchers, $customer_id );
}
