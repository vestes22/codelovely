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
 * @package   WC-PDF-Product-Vouchers/Admin
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Admin
 *
 * In 3.0.0 renamed from MWC_Gift_Certificates_Admin_Voucher_Templates
 * to MWC_Gift_Certificates_Admin_Voucher_Templates.
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Admin_Voucher_Templates {


	/**
	 * Initializes the voucher templates admin
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		add_action( 'publish_wc_voucher_template', array( $this, 'wc_voucher_template_private' ) );

		// redirect voucher template to customizer
		add_action( 'add_meta_boxes_wc_voucher_template', array( $this, 'redirect_voucher_template_to_customizer' ) );
	}


	/**
	 * Automatically makes the voucher posts private when they are published
	 *
	 * That way we can have them be publicly_queryable for the purposes of
	 * generating a preview pdf for the admin user, while having them always
	 * hidden on the frontend (draft posts are not visible by definition).
	 *
	 * @since 1.2.0
	 * @param int $post_id the voucher post identifier
	 */
	public function wc_voucher_template_private( $post_id ) {
		global $wpdb;

		$wpdb->update( $wpdb->posts, array( 'post_status' => 'private' ), array( 'ID' => $post_id ) );
	}


	/**
	 * Redirects edit/new voucher template screen to customizer
	 *
	 * @since 3.0.0
	 * @param \WP_Post $post the post object
	 */
	public function redirect_voucher_template_to_customizer( $post ) {
		global $pagenow;

		// "bootstrap" the auto draft
		if ( 'post-new.php' === $pagenow ) {

			// make sure the auto-draft title is empty, so that we can display the placeholder
			// instead of "Auto Draft"
			wp_update_post( array(
				'ID'          => $post->ID,
				'post_title'  => '',
			) );

			// set voucher image to the default voucher image ID
			$image_id = get_option( 'wc_pdf_product_vouchers_default_voucher_image' );

			update_post_meta( $post->ID, '_thumbnail_id', $image_id );
			update_post_meta( $post->ID, '_image_ids', array( $image_id ) );

			/** install default settings for the template. @see MWC_Gift_Certificates_Customizer::add_customizer_settings() */
			// TODO: refactor so that the defaults will be automatically set based on the settings added to customizer {IT 2017-02-08}
			update_post_meta( $post->ID, '_voucher_font_family', 'Helvetica' );
			update_post_meta( $post->ID, '_voucher_font_size', 16 );
			update_post_meta( $post->ID, '_voucher_text_align', 'left' );
			update_post_meta( $post->ID, '_voucher_font_color', '#000000' );
			update_post_meta( $post->ID, '_voucher_image_dpi', 300 );
			update_post_meta( $post->ID, '_voucher_type', 'single' );
			update_post_meta( $post->ID, '_allow_online_redemptions', 1 );

			// set default styles if the image has them
			$default_styles = get_post_meta( $image_id, '_wc_voucher_template_default_styles', true );

			if ( ! empty( $default_styles ) ) {
				foreach ( $default_styles as $field => $styles ) {
					foreach ( $styles as $style => $value ) {
						update_post_meta( $post->ID, '_' . $field . '_' . $style, $value );
					}
				}
			}
		}

		// redirect to the customizer
		wp_safe_redirect( get_edit_post_link( $post ) );
		exit;
	}


}
