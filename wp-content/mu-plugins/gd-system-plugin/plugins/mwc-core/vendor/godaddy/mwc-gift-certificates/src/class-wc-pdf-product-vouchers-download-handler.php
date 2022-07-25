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
 * PDF Voucher Download Handler.
 *
 * Allows admins and customers to download otherwise inaccessible generated PDF vouchers.
 * Based on WC_Download_Handler.
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Download_Handler {


	/**
	 * Initializes the download handler class
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		if ( ! empty( $_GET['download_wc_voucher_pdf'] ) ) {
			add_action( 'init', array( $this, 'download_voucher_pdf' ) );
		}

		add_filter( 'user_has_cap', array( $this, 'user_has_download_voucher_cap' ), 10, 3 );

		// maybe render the prompt on the "thank you" page
		// we don't use the woocommerce_thankyou / template_redirect action as we can't consistently add a notice for immediate display
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'maybe_render_voucher_generating_message' ), 10, 2 );
	}


	/**
	 * Downloads a generated PDF file
	 *
	 * @since 3.0.0
	 */
	public function download_voucher_pdf() {

		$voucher = wc_pdf_product_vouchers_get_voucher( $_GET['download_wc_voucher_pdf'] );

		if ( ! $voucher || empty( $_GET['key'] ) || $_GET['key'] !== $voucher->get_voucher_key() ) {
			$this->download_error( __( 'Invalid gift certificate download link.', 'woocommerce-pdf-product-vouchers' ) );
		}

		// redirect customers if the voucher isn't generated yet
		if ( ! $voucher->file_exists() ) {

			$order = $voucher->get_order();

			if ( $order ) {

				$url = add_query_arg(
					array(
						'generating_wc_voucher' => $_GET['download_wc_voucher_pdf'],
					),
					$order->get_checkout_order_received_url()
				);

				wp_safe_redirect( $url );
				exit;
			}
		}

		// check if current user can download the voucher
		if ( $voucher->get_customer_id() && 'yes' === get_option( 'woocommerce_downloads_require_login' ) ) {

			if ( ! is_user_logged_in() ) {

				if ( wc_get_page_id( 'myaccount' ) ) {
					wp_safe_redirect( add_query_arg( 'wc_error', urlencode( __( 'You must be logged in to download gift certificates.', 'woocommerce-pdf-product-vouchers' ) ), wc_get_page_permalink( 'myaccount' ) ) );
					exit;
				} else {
					$this->download_error( __( 'You must be logged in to download gift certificates.', 'woocommerce-pdf-product-vouchers' ) . ' <a href="' . esc_url( wp_login_url( wc_get_page_permalink( 'myaccount' ) ) ) . '" class="wc-forward">' . __( 'Login', 'woocommerce-pdf-product-vouchers' ) . '</a>', __( 'Log in to Download Gift Certificates', 'woocommerce-pdf-product-vouchers' ), 403 );
				}

			} elseif ( ! current_user_can( 'download_voucher', $voucher ) ) {
				$this->download_error( __( 'This is not your download link.', 'woocommerce-pdf-product-vouchers' ), '', 403 );
			}
		}

		$file_path = $voucher->get_voucher_full_filename();
		$file_url  = wc_pdf_product_vouchers_convert_path_to_url( $file_path );
		$filename  = basename( $file_path );

		if ( false !== strpos( $filename, '?' ) ) {
			$filename = current( explode( '?', $filename ) );
		}

		$file_download_method = get_option( 'woocommerce_file_download_method', 'force' );

		// count downloads, unless an admin is downloading the pdf from admin backend
		if ( ! is_admin() || is_admin() && ! current_user_can( 'manage_woocommerce' ) ) {
			$voucher->count_download();
		}

		// add action to prevent issues in IE
		add_action( 'nocache_headers', array( 'WC_Download_Handler', 'ie_nocache_headers_fix' ) );

		// trigger download via one of the methods. WC_Download_Handler will take over from here
		do_action( 'woocommerce_download_file_' . $file_download_method, $file_url, $filename );
	}


	/**
	 * Displays a message on the thank you page if redirected from a voucher that's not ready yet.
	 *
	 * @since 3.2.2
	 *
	 * @param string $text the thankyou page message text
	 * @param \WC_Order $order the placed order object, unused
	 * @return string the updated text
	 */
	public function maybe_render_voucher_generating_message( $text, $order ) {

		if ( isset( $_GET['generating_wc_voucher'] ) ) {
			$text = '<div class="woocommerce-info">' . __( "Whoops, we're still preparing your gift certificate! It will be ready shortly and will be sent in a separate email.", 'woocommerce-pdf-product-vouchers' ) . ' </div>' . $text;
		}

		return $text;
	}


	/**
	 * Dies with an error message if the download fails
	 *
	 * @since 3.0.0
	 * @param string $message error message
	 * @param string $title (optional) error message title to use
	 * @param integer $status (optional) http status code to use, defaults to 404
	 */
	private function download_error( $message, $title = '', $status = 404 ) {
		wp_die( $message, $title, array( 'response' => $status ) );
	}


	/**
	 * Checks if a user has the capability to download a certain voucher.
	 *
	 * @since 3.0.0
	 *
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @return array
	 */
	public function user_has_download_voucher_cap( $allcaps, $caps, $args ) {

		foreach ( $caps as $cap ) {
			if ( 'download_voucher' === $cap ) {
				$user_id = $args[1];
				$voucher = $args[2];

				if ( $user_id == $voucher->get_customer_id() || current_user_can( 'manage_woocommerce' ) ) {
					$allcaps['download_voucher'] = true;
					return $allcaps;
				}
			}
		}

		return $allcaps;
	}


}
