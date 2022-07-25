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
 * Handles SPV redemption
 *
 * @since 3.5.0
 */
class MWC_Gift_Certificates_Redeem_Single implements MWC_Gift_Certificates_Redeem {

	/**
	 * Handles SPV redemption.
	 *
	 * @since 3.5.0
	 *
	 * @param WC_Voucher $voucher
	 * @param integer $amount
	 * @return MWC_Gift_Certificates_Redeem_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function redeem( $voucher, $amount ) {

		$remaining    = $this->redeem_single( $voucher, $amount );
		/* translators: Single voucher redeemed notice. Placeholders: %1$s - voucher number */
		$message_text = esc_html__( 'Gift certificate %s x 1 was redeemed.', 'woocommerce-pdf-product-vouchers' );
		if ( $remaining > 0 ) {
			/* translators: Single voucher redeemed notice. Placeholders: %1$s - <br />, %2$s - remaining count tag */
			$message_text .= esc_html__( '%sThere are %d uses remaining', 'woocommerce-pdf-product-vouchers' );
		}
		$message = sprintf( $message_text, $voucher->get_voucher_number(), '<br />', $remaining );

		$response = new MWC_Gift_Certificates_Redeem_Response_Single();
		$response->set_message( $message );
		$response->set_remaining( $remaining );

		return $response;
	}


	/**
	 * Redeems single purpose voucher once.
	 *
	 * @since 3.5.0
	 *
	 * @param WC_Voucher $voucher
	 * @return integer
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function redeem_single( $voucher, $amount ) {

		$voucher->redeem( array(
			'amount'  => wc_format_decimal( $amount ),
			'user_id' => get_current_user_id()
		) );

		return max( 0, $voucher->get_product_quantity() - count( $voucher->get_redemptions() ) );
	}
}
