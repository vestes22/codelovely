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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin\MetaBoxes\Views;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_statuses;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Voucher data (details) meta box admin template
 *
 * @type \WP_Post $post current the post object
 * @type WC_Voucher $voucher current voucher instance
 *
 * @since 3.0.0
 * @version 3.5.0
 */
?>

<style type="text/css">
	#post-body-content, #titlediv { display:none }
</style>

<div class="panel-wrap woocommerce">

	<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? '' : esc_attr( $post->post_title ); ?>" />
	<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
	<input id="voucher_type" type="hidden" class="js-voucher-type" value="<?php echo esc_attr( $voucher->get_voucher_type() ); ?>" />

	<div id="voucher_data" class="panel">

		<h2><?php /* translators: %s - voucher number */ echo esc_html( sprintf( __( 'Gift Certificate #%s details', 'woocommerce-pdf-product-vouchers' ), $voucher->get_voucher_number() ) ); ?></h2>
		<?php if ( $voucher->get_order() ) : ?>
			<p class="voucher_data_order"><?php /* translators: %1$s - <a> tag, %2$s - order number, %3$s - </a> tag */  printf( esc_html__( 'Purchased in %1$sOrder #%2$s%3$s', 'woocommerce-pdf-product-vouchers' ), '<a href="' . esc_url( get_edit_post_link( $voucher->get_order()->get_id() ) ) . '">', $voucher->get_order()->get_order_number(), '</a>' ); ?></p>
		<?php endif; ?>
		<div class="voucher_data_column_container">
			<div class="voucher_data_column">
				<h3><?php esc_html_e( 'General Details', 'woocommerce-pdf-product-vouchers' ); ?></h3>

				<p class="form-field form-field-wide"><label for="voucher_date"><?php esc_html_e( 'Gift Certificate date:', 'woocommerce-pdf-product-vouchers' ) ?></label>
					<input type="text" class="date-picker" name="voucher_date" id="voucher_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $post->post_date ) ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />@<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'woocommerce-pdf-product-vouchers' ) ?>" name="voucher_date_hour" id="voucher_date_hour" min="0" max="23" step="1" value="<?php echo date_i18n( 'H', strtotime( $post->post_date ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'woocommerce-pdf-product-vouchers' ) ?>" name="voucher_date_minute" id="voucher_date_minute" min="0" max="59" step="1" value="<?php echo date_i18n( 'i', strtotime( $post->post_date ) ); ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field form-field-wide"><label for="expiration_date"><?php esc_html_e( 'Expiration date:', 'woocommerce-pdf-product-vouchers' ) ?></label>
					<?php $expiration_date = $voucher->get_local_expiration_date( 'timestamp' ); ?>
					<input type="text" class="date-picker" name="expiration_date" id="expiration_date" maxlength="10" value="<?php echo ( $expiration_date ? date_i18n( 'Y-m-d', $expiration_date ) : '' ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />@<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'woocommerce-pdf-product-vouchers' ) ?>" name="expiration_date_hour" id="expiration_date_hour" min="0" max="23" step="1" value="<?php echo ( $expiration_date ? date_i18n( 'H', $expiration_date ) : '' ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'woocommerce-pdf-product-vouchers' ) ?>" name="expiration_date_minute" id="expiration_date_minute" min="0" max="59" step="1" value="<?php echo ( $expiration_date ?  date_i18n( 'i', $expiration_date ) : '' ); ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field form-field-wide wc-order-status"><label for="voucher_status"><?php esc_html_e( 'Gift Certificate status:', 'woocommerce-pdf-product-vouchers' ) ?></label>
				<select id="post_status" name="post_status" class="wc-enhanced-select">
					<?php
						$statuses = wc_pdf_product_vouchers_get_voucher_statuses();
						foreach ( $statuses as $status => $attrs ) {
							echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'wcpdf-' . $voucher->get_status(), false ) . '>' . esc_html( $attrs['label'] ) . '</option>';
						}
					?>
				</select></p>

				<?php
					/**
					 * Triggered after rendering the main voucher details in voucher data meta box
					 *
					 * @since 3.0.0
					 * @param WC_Voucher $voucher Voucher instance
					 */
					do_action( 'woocommerce_admin_voucher_data_after_voucher_details', $voucher );
				?>
			</div>

			<div class="voucher_data_column voucher-purchaser-details">
				<h3>
					<?php esc_html_e( 'Purchaser Details', 'woocommerce-pdf-product-vouchers' ); ?>
					<a href="#" class="edit-voucher-details"><?php esc_html_e( 'Edit', 'woocommerce-pdf-product-vouchers' ); ?></a>
					<a href="#" class="tips load-customer-details" data-tip="<?php esc_attr_e( 'Load customer details', 'woocommerce-pdf-product-vouchers' ); ?>" style="display:none;"><?php esc_html_e( 'Load customer details', 'woocommerce-pdf-product-vouchers' ); ?></a>
				</h3>

				<div class="address view-details">

					<?php if ( $voucher->has_purchaser_details() ) : ?>

						<p><strong><?php esc_html_e( 'Purchaser Name', 'woocommerce-pdf-product-vouchers' ); ?>:</strong> <?php echo esc_html( $voucher->get_purchaser_name() ); ?></p>
						<p><strong><?php esc_html_e( 'Purchaser Email', 'woocommerce-pdf-product-vouchers' ); ?>:</strong> <?php echo esc_html( $voucher->get_purchaser_email() ); ?></p>

					<?php else : ?>

						<p><?php esc_html_e( 'No purchaser details available', 'woocommerce-pdf-product-vouchers' ); ?></p>

					<?php endif; ?>

				</div>

				<div class="edit-details">

					<p class="form-field form-field-wide wc-customer-user">

						<?php $customer_id = $voucher->get_customer_id(); ?>

						<label for="customer_id"><?php esc_html_e( 'Customer:', 'woocommerce-pdf-product-vouchers' ) ?>
							<?php
								if ( ! empty( $customer_id ) ) {
									$args = array(
										'post_status' => 'all',
										'post_type'   => 'wc_voucher',
										'customer_id' => absint( $voucher->get_customer_id() ),
									);
								}
							?>
						</label>

						<?php
							$user_string = '';
							$user_id     = '';

							if ( ! empty( $customer_id ) ) {
								$user_id     = absint( $customer_id );
								$user        = get_user_by( 'id', $user_id );
								$user_string = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')';
							}
						?>

						<select
								name="customer_id"
								id="customer_id"
								class="wc-customer-search"
								data-placeholder="<?php esc_attr_e( 'Guest', 'woocommerce-pdf-product-vouchers' ); ?>"
								data-allow_clear="true">
							<?php if ( $user_id ) : ?>
								<option value="<?php echo esc_attr( $user_id ); ?>" selected><?php echo $user_string; ?></option>
							<?php endif; ?>
						</select>
					</p>

					<?php woocommerce_wp_text_input( array( 'id' => '_purchaser_name', 'label' => __( 'Purchaser Name', 'woocommerce-pdf-product-vouchers' ) ) ); ?>

					<?php woocommerce_wp_text_input( array( 'id' => '_purchaser_email', 'type' => 'email', 'label' => __( 'Purchaser Email', 'woocommerce-pdf-product-vouchers' )  ) ); ?>

					<div class="clear"></div>

				</div>

				<?php
					/**
					 * Triggered after rendering the voucher purchaser details in voucher data meta box
					 *
					 * @since 3.0.0
					 * @param WC_Voucher $voucher Voucher instance
					 */
					do_action( 'wc_pdf_product_vouchers_voucher_admin_after_purchaser_details', $voucher );
				?>
			</div>

			<div class="voucher_data_column voucher-recipient-details">

				<h3>
					<?php esc_html_e( 'Recipient Details', 'woocommerce-pdf-product-vouchers' ); ?>
					<a href="#" class="edit-voucher-details"><?php esc_html_e( 'Edit', 'woocommerce-pdf-product-vouchers' ); ?></a>
					<a href="#" class="tips copy-purchaser-details" data-tip="<?php esc_attr_e( 'Copy from purchaser details', 'woocommerce-pdf-product-vouchers' ); ?>" style="display:none;"><?php esc_html_e( 'Copy from purchaser details', 'woocommerce-pdf-product-vouchers' ); ?></a>
				</h3>

				<?php $voucher_template  = $voucher->get_template(); ?>
				<?php $user_input_fields = $voucher_template ? $voucher_template->get_user_input_voucher_fields() : array(); ?>

				<div class="address view-details">

					<?php if ( ! empty( $user_input_fields ) && $voucher->has_recipient_details() ) : ?>

						<?php

						foreach ( $user_input_fields as $field_id => $attrs ) :

							// skip purchaser name, as it is already handled in the purchaser details
							if ( 'purchaser_name' === $field_id ) {
								continue;
							}

							$value = $voucher->get_field_value_formatted( $field_id );

							if ( isset( $attrs['type'] ) && 'textarea' === $attrs['type'] ) {
								$value = wp_kses_post( nl2br( $value ) );
							} else {
								$value = esc_html( $value );
							}

							echo '<p><strong>' . esc_html( $attrs['label'] ) . ':</strong> ' . $value . '</p>';

						endforeach;

						?>

					<?php else : ?>

						<p><?php esc_html_e( 'No recipient details available', 'woocommerce-pdf-product-vouchers' ); ?></p>

					<?php endif; ?>
				</div>

				<div class="edit-details">
				<?php
					if ( ! empty( $user_input_fields ) ) {
						foreach ( $user_input_fields as $field_id => $attrs ) {

							// skip purchaser name, as it is already handled in the puchaser details
							if ( 'purchaser_name' === $field_id ) {
								continue;
							}

							if ( ! isset( $attrs['type'] ) ) {
								$attrs['type'] = 'text';
							}

							$attrs['id'] = '_' . $field_id;

							switch ( $attrs['type'] ) {
								case 'select' :
									woocommerce_wp_select( $attrs );
								break;
								case 'textarea' :
									woocommerce_wp_textarea_input( $attrs );
								break;
								default :
									woocommerce_wp_text_input( $attrs );
								break;
							}
						}
					}
				?>
					<div class="clear"></div>
				</div>

				<?php
					/**
					 * Triggered after rendering the voucher recipient details in voucher data meta box
					 *
					 * @since 3.0.0
					 * @param WC_Voucher $voucher Voucher instance
					 */
					do_action( 'wc_pdf_product_vouchers_voucher_admin_after_recipient_details', $voucher );
				?>
			</div>
		</div>
		<div class="clear"></div>
	</div>
</div>
