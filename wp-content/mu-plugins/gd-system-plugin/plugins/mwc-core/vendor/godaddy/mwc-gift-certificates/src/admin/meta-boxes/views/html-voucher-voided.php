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

$user_id = get_post_meta( $voucher->get_id(), '_voided_by', true );
$user    = $user_id  ? get_user_by( 'id', $user_id ) : null;
?>

<?php if ( $voucher->has_status( 'voided' ) ) : ?>
<tbody id="voucher-void">
	<tr class="void">

		<td class="thumb"><div></div></td>

		<td class="item" colspan="3">
			<?php esc_html_e( 'Remaining Value Voided', 'woocommerce-pdf-product-vouchers' ); ?>

			<table class="redemption-meta">
				<tr>
					<th><?php esc_html_e( 'Date', 'woocommerce-pdf-product-vouchers' ); ?>:</th>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $voucher->get_voided_date( 'timestamp' ) ) ); ?></td>
				</tr>

				<?php if ( $user ) : ?>
				<tr>
					<th><?php esc_html_e( 'Added By', 'woocommerce-pdf-product-vouchers' ); ?>:</th>
					<td>
						<a href="<?php echo get_edit_user_link( $user->ID ); ?>"><?php echo esc_attr( $user->display_name ); ?></a>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( $reason = $voucher->get_void_reason() ) : ?>
				<tr class="view">
					<th><?php esc_html_e( 'Reason', 'woocommerce-pdf-product-vouchers' ); ?>:</th>
					<td><?php echo wp_kses_post( $reason ); ?></td>
				</tr>
				<?php endif; ?>

			</table>
		</td>

		<td class="value" width="1%">
			<?php echo wc_price( $voucher->get_remaining_value(), array( 'currency' => $voucher->get_voucher_currency() ) ); ?>
		</td>

		<td class="tax" width="1%">
			<?php echo wc_price( $voucher->get_remaining_value() * $voucher->get_tax_rate(), array( 'currency' => $voucher->get_voucher_currency() ) ); ?>
		</td>

		<td class="actions" width="1%"></td>

	</tr>
</tbody>
<?php endif; ?>

