<?php
/**
 * WooCommerce URL Coupons
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce URL Coupons to newer
 * versions in the future. If you wish to customize WooCommerce URL Coupons for your
 * needs please refer to http://docs.woocommerce.com/document/url-coupons/ for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2020, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\UrlCoupons;

defined( 'ABSPATH' ) or exit;

/**
 * Ajax class - handles ajax actions and callbacks
 *
 * @since 2.1.5
 */
class WC_URL_Coupons_AJAX {


	/**
	 * Add actions
	 *
	 * @since 2.1.5
	 */
	public function __construct() {

		// search page to redirect to
		add_action( 'wp_ajax_wc_url_coupons_json_search_page_redirects', array( $this, 'search_page_redirects' ) );
	}


	/**
	 * Search page redirects
	 *
	 * @since 2.1.5
	 */
	public function search_page_redirects() {

		check_ajax_referer( 'search-page-redirects', 'security' );

		// Get the search term.
		$keyword = isset( $_GET['term'] ) ? urldecode( stripslashes( strip_tags( $_GET['term'] ) ) ) : '';

		if ( empty( $keyword ) ) {
			die;
		}

		$page_types   = wc_url_coupons()->get_admin_instance()->get_redirect_pages( $keyword );
		$found_values = array();

		if ( ! empty( $page_types ) ) {

			foreach ( $page_types as $group ) {

				foreach ( $group as $page_id => $page ) {

					if ( isset( $page['type'], $page['title'] ) && stristr( $page['title'], $keyword ) )  {

						$found_values[ $page['type'] . '|' . $page_id ] = esc_html( $page['title'] );
					}
				}
			}
		}

		echo json_encode( $found_values );
		exit;
	}


}
