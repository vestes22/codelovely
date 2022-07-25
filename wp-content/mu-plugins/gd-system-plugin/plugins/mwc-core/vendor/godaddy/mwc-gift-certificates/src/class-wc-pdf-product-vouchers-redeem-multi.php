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
 * Handle MPV redemption.
 *
 * @since 3.5.0
 */
class MWC_Gift_Certificates_Redeem_Multi implements MWC_Gift_Certificates_Redeem {


	/**
	 * Handles MPV redemption
	 *
	 * @since 3.5.0
	 *
	 * @param WC_Voucher $voucher
	 * @param integer $amount
	 * @return MWC_Gift_Certificates_Redeem_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function redeem( $voucher, $amount ) {

		if ( $amount > 0 ) {

			$missing_amount = false;
			$remaining      = $this->redeem_multi( $voucher, $amount );
			/* translators: Multipurpose voucher redeemed notice. Placeholders: %1$s - voucher number, %2$s - Redeemed amount, %3$s - <br />, %4$s - remaining amount */
			$message_text = esc_html__( 'Gift certificate %s was redeemed for %s!%sThis gift certificate has %s remaining', 'woocommerce-pdf-product-vouchers' );
			$message      = sprintf( $message_text, $voucher->get_voucher_number(), wc_price( $amount, [ 'currency' => $voucher->get_voucher_currency() ] ), '<br />', wc_price( $remaining, [ 'currency' => $voucher->get_voucher_currency() ] ) );

		} else {

			$missing_amount = true;
			$remaining      = $voucher->get_remaining_value_for_display();
			/* translators: Multipurpose voucher missing redemption amount. Placeholders: %1$s - voucher number, %2$s - <br />, %3$s - remaining amount, %4$s - <a>, %5$s - </a> */
			$message_text = esc_html__( 'Gift certificate %s requires an amount for redemption!%sThis gift certificate has %s available. Enter an amount to redeem, or %sclick here%s to view the gift certificate.', 'woocommerce-pdf-product-vouchers' );
			$message      = sprintf( $message_text, $voucher->get_voucher_number(), '<br />', wc_price( $remaining, [ 'currency' => $voucher->get_voucher_currency() ] ), '<a href="' . get_edit_post_link( $voucher->get_id() ) . '">', '</a>' );
		}

		$response = new MWC_Gift_Certificates_Redeem_Response_Multi();
		$response->set_missing_amount( $missing_amount );
		$response->set_message( $message );
		$response->set_remaining( $remaining );

		return $response;
	}


	/**
	 * Redeems multi purpose voucher by given amount.
	 *
	 * @since 3.5.0
	 *
	 * @param WC_Voucher $voucher
	 * @param float $amount
	 * @return float
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	private function redeem_multi( $voucher, $amount ) {

		$voucher->redeem( array(
			'amount'  => wc_format_decimal( $amount ),
			'user_id' => get_current_user_id()
		) );

		return $voucher->get_remaining_value_for_display();
	}


}
