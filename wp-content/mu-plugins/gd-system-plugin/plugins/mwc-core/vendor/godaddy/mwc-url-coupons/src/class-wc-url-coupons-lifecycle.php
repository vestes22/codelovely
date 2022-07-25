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

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Plugin lifecycle handler.
 *
 * @since 2.7.0
 *
 * @method \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons get_plugin()
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @since 2.7.4
	 *
	 * @param \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'1.0.2',
			'2.0.0',
			'2.1.1',
			'2.5.1',
			'2.11.0',
		];
	}


	/**
	 * Adds default settings upon install.
	 *
	 * @since 2.11.0
	 */
	protected function install() {

		update_option( 'wc_url_coupons_url_prefix', '' );
		update_option( 'wc_url_coupons_hide_coupon_field_cart', 'no' );
		update_option( 'wc_url_coupons_hide_coupon_field_checkout', 'no' );
	}


	/**
	 * Updates to v1.0.2
	 *
	 * Prior versions had a bug where any coupons trashed would not remove the associated unique URL from the active list,
	 * resulting in "coupon does not exist" errors when the unique URLs were visited.
	 * This wasn't a very visible problem with very unique URLs, but becomes a serious problem
	 * when someone uses "/checkout/" as the unique URL.
	 *
	 * @since 2.7.4
	 */
	protected function upgrade_to_1_0_2() {

		// load active coupon list
		$coupons = (array) get_option( 'wc_url_coupons_active_urls', [] );

		// iterate through post IDs
		foreach ( $coupons as $coupon_id => $coupon_data ) {

			// if coupon doesn't exist or is not published, remove from active list
			if ( 'publish' !== get_post_status( $coupon_id ) ) {
				unset( $coupons[ $coupon_id ] );
			}
		}

		// update active list
		update_option( 'wc_url_coupons_active_urls', $coupons );

		// clear transient
		delete_transient( 'wc_url_coupons_active_urls' );
	}


	/**
	 * Updates to v2.0.0
	 *
	 * Two changes to the coupon data:
	 *
	 * 1) "force apply" is now called "defer apply"
	 * 2) prior versions didn't support the redirect page type and while we can (and do) use `page` as the default,
	 *    it's nicer to have the redirect page type set properly
	 *
	 * @since 2.7.4
	 */
	protected function upgrade_to_2_0_0() {

		$coupons = (array) get_option( 'wc_url_coupons_active_urls', [] );

		foreach ( $coupons as $coupon_id => $data ) {

			// force => defer
			$coupons[ $coupon_id ]['defer'] = isset( $coupons[ $coupon_id ]['force'] ) ? $coupons[ $coupon_id ]['force'] : false;

			if ( $coupons[ $coupon_id ]['defer'] ) {

				if ( is_numeric( $coupon_id ) && $coupon = new \WC_Coupon( $coupon_id ) ) {

					$coupon->update_meta_data( '_wc_url_coupons_defer_apply', 'yes' );
					$coupon->save_meta_data();
				}
			}

			// remove force
			unset( $coupons[ $coupon_id ]['force'] );

			if ( is_numeric( $coupon_id ) && $coupon = new \WC_Coupon( $coupon_id ) ) {

				$coupon->delete_meta_data( '_wc_url_coupons_force_apply' );
				$coupon->save_meta_data();
			}

			// update redirect page type
			if ( empty( $data['redirect'] ) ) {
				continue;
			}

			$post_type = get_post_type( $data['redirect'] );

			// no existing redirects should be set to these post types, but just in case
			if ( ! $post_type ) {
				$post_type = 'page';
			} elseif ( 'product_variation' === $post_type ) {
				$post_type = 'product';
			}

			$coupons[ $coupon_id ]['redirect_page_type'] = $post_type;
		}

		// update active list
		update_option( 'wc_url_coupons_active_urls', $coupons );

		// clear transient
		delete_transient( 'wc_url_coupons_active_urls' );
	}


	/**
	 * Updates to version 2.1.1
	 *
	 * Prior versions didn't update the redirect post type coupon meta.
	 *
	 * @since 2.7.4
	 */
	protected function upgrade_to_2_1_1() {

		$coupons = (array) get_option( 'wc_url_coupons_active_urls', [] );

		foreach ( $coupons as $coupon_id => $data ) {

			// update redirect page type
			if ( empty( $data['redirect'] ) ) {
				continue;
			}

			if ( is_numeric( $coupon_id ) && $coupon = new \WC_Coupon( $coupon_id ) ) {

				$redirect_page_type = $coupon->get_meta( '_wc_url_coupons_redirect_page_type' );

				if ( ! empty( $redirect_page_type ) ) {
					continue;
				}
			}

			$post_type = get_post_type( $data['redirect'] );

			// no existing redirects should be set to these post types, but just in case
			if ( ! $post_type ) {
				$post_type = 'page';
			} elseif ( 'product_variation' === $post_type ) {
				$post_type = 'product';
			}

			if ( is_numeric( $coupon_id ) && $coupon = new \WC_Coupon( $coupon_id ) ) {

				$coupon->update_meta_data( '_wc_url_coupons_redirect_page_type', $post_type );
				$coupon->save_meta_data();
			}
		}
	}


	/**
	 * Updates to version 2.5.1
	 *
	 * Upgrade to 2.5.1, only from 2.5.0 and if running WC 3.0+.
	 * Some data was incorrectly set for WC 3.0+ using v2.5.0 due to select2 upgrade.
	 *
	 * Note: the following update script wouldn't run anymore in newer versions of the plugin, but it's left here for reference.
	 *
	 * @since 2.7.4
	 */
	protected function upgrade_to_2_5_1() {

		// special handling if upgrading from 2.5.0 to 2.5.1
		if ( ! version_compare( $this->get_installed_version(), '2.5.0', '=' ) ) {
			return;
		}

		$plugin = $this->get_plugin();

		if ( '2.5.1' === $plugin::VERSION ) {

			$coupons  = (array) get_option( 'wc_url_coupons_active_urls', [] );
			$new_data = [];

			foreach ( $coupons as $coupon_id => $data ) {

				$new_data[ $coupon_id ] = $data;

				// loose check: prior versions didn't properly save the redirect page ID
				if ( 0 == $data['redirect'] ) {

					// good news! coupon meta wasn't updated as as result of this error since checks failed, so we can still get it
					$coupon = is_numeric( $coupon_id ) ? new \WC_Coupon( $coupon_id ) : null;

					$new_data[ $coupon_id ]['redirect'] = $coupon ? $coupon->get_meta( '_wc_url_coupons_redirect_page' ) : '';
				}
			}

			update_option( 'wc_url_coupons_active_urls', $new_data );

			delete_transient( 'wc_url_coupons_active_urls' );
		}
	}


	/**
	 * Updates to version 2.10.0
	 *
	 * @since 2.10.0-dev.1
	 */
	protected function upgrade_to_2_11_0() {

		$coupons  = (array) get_option( 'wc_url_coupons_active_urls', [] );
		$new_data = [];

		foreach ( $coupons as $coupon_id => $data ) {

			if ( ! isset( $data['prefix'] ) ) {

				$data['prefix'] = '';

				$coupon = new \WC_Coupon( $coupon_id );
				$coupon->update_meta_data( '_wc_url_coupons_url_prefix', '' );
				$coupon->save_meta_data();
			}

			$new_data[ $coupon_id ] = $data;
		}

		update_option( 'wc_url_coupons_active_urls', $new_data );

		delete_transient( 'wc_url_coupons_active_urls' );
	}


}
