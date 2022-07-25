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

/**
 * The frontend my account vouchers template
 *
 * @version 3.0.0
 * @since 3.0.0
 */

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_account_vouchers_columns;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_customer_available_vouchers;

defined( 'ABSPATH' ) or exit;

$vouchers     = wc_pdf_product_vouchers_get_customer_available_vouchers();
$has_vouchers = (bool) $vouchers;

/**
 * Fires before the vouchers section under my account
 *
 * @since 3.0.0
 * @param bool $has_vouchers
 */
do_action( 'wc_pdf_product_vouchers_before_account_vouchers', $has_vouchers ); ?>

<?php if ( $has_vouchers ) : ?>

	<?php
		/**
		 * Fires before available vouchers under my account
		 *
		 * @since 3.0.0
		 */
		do_action( 'wc_pdf_product_vouchers_before_available_vouchers' );
	?>

	<table class="woocommerce-MyAccount-vouchers shop_table shop_table_responsive">

		<thead>
			<tr>
				<?php foreach ( wc_pdf_product_vouchers_get_account_vouchers_columns() as $column_id => $column_name ) : ?>
					<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<?php foreach ( $vouchers as $voucher ) : ?>
			<tr id="<?php echo esc_attr( $voucher->get_voucher_number() ); ?>">
				<?php foreach ( wc_pdf_product_vouchers_get_account_vouchers_columns() as $column_id => $column_name ) : ?>

					<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">

						<?php if ( has_action( "wc_pdf_product_vouchers_account_vouchers_column_{$column_id}" ) ) : ?>
							<?php
								/**
								 * Fires for a custom voucher column under My Account > Vouchers
								 *
								 * @since 3.0.0
								 * @param WC_Voucher $voucher
								 */
								do_action( "wc_pdf_product_vouchers_account_vouchers_column_{$column_id}", $voucher );
							?>

						<?php elseif ( 'voucher-number' === $column_id ) : ?>
							<?php echo esc_html( $voucher->get_voucher_number() ); ?>

						<?php elseif ( 'voucher-expires' === $column_id ) : ?>

							<?php if ( $voucher->get_expiration_date() ) : ?>
								<time datetime="<?php echo date( 'Y-m-d', $voucher->get_expiration_date( 'timestamp' ) ); ?>" title="<?php echo esc_attr( $voucher->get_expiration_date( 'timestamp' ) ); ?>"><?php echo esc_html( $voucher->get_formatted_expiration_date() ); ?></time>
							<?php else : ?>
								<?php esc_html_e( 'Never', 'woocommerce-pdf-product-vouchers' ); ?>
							<?php endif; ?>

						<?php elseif ( 'voucher-remaining' === $column_id ) : ?>
							<?php echo wp_kses_post( wc_price( $voucher->get_remaining_value_for_display(), [ 'currency' => $voucher->get_voucher_currency() ] ) ); ?>

						<?php elseif ( 'voucher-actions' === $column_id ) : ?>
							<?php
								$actions = array(
									'download'  => array(
										'url'  => $voucher->get_download_url(),
										'name' => __( 'Download', 'woocommerce-pdf-product-vouchers' ),
									),
								);

								/**
								 * Filter voucher actions under My Account > Vouchers
								 *
								 * @since 3.0.0
								 * @param array $actions
								 * @param WC_Voucher $voucher
								 */
								if ( $actions = apply_filters( 'wc_pdf_product_vouchers_account_vouchers_actions', $actions, $voucher ) ) {
									foreach ( $actions as $key => $action ) {
										echo '<a href="' . esc_url( $action['url'] ) . '" class="button woocommerce-Button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
									}
								}
							?>

						<?php endif; ?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</table>

	<?php
		/**
		 * Fires after the available vouchers under my account
		 *
		 * @since 3.0.0
		 */
		do_action( 'wc_pdf_product_vouchers_after_available_vouchers' );
	?>

<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">

		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Go Shop', 'woocommerce-pdf-product-vouchers' ) ?>
		</a>

		<?php esc_html_e( 'No gift certificates available yet.', 'woocommerce-pdf-product-vouchers' ); ?>
	</div>
<?php endif; ?>

<?php
/**
 * Fires after the vouchers section under my account
 *
 * @since 3.0.0
 * @param bool $has_vouchers
 */
do_action( 'wc_pdf_product_vouchers_after_account_vouchers', $has_vouchers );
?>
