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

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Voucher Actions Meta Box
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Meta_Box_Voucher_Actions {


	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30 );
		add_action( 'wc_pdf_product_vouchers_process_voucher_meta', array( $this, 'save' ), 30, 2 );
	}


	/**
	 * Adds the meta box
	 *
	 * @since 3.0.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-pdf-product-vouchers-voucher-actions', __( 'Gift Certificate Actions', 'woocommerce-pdf-product-vouchers' ), array( $this, 'output' ), 'wc_voucher', 'side', 'high' );
	}


	/**
	 * Outputs meta box contents
	 *
	 * @since 3.0.0
	 */
	public function output() {
		global $post, $voucher;

		?>
		<ul class="voucher_actions submitbox">

			<?php
				/**
				 * Triggered at the beginning of the voucher actions meta box
				 *
				 * @since 3.0.0
				 * @param int $voucher_id Voucher (post) ID
				 */
				do_action( 'woocommerce_pdf_product_vouchers_voucher_actions_start', $post->ID );
			?>

			<li class="wide" id="actions">
				<select name="wc_voucher_action">
					<option value=""><?php esc_html_e( 'Actions', 'woocommerce-pdf-product-vouchers' ); ?></option>
					<optgroup label="<?php esc_attr_e( 'Gift Certificate emails', 'woocommerce' ); ?>">
						<?php

						/**
						 * Filter the emails avilable for resending in voucher actions meta box
						 *
						 * @since 3.0.0
						 * @param string[] Array of email IDs
						 */
						$available_emails = apply_filters( 'woocommerce_pdf_product_vouchers_resend_voucher_emails_available', array( 'wc_pdf_product_vouchers_voucher_recipient', 'wc_pdf_product_vouchers_voucher_purchaser' ) );

						$mailer = WC()->mailer();
						$mails  = $mailer->get_emails();

						if ( ! empty( $mails ) ) {
							foreach ( $mails as $mail ) {
								if ( in_array( $mail->id, $available_emails ) && 'no' !== $mail->enabled ) {
									echo '<option value="send_email_'. esc_attr( $mail->id ) .'">' . esc_html( $mail->title ) . '</option>';
								}
							}
						}
						?>
					</optgroup>

					<option value="generate_pdf"><?php esc_html_e( 'Generate PDF', 'woocommerce-pdf-product-vouchers' ); ?></option>
					<?php if ( $voucher->is_editable() && ! $voucher->has_redemptions() ) : ?>
					<option value="calculate_product_tax"><?php esc_html_e( 'Calculate Taxes', 'woocommerce-pdf-product-vouchers' ); ?></option>
					<?php endif; ?>

					<?php
						/**
						 * Filter voucher actions in voucher action meta box
						 *
						 * @since 3.0.0-1
						 * @param array
						 */
						$actions = apply_filters( 'woocommerce_pdf_product_vouchers_voucher_actions', array() );
					?>

					<?php foreach( $actions as $action => $title ) : ?>
						<option value="<?php echo $action; ?>"><?php echo $title; ?></option>
					<?php endforeach; ?>
				</select>

				<button class="button wc-reload" title="<?php esc_attr_e( 'Apply', 'woocommerce-pdf-product-vouchers' ); ?>"><span><?php esc_html_e( 'Apply', 'woocommerce-pdf-product-vouchers' ); ?></span></button>
			</li>

			<li class="wide">
				<div id="delete-action"><?php

					if ( current_user_can( 'delete_post', $post->ID ) ) {

						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = esc_html__( 'Delete Permanently', 'woocommerce-pdf-product-vouchers' );
						} else {
							$delete_text = esc_html__( 'Move to Trash', 'woocommerce-pdf-product-vouchers' );
						}
						?><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo $delete_text; ?></a><?php
					}
				?></div>

				<input type="submit" class="button save_voucher button-primary tips" name="save" value="<?php esc_html_e( 'Save Gift Certificate', 'woocommerce-pdf-product-vouchers' ); ?>" data-tip="<?php esc_html_e( 'Save/update the gift certificate', 'woocommerce-pdf-product-vouchers' ); ?>" />
			</li>

			<?php
				/**
				 * Triggered at the end of the voucher actions meta box
				 *
				 * @since 3.0.0
				 * @param int $voucher_id Voucher (post) ID
				 */
				do_action( 'woocommerce_pdf_product_vouchers_voucher_actions_end', $post->ID );
			?>

		</ul>
		<?php
	}


	/**
	 * Processs and saves meta box data
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param int $post_id post identifier
	 * @param \WP_Post $post the post object
	 */
	public function save( $post_id, \WP_Post $post ) {
		// Order data saved, now get it so we can manipulate status
		$voucher = wc_pdf_product_vouchers_get_voucher( $post_id );

		// Handle button actions
		if ( ! empty( $_POST['wc_voucher_action'] ) ) {

			$action = wc_clean( $_POST['wc_voucher_action'] );

			if ( strstr( $action, 'send_email_' ) ) {

				/**
				 * Fires before resending voucher emails from admin
				 *
				 * @since 3.0.0
				 * @param WC_Voucher $voucher
				 */
				do_action( 'woocommerce_pdf_product_vouchers_before_resend_voucher_emails', $voucher );

				// Load mailer
				$mailer = WC()->mailer();

				$email_to_send = str_replace( 'send_email_', '', $action );

				$mails = $mailer->get_emails();

				if ( ! empty( $mails ) ) {
					foreach ( $mails as $mail ) {
						if ( $mail->id == $email_to_send ) {

							$mail->trigger( $voucher->get_id() );

							/* translators: %s - email title */
							$voucher->add_note( sprintf( __( '%s email notification manually sent.', 'woocommerce-pdf-product-vouchers' ), $mail->title ), false, true );
						}
					}
				}

				/**
				 * Fires after resending voucher emails from admin
				 *
				 * @since 3.0.0
				 * @param WC_Voucher $voucher the voucher objct
				 * @param string $email_to_send email identifier
				 */
				do_action( 'woocommerce_pdf_product_vouchers_after_resend_voucher_emails', $voucher, $email_to_send );

				// change the post saved message
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'generate_pdf' === $action ) {

				// regenerate the PDF
				try {
					$voucher->generate_pdf();

					// change the post saved message
					add_filter( 'redirect_post_location', array( __CLASS__, 'set_pdf_generated_message' ) );

				} catch( Framework\SV_WC_Plugin_Exception $e ) {
					/* Translators: %s - error message */
					wc_pdf_product_vouchers()->get_message_handler()->add_error( sprintf( __( 'Could not generate gift certificate PDF: %s', 'woocommerce-pdf-product-vouchers' ), $e->getMessage() ) );
				}

			} elseif ( 'calculate_product_tax' === $action ) {

				// recalculate & update taxes
				if ( $voucher->is_editable() && ! $voucher->has_redemptions() ) {

					$new_tax = $voucher->calculate_product_tax();

					if ( $new_tax != $voucher->get_product_tax() ) {
						update_post_meta( $post_id, '_product_tax', $new_tax );
					}
				}

			} else {

				if ( ! did_action( 'woocommerce_pdf_product_vouchers_voucher_action_' . sanitize_title( $action ) ) ) {

					/**
					 * Fires when triggering a custom action in voucher admin
					 *
					 * @since 3.0.0
					 * @param WC_Voucher $voucher the voucher object
					 */
					do_action( 'woocommerce_pdf_product_vouchers_voucher_action_' . sanitize_title( $action ), $voucher );
				}
			}
		}
	}


	/**
	 * Sets the correct message ID for when email was sent
	 *
	 * @since 3.0.0
	 * @param string $location
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}


	/**
	 * Sets the correct message ID for when the PDF was generated
	 *
	 * @since 3.0.0
	 * @param string $location
	 * @return string
	 */
	public static function set_pdf_generated_message( $location ) {
		return add_query_arg( 'message', 12, $location );
	}


}
