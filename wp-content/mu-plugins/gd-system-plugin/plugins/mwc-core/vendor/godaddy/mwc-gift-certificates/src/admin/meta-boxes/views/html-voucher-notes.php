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
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Voucher notes template
 *
 * @type WC_Voucher $voucher current voucher instance
 *
 * @since 3.0.0
 * @version 3.5.0
 */

$notes = $voucher->get_notes();
?>

<ul class="voucher-notes">

	<?php if ( $notes ) : ?>

		<?php
			foreach ( $notes as $note ) {

				/**
				 * Allow actors to adjust the voucher note class
				 *
				 * @since 3.0.0
				 * @param array $classes Array of note classes
				 * @param string $note Voucher note
				 * @param WC_Voucher $voucher Voucher instance
				 */
				$note_classes = apply_filters( 'wc_pdf_product_vouchers_voucher_note_class', array( 'note' ), $note, $voucher );

				include( wc_pdf_product_vouchers()->get_plugin_path() . '/src/admin/meta-boxes/views/html-voucher-note.php' );

			}
		?>

	<?php else: ?>

		<li class="no-notes" style="<?php echo ( $notes ? 'display:none;' : '' ); ?>">
			<?php esc_html_e( 'There are no notes yet.', 'woocommerce-pdf-product-vouchers' ); ?>
		</li>

	<?php endif; ?>

</ul>
