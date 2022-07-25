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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Customizer;

defined( 'ABSPATH' ) or exit;

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_template;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Voucher Template Setting
 *
 * This class handles saving and getting the voucher template setting
 * values for the Customizer.
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Voucher_Template_Setting extends \WP_Customize_Setting {


	/** @var string custom setting type */
	public $type = 'wc_voucher_template';

	/** @var string transport type, default to postMessage */
	public $transport = 'postMessage';


	/**
	 * Returns the root value for a setting.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $default value to return if root does not exist
	 * @return mixed
	 */
	protected function get_root_value( $default = null ) {

		// get the current post_id
		$post_id = wc_pdf_product_vouchers()->get_customizer_instance()->get_currently_previewed_voucher_template_id();

		if ( ! $post_id ) {
			return;
		}

		$voucher_template = wc_pdf_product_vouchers_get_voucher_template( $post_id );
		$key              = str_replace( 'wc_voucher_template_', '', $this->id );

		switch ( $key ) {

			case 'post_title':
				return get_the_title( $post_id );

			case 'voucher_primary_image':

				$image_id = $voucher_template ? $voucher_template->get_image_id() : 0;
				$src      = $image_id > 0 ? wp_get_attachment_image_src( $image_id, 'full' ) : null;

				return $src ? array(
					'id'     => $image_id,
					'src'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
				) : array();

			case 'voucher_additional_image':
				return $voucher_template ? $voucher_template->get_additional_image_id() : 0;

			case 'logo_image_id':
				return $voucher_template ? $voucher_template->get_logo_id() : 0;

			case 'voucher_images':
				return $voucher_template ? $voucher_template->get_image_ids() : array();

			default:
				if ( metadata_exists( 'post', $post_id, '_' . $key ) ) {
					return get_post_meta( $post_id, '_' . $key, true );
				}
		}

		return $default;
	}


	/**
	 * Saves the value of the setting
	 *
	 * @since 3.0.0
	 * @param mixed $value the value to update
	 * @return bool the result of saving the value
	 */
	protected function update( $value ) {

		$post_id = $this->get_voucher_template_id_from_referrer();

		// sanity check for valid post type
		if ( ! $post_id || 'wc_voucher_template' !== get_post_type( $post_id ) ) {
			return;
		}

		$key = str_replace( 'wc_voucher_template_', '', $this->id );

		// handle setting the font_size back to teh default value
		if ( Framework\SV_WC_Helper::str_ends_with( $key, '_font_size' ) && ! $value ) {
			return delete_post_meta( $post_id, '_' . $key );
		}

		switch ( $key ) {

			case 'post_title':
				return wp_update_post( array(
					'ID'          => $post_id,
					'post_title'  => $value,
					'post_status' => 'private', // voucher template requires a name to be "published" - this hopefully helps admins to distinguish between our quasi-auto-drafts and actual voucher templates
				) );

			case 'voucher_primary_image':

				if ( 'remove-image' === $value || ! $value || empty( $value['id'] ) ) {
					return delete_post_thumbnail( $post_id );
				} else {
					return set_post_thumbnail( $post_id, $value['id'] );
				}

			case 'voucher_additional_image':

				$value = attachment_url_to_postid( $value );

				return update_post_meta( $post_id, '_additional_image_id', $value );

			case 'logo_image_id':

				$value = attachment_url_to_postid( $value );

				return update_post_meta( $post_id, '_logo_image_id', $value );

			case 'voucher_images':

				return update_post_meta( $post_id, '_image_ids', $value );

			default:
				return update_post_meta( $post_id, '_' . $key, $value );

		}
	}


	/**
	 * Returns the voucher template id from the referer
	 *
	 * Unfortunately, WP Customizer does not seem to provide a clean way to
	 * pass the post_id to the AJAX POST call for customize_save, so we fall
	 * back to parsing the referer, and its ?url param, to see if there is a
	 * ?p post id there.
	 *
	 * Intercepting the AJAX call with $.ajaxPrefilter seems to not work either,
	 * since it seems to somehow invalidate the request, making Customizer think
	 * that the changes were not saved.
	 *
	 * @since 3.0.0
	 * @return int|null
	 */
	private function get_voucher_template_id_from_referrer() {

		$referer = wp_get_referer();

		if ( ! $referer ) {
			return null;
		}

		$query = parse_url( $referer, PHP_URL_QUERY );

		if ( ! $query ) {
			return null;
		}

		$args = array();
		parse_str( $query, $args );

		if ( empty( $args ) || empty( $args['url'] ) ) {
			return null;
		}

		$url = urldecode( $args['url'] );
		$preview_query = parse_url( $url, PHP_URL_QUERY );

		if ( ! $preview_query ) {
			return null;
		}

		$preview_args = array();
		parse_str( $preview_query, $preview_args );

		return ! empty( $preview_args ) && ! empty( $preview_args['p'] ) ? (int) $preview_args['p'] : null;
	}


}
