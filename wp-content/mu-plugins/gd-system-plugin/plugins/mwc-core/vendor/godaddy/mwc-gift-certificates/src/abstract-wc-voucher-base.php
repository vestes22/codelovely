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

namespace GoDaddy\WordPress\MWC\GiftCertificates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * WooCommerce Voucher Base class
 *
 * The WooCommerce PDF Product Voucher Base class acts as an abstract
 * class for both the vouchers and voucher templates.
 *
 * @since 3.0.0
 */
abstract class MWC_Gift_Certificate_Base {


	/** @var int post id */
	public $id;

	/** @var \WP_Post post object */
	public $post;

	/** @var string post type */
	public $post_type;


	/**
	 * Construct with $id
	 *
	 * @since 3.0.0
	 *
	 * @param int|\WP_Post|WC_Voucher|WC_Voucher_Template $id voucher/template id or post object
	 */
	public function __construct( $id ) {

		if ( ! $id ) {
			return;
		}

		if ( is_numeric( $id ) ) {
			$this->post = get_post( $id );
		} elseif ( is_object( $id ) ) {
			$this->post = $id;
		}

		if ( $this->post ) {
			$this->id = $this->post->ID;
		}

	}


	/**
	 * Returns the voucher or template identifier
	 *
	 * @since 3.0.0
	 *
	 * @return int the object id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the image id for the voucher or template
	 *
	 * @since 3.0.0
	 *
	 * @return int image (attachment) id
	 */
	public function get_image_id() {
		return get_post_meta( $this->id, '_thumbnail_id', true );
	}


	/**
	 * Returns the full image url for the voucher or template
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_image_url() {
		return wp_get_attachment_url( $this->get_image_id() );
	}


	/**
	 * Returns the full image path on filesystem for the voucher or template
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public function get_image_path() {
		return get_attached_file( $this->get_image_id() );
	}


	/**
	 * Returns the primary voucher/template image, or a placeholder
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.0.0
	 *
	 * @return string voucher primary img tag
	 */
	public function get_image( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {
		global $_wp_additional_image_sizes;

		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size );
		} else {
			$width = isset( $_wp_additional_image_sizes[ $size ] ) ? $_wp_additional_image_sizes[ $size ]['width'] : MWC_Gift_Certificates::VOUCHER_IMAGE_THUMB_WIDTH;
			$image = '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce-pdf-product-vouchers' ) . '" width="' . $width . '" />';
		}

		return $image;
	}


	/**
	 * Returns the additional image ID for the voucher or template
	 *
	 * @since 3.0.0
	 *
	 * @return int image (attachment) id
	 */
	abstract public function get_additional_image_id();


	/**
	 * Returns the full additional image url for the voucher or template
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_additional_image_url() {
		return wp_get_attachment_url( $this->get_additional_image_id() );
	}


	/**
	 * Returns the full additional image path on filesystem for the voucher or template
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public function get_additional_image_path() {
		return get_attached_file( $this->get_additional_image_id() );
	}


	/**
	 * Returns the voucher type
	 *
	 * @since 3.1.0
	 *
	 * @return string voucher type
	 */
	public function get_voucher_type() {

		$type = get_post_meta( $this->get_id(), '_voucher_type', true );

		return $type ? $type : 'single';
	}


}
