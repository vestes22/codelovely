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

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Product Redemption Vouchers Meta Box
 *
 * @since 3.4.0
 */
class MWC_Gift_Certificates_Meta_Box_Product_Redemption_Vouchers {


	/**
	 * Sets up the meta box.
	 *
	 * @since 3.4.0
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ), 30 );
	}


	/**
	 * Adds the meta box.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-pdf-product-vouchers-product-redemption-vouchers', __( 'Redemption gift certificates', 'woocommerce-pdf-product-vouchers' ), array( $this, 'output' ), 'product', 'side' );
	}


	/**
	 * Outputs meta box contents.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param \WP_Post $post the post object
	 */
	public function output( $post ) {
		$voucher_template_ids = explode(',', get_post_meta( $post->ID, '_wc_pdf_product_vouchers_redeemable_by', true ) );
		$voucher_templates    = ! empty( $voucher_template_ids ) ? array_filter( array_map( '\GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_template', $voucher_template_ids ) ) : array();
		?>

		<label for="_wc_pdf_product_vouchers_redeemable_by"><?php esc_html_e( 'Redeemable by', 'woocommerce-pdf-product-vouchers' ); ?></label>

		<?php echo wc_help_tip( __( 'Select any single-purpose PDF product gift certificates that can be used to redeem this product online.', 'woocommerce-pdf-product-vouchers' ) ); ?>

		<p>
			<select
					name="_wc_pdf_product_vouchers_redeemable_by[]"
					id="_wc_pdf_product_vouchers_redeemable_by"
					class="sv-wc-enhanced-search"
					style="min-width: 100%;"
					multiple="multiple"
					data-action="wc_pdf_product_vouchers_json_search_single_purpose_voucher_templates"
					data-nonce="<?php echo wp_create_nonce( 'search-voucher-templates' ); ?>"
					data-placeholder="<?php esc_attr_e( 'Search for a gift certificate template&hellip;', 'woocommerce-pdf-product-vouchers' ); ?>"
					data-allow_clear="true">
				<?php if ( ! empty( $voucher_templates ) ) : ?>
					<?php foreach( $voucher_templates as $voucher_template ) : ?>
						<option value="<?php echo esc_attr( $voucher_template->get_id() ); ?>" selected><?php echo esc_html( $voucher_template->get_name() ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</p>

		<?php

		Framework\SV_WC_Helper::render_select2_ajax();
	}


	/**
	 * Processes and saves meta box data.
	 *
	 * @internal
	 *
	 * @since 3.4.0
	 *
	 * @param int $post_id post identifier
	 */
	public function save( $post_id ) {

		$voucher_template_ids = ! empty( $_POST['_wc_pdf_product_vouchers_redeemable_by'] ) ? $_POST['_wc_pdf_product_vouchers_redeemable_by'] : null;

		if ( ! empty( $voucher_template_ids ) ) {
			$voucher_template_ids = implode( ',', array_map( 'absint', $voucher_template_ids ) );
		}

		update_post_meta( $post_id, '_wc_pdf_product_vouchers_redeemable_by', $voucher_template_ids );
	}


}
