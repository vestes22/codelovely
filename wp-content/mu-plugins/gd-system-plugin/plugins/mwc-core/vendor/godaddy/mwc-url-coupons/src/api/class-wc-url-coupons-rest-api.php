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

namespace GoDaddy\WordPress\MWC\UrlCoupons\API;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;
use function GoDaddy\WordPress\MWC\UrlCoupons\wc_url_coupons;

/**
 * URL Coupons REST API handler.
 *
 * @since 2.7.3
 *
 * @method \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons get_plugin()
 */
class REST_API extends Framework\REST_API {


	/**
	 * REST API handler constructor.
	 *
	 * @since 2.7.3
	 *
	 * @param \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons $plugin main instance
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		add_action( 'woocommerce_rest_insert_shop_coupon_object', array( $this, 'handle_insert_shop_coupon_data' ) );
	}


	/**
	 * Saves URL Coupon data when inserting a coupon via the WC REST API.
	 *
	 * @internal
	 *
	 * @since 2.7.3
	 *
	 * @param \WC_Coupon $coupon
	 */
	public function handle_insert_shop_coupon_data( $coupon ) {

		// update active coupon array option
		wc_url_coupons()->get_admin_instance()->update_coupons( array(
			'coupon_id'          => $coupon->get_id(),
			'unique_url'         => $coupon->get_meta( '_wc_url_coupons_unique_url' ),
			'redirect_page'      => $coupon->get_meta( '_wc_url_coupons_redirect_page' ),
			'redirect_page_type' => $coupon->get_meta( '_wc_url_coupons_redirect_page_type' ),
			'product_ids'        => $coupon->get_meta( '_wc_url_coupons_product_ids' ),
			'defer_apply'        => $coupon->get_meta( '_wc_url_coupons_defer_apply' ),
		) );
	}


}
