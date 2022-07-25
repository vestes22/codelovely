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
 * PDF Product Vouchers Voucher Field Position Control
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Customize_Position_Control extends \WP_Customize_Control {


	/** @var string custom control type */
	public $type = 'wc_pdf_product_vouchers_position';

	/** @var array Button labels */
	public $button_labels = array();

	/** @var array voucher field ID */
	public $voucher_field_id;


	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 * @param \WP_Customize_Manager $manager customizer bootstrap instance
	 * @param string $id control id
	 * @param array $args (optional) arguments to override class property defaults
	 */
	public function __construct( \WP_Customize_Manager $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );

		$this->button_labels = wp_parse_args( $this->button_labels, array(
			'set'    => __( 'Set Position', 'woocommerce-pdf-product-vouchers' ),
			'done'   => __( 'Done', 'woocommerce-pdf-product-vouchers' ),
			'cancel' => __( 'Cancel', 'woocommerce-pdf-product-vouchers' ),
			'remove' => __( 'Remove position', 'woocommerce-pdf-product-vouchers' ),
		) );
	}


	/**
	 * Refreshes the parameters passed to the JavaScript via JSON
	 *
	 * @see \WP_Customize_Control::to_json()
	 *
	 * @since 3.0.0
	 */
	public function to_json() {

		parent::to_json();

		$this->json['state']            = 'default';
		$this->json['button_labels']    = $this->button_labels;
		$this->json['voucher_field_id'] = $this->voucher_field_id;

		$value = $this->value();

		if ( $value ) {
			$this->json['position'] = explode( ',', $value );
		}
	}


	/**
	 * Don't render any content for this control from PHP.
	 *
	 * @see MWC_Gift_Certificates_Customize_Position_Control::content_template()
	 *
	 * @since 3.0.0
	 */
	public function render_content() {}


	/**
	 * Renders a JS template for the content of the position control.
	 *
	 * @since 3.0.0
	 */
	public function content_template() {
		?>
		<div class="actions">
			<# if ( 'default' === data.state ) { #>
			<button type="button" class="button start-button control-focus" id="{{ data.settings['default'] }}-button">{{ data.button_labels.set }}</button>
			<# if ( data.position && data.position.length ) { #>
			<button type="button" class="button remove-button">{{ data.button_labels.remove }}</button>
			<# } #>
			<# } else if ( 'positioning' === data.state ) { #>
			<button type="button" class="button done-button">{{ data.button_labels.done }}</button>
			<button type="button" class="button cancel-button">{{ data.button_labels.cancel }}</button>
			<# } #>
			<div style="clear:both"></div>
		</div>
		<?php
	}


}
