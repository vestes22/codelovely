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
 * PDF Product Vouchers Voucher Font Style Control
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Customize_Font_Style_Control extends \WP_Customize_Control {


	/** @var string custom control type */
	public $type = 'wc_pdf_product_vouchers_font_style';


	/**
	 * Enqueues control related scripts/styles
	 *
	 * @since 3.0.0
	 */
	public function enqueue() {

		if ( ! wp_script_is( 'jquery-tiptip', 'registered' ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
		}

		wp_enqueue_script( 'jquery-tiptip' );
	}


	/**
	 * Renders setting field HTML
	 *
	 * @since 3.0.0
	 * @param int|string $key setting key
	 * @param MWC_Gift_Certificates_Voucher_Template_Setting $setting setting instance
	 */
	public function render_field_html( $key, MWC_Gift_Certificates_Voucher_Template_Setting $setting ) {

		if ( Framework\SV_WC_Helper::str_ends_with( $setting->id, 'style_b' ) ) {
			?>
				<label class="font-style-button <?php if ( $this->value( $key ) ) : echo 'active'; endif; ?>" data-tip="<?php esc_attr_e( 'Bold', 'woocommerce-pdf-product-vouchers' ); ?>">
					<input type="checkbox" class="font-style-bold" value="<?php echo esc_attr( $this->value( $key ) ); ?>" <?php $this->link( $key ); checked( $this->value( $key ) ); ?> />
					<span class="dashicons dashicons-editor-bold"></span>
				</label>
			<?php
		}

		elseif ( Framework\SV_WC_Helper::str_ends_with( $setting->id, 'style_i' ) ) {
			?>
				<label class="font-style-button <?php if ( $this->value( $key ) ) : echo 'active'; endif; ?>" data-tip="<?php esc_attr_e( 'Italic', 'woocommerce-pdf-product-vouchers' ); ?>">
					<input type="checkbox" class="font-style-italic" value="<?php echo esc_attr( $this->value( $key ) ); ?>" <?php $this->link( $key ); checked( $this->value( $key ) ); ?> />
					<span class="dashicons dashicons-editor-italic"></span>
				</label>
			<?php
		}

		elseif ( Framework\SV_WC_Helper::str_ends_with( $setting->id, 'text_align' ) ) {

			$name = '_customize-radio-' . $setting->id;

			$choices = array(
				'left'   => __( 'Align left', 'woocommerce-pdf-product-vouchers' ),
				'center' => __( 'Align center', 'woocommerce-pdf-product-vouchers' ),
				'right'  => __( 'Align right', 'woocommerce-pdf-product-vouchers' ),
			);

			?>
			<span class="font-style-radio-container">

				<?php /* hidden radio option to allow unchecking the text align control */ ?>
				<input type="radio" value="" class="font-style-text-align-empty" style="display: none;" name="<?php echo esc_attr( $name ); ?>" <?php $this->link( $key ); checked( ! $this->value( $key ), true ); ?> />

				<?php foreach ( $choices as $value => $label ) : ?>
					<label class="font-style-button <?php if ( $this->value( $key ) === $value ) : echo 'active'; endif; ?>" data-tip="<?php echo esc_html( $label ); ?>">
						<input type="radio" class="font-style-text-align" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link( $key ); checked( $this->value( $key ), $value ); ?> />
						<span class="dashicons dashicons-editor-align<?php echo esc_attr( $value ); ?>"></span>
					</label>
				<?php endforeach; ?>
			</span>
			<?php
		}
	}


	/**
	 * Renders the control's content
	 *
	 * @since 3.0.0
	 */
	public function render_content() {

		echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';

		foreach ( $this->settings as $key => $setting ) {
			$this->render_field_html( $key, $setting );
		}
	}


}
