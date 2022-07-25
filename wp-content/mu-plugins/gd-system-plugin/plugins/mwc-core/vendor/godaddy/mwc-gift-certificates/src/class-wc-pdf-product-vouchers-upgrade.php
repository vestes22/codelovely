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
 * PDF Product Vouchers lifecycle handler.
 *
 * This class handles actions triggered upon plugin updates from an earlier to the current latest version.
 *
 * Since version 3.5.0 this class extends the Framework 5.2 Lifecycle handler.
 *
 * @since 3.0.0
 *
 * @method MWC_Gift_Certificates get_plugin()
 */
class MWC_Gift_Certificates_Upgrade extends Framework\Plugin\Lifecycle {


	/**
	 * MWC_Gift_Certificates_Upgrade constructor.
	 *
	 * @since 3.5.5
	 *
	 * @param MWC_Gift_Certificates $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'2.0.4',
			'3.0.0',
			'3.1.0',
			'3.1.2',
			'3.9.0',
			'4.0.0',
		];
	}


	/**
	 * Handles plugin installation.
	 *
	 * @since 3.5.5
	 */
	protected function install() {

		register_shutdown_function( [ $this, 'catch_errors' ] );

		$this->create_files();

		// make custom endpoints available
		flush_rewrite_rules();
	}


	/**
	 * Handles plugin upgrades.
	 *
	 * @since 3.5.5
	 *
	 * @param string $installed_version the currently installed version we are upgrading from
	 */
	protected function upgrade( $installed_version ) {

		parent::upgrade( $installed_version );

		// make custom endpoints available
		flush_rewrite_rules();
	}


	/**
	 * Catches max memory limit and timeout errors on shutdown.
	 *
	 * Triggers upon upgrade or install and rolls back the MYSQL transaction if possible.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function catch_errors() {

		if ( $error = error_get_last() ) {

			wc_transaction_query( 'rollback' );
		}
	}


	/**
	 * Updates to v2.0.4
	 *
	 * @since 3.5.5
	 */
	protected function upgrade_to_2_0_4() {
		global $wpdb;

		// Actually in version 2.0 a field name changed 'display_name' -> 'label' that we forgot to update for existing shops
		$results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='_voucher_fields'" );

		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {

				$fixed          = false;
				$voucher_fields = maybe_unserialize( $row->meta_value );

				if ( is_array( $voucher_fields ) ) {
					foreach ( $voucher_fields as $name => $field ) {

						if ( isset( $field['display_name'] ) && ( ! isset( $field['label'] ) || ! $field['label'] ) ) {

							// old-style
							if ( 'recipient_name' == $name ) {
								$voucher_fields[ $name ]['label'] = 'Recipient Name';
								unset( $voucher_fields[ $name ]['display_name'] );

							} elseif ( 'message' == $name ) {
								$voucher_fields[ $name ]['label'] = 'Message to recipient';
								unset( $voucher_fields[ $name ]['display_name'] );
							}

							$fixed = true;
						}
					}
				}

				if ( $fixed ) {
					update_post_meta( $row->post_id, '_voucher_fields', $voucher_fields );
				}
			}
		}

		unset( $results );
	}


	/**
	 * Updates to v3.0.0
	 *
	 * This will transition legacy Voucher templates and vouchers to use dedicated post types for each.
	 *
	 * @since 3.5.5
	 */
	protected function upgrade_to_3_0_0() {
		global $wpdb;

		// PHP 5.3+ is required for the upgrade script to run
		if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
			return;
		}

		// 1. convert all wc_voucher posts to wc_voucher_template posts
		$this->convert_voucher_templates();

		// 2. update product meta for attached vouchers
		$this->convert_product_attached_vouchers();

		// 3. create vouchers for all order items with a voucher_id
		$this->create_order_vouchers();

		// 4. remove voucher download permissions, as they are now handled by our dedicated download handler
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE download_id LIKE 'wc_vouchers%'" );

		// 5. create files
		$this->create_files();

		// 6. clean up helper options once we're done
		delete_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_converted' );
		delete_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_products_converted' );
		delete_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_vouchers_created' );

		// 7. set an update flag for our welcome notice
		update_option( 'wc_pdf_product_vouchers_upgraded_to_3_0_0', 'yes' );
	}


	/**
	 * Updates to v3.1.0
	 *
	 * This will fix some issues that the 3.0.0 upgrade script may have caused:
	 * * invalid voucher dates were created if the site was upgraded to WC 3.0 before upgrading PDF Vouchers to 3.0.0
	 * * some orders were skipped when converting legacy vouchers if the store had more than 50 ordered vouchers
	 *
	 * @since 3.5.5
	 */
	protected function upgrade_to_3_1_0() {

		// PHP 5.3+ is required for the upgrade script to run
		if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
			return;
		}

		// 1. set voucher types
		$this->set_voucher_types();

		// 2. set voucher customers
		$this->set_voucher_customers();

		// 3. fix invalid voucher dates
		$this->fix_legacy_voucher_dates();

		// 4. create missing vouchers for all order items with a voucher_id and voucher_number
		$this->create_order_vouchers();

		// 5. clean up helper options once we're done
		delete_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_vouchers_created' );
	}


	/**
	 * Updates to v3.1.2
	 *
	 * Note: there was a fatal error in this routine in 3.1.1, so it's now an upgrade to 3.1.2.
	 *
	 * This will add post author in meta like WC does for orders and then update the voucher author ( post author ) to 1.
	 *
	 * @since 3.5.5
	 */
	protected function upgrade_to_3_1_2() {
		global $wpdb;

		wc_pdf_product_vouchers()->log( 'Starting update post author for ordered vouchers in 3.1.2' );

		$limit = 50;

		// process 50 vouchers at one time, so in case of timeouts
		// one can always resume the script by activating again...
		do {

			// find all vouchers
			$vouchers = $wpdb->get_results( $wpdb->prepare( "
				SELECT ID, post_author FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm
				ON ( p.ID = pm.post_id AND pm.meta_key = '_customer_user' )
				WHERE p.post_type = 'wc_voucher'
				AND pm.post_id IS NULL
				LIMIT 0, %d
			", $limit ) );

			if ( empty( $vouchers ) ) {
				break;
			}

			foreach ( $vouchers as $voucher ) {

				update_post_meta( $voucher->ID, '_customer_user', $voucher->post_author );

				wp_update_post( [
					'ID'          => $voucher->ID,
					'post_author' => 1,
				] );
			}

		} while ( count( $vouchers ) === $limit );

		wc_pdf_product_vouchers()->log( 'Completed update post author for ordered vouchers in 3.1.2' );
	}

	/**
	 * Updates to v3.9.0
	 *
	 * "Google Fonts" free add on is merged into core and thus can be deactivated.
	 *
	 * @since 3.9.0
	 */
	protected function upgrade_to_3_9_0() {

		if ( $this->get_plugin()->is_plugin_active( 'woocommerce-pdf-vouchers-google-fonts.php' ) ) {

			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			deactivate_plugins( 'woocommerce-pdf-vouchers-google-fonts/woocommerce-pdf-vouchers-google-fonts.php' );

			update_option( 'wc_pdf_product_vouchers_merged_google_fonts_free_add_on', 'yes' );
		}

		delete_option( 'wc_pdf_vouchers_google_fonts_version' );
	}


	/**
	 * Updates to v4.0.0
	 *
	 * Adds attachments for the new default template images that are available with the Gift Certificate native feature.
	 *
	 * @since 4.0.0
	 */
	protected function upgrade_to_4_0_0() {

		$option_name = 'wc_pdf_product_vouchers_default_voucher_image';

		// delete the ID of the default template image but keep a backup in case something goes wrong trying to add the new images
		$default_image_id = get_option( $option_name );

		delete_option( $option_name );

		$this->create_voucher_image_attachments();

		// restore the ID of the default template image if a new value was not provided
		if ( ! get_option( $option_name ) ) {

			update_option( $option_name, $default_image_id );
		}
	}


	/**
	 * Maps legacy text alignments to new text alignment values.
	 *
	 * @since 3.0.0
	 *
	 * @param string $alignment
	 * @return string mapped alignment
	 */
	private function map_text_alignment( $alignment ) {

		$alignments = [
			'L' => 'left',
			'C' => 'center',
			'R' => 'right',
		];

		return isset( $alignments[ $alignment ] ) ? $alignments[ $alignment ] : null;
	}


	/**
	 * Converts wc_voucher posts to wc-voucher_template posts
	 *
	 * @since 3.0.0
	 */
	private function convert_voucher_templates() {
		global $wpdb;

		// safety net in case the upgrade timed out and was restarted, but templates were already converted.
		if ( get_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_converted' ) ) {
			return;
		}

		wc_pdf_product_vouchers()->log( 'Starting voucher templates upgrade to 3.0.0' );

		$limit = 50;

		// process 50 templates at one time, so in case of timeouts
		// one can always resume the script by activating again...
		do {

			$offset = (int) get_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_offset', 0 );

			$template_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT ID FROM $wpdb->posts WHERE post_type = 'wc_voucher'
				AND post_status IN ( 'private', 'draft', 'trash' )
				LIMIT %d, %d
			", $offset, $limit ) );

			if ( empty( $template_ids ) ) {
				break;
			}

			// loop over templates and convert each to the new format
			foreach ( $template_ids as $template_id ) {

				// sanity check: if this was already converted, skip to next voucher template
				if ( get_post_meta( $template_id, '_wc_pdf_product_vouchers_is_converted', true ) ) {
					continue;
				}

				// get current values
				$image_ids      = get_post_meta( $template_id, '_image_ids', true );
				$text_align     = get_post_meta( $template_id, '_voucher_text_align', true );
				$font_style     = get_post_meta( $template_id, '_voucher_font_style', true );
				$voucher_fields = get_post_meta( $template_id, '_voucher_fields', true );

				wc_transaction_query( 'start' );

				// set thumbnail (primary image) id
				if ( ! empty( $image_ids ) ) {
					update_post_meta( $template_id, '_thumbnail_id', $image_ids[0] );
				}

				// by default, all legacy templates had 72 DPI
				update_post_meta( $template_id, '_voucher_image_dpi', 72 );

				// update voucher text alignment
				$text_align = $this->map_text_alignment( $text_align );

				// default voucher text alignment is left
				if ( ! $text_align ) {
					$text_align = 'left';
				}

				update_post_meta( $template_id, '_voucher_text_align', $text_align );

				// update voucher font style
				if ( ! empty( $font_style ) ) {

					if ( Framework\SV_WC_Helper::str_exists( $font_style, 'B' ) ) {
						update_post_meta( $template_id, '_voucher_font_style_b', 1 );
					}

					if ( Framework\SV_WC_Helper::str_exists( $font_style, 'I' ) ) {
						update_post_meta( $template_id, '_voucher_font_style_i', 1 );
					}

					delete_post_meta( $template_id, '_voucher_font_style' );
				}

				// update voucher fields
				if ( ! empty( $voucher_fields ) ) {
					foreach ( $voucher_fields as $field_id => $attrs ) {

						// update voucher field font properties
						if ( ! empty( $attrs['font'] ) ) {

							foreach ( $attrs['font'] as $font_property => $value ) {

								if ( ! empty( $value ) ) {

									if ( 'style' === $font_property ) {

										if ( Framework\SV_WC_Helper::str_exists( $value, 'B' ) ) {
											update_post_meta( $template_id, '_' . $field_id . '_font_style_b', 1 );
										}

										if ( Framework\SV_WC_Helper::str_exists( $value, 'I' ) ) {
											update_post_meta( $template_id, '_' . $field_id . '_font_style_i', 1 );
										}

									} else {
										update_post_meta( $template_id, '_' . $field_id . '_font_' . $font_property, $value );
									}
								}
							}
						}

						// update voucher field position
						if ( ! empty( $attrs['position'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_pos', implode( ', ', $attrs['position'] ) );
						}

						// update voucher field text alignment
						if ( ! empty( $attrs['text_align'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_text_align', $this->map_text_alignment( $attrs['text_align'] ) );
						}

						// update user input fields
						if ( ! empty( $attrs['label'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_label', $attrs['label'] );
						}

						if ( ! empty( $attrs['is_enabled'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_is_enabled', $attrs['is_enabled'] );
						}

						if ( ! empty( $attrs['is_required'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_is_required', $attrs['is_required'] );
						}

						if ( ! empty( $attrs['max_length'] ) ) {
							update_post_meta( $template_id, '_' . $field_id . '_max_length', $attrs['max_length'] );
						}
					}
				}

				delete_post_meta( $template_id, '_voucher_fields' );
				update_post_meta( $template_id, '_wc_pdf_product_vouchers_is_converted', true );

				wc_transaction_query( 'commit' );
			}

			// update offset to move the pointer 50 items forward in the next batch
			update_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_offset', $offset + $limit );

		} while ( count( $template_ids ) === $limit );

		// set the correct post_type, post_status check is used as a safety net
		// against accidentally converting non-legacy vouchers to templates
		$rows = $wpdb->query( "
			UPDATE $wpdb->posts SET post_type = 'wc_voucher_template'
			WHERE post_type = 'wc_voucher' AND post_status IN ( 'private', 'draft', 'trash' )
		" );

		// once the while loop is complete we can delete the offset option
		delete_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_offset' );

		// clean up the upgrade/conversion flags
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_wc_pdf_product_vouchers_is_converted'" );

		wc_pdf_product_vouchers()->log( sprintf( '%d voucher templates updated.', (int) $rows ) );
		wc_pdf_product_vouchers()->log( 'Completed voucher templates upgrade to 3.0.0' );

		// indicate that templates have been converted
		update_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_templates_converted', true );
	}


	/**
	 * Updates product meta for attached vouchers.
	 *
	 * @since 3.0.0
	 */
	public function convert_product_attached_vouchers() {
		global $wpdb;

		// safety net in case the upgrade timed out and was restarted, but vouchers were already created
		if ( get_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_products_converted' ) ) {
			return;
		}

		wc_pdf_product_vouchers()->log( 'Starting voucher products upgrade to 3.0.0' );

		// add new meta has_voucher => yes if a voucher ID is attached
		$product_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
 			WHERE posts.post_type IN ( 'product', 'product_variation', 'subscription', 'subscription_variation' )
 			AND postmeta.meta_key = '_voucher_id'
 		" );

		foreach ( (array) $product_ids as $id ) {
			update_post_meta( $id, '_has_voucher', 'yes' );
		}

		// old key: _voucher_id
		// new key: _voucher_template_id
		$rows = $wpdb->query( "
 			UPDATE {$wpdb->postmeta} pm
 			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
 			SET pm.meta_key = '_voucher_template_id'
 			WHERE pm.meta_key = '_voucher_id'
 			AND p.post_type IN ( 'product', 'product_variation', 'subscription', 'subscription_variation' )
 		" );

		wc_pdf_product_vouchers()->log( sprintf( '%d products with attached gift certificates updated.',  (int) $rows ) );
		wc_pdf_product_vouchers()->log( 'Completed voucher products upgrade to 3.0.0' );

		update_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_products_converted', true );
	}


	/**
	 * Creates wc_voucher posts for each order voucher
	 *
	 * @since 3.0.0
	 */
	private function create_order_vouchers() {
		global $wpdb;

		// safety net in case the upgrade timed out and was restarted, but vouchers were already created
		if ( get_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_vouchers_created' ) ) {
			return;
		}

		wc_pdf_product_vouchers()->log( 'Starting ordered vouchers upgrade to 3.0.0' );

		$limit = 50;

		// process 50 orders at one time, so in case of timeouts
		// one can always resume the script by activating again...
		do {

			// Find all orders with generated vouchers and create a wc_voucher post
			// for each of them.
			$order_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT ID FROM $wpdb->posts p
				LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON ( p.ID = oi.order_id )
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim1 ON oi.order_item_id = oim1.order_item_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON oi.order_item_id = oim2.order_item_id
				WHERE 1=1
				AND oim1.meta_key = '_voucher_id'
				AND oim2.meta_key = '_voucher_number'
				LIMIT 0, %d
			", $limit ) );

			if ( empty( $order_ids ) ) {
				break;
			}

			// loop over orders
			foreach ( $order_ids as $order_id ) {

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					continue;
				}

				foreach ( $order->get_items() as $order_item_id => $item ) {

					// sanity check: if a voucher was already created, skip to next order item
					if ( ! empty( $item['voucher_id'] ) && ! isset( $item['wc_pdf_product_vouchers_is_converted'] ) ) {

						// get existing redemptions
						$old_redemptions = isset( $item['voucher_redeem'] ) ? array_filter( maybe_unserialize( $item['voucher_redeem'] ) ) : null;

						// determine product price
						$product_price = $item['line_subtotal'] / $item['qty'];
						$product_tax   = ! empty( $item['line_tax'] ) ? $item['line_tax'] / $item['qty'] : 0;

						// get order date
						$order_date = $order->get_date_created( 'edit' );
						$order_date = wc_pdf_product_vouchers_format_date( $order_date->getTimestamp() );

						wc_transaction_query( 'start' );

						// create the wc_voucher instance
						$voucher = wc_pdf_product_vouchers_create_voucher( [
							'number'              => $order_id . '-' . $item['voucher_number'],
							'voucher_template_id' => $item['voucher_id'],
							'voucher_image_id'    => $item['voucher_image_id'],
							'currency'            => $order->get_currency(),
							'user_id'             => $order->get_user_id(),
							'order_id'            => $order_id,
							'product_id'          => $item['product_id'],
							'product_price'       => $product_price,
							'product_tax'         => $product_tax,
							'product_quantity'    => $item['qty'],
							'order_item_id'       => $order_item_id,
							'date'                => get_gmt_from_date( $order_date ),
						] );

						// check for errors while creating a voucher
						if ( is_wp_error( $voucher ) ) {
							wc_pdf_product_vouchers()->log( "Unable to upgrade item: {$order_item_id} for order: {$order_id} - " . $voucher->get_error_message() );
							continue;
						}

						// update voucher id on order item
						wc_update_order_item_meta( $order_item_id, '_voucher_id', $voucher->get_id() );

						// convert redemptions
						// Previously, redemptions were nothing more than dates for each qty sold. We'll
						// take each of those dates and add a redemption for it, setting the redemption amount
						// to $line_subtotal / $qty_sold, since there were not dollar-amount based redemptions
						// and each redemption redeemed a single qty
						if ( ! empty( $old_redemptions ) ) {

							$redemptions = [];

							foreach ( $old_redemptions as $redemption_date ) {
								$redemptions[] = [
									'amount' => $product_price,
									'date'   => $redemption_date
								];
							}

							try {

								$voucher->set_redemptions( $redemptions );

							} catch( Framework\SV_WC_Plugin_Exception $e ) {

								wc_pdf_product_vouchers()->log( 'Unable to redeem gift certificate: ' . $voucher->get_id() . ' - ' . $e->getMessage() );
							}
						}

						// set expiration date
						if ( ! empty( $item['voucher_expiration'] ) ) {

							$timezone    = wc_timezone_string();
							$date_format = 'Y-m-d H:i:s';

							// previously, dates were stored in local timezone
							$expiration_date = date( $date_format, wc_pdf_product_vouchers_adjust_date_by_timezone( strtotime( $item['voucher_expiration'] ), 'timestamp', $timezone ) );

							$voucher->set_expiration_date( $expiration_date );

							// set voucher status if applicable
							if ( ! $voucher->has_status( 'redeemed' ) && strtotime( $expiration_date ) <= time() ) {
								$voucher->update_status( 'expired' );
							}
						}

						// set user-defined voucher field values
						$user_input_fields = [];

						foreach ( WC_Voucher_Template::get_voucher_user_input_fields() as $field_id => $attrs ) {

							// previous the user inptu values were stored on order item meta, with the meta key
							// being the label for the field, rather than the field identifier
							$label = get_post_meta( $voucher->get_template_id(), '_' . $field_id . '_label', true );
							$label = $label ? $label : $attrs['label'];

							if ( ! empty( $item[ $label ] ) ) {
								update_post_meta( $voucher->get_id(), '_'. $field_id, $item[ $label ] );

								// clean up unused meta
								wc_delete_order_item_meta( $order_item_id, $field_id );
							}
						}

						// set purchaser details
						update_post_meta( $voucher->get_id(), '_purchaser_name', $order->get_billing_first_name( 'edit' ) . ' ' . $order->get_billing_last_name( 'edit' ) );
						update_post_meta( $voucher->get_id(), '_purchaser_email', $order->get_billing_email( 'edit' ) );

						// try and move the existing, generated PDF to the new location
						$vouchers_path = wc_pdf_product_vouchers()->get_uploads_path();
						$relative_path = str_pad( substr( $item['voucher_number'], -3 ), 3, 0, STR_PAD_LEFT );
						$legacy_path   = $vouchers_path . '/' . $relative_path . '/' . $voucher->get_voucher_filename();

						if ( is_readable( $legacy_path ) ) {

							// move file to a the new location
							$voucher_pdf_path = $vouchers_path . '/' . $voucher->get_voucher_path();

							// ensure the path that will hold the voucher pdf exists
							if ( ! file_exists( $voucher_pdf_path ) ) {
								@mkdir( $voucher_pdf_path, 0777, true );
							}

							// is the new path writable?
							if ( ! is_writable( $voucher_pdf_path ) ) {
								/* translators: %s - voucher file path */
								wc_pdf_product_vouchers()->log( sprintf( __( 'Gift certificate path %s is not writable', 'woocommerce-pdf-product-vouchers' ), $voucher_pdf_path ) );
								continue;
							}

							rename( $legacy_path, $voucher->get_voucher_full_filename() );

							// Set voucher status. In previous versions, there was no status, but the PDF was only
							// generated when the order was completed or processing, essentially meaning that the voucher
							// was active. So, we look if the file exists and if it does, we'll set the status to active.
							$voucher->update_status( 'active' );
						}

						// clean up unused meta
						wc_delete_order_item_meta( $order_item_id, '_voucher_expiration' );
						wc_delete_order_item_meta( $order_item_id, '_voucher_image_id' );
						wc_delete_order_item_meta( $order_item_id, '_voucher_number' );
						wc_delete_order_item_meta( $order_item_id, '_voucher_redeem' );

						wc_update_order_item_meta( $order_item_id, '_wc_pdf_product_vouchers_is_converted', true );

						wc_transaction_query( 'commit' );
					}
				}
			}

		} while ( count( $order_ids ) === $limit );

		// clean up the upgrade/conversion flags
		$rows = $wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_wc_pdf_product_vouchers_is_converted'" );

		wc_pdf_product_vouchers()->log( sprintf( '%d ordered vouchers generated.', (int) $rows ) );
		wc_pdf_product_vouchers()->log( 'Completed ordered vouchers upgrade to 3.0.0' );

		// indicate that vouchers have been created
		update_option( 'wc_pdf_product_vouchers_upgrade_to_3_0_0_vouchers_created', true );
	}


	/**
	 * Sets voucher types for existing vouchers and templates.
	 *
	 * @since 3.1.0
	 */
	private function set_voucher_types() {
		global $wpdb;

		wc_pdf_product_vouchers()->log( 'Starting setting voucher types in 3.1.0' );

		// find all voucher templates and set their voucher type, there are likely only a handful of these
		$voucher_template_posts = get_posts( [
			'post_type'    => 'wc_voucher_template',
			'post_status'  => 'any',
			'meta_key'     => '_voucher_type',
			'meta_compare' => 'NOT EXISTS',
		] );

		if ( ! empty( $voucher_template_posts ) ) {
			foreach ( $voucher_template_posts as $voucher_template_post ) {

				if ( $voucher_template = wc_pdf_product_vouchers_get_voucher_template( $voucher_template_post ) ) {

					$type = $voucher_template->has_quantity_field() ? 'single' : 'multi';

					update_post_meta( $voucher_template->get_id(), '_voucher_type', $type );
				}
			}
		}

		// find all vouchers and set their voucher type
		$limit = 100;

		// process 100 vouchers at one time, so in case of timeouts
		// one can always resume the script by activating again...
		do {

			// find all vouchers without a type
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT p.ID AS id, p.post_parent AS template_id FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = '_voucher_type' )
				WHERE p.ID > 0
				AND p.post_type = 'wc_voucher'
				AND pm.post_id IS NULL
				LIMIT 0, %d
			", $limit ) );

			if ( empty( $results ) ) {
				break;
			}

			foreach ( $results as $result ) {

				// get type from template, if template is set (sometimes it can be 0)
				$type = ! empty( $result->template_id ) ? get_post_meta( $result->template_id, '_voucher_type', true ) : null;

				// if no type found from template, try to determine it based on the voucher product quantity
				if ( ! $type ) {

					// yes, a voucher with quantity > 1 is indeed a single-purpose voucher, contradictory as it may feel
					$type = absint( get_post_meta( $result->id, '_product_quantity', true ) ) > 1 ? 'single' : 'multi';
				}

				// override type to multi if this is a SPV, but already has redemptions and the remaining value is not
				// a multiple of the product price, as not to block admins from redeeming the remaining amount
				if ( 'single' === $type ) {

					$product_price   = get_post_meta( $result->id, '_product_price', true );
					$remaining_value = get_post_meta( $result->id, '_remaining_value', true );

					if ( is_numeric( $product_price ) && is_numeric( $remaining_value ) && fmod( (float) $remaining_value, (float) $product_price ) ) {
						$type = 'multi';
					}
				}

				update_post_meta( $result->id, '_voucher_type', $type );
			}

		} while ( count( $results ) === $limit );

		wc_pdf_product_vouchers()->log( 'Completed setting voucher types in 3.1.0' );
	}


	/**
	 * Sets voucher customer (post_author) for existing vouchers if possible
	 *
	 * @since 3.1.0
	 */
	private function set_voucher_customers() {
		global $wpdb;

		wc_pdf_product_vouchers()->log( 'Starting setting voucher customers in 3.1.0' );

		// find all vouchers without a customer id (post_author) and try to set it based on the
		// order customer_user
		$wpdb->query("
			UPDATE {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm  ON p.ID = pm.post_id
			INNER JOIN {$wpdb->postmeta} pm2 ON pm.meta_value = pm2.post_id
			SET p.post_author = pm2.meta_value
			WHERE p.post_type = 'wc_voucher'
			AND p.post_author = 0
			AND pm.meta_key  = '_order_id'
			AND pm2.meta_key = '_customer_user'
			AND pm2.meta_value > 0
		");

		wc_pdf_product_vouchers()->log( 'Completed setting voucher customers in 3.1.0' );
	}


	/**
	 * Fixes dates for legacy vouchers converted to 3.0.0 with an invalid date
	 *
	 * @since 3.1.0
	 */
	private function fix_legacy_voucher_dates() {
		global $wpdb;

		wc_pdf_product_vouchers()->log( 'Starting ordered vouchers date fixes in 3.1.0' );

		$limit = 50;

		// process 50 vouchers at one time, so in case of timeouts
		// one can always resume the script by activating again...
		do {

			// find all vouchers with an invalid date that have an order attached
			$voucher_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT ID FROM $wpdb->posts p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE 1=1
				AND p.post_type = 'wc_voucher'
				AND p.post_date_gmt = '1970-01-01 00:00:00'
				AND pm.meta_key = '_order_id'
				LIMIT 0, %d
			", $limit ) );

			if ( empty( $voucher_ids ) ) {
				break;
			}

			foreach ( $voucher_ids as $voucher_id ) {

				$voucher = wc_pdf_product_vouchers_get_voucher( $voucher_id );

				if ( ! $voucher ) {
					continue;
				}

				$order = $voucher->get_order();

				if ( ! $order ) {
					continue;
				}

				$order_date     = $order->get_date_created( 'edit' );
				$order_date     = wc_pdf_product_vouchers_format_date( $order_date->getTimestamp() );
				$order_date_gmt = get_gmt_from_date( $order_date );

				wp_update_post( [
					'ID'            => $voucher_id,
					'post_date'     => $order_date,
					'post_date_gmt' => $order_date_gmt,
				] );
			}

		} while ( count( $voucher_ids ) === $limit );

		wc_pdf_product_vouchers()->log( 'Completed ordered vouchers date fixes in 3.1.0' );
	}


	/**
	 * Creates files/directories
	 *
	 * @see \WC_Install::create_files()
	 *
	 * @since 3.0.0
	 */
	private function create_files() {

		// Install files and folders for font cache and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$files = [
			[
				'base'    => $upload_dir['basedir'] . '/pdf_vouchers_font_cache',
				'file'    => 'index.html',
				'content' => ''
			],
			[
				'base'    => $upload_dir['basedir'] . '/pdf_vouchers_font_cache',
				'file'    => '.htaccess',
				'content' => 'deny from all'
			],
		];

		foreach ( $files as $file ) {

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {

					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}

		$this->create_voucher_image_attachments();
	}


	/**
	 * Creates the default gift certificate image attachments.
	 *
	 * @since 4.0.0
	 */
	protected function create_voucher_image_attachments() {

		$plugin_path = wc_pdf_product_vouchers()->get_plugin_path();

		// default styles for default vouchers
		$group1_styles = [
			'voucher' => [
				'font_size' => 19
			],
			'product_name' => [
				'pos'          => '830,328,1400,280',
				'font_style_b' => true
			],
			'product_price' => [
				'pos'          => '2460,600,490,200',
				'text_align'   => 'center',
				'font_style_b' => true
			],
			'recipient_name' => [
				'pos'        => '321,771,1910,90',
				'is_enabled' => true,
			],
			'voucher_number' => [
				'pos' => '312,1220,760,90',
			],
			'product_quantity' => [
				'pos' => '1270,1220,760,90',
			],
			'expiration_date' => [
				'pos' => '2225,1220,760,90',
			],
			'message' => [
				'pos'          => '312,1470,2670,610',
				'font_style_i' => true,
				'is_enabled'   => true,
			],
		];

		$group2_styles = [
			'voucher' => [
				'font_size' => 15
			],
			'product_name' => [
				'pos'          => '530,180,1700,80',
			],
			'product_price' => [
				'pos'          => '2500,700,600,200',
				'text_size'    => 24,
				'text_align'   => 'center',
				'font_style_b' => true,
				'font_color'   => '#ffffff',
			],
			'recipient_name' => [
				'pos'        => '780,395,1450,80',
				'is_enabled' => true,
			],
			'voucher_number' => [
				'pos' => '595,663,540,80',
			],
			'expiration_date' => [
				'pos' => '1575,663,650,80',
			],
			'message' => [
				'pos'          => '210,840,2020,390',
				'font_style_i' => true,
				'is_enabled'   => true,
			],
		];

		$default_images = [
			[
				'path'   => $plugin_path . '/assets/images/modern.png',
				'title'  => __( 'Gift Certificate Group 1: Modern', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-1-modern',
				'styles' => $group1_styles,
			],
			[
				'path'   => $plugin_path . '/assets/images/formal.png',
				'title'  => __( 'Gift Certificate Group 1: Formal', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-1-formal',
				'styles' => $group1_styles,
			],
			[
				'path'   => $plugin_path . '/assets/images/xmas.png',
				'title'  => __( 'Gift Certificate Group 1: Christmas', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-1-christmas',
				// christmas is a special time of year, why don't we make the voucher a bit sepcial aswell?
				'styles' => wp_parse_args( [ 'product_price' => [
					'pos'          => '2400,600,490,200',
					'text_align'   => 'center',
					'font_style_b' => true
				] ], $group1_styles ),
			],
			[
				'path'   => $plugin_path . '/assets/images/celebration.png',
				'title'  => __( 'Gift Certificate Group 2: Celebration', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-2-celebration',
				'styles' => $group2_styles,
			],
			[
				'path'   => $plugin_path . '/assets/images/childrens.png',
				'title'  => __( 'Gift Certificate Group 2: Children', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-2-children',
				'styles' => $group2_styles,
			],
			[
				'path'   => $plugin_path . '/assets/images/feminine.png',
				'title'  => __( 'Gift Certificate Group 2: Feminine', 'woocommerce-pdf-product-vouchers' ),
				'id'     => 'group-2-feminine',
				'styles' => $group2_styles,
			],
		];

		foreach ( $default_images as $attrs ) {

			$this->create_voucher_image_attachment( $attrs );
		}
	}


	/**
	 * Creates voucher default image attachments
	 *
	 * @since 3.0.0
	 * @param array $attrs Image attributes
	 */
	private function create_voucher_image_attachment( $attrs ) {
		global $wpdb;

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		// file path does not exist, bail out
		if ( ! file_exists( $attrs['path'] ) ) {
			return;
		}

		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_mwc_gift_certificate_template_default_image_id' AND meta_value = %s", $attrs['id'] ) );

		// attachment for this default image already exists, skip
		if ( $attachment_id ) {
			return;
		}

		wc_transaction_query( 'start' );

		$filename  = basename( $attrs['path'] );
		$extension = pathinfo( $attrs['path'], PATHINFO_EXTENSION );
		$tmp_path  = wp_tempnam();

		copy( $attrs['path'], $tmp_path );

		$files = [
			'name'     => $filename,
			'type'     => 'image/' . $extension,
			'tmp_name' => $tmp_path,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $attrs['path'] ),
		];

		add_filter( 'intermediate_image_sizes_advanced', [ $this, 'voucher_template_image_sizes' ], 999 );

		$attachment_id = media_handle_sideload( $files, null, $attrs['title'] );

		remove_filter( 'intermediate_image_sizes_advanced', [ $this, 'voucher_template_image_sizes' ], 999 );

		@unlink( $tmp_path ); // Delete the tmp file. Closing it should also delete it, so hide any warnings with @

		// sideload failed, bail out
		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {

			/* Translators: %1$s - file name, %2$s - error message */
			$message = sprintf( __( 'Could not install default gift certificate image %1$s: %2$s', 'woocommerce-pdf-product-vouchers' ), $filename, $attachment_id->get_error_message() );

			wc_pdf_product_vouchers()->get_message_handler()->add_error( $message );

			wc_pdf_product_vouchers()->log( $message );

			return;
		}

		// the Gift Certificates native feature uses new versions of the default images:
		// we started using a new meta key to identify those images to allow this upgrade routine to work for both new users
		// and users of the community plugin that were migrated to the native feature
		update_post_meta( $attachment_id, '_mwc_gift_certificate_template_default_image_id', $attrs['id'] );

		// Indicate that this is one of the default images for voucher templates
		update_post_meta( $attachment_id, '_is_mwc_gift_certificate_template_default_image', 1 );

		update_post_meta( $attachment_id, '_wc_voucher_template_default_styles', $attrs['styles'] );

		// designate the first image as the default background image for any new vouchers
		if ( ! get_option( 'wc_pdf_product_vouchers_default_voucher_image' ) ) {
			update_option( 'wc_pdf_product_vouchers_default_voucher_image', $attachment_id );
		}

		wc_transaction_query( 'commit' );
	}


	/**
	 * Modifies default voucher image sizes.
	 *
	 * Since we only care about the full size + thumbnails,
	 * remove all the other sizes so that the install/upgrade won't time out.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param array $sizes
	 * @return array
	 */
	public function voucher_template_image_sizes( $sizes ) {

		foreach ( array_keys( $sizes ) as $size ) {
			if ( ! in_array( $size, [ 'thumbnail', 'wc-pdf-product-vouchers-voucher-thumb' ], false ) ) {
				unset( $sizes[ $size ] );
			}
		}

		return $sizes;
	}


	/**
	 * Generates a milestone notice message.
	 *
	 * @param string $custom_message custom text that notes what milestone was completed.
	 * @return string
	 */
	protected function generate_milestone_notice_message( $custom_message ) {

		// to be prepended at random to each milestone notice
		$exclamations = array(
			__( 'Awesome', 'woocommerce-plugin-framework' ),
			__( 'Fantastic', 'woocommerce-plugin-framework' ),
			__( 'Congratulations', 'woocommerce-plugin-framework' ),
		);

		$message = $exclamations[ array_rand( $exclamations ) ] . ', ' . esc_html( $custom_message ) . ' ';

		$message .= sprintf(
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			__( 'If you have any questions, we\'re happy to help – please %1$sreach out to our support team%2$s.', 'woocommerce-plugin-framework' ),
			'<a href="' . esc_url('/wp-admin/admin.php?page=godaddy-get-help') . '">', '</a>'
		);

		return $message;
	}


}
