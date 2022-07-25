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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin;

defined( 'ABSPATH' ) or exit;

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * MWC_Gift_Certificates_Admin_Barcode_Redeem_Vouchers
 * Add menu page to redeem vouchers using barcode.
 *
 * @since 3.5.0
 */
class MWC_Gift_Certificates_Admin_Barcode_Redeem_Vouchers {

	/**
	 * MWC_Gift_Certificates_Admin_Barcode_Redeem_Vouchers constructor.
	 * Initialize class and add action/filter hooks in constructor
	 *
	 * @since 3.5.0
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'voucher_redeem_page' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_sub_menu' ) );
		add_filter( 'parent_file', array( $this, 'highlight_parent_menu' ) );
	}


	/**
	 * Highlights `Gift Certificates` submenu entry as current active one.
	 *
	 * @internal
	 *
	 * @since 3.5.0
	 *
	 * @param string $menu_file
	 * @return string
	 */
	public function highlight_sub_menu( $menu_file ) {

		$screen = get_current_screen();

		if ( 'admin_page_wc-pdf-product-vouchers-redeem-voucher' === $screen->id ) {
			$menu_file = 'edit.php?post_type=wc_voucher';
		}

		return $menu_file;
	}


	/**
	 * Highlights WooCommerce as parent menu.
	 *
	 * @internal
	 *
	 * @since 3.5.0
	 *
	 * @param string $menu_file
	 * @return mixed
	 */
	public function highlight_parent_menu( $menu_file ) {
		global $plugin_page;

		$screen = get_current_screen();

		if ( 'admin_page_wc-pdf-product-vouchers-redeem-voucher' === $screen->id ) {
			$plugin_page = 'woocommerce';
		}

		return $menu_file;
	}


	/**
	 * Registers admin page to redeem vouchers using barcodes.
	 *
	 * @internal
	 *
	 * @since 3.5.0
	 */
	public function voucher_redeem_page() {

		if ( current_user_can( 'manage_woocommerce' ) ) {

			add_submenu_page(
				null,
				esc_html__( 'Redeem Gift Certificate', 'woocommerce-pdf-product-vouchers' ),
				esc_html__( 'Redeem Gift Certificate', 'woocommerce-pdf-product-vouchers' ),
				'redeem_woocommerce_vouchers',
				'wc-pdf-product-vouchers-redeem-voucher',
				[ $this, 'render_redeem_admin_page' ]
			);

		} else {

			// we register a top level admin page because users without the manage_woocommerce capability can't see the WooCommerce admin menu
			add_menu_page(
				esc_html__( 'Redeem Gift Certificate', 'woocommerce-pdf-product-vouchers' ),
				esc_html__( 'Redeem Gift Certificate', 'woocommerce-pdf-product-vouchers' ),
				'redeem_woocommerce_vouchers',
				'wc-pdf-product-vouchers-redeem-voucher',
				[ $this, 'render_redeem_admin_page' ],
				'dashicons-tickets-alt',
				'55.5'
			);
		}
	}


	/**
	 * Renders admin page to redeem via barcode.
	 *
	 * @internal
	 *
	 * @since 3.5.0
	 */
	public function render_redeem_admin_page() {

		?>
		<div class="wrap">
			<div class="barcode-voucher-wrap">
				<h1><?php esc_html_e( 'Redeem Gift Certificate', 'woocommerce-pdf-product-vouchers' ); ?></h1>
				<div class="text-italic">
					<p>
						<?php
						printf(
							/* translators: Placeholders: %1$s - <a>, %2$s - </a> tag */
							esc_html__( 'Redeem a gift certificate by scanning or entering a barcode value below. %1$sLearn more%2$s about using QR or barcodes in gift certificates.', 'woocommerce-pdf-product-vouchers' ),
							'<a href="' . esc_url( wc_pdf_product_vouchers()->get_documentation_url() ) . '#barcode-scanning" target="_blank">',
							'</a>'
						);
						?>
					</p>
				</div>
				<div class="notice notice-success is-dismissible js-redeem-message hidden"></div>

				<canvas id="canvas" hidden></canvas>

				<form id="redeem-voucher-form">
					<table class="form-table">
						<tbody>
							<tr>
								<td>
									<input type="button" id="scan-qr" value="<?php esc_html_e( 'Scan QR Code', 'woocommerce-pdf-product-vouchers' ); ?>" class="button-primary barcode-scan">
								</td>
							</tr>
						<tr>
							<th scope="row">
								<label for="barcode-value">
									<?php esc_html_e( 'Or enter barcode', 'woocommerce-pdf-product-vouchers' ); ?>
								</label>
							</th>
							<td>
								<input type="text" name="barcode-value" value="" id="barcode-value" class="barcode-value" autocomplete="off" required autofocus>
								<input type="submit" value="<?php esc_html_e( 'Redeem', 'woocommerce-pdf-product-vouchers' ); ?>" class="button-primary" name="Redeem" id="redeem-voucher">
							</td>
						</tr>
						<tr id="redemption-amount-row" class="hidden">
							<th scope="row">
								<label for="redemption-amount">
									<?php esc_html_e( 'Redemption amount', 'woocommerce-pdf-product-vouchers' ); ?>
								</label>
							</th>
							<td>
								<input type="number" name="redemption-amount" value="" id="redemption-amount">
							</td>
						</tr>
						</tbody>
					</table>
					<?php wp_nonce_field( 'redeem-voucher', 'redeem-voucher-from-barcode' ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
