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

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Voucher Data Meta Box
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Meta_Box_Voucher_Notes {


	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}


	/**
	 * Adds the meta box
	 *
	 * @since 3.0.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-pdf-product-vouchers-voucher-notes', __( 'Gift Certificate Notes', 'woocommerce-pdf-product-vouchers' ), array( $this, 'output' ), 'wc_voucher', 'side' );
	}


	/**
	 * Outputs meta box contents
	 *
	 * @since 3.0.0
	 */
	public function output() {
		global $post, $voucher;

		include( wc_pdf_product_vouchers()->get_plugin_path() . '/src/admin/meta-boxes/views/html-voucher-notes.php' );

		?>
		<div class="add-note">
			<h4><?php esc_html_e( 'Add note', 'woocommerce-pdf-product-vouchers' ); ?> <?php echo wc_help_tip( __( 'Add a note for your reference.', 'woocommerce-pdf-product-vouchers' ) ); ?></h4>
			<p>
				<textarea type="text" name="order_note" id="voucher-note" class="input-text" cols="20" rows="5"></textarea>
			</p>
			<p><a href="#" class="js-add-note button"><?php esc_html_e( 'Add Note', 'woocommerce-pdf-product-vouchers' ); ?></a></p>
		</div>
		<?php
	}


}
