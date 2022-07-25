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

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Customizer Voucher Image Control
 *
 * @see WP_Customize_Header_Image_Control
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Customize_Voucher_Image_Control extends \WP_Customize_Control {


	/** @var string custom control type */
	public $type = 'wc_pdf_product_vouchers_voucher_image';


	/**
	 * Enqueues control related scripts/styles
	 *
	 * @since 3.0.0
	 */
	public function enqueue() {

		wp_enqueue_media();
		wp_enqueue_script( 'customize-views' );

		wp_localize_script( 'customize-views', '_wpCustomizeHeader', array(
			'data' => array(
				// TODO: handle with/height, so that it's optional to crop and works with undefined width/height in crop modal
				'width'         => 1000,
				'height'        => 1000,
				'flex-width'    => true,
				'flex-height'   => true,
				'currentImgSrc' => $this->get_primary_image_src(),
			),
			'nonces' => array(
				'add'    => wp_create_nonce( 'header-add' ),
				'remove' => wp_create_nonce( 'header-remove' ),
			),
			'uploads'       => $this->get_uploaded_voucher_images(),
			'defaults'      => array(), // defaults UI is not used for voucher image control

			// namespace our custom args to avoid potential collisions
			'_pdf_vouchers' => array(
				'default_images' => $this->get_default_image_ids(), // passed to the media file frame
				'i18n' => array(
					'default_images_title' => __( 'Default Images', 'woocommerce-pdf-product-vouchers' ),
				),
			),
		) );

		parent::enqueue();
	}


	/**
	 * Returns a list of uploaded voucher images
	 *
	 * @since 3.0.0
	 * @return array voucher images as associative arrays, consumable by the header image control
	 */
	private function get_uploaded_voucher_images() {

		$image_ids = $this->value( 'uploads' );
		$images    = array();

		if ( is_array( $image_ids ) && ! empty( $image_ids ) ) {
			foreach ( $image_ids as $image_id ) {

				$url = esc_url_raw( wp_get_attachment_url( $image_id ) );

				// sanity check - if image does not exist anymore, the url will be false
				if ( ! $url ) {
					continue;
				}

				$image_data  = wp_get_attachment_metadata( $image_id );
				$image_index = $image_id;

				$images[ $image_index ]                  = array();
				$images[ $image_index ]['attachment_id'] = $image_id;
				$images[ $image_index ]['url']           = $url;
				$images[ $image_index ]['thumbnail_url'] = $url;
				$images[ $image_index ]['alt_text']      = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

				if ( isset( $image_data['width'] ) ) {
					$images[ $image_index ]['width'] = $image_data['width'];
				}

				if ( isset( $image_data['height'] ) ) {
					$images[ $image_index ]['height'] = $image_data['height'];
				}
			}
		}

		return $images;
	}


	/**
	 * Prints the voucher image template
	 *
	 * @see \WP_Customize_Header_Image_Control::print_header_image_template()
	 *
	 * @since 3.0.0
	 */
	public function print_voucher_image_template() {
		?>
		<script type="text/template" id="tmpl-header-choice">

			<button type="button" class="choice thumbnail"
				data-customize-image-value="{{{data.header.url}}}"
				data-customize-header-image-data="{{JSON.stringify(data.header)}}">
				<span class="screen-reader-text"><?php esc_html_e( 'Set image', 'woocommerce-pdf-product-vouchers' ); ?></span>
				<img src="{{{data.header.thumbnail_url}}}" alt="{{{data.header.alt_text || data.header.description}}}">
			</button>

			<# if ( data.type === 'uploaded' ) { #>
				<button type="button" class="dashicons dashicons-no close"><span class="screen-reader-text"><?php esc_html_e( 'Remove image', 'woocommerce-pdf-product-vouchers' ); ?></span></button>
			<# } #>
		</script>

		<script type="text/template" id="tmpl-header-current">

			<# if (data.choice) { #>
			<img src="{{{data.header.thumbnail_url}}}" alt="{{{data.header.alt_text || data.header.description}}}" />
			<# } else { #>

			<div class="placeholder">
				<?php esc_html_e( 'No image set', 'woocommerce-pdf-product-vouchers' ); ?>
			</div>

			<# } #>
		</script>
		<?php
	}


	/**
	 * Gets the primary voucher image source url
	 *
	 * @since 3.0.0
	 * @return string primary image url
	 */
	public function get_primary_image_src() {

		$image = $this->value( 'primary' );

		return esc_url_raw( ! empty( $image['src'] ) ? $image['src'] : '' );
	}


	/**
	 * Gets default voucher image ids
	 *
	 * @since 3.0.0
	 * @return int[]
	 */
	private function get_default_image_ids() {
		global $wpdb;

		return $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_is_mwc_gift_certificate_template_default_image' AND meta_value = 1" );
	}


	/**
	 * Renders the voucher image control content
	 *
	 * @see \WP_Customize_Header_Image_Control::render_content()
	 *
	 * @since 3.0.0
	 */
	public function render_content() {

		$this->print_voucher_image_template();

		?>
		<div class="customize-control-content">

			<span class="customize-control-title"><?php echo esc_html( $this->label ) ?></span>

			<div class="customize-control-notifications-container"></div>

			<p class="customizer-section-intro customize-control-description">
				<?php _e( 'Please upload one or more gift certificate background images by clicking <strong>Add New Image</strong>. While any dimensions are accepted, if you upload more than one background, please ensure alternative images have the <strong>same dimensions and resolution</strong> as the primary image.', 'woocommerce-pdf-product-vouchers' ); ?>
			</p>

			<div class="current">
				<label for="wc_pdf_product_vouchers_voucher_image-button">
					<span class="customize-control-title">
						<?php esc_html_e( 'Primary image', 'woocommerce-pdf-product-vouchers' ); ?>
					</span>
				</label>
				<div class="container">
				</div>
			</div>

			<div class="actions">
				<?php if ( current_user_can( 'upload_files' ) ): ?>
				<button type="button" class="button new" id="wc_pdf_product_vouchers_voucher_image-button"  aria-label="<?php esc_attr_e( 'Add new gift certificate image', 'woocommerce-pdf-product-vouchers' ); ?>"><?php esc_html_e( 'Add new image', 'woocommerce-pdf-product-vouchers' ); ?></button>
				<div style="clear:both"></div>
				<?php endif; ?>
			</div>

			<div class="choices">

				<span class="customize-control-title uploaded-voucher-images">
					<?php esc_html_e( 'All Gift Certificate Images', 'woocommerce-pdf-product-vouchers' ); ?>
				</span>

				<span class="customize-control-description">
					<?php esc_html_e( 'If there is more than one image, images will appear as alternative gift certificate image options for the customer. Click on an image to make it primary.' ); ?>
				</span>

				<div class="uploaded">
					<div class="list">
					</div>
				</div>

				<div class="default">
					<div class="list">
					</div>
				</div>

			</div>
		</div>
		<?php
	}


}
