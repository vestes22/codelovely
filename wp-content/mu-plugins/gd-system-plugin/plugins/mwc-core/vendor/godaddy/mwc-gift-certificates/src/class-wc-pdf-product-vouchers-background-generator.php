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
 * PDF Product Vouchers Background Generator
 *
 * Handles generating voucher PDFs in the background.
 *
 * @since 3.2.0
 */
class MWC_Gift_Certificates_Background_Generator extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Initializes background generator handler.
	 *
	 * @since 3.2.0
	 */
	public function __construct() {

		$this->prefix   = 'wc_pdf_product_vouchers';
		$this->action   = 'background_generate';
		$this->data_key = 'voucher_ids';

		parent::__construct();

		add_action( "{$this->identifier}_job_complete", array( $this, 'finish_generation' ) );
	}


	/**
	 * Processes a single item from the job.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $item Voucher ID
	 * @param object $job related job instance
	 */
	public function process_item( $item, $job ) {

		$voucher = wc_pdf_product_vouchers_get_voucher( $item );
		wc_pdf_product_vouchers()->get_voucher_handler_instance()->generate_voucher_pdf( $voucher );
	}

	/**
	 * Finishes the PDF generation by triggering an email on jobs with order ids.
	 *
	 * @since 3.2.0
	 *
	 * @param $job
	 */
	public function finish_generation( $job ) {

		if ( property_exists( $job, 'order_id' ) ) {
			$mailer = WC()->mailer();

			foreach ( $mailer->get_emails() as $email ) {
				if ( in_array( $email->id, array( 'wc_pdf_product_vouchers_voucher_recipient', 'wc_pdf_product_vouchers_voucher_purchaser' ), true ) ) {
					$email->trigger( $job->order_id );
				}
			}
		}
	}


}
