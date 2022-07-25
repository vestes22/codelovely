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
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Voucher balance meta box admin template
 *
 * @type WC_Voucher $voucher current voucher instance
 *
 * @since 3.0.0
 * @version 3.5.0
 */
?>

<div class="wc-voucher-balance-wrapper wc-voucher-items-editable">
	<table cellpadding="0" cellspacing="0" class="wc-voucher-balance">

		<thead>
			<tr>
				<th class="item" colspan="2"><?php esc_html_e( 'Item', 'woocommerce-pdf-product-vouchers' ); ?></th>
				<th class="value"><?php esc_html_e( 'Value', 'woocommerce-pdf-product-vouchers' ); ?></th>
				<th class="quantity"><?php esc_html_e( 'Qty', 'woocommerce-pdf-product-vouchers' ); ?></th>
				<th class="total"><?php esc_html_e( 'Total', 'woocommerce-pdf-product-vouchers' ); ?></th>
				<th class="tax"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
				<th class="wc-voucher-item-actions" width="1%">&nbsp;</th>
			</tr>
		</thead>

		<?php include( 'html-voucher-product.php'); ?>

		<?php include( 'html-voucher-redemptions.php'); ?>

		<?php include( 'html-voucher-voided.php'); ?>

	</table>
</div>

<div class="wc-voucher-data-row wc-voucher-totals-wrapper">
	<?php include( 'html-voucher-totals.php'); ?>
</div>

<?php if ( $voucher->is_editable() ) : ?>
<div class="wc-voucher-data-row wc-voucher-redeem-wrapper wc-voucher-data-row-toggle" style="display: none;">
	<?php include( 'html-voucher-redeem.php'); ?>
</div>
<div class="wc-voucher-data-row wc-voucher-void-wrapper wc-voucher-data-row-toggle" style="display: none;">
	<?php include( 'html-voucher-void.php'); ?>
</div>
<?php endif; ?>

<div class="wc-voucher-data-row wc-voucher-balance-actions wc-voucher-data-row-toggle">
	<p class="actions">

		<?php if ( $voucher->is_editable() ) : ?>
			<button type="button" class="button js-void-action"><?php esc_html_e( 'Void remaining value', 'woocommerce-pdf-product-vouchers' ); ?></button>
			<button type="button" class="button js-calculate-tax-action"><?php esc_html_e( 'Calculate Taxes', 'woocommerce-pdf-product-vouchers' ); ?></button>
			<button type="button" class="button button-primary js-redeem-action"><?php esc_html_e( 'Redeem', 'woocommerce-pdf-product-vouchers' ); ?></button>
			<span class="description js-customer-changed-notice" style="display:none"><?php echo wc_help_tip( __( 'To calculate taxes or redeem this gift certificate, please save it first.', 'woocommerce-pdf-product-vouchers' ) ); ?></span>
		<?php elseif ( $voucher->has_status('redeemed') )  : ?>
			<span class="description"><?php echo wc_help_tip( __( 'To edit this gift certificate change the status back to "Pending"', 'woocommerce-pdf-product-vouchers' ) ); ?> <?php esc_html_e( 'This gift certificate has been fully redeemed.', 'woocommerce-pdf-product-vouchers' ); ?></span>
		<?php elseif ( $voucher->has_status('voided') )  : ?>
			<button type="button" class="button restore-action js-restore-action"><?php esc_html_e( 'Restore voided balance', 'woocommerce-pdf-product-vouchers' ); ?></button>
			<span class="description"><?php echo wc_help_tip( __( 'To edit this gift certificate change the status back to "Pending"', 'woocommerce-pdf-product-vouchers' ) ); ?> <?php esc_html_e( 'This gift certificate has been voided and can no longer be redeemed.', 'woocommerce-pdf-product-vouchers' ); ?></span>
		<?php endif; ?>

		<?php
			/**
			 * Triggered after rendering the voucher balance action buttons
			 *
			 * @since 3.0.0
			 * @param WC_Voucher $voucher
			 */
			do_action( 'wc_pdf_product_vouchers_voucher_balance_action_buttons', $voucher );
		?>
	</p>
</div>

<div class="wc-voucher-data-row wc-voucher-edit-redemption-actions wc-voucher-data-row-toggle" style="display:none;">
	<p class="actions">
		<button type="button" class="button js-cancel-action"><?php esc_html_e( 'Cancel', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<button type="button" class="button button-primary js-save-redemptions-action"><?php esc_html_e( 'Save', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<?php
			/**
			 * Triggered after rendering the action buttons when editing a redemption
			 *
			 * @since 3.0.0
			 * @param WC_Voucher $voucher
			 */
			do_action( 'wc_pdf_product_vouchers_voucher_edit_redemption_action_buttons', $voucher );
		?>
	</p>
</div>

<div class="wc-voucher-data-row wc-voucher-redeem-actions wc-voucher-data-row-toggle" style="display:none;">
	<p class="actions">
		<button type="button" class="button js-cancel-action"><?php esc_html_e( 'Cancel', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<button type="button" class="button button-primary js-redeem-voucher-action"><?php esc_html_e( 'Redeem', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<?php
			/**
			 * Triggered after rendering the action buttons for adding a voucher redemption
			 *
			 * @since 3.0.0
			 * @param WC_Voucher $voucher
			 */
			do_action( 'wc_pdf_product_vouchers_voucher_redeem_action_buttons', $voucher );
		?>
	</p>
</div>

<div class="wc-voucher-data-row wc-voucher-void-actions wc-voucher-data-row-toggle" style="display:none;">
	<p class="actions">
		<button type="button" class="button js-cancel-action"><?php esc_html_e( 'Cancel', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<button type="button" class="button button-primary js-void-voucher-action"><?php esc_html_e( 'Void', 'woocommerce-pdf-product-vouchers' ); ?></button>
		<?php
			/**
			 * Triggered after rendering the action buttons for voiding a voucher
			 *
			 * @since 3.0.0
			 * @param WC_Voucher $voucher
			 */
			do_action( 'wc_pdf_product_vouchers_voucher_void_action_buttons', $voucher );
		?>
	</p>
</div>
