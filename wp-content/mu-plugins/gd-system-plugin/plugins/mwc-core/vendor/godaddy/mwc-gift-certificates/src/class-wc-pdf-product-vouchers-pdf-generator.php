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

use Dompdf\Dompdf;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Generator class
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_PDF_Generator {


	/**
	 * Loads and instantiates dompf.
	 *
	 * @link https://github.com/dompdf/dompdf/wiki/Usage for dompdf usage.
	 *
	 * @since 3.1.5
	 *
	 * @return Dompdf
	 */
	private static function load_dompdf() {

		if ( ! class_exists( '\\Dompdf\\Dompdf' ) ) {

			require_once( wc_pdf_product_vouchers()->get_plugin_path() . '/vendor/autoload.php' );
		}

		return new Dompdf();
	}


	/**
	 * Generates and saves or streams a PDF file for a voucher.
	 *
	 * @link https://github.com/dompdf/dompdf/wiki/Usage for dompdf usage.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Voucher $voucher the voucher object to generate the preview image for
	 * @param bool $save (optional) whether to save the pdf to filesystem or stream the output
	 * @throws Framework\SV_WC_Plugin_Exception if the voucher image is not available
	 */
	public static function generate_voucher_pdf( WC_Voucher $voucher, $save = true ) {

		$upload_dir = wp_upload_dir();
		$image      = wp_get_attachment_metadata( $voucher->get_image_id() );

		// make sure the image hasn't been deleted through the media editor
		if ( ! $image ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Gift certificate image not found', 'woocommerce-pdf-product-vouchers' ) );
		}

		// make sure the file exists and is readable
		if ( ! is_readable( $voucher->get_image_path() ) ) {
			/* translators: Placeholders: %s - image path */
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Gift certificate image file missing or not readable: %s', 'woocommerce-pdf-product-vouchers' ), $upload_dir['basedir'] . '/' . $image['file'] ) );
		}

		// try to give us unlimited time if possible - large background images may take a lot of time to render in pdf
		set_time_limit( 0 );

		$dpi = $voucher->get_dpi();

		// get the width and height in points
		$width_pt  = self::convert_pixels_to_points( $image['width'], $dpi );
		$height_pt = self::convert_pixels_to_points( $image['height'], $dpi );

		// instantiate and use the dompdf class
		$dompdf = self::load_dompdf();

		$upload_dir = wp_upload_dir( null, false );

		$dompdf->getOptions()->setFontCache( $upload_dir['basedir'] . '/pdf_vouchers_font_cache' );
		$dompdf->getOptions()->setFontDir( $upload_dir['basedir'] . '/pdf_vouchers_font_cache' );
		$dompdf->getOptions()->setIsRemoteEnabled( true );
		$dompdf->getOptions()->setDpi( $dpi );

		// set a longer timeout if loopback connections are supported
		$background_handler = wc_pdf_product_vouchers()->get_background_generator_instance();
		$timeout            = $background_handler && $background_handler->test_connection() ? 15 : 5;

		/**
		 * Filters the timeout value for the voucher HTML GET request.
		 *
		 * @since 3.4.0
		 *
		 * @param int $timeout the timeout in seconds
		 */
		$timeout = (int) apply_filters( 'wc_pdf_product_vouchers_render_voucher_html_timeout', $timeout );

		$response = wp_remote_get( $voucher->get_render_url(), array( 'timeout' => $timeout ) );

		if ( is_wp_error( $response ) ) {
			/* translators: Placeholders: %s - error message */
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( "Cannot load gift certificate HTML: %s", 'woocommerce-pdf-product-vouchers' ), $response->get_error_message() ) );
		}

		if ( isset( $response['response']['code'] ) && 200 !== (int) $response['response']['code'] ) {
			/* translators: Placeholders: %1$d - HTTP response code, %2$s - HTTP error message */
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Cannot load gift certificate HTML: %1$d - %2$s', 'woocommerce-pdf-product-vouchers' ), $response['response']['code'], $response['response']['message'] ) );
		}

		$html = wp_remote_retrieve_body( $response );

		if ( empty( $html ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Gift certificate HTML is empty', 'woocommerce-pdf-product-vouchers' ) );
		}

		$image_paths = [ ABSPATH ];
		$image_path  = $voucher->get_image_path();

		if ( self::is_readable_local_file_path( $image_path ) ) {

			$path = trailingslashit( dirname( $image_path ) );

			if ( ! Framework\SV_WC_Helper::str_starts_with( $path, ABSPATH ) ) {
				$image_paths[] = $path;
			}

			// if possible, load the voucher images from local filesystem instead of retrieving them remotely
			$html = str_replace( $voucher->get_image_url(), 'file://' . $image_path, $html );
		}

		$additional_image_path = $voucher->get_additional_image_path();

		// only replace additional image url with path if image is defined and readable
		if ( $voucher->get_additional_image_id() && self::is_readable_local_file_path( $additional_image_path ) ) {

			$path = trailingslashit( dirname( $additional_image_path ) );

			if ( ! Framework\SV_WC_Helper::str_starts_with( $path, ABSPATH ) ) {
				$image_paths[] = $path;
			}

			// if possible, load the voucher images from local filesystem instead of retrieving them remotely
			$html = str_replace( $voucher->get_additional_image_url(), 'file://' . $additional_image_path, $html );
		}

		$logo_image_path = $voucher->get_logo_path();

		// only replace logo url with path if logo is defined and readable
		if ( $voucher->get_logo_id() && self::is_readable_local_file_path( $logo_image_path ) ) {

			$path = trailingslashit( dirname( $logo_image_path ) );

			if ( ! Framework\SV_WC_Helper::str_starts_with( $path, ABSPATH ) ) {
				$image_paths[] = $path;
			}

			// if possible, load the voucher images from local filesystem instead of retrieving them remotely
			$html = str_replace( $voucher->get_logo_url(), 'file://' . $logo_image_path, $html );
		}

		// detect encoding from input HTML
		$encoding = mb_detect_encoding( $html );

		// if that fails, use the site's charset
		if ( ! $encoding ) {
			$encoding = get_bloginfo( 'charset' );
		}

		// convert special characters to HTML entities to avoid potential encoding conversion issues when dompdf loads the HTML
		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', $encoding );

		// Help prevent 'No block-level parent found.  Not good.' error when HTML contains
		// whitespace or invisible text nodes between tags, see:
		// - https://github.com/dompdf/dompdf/issues/1494
		// - https://github.com/dompdf/dompdf/pull/1570
		// - https://github.com/dompdf/dompdf#limitations-known-issues
		// TODO: this can probably be removed once dompdf 0.8.2 is released {IT 2017-11-17}
		$html = preg_replace( '/>\s+</', '><', $html );

		// set chroot for file handling
		$dompdf->getOptions()->setChroot( $image_paths );

		// pass the HTML to DomPdf to do it's magic
		$dompdf->loadHtml( $html );

		// (optional) setup the paper size and orientation
		$dompdf->setPaper( array( 0, 0, $width_pt, $height_pt ) );

		// render the HTML as PDF
		try {
			$dompdf->render();
		} catch ( \Exception $e ) {
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Cannot create gift certificate: %s', 'woocommerce-pdf-product-vouchers' ), $e->getMessage() ) );
		}

		if ( ! $save ) {
			// download file
			return $dompdf->stream( 'voucher-preview-' . $voucher->get_id() );
		}

		$voucher_path = wc_pdf_product_vouchers()->get_uploads_path() . '/' . $voucher->get_voucher_path();

		// ensure the path that will hold the voucher pdf exists
		if ( ! file_exists( $voucher_path ) ) {
			@mkdir( $voucher_path, 0777, true );
		}

		// is the output path writable?
		if ( ! is_writable( $voucher_path ) ) {
			/* translators: %s - voucher file path */
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Gift certificate path %s is not writable', 'woocommerce-pdf-product-vouchers' ), $voucher_path ) );
		}

		$file_path = $voucher->get_voucher_full_filename();

		// save the pdf as a file
		file_put_contents( $file_path, $dompdf->output() );

		/**
		 * Filters whether preview images should be generated for each individual voucher.
		 *
		 * @since 3.9.9
		 *
		 * @param bool $generate_previews_enabled true if generating individual preview images is enabled (default)
		 */
		if ( (bool) apply_filters( 'wc_pdf_product_vouchers_generate_individual_preview_images', true ) ) {

			// try to create a preview image of the PDF - this only works if Imagick is installed and enabled
			self::generate_voucher_preview_image( $voucher );
		}
	}


	/**
	 * Generates a preview image of the PDF for the voucher.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Voucher $voucher the voucher object to generate the preview image for
	 */
	public static function generate_voucher_preview_image( WC_Voucher $voucher ) {

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'application/pdf' ) ) ) {
			return;
		}

		$file_path = $voucher->get_voucher_full_filename();
		$preview   = wp_get_image_editor( $file_path );

		if ( ! is_wp_error( $preview ) ) { // Most likely cause for error is that ImageMagick is not available.

			$preview_file_path = $voucher->get_voucher_full_filename( 'png' );

			$preview->resize( 768, null ); // medium size image width

			$result = $preview->save( $preview_file_path, 'image/png' );

			if ( ! is_wp_error( $result ) ) {
				update_post_meta( $voucher->get_id(), '_preview_image', true );
			} else {
				delete_post_meta( $voucher->get_id(), '_preview_image' );
			}
		}
	}


	/**
	 * Converts a pixel value to points.
	 *
	 * Since v3.0.0 moved here from {@see WC_Voucher}, added the $dpi param.
	 *
	 * @since 2.3.0
	 *
	 * @param int $pixels the pixel value
	 * @param int $dpi the dpi value
	 * @return float the point value
	 */
	public static function convert_pixels_to_points( $pixels, $dpi ) {
		return ( (int) $pixels * 72 ) / $dpi;
	}


	/**
	 * Checks if a file path is local and readable.
	 *
	 * This is useful to tell if a path points to some custom registered protocol
	 * that may appear readable at one point, but not when the voucher is being generated.
	 *
	 * @since 3.6.0
	 *
	 * @param bool|string $path file path
	 * @return bool
	 */
	public static function is_readable_local_file_path( $path ) : bool {

		$protocol = $path ? strstr( $path, '://', true ) : false;

		return $path && ( ! $protocol || 'file' === $protocol ) && is_readable( $path );
	}


}
