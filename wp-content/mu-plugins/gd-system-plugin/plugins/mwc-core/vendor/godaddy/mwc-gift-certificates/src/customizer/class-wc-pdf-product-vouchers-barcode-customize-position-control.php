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
 * PDF Product Vouchers Voucher Barcode Field Position Control
 *
 * @since 3.5.0
 */
class MWC_Gift_Certificates_Barcode_Customize_Position_Control extends MWC_Gift_Certificates_Customize_Position_Control {


	/** @var string custom control type */
	public $type = 'wc_pdf_product_vouchers_barcode_position';


	/**
	 * Constructor
	 *
	 * @since 3.5.0
	 *
	 * @param \WP_Customize_Manager $manager customizer bootstrap instance
	 * @param string $id control id
	 * @param array $args (optional) arguments to override class property defaults
	 */
	public function __construct( \WP_Customize_Manager $manager, $id, $args = array() ) {

		parent::__construct( $manager, $id, $args );
	}


	/**
	 * Refreshes the parameters passed to the JavaScript via JSON
	 *
	 * @see \WP_Customizeomize_Control::to_json()
	 *
	 * @since 3.5.0
	 */
	public function to_json() {

		parent::to_json();

		if ( 'barcode' === $this->voucher_field_id ) {

			$this->json['aspect_ratio'] = array(
				'qr'       => '250:250',
				'dmtx'     => '250:250',
				'code-39'  => '750:250',
				'code-93'  => '750:250',
				'code-128' => '750:250',
			);

		}

	}
}
