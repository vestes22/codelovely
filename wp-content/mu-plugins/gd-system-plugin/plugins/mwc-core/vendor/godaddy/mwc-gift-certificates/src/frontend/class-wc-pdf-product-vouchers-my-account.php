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

namespace SkyVerge\WooCommerce\PDF_Product_Vouchers\Frontend;

namespace GoDaddy\WordPress\MWC\GiftCertificates\Frontend;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Handles the WooCommerce My Account page.
 *
 * @since 3.5.4
 */
class My_Account {


	/** @var string Vouchers query var */
	private $vouchers_query_var;

	/** @var string Vouchers endpoint */
	private $vouchers_endpoint;


	/**
	 * Hooks in WP and WC to output the Vouchers page in My Account dashboard.
	 *
	 * @since 3.5.4
	 */
	public function __construct() {

		$this->vouchers_query_var = 'vouchers';
		$this->vouchers_endpoint  = $this->get_vouchers_endpoint();

		// add the Vouchers endpoint and My Account menu item
		add_action( 'init',                           array( $this, 'add_endpoints' ), 0 );
		add_filter( 'query_vars',                     array( $this, 'add_query_vars' ), 0 );
		add_filter( 'woocommerce_get_query_vars',     array( $this, 'add_query_vars' ), 0 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ), 0 );

		// output the Vouchers page and its title
		add_action( "woocommerce_account_{$this->vouchers_endpoint}_endpoint", '\GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_account_vouchers' );
		add_filter( 'the_title', array( $this, 'handle_endpoint_titles' ) );
	}


	/**
	 * Gets the Vouchers endpoint.
	 *
	 * @internal
	 *
	 * @since 3.5.4
	 *
	 * @return string
	 */
	public function get_vouchers_endpoint() {

		if ( is_string( $this->vouchers_endpoint ) ) {
			$endpoint = $this->vouchers_endpoint;
		} elseif ( get_option( 'permalink_structure' ) ) {
			$endpoint = (string) get_option( 'wc_pdf_product_vouchers_my_account_vouchers_endpoint', $this->vouchers_query_var );
		} else {
			$endpoint = $this->vouchers_query_var;
		}

		return $endpoint;
	}


	/**
	 * Registers a new endpoint to use inside My Account page.
	 *
	 * @internal
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 *
	 * @since 3.5.4
	 */
	public function add_endpoints() {

		if ( $endpoint = $this->get_vouchers_endpoint() ) {

			$ep_mask = EP_PAGES;

			// special handling when the My Account page is set as the home page
			if ( 'page' === get_option( 'show_on_front' ) ) {

				$page_on_front_id   = (int) get_option( 'page_on_front', 0 );
				$my_account_page_id = (int) wc_get_page_id( 'myaccount' );

				if ( $page_on_front_id > 0 && $my_account_page_id > 0 && $page_on_front_id === $my_account_page_id ) {
					$ep_mask = EP_ROOT | EP_PAGES;
				}
			}

			// add Vouchers endpoint
			add_rewrite_endpoint( $endpoint, $ep_mask );
		}
	}


	/**
	 * Adds vouchers new query var.
	 *
	 * @internal
	 *
	 * @since 3.5.4
	 *
	 * @param array $query_vars associative array of whitelisted query variables
	 * @return array
	 */
	public function add_query_vars( $query_vars ) {

		if ( ! isset( $query_vars[ $this->vouchers_query_var ] ) && ( $endpoint = $this->get_vouchers_endpoint() ) ) {
			$query_vars[ $this->vouchers_query_var ] = $endpoint;
		}

		return $query_vars;
	}


	/**
	 * Adds Vouchers to the My Account dashboard menu items.
	 *
	 * @internal
	 *
	 * @since 3.5.4
	 *
	 * @param array $items associative array of my account menu items
	 * @return array
	 */
	public function add_menu_items( $items ) {

		if ( ! isset( $items[ $this->vouchers_query_var ] ) && $this->get_vouchers_endpoint() ) {

			// remove the logout menu item
			$logout = $items['customer-logout'];

			unset( $items['customer-logout'] );

			// add our custom menu item
			$items[ $this->vouchers_query_var ] = __( 'Gift Certificates', 'woocommerce-pdf-product-vouchers' );

			// insert back the logout item
			$items['customer-logout'] = $logout;
		}

		return $items;
	}


	/**
	 * Adjusts vouchers endpoint default title.
	 *
	 * @internal
	 *
	 * @since 3.5.4
	 *
	 * @param string $title original title
	 * @return string
	 */
	public function handle_endpoint_titles( $title ) {
		global $wp_query;

		$is_endpoint = isset( $wp_query->query_vars[ $this->vouchers_query_var ] );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {

			$title = __( 'Active Gift Certificates', 'woocommerce-pdf-product-vouchers' );

			remove_filter( 'the_title', array( $this, 'handle_endpoint_titles' ) );
		}

		return $title;
	}


}
