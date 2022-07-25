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
 * Voucher product admin template
 *
 * @type WC_Voucher $voucher current voucher instance
 *
 * @since 3.0.0
 * @version 3.5.0
 */

$name      = $voucher->get_product_name();
$value     = $voucher->get_product_price();
$tax       = $voucher->get_product_tax();
$quantity  = $voucher->get_product_quantity();
$product   = $voucher->get_product();
$edit_link = $product ? get_edit_post_link( $product->get_id() ) : null;
$thumbnail = $product ? $product->get_image( 'thumbnail', array( 'title' => '' ) ) : null;

?>

<tbody id="voucher-product">
	<tr class="product">

		<td class="thumb">
			<div class="wc-voucher-product-thumbnail"><?php echo wp_kses_post( $thumbnail ); ?></div>
		</td>

		<td class="item">
			<?php

				if ( $edit_link ) {
					echo '<a href="' . esc_url( $edit_link ) . '" class="wc-voucher-item-name">' .  esc_html( $name ) . '</a>';
				} else {
					echo '<span class="wc-voucher-item-name">' .  esc_html( $name ) . '</span>';
				}

				if ( $product && $product->get_sku() ) {
					echo '<div class="wc-voucher-item-sku"><strong>' . esc_html__( 'SKU:', 'woocommerce-pdf-product-vouchers' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>';
				}
			?>
			<input type="hidden" class="_product_id" name="_product_id" value="<?php echo esc_attr( $voucher->get_product_id() ); ?>" />
		</td>

		<td class="value" width="1%">
			<?php echo wc_price( $value, array( 'currency' => $voucher->get_voucher_currency() ) ); ?>
			<input type="hidden" id="voucher_product_price" name="_product_price" value="<?php echo wc_format_localized_price( $value ); ?>" />
			<input type="hidden" id="voucher_product_price_for_display" value="<?php echo wc_format_localized_price( $voucher->get_product_price_for_display() ); ?>" />
		</td>

		<td class="quantity" width="1%">
			&times;<?php echo esc_html( $quantity ); ?>
		</td>

		<td class="total" width="1%">
			<?php echo wc_price( $value * $quantity, array( 'currency' => $voucher->get_voucher_currency() ) ); ?>
		</td>

		<td class="tax" width="1%">
			<?php echo wc_price( $tax * $quantity, array( 'currency' => $voucher->get_voucher_currency() ) ); ?>
			<input type="hidden" name="_product_tax" value="<?php echo wc_format_localized_price( $tax ); ?>" />
		</td>


		<td class="actions" width="1%">
			<?php if ( $voucher->is_editable() ) : ?>
				<?php if ( ! $voucher->has_redemptions() && ! $voucher->get_order_id() ) : ?>
					<div class="wc-voucher-edit-item-actions">
						<a class="edit-voucher-item tips js-edit-voucher-product" href="#" data-tip="<?php esc_attr_e( 'Edit product', 'woocommerce-pdf-product-vouchers' ); ?>"></a>
					</div>
				<?php elseif ( $voucher->get_order_id() ) : ?>
					<?php echo wc_help_tip( __( 'This gift certificate was purchased in an order, so the product cannot be changed.', 'woocommerce-pdf-product-vouchers' ) ); ?>
				<?php elseif ( $voucher->has_redemptions() ) : ?>
					<?php echo wc_help_tip( __( 'This gift certificate has redemptions, so the product can no longer be changed.', 'woocommerce-pdf-product-vouchers' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>
		</td>

	</tr>
</tbody>
