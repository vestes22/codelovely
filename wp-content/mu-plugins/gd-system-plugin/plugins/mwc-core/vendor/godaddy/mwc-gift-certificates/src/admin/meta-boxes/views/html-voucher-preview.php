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
 * Voucher preview admin template
 *
 * @type WC_Voucher $voucher current voucher instance
 * @type string $preview_image HTML
 * @type bool $unsaved_preview
 *
 * @since 3.0.0
 * @version 3.5.0
 */

$voucher_template = $voucher->get_template();

?>

<input type="hidden" name="post_parent" value="<?php echo esc_attr( $voucher->get_template_id() ); ?>" />
<input type="hidden" name="_thumbnail_id" id="_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
<input type="hidden" id="_voucher_image_options" value="<?php echo $voucher_template ? esc_attr( implode( ',', $voucher_template->get_image_ids() ) ) : ''; ?>" />

<?php if ( ! $voucher->file_exists() ) : ?>

	<p><?php esc_html_e( 'Gift certificate PDF has not been generated yet. You can generate it using the Gift Certificate Actions.', 'woocommerce-pdf-product-vouchers' ) ; ?></p>

<?php else : ?>

	<div class="preview-image-container"><?php echo $preview_image; ?></div>

	<?php if ( $voucher_template && count( $voucher_template->get_image_ids() ) > 1 ) : ?>
		<p><a href="#" class="js-select-voucher-image"><?php esc_html_e( 'Change gift certificate image', 'woocommerce-pdf-product-vouchers' ); ?></a></p>
	<?php endif; ?>

	<?php if ( $unsaved_preview ) : ?>
		<p class="unsaved-notice"><?php esc_html_e( 'Please save the gift certificate to regenerate the PDF.', 'woocommerce-pdf-product-vouchers' ); ?></p>
	<?php else: ?>
		<p><a href="<?php echo esc_url( $voucher->get_download_url( 'admin' ) ); ?>">
			<?php esc_html_e( 'View Gift Certificate PDF', 'woocommerce-pdf-product-vouchers' ); ?>
		</a></p>
	<?php endif; ?>

<?php endif; ?>
