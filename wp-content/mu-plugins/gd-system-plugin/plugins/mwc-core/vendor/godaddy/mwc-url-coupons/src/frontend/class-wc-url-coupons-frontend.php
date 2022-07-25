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

namespace GoDaddy\WordPress\MWC\UrlCoupons\Frontend;

defined( 'ABSPATH' ) or exit;

use function GoDaddy\WordPress\MWC\UrlCoupons\wc_url_coupons;

/**
 * Frontend class - handles applying coupons and rendering messages
 *
 * @since 2.0.0
 */
class WC_URL_Coupons_Frontend {


	/** @var array of active coupons in format: key: post id of coupon, value: array( 'url' => url, 'redirect' => redirect page ID ) */
	private $active_coupon_urls = array();

	/** @var array of deferred coupons in format: key: post id of coupon, value: array( 'code' => coupon code, 'notice' => the deferred notice message ) */
	private $deferred_coupons = array();


	/**
	 * Sets up front end class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		// load coupons with unique URLs into transient
		$this->load_coupons();

		add_action( 'wp_loaded', array( $this, 'maybe_apply_coupon' ), 11 );

		// handle applying deferred coupons
		add_action( 'woocommerce_check_cart_items', array( $this, 'maybe_apply_deferred_coupons' ), 0 );

		// maybe hide coupon field
		add_filter( 'woocommerce_coupons_enabled', [ $this, 'hide_coupon_field' ] );
	}


	/**
	 * Load coupons from options into a 60 minute transient
	 *
	 * @since 1.0.0
	 */
	private function load_coupons() {

		// transient does not exist
		if ( false === ( $coupons = get_transient( 'wc_url_coupons_active_urls' ) ) ) {

			// get active coupons from option
			$this->active_coupon_urls = get_option( 'wc_url_coupons_active_urls' );

			// set 60 minute transient
			set_transient( 'wc_url_coupons_active_urls', $this->active_coupon_urls, HOUR_IN_SECONDS );

		} else {

			// transient exists
			$this->active_coupon_urls = $coupons;
		}
	}


	/**
	 * Applies discount by checking request URI against array of coupon URLs.
	 *
	 * If there's a match, apply the discount and redirect to the page specified on the coupons page.
	 *
	 * @internal
	 *
	 * @since 1.0
	 *
	 * @throws \Exception
	 */
	public function maybe_apply_coupon() {

		// bail if no URL coupons exist
		if ( ! is_array( $this->active_coupon_urls ) || 0 === count( $this->active_coupon_urls ) ) {
			return;
		}

		// bail out to prevent adding more products to cart in some circumstances
		if ( is_ajax() ) {
			return;
		}

		// bail if not a HTTP request
		if ( ! isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		// form URL to start from for comparison
		// we cannot get `$wp->request` because it's not set this early, and we can't
		// hook in later due to the potential need to redirect
		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// force the URL scheme here because we can't depend on home_url giving us the one we want,
		// as it can often improperly be filtered by plugins / themes, while set_url_scheme is less likely to be filtered
		$home_url = set_url_scheme( home_url( '/' ), 'http' );

		// remove WP site URL to get request URI; this is used instead of the pure REQUEST_URI, as sites can
		// be hosted inside a sub-directory, which will be removed with this method
		$url = strtolower( str_replace( $home_url, '', $url ) );

		// save query vars
		parse_str( $_SERVER['QUERY_STRING'], $query_vars );

		// check if URL exists in coupons
		foreach ( $this->active_coupon_urls as $coupon_id => $coupon ) {

			// skip if coupon does not have unique url - avoids a rare redirect issue
			if ( ! isset( $coupon['url'] ) ) {
				continue;
			}

			$coupon_url = $coupon['url'];

			if ( ! empty( $coupon['prefix'] ) ) {
				$prefixed_coupon_url = trailingslashit( $coupon['prefix'] ) . $coupon_url;
			} else {
				$prefixed_coupon_url = $coupon_url;
			}

			// if uri starts with coupon URL, there is a match
			$url_matches = ! strncmp( $url, strtolower( $prefixed_coupon_url ), strlen( $prefixed_coupon_url ) );

			/**
			 * Filters whether the current URL matches a coupon URL.
			 *
			 * By default, any URL that starts with the coupon URL will be considered a match.
			 *
			 * @since 2.6.1
			 *
			 * @param bool $url_matches whether the url matches or not
			 * @param string $url current url
			 * @param string $prefixed_coupon_url coupon url (maybe prefixed)
			 * @param array $coupon coupon data
			 */
			if ( (bool) apply_filters( 'wc_url_coupons_url_matches_coupon', $url_matches, $url, $prefixed_coupon_url, $coupon ) ) {

				// add products to the cart
				if ( is_array( $coupon['products'] ) && count( $coupon['products'] ) >= 1 ) {
					// do not remove, before 2.1.5 this returned non empty `array( 0 => 0 )`
					if ( 0 !== $coupon['products'][0] ) {
						$this->add_product_to_cart( $coupon['products'] );
					}
				}

				// ensure code isn't filtered by the_title since we need it to match WC's checks
				$coupon_code = get_post( $coupon_id )->post_title;

				// check that coupon has not already been applied
				if ( ! WC()->cart->has_discount( $coupon_code ) ) {

					// apply the discount
					$applied = WC()->cart->add_discount( $coupon_code );

					// if the coupon couldn't be applied, defer it if allowed
					if ( ! $applied ) {

						if ( $coupon['defer'] ) {

							// start a session if needed
							if ( ! WC()->session->has_session() ) {
								WC()->session->set_customer_session_cookie( true );
							}

							// defer applying the coupon until it's valid
							$this->defer_apply( $coupon_id, $coupon_code );
						}

					} else {

						// if the coupon applied successfully and there's not
						// currently a session, start the customer session so the
						// coupon persists until the customer adds an item to the cart
						if ( ! WC()->session->has_session() ) {

							WC()->session->set_customer_session_cookie( true );
						}
					}
				}

				$redirect = $this->get_coupon_redirect_url( $url, $coupon );

				// bail if not redirecting
				if ( empty( $redirect ) ) {
					return;
				}

				/**
				 * Filters the query vars that are used in the redirect before redirecting.
				 *
				 * @since 2.5.3
				 *
				 * @param string[] $query_vars the query args to add
				 * @param \WC_Coupon $coupon_object the coupon object
				 * @param string[] $coupon URL coupon data {
				 *  @type string $url coupon URL slug
				 *  @type string $prefix coupon URL prefix
				 *  @type int $redirect redirect object ID (post or taxonomy)
				 *  @type string $redirect_page_type redirect object type (ie "page")
				 *  @type string[] $products product IDs to add to the cart
				 *  @type string $defer whether to defer apply, yes or no
				 * }
				 */
				$query_vars = apply_filters( 'wc_url_coupons_redirect_query_args', $query_vars, new \WC_Coupon( $coupon_id ), $coupon );

				// add query vars back so things like google analytics campaign tracking works
				if ( ! empty( $query_vars ) ) {
					$redirect = add_query_arg( $query_vars, $redirect );
				}

				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}


	/**
	 * Defers applying a URL coupon until it's valid.
	 *
	 * @since 2.0.0
	 *
	 * @param $coupon_id
	 * @param $coupon_code
	 * @throws \Exception
	 */
	protected function defer_apply( $coupon_id, $coupon_code ) {

		// get already deferred coupons
		$this->deferred_coupons = WC()->session->get( 'deferred_url_coupons', array() );

		// get the coupon error message
		$coupon = new \WC_Coupon( $coupon_code );
		$coupon->is_valid();

		$coupon_error = $coupon->get_error_message();

		// remove the core error notice as we'll be replacing it
		$this->maybe_remove_error_notices( $coupon_error );

		// add custom defer apply error messages
		add_filter( 'woocommerce_coupon_error', array( $this, 'get_defer_apply_notice_message' ), 10, 3 );

		// must check if coupon is valid again so our error message filtering works
		$coupon->is_valid();

		$defer_notice_message = $coupon->get_error_message();

		// remove custom defer apply error messages so we don't affect any unrelated notices
		remove_filter( 'woocommerce_coupon_error', array( $this, 'get_defer_apply_notice_message' ), 10 );

		$this->deferred_coupons[ $coupon_id ] = array(
			'code'   => $coupon_code,
			'notice' => $defer_notice_message,
		);

		// save to session
		WC()->session->set( 'deferred_url_coupons', $this->deferred_coupons );

		// display the notice
		if ( ! wc_has_notice( $defer_notice_message, 'notice' ) ) {
			wc_add_notice( $defer_notice_message, 'notice' );
		}
	}


	/**
	 * Gets the defer apply error message.
	 *
	 * @since 2.4.0
	 *
	 * @param string $error_message The error message
	 * @param int $error_code The coupon code
	 * @param object $coupon The coupon
	 * @return string The defer apply error message
	 */
	public function get_defer_apply_notice_message( $error_message, $error_code, $coupon ) {

		// change error message only if error is "actionable"
		if ( $this->is_error_actionable( $error_code ) ) {

			$coupon_id            = $coupon->get_id();
			$coupon_added         = __( 'Coupon added but not yet applied:', 'woocommerce-url-coupons' );
			$coupon_already_added = __( 'Coupon already added but not yet applied:', 'woocommerce-url-coupons' );
			$deferred_notice      = isset( $this->deferred_coupons[ $coupon_id ] ) ? $coupon_already_added : $coupon_added;

			$error_message = $deferred_notice . '<br />' . $error_message;
		}

		/**
		 * Filters the defer apply error message.
		 *
		 * @since 2.4.0
		 *
		 * @param string $error_message The error message
		 * @param int $error_code The coupon code
		 * @param \WC_Coupon $coupon The coupon
		 */
		return apply_filters( 'wc_url_coupons_defer_apply_notice_message', $error_message, $error_code, $coupon );
	}


	/**
	 * Maybe apply previously deferred coupons, this is hooked into the cart
	 * item check so it should only occur on the cart/checkout pages
	 *
	 * Note that if the customer then takes some action to make the coupon invalid
	 * (e.g. changing the cart total for a minimum spend coupon), the coupon will
	 * not* be re-applied and the customer will need to visit the URL in order
	 * to apply it again.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function maybe_apply_deferred_coupons() {

		$deferred_coupons = WC()->session->get( 'deferred_url_coupons' );

		if ( empty( $deferred_coupons ) ) {
			return;
		}

		// blank coupon error messages so the associated error notices can be removed
		add_filter( 'woocommerce_coupon_error', '__return_empty_string' );

		// Try and apply the coupon regardless of the "Hide coupon field" setting
		remove_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field' ) );

		foreach ( $deferred_coupons as $id => $coupon ) {

			// Check for old session storage format for backwards compatibility
			if ( ! is_array( $coupon ) ) {
				$coupon = array(
					'code'   => $coupon,
					'notice' => '',
				);
			}

			// apply the coupon
			if ( WC()->cart->add_discount( $coupon['code'] ) ) {

				// remove it if successful
				$this->remove_deferred_notice( $coupon['notice'] );

				unset( $deferred_coupons[ $id ] );
			}
		}

		// Restore the "Hide coupon field" setting
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field' ) );

		// housekeeping
		if ( empty( $deferred_coupons ) ) {
			unset( WC()->session->deferred_url_coupons );
		} else {
			WC()->session->set( 'deferred_url_coupons', $deferred_coupons );
		}

		// remove error notices for failed attempts
		$this->maybe_remove_error_notices();
	}


	/**
	 * Maybe removes a specific and/or blank error notices from the WC notice queue.
	 *
	 * @since 2.0.0
	 *
	 * @param string|null $error specific notice text to remove.
	 */
	protected function maybe_remove_error_notices( $error = null ) {

		$this->maybe_remove_notices( 'error', $error );
	}


	/**
	 * Removes specific or blank notices from the WC notice queue.
	 *
	 * @since 2.9.1
	 *
	 * @param string $notice_type the name of the notice type - either error, success or notice
	 * @param string|null $message specific notice text to remove
	 */
	private function maybe_remove_notices( $notice_type, $message = null ) {

		$notices = wc_get_notices();

		// nothing to do if no notices present
		if ( empty( $notices[ $notice_type ] ) ) {
			return;
		}

		foreach ( array_keys( $notices[ $notice_type ] ) as $key ) {

			$notice = $notices[ $notice_type ][ $key ];

			// remove a specific notice in WC 3.9+
			if ( isset( $notice['notice'] ) ) {

				if ( (string) $message === (string) $notice['notice'] || '' === (string) $notice['notice'] ) {
					unset( $notices[ $notice_type ][ $key ] );
				}

			// remove a specific notice in WC 3.8 or older
			} elseif ( is_string( $notice ) ) {

				if ( (string) $message === $notice || '' === $notice ) {
					unset( $notices[ $notice_type ][ $key ] );
				}
			}
		}

		wc_set_notices( $notices );
	}


	/**
	 * Removes a specific coupon deferment notice from the WC notice queue.
	 *
	 * @since 2.1.3
	 *
	 * @param string $notice The specific notice text to remove.
	 */
	protected function remove_deferred_notice( $notice ) {

		$this->maybe_remove_notices( 'notice', $notice );
	}


	/**
	 * Determines if provided error code is actionable.
	 *
	 * For example, whether the customer is able to recover from the error by
	 * performing an action such as removing something from the cart.
	 *
	 * @since 2.4.0
	 *
	 * @param int $error_code The error code.
	 * @return bool
	 */
	private function is_error_actionable( $error_code ) {

		$actionable_errors = array(
			\WC_Coupon::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET,
			\WC_Coupon::E_WC_COUPON_MAX_SPEND_LIMIT_MET,
			\WC_Coupon::E_WC_COUPON_NOT_APPLICABLE,
			\WC_Coupon::E_WC_COUPON_EXCLUDED_PRODUCTS,
			\WC_Coupon::E_WC_COUPON_EXCLUDED_CATEGORIES,
			\WC_Coupon::E_WC_COUPON_NOT_VALID_SALE_ITEMS,
		);

		/**
		 * Filters whether the error is actionable.
		 *
		 * @since 2.4.0
		 * @param bool $is_error_actionable true if error is actionable, false otherwise
		 */
		return apply_filters( 'wc_url_coupons_is_error_actionable', in_array( $error_code, $actionable_errors, false ) );
	}


	/**
	 * Gets the redirect URL for a given URL coupon.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url
	 * @param array|\WC_Coupon $coupon Coupon object.
	 * @return bool|null|string
	 */
	protected function get_coupon_redirect_url( $url, $coupon ) {

		// don't redirect if none was set
		if ( 0 === $coupon['redirect'] ) {
			return false;
		}

		// redirect to given page
		$redirect = wc_url_coupons()->get_object_url( $coupon['redirect'], $coupon['redirect_page_type'] );

		// default to homepage if errors occur
		if ( ! $redirect || is_wp_error( $redirect ) ) {
			$redirect = home_url();
		}

		// don't redirect if unique uri is the same as the redirect uri
		if ( str_replace( '/', '', $url ) === str_replace( '/', '', parse_url( $redirect, PHP_URL_PATH ) ) ) {
			return null;
		}

		return $redirect;
	}


	/**
	 * Adds the given product IDs to the customer's cart.
	 *
	 * @since 1.0
	 *
	 * @param array $product_ids
	 * @throws \Exception
	 */
	private function add_product_to_cart( $product_ids ) {

		foreach ( $product_ids as $product_id ) {

			$product = wc_get_product( absint( $product_id ) );

			if ( ! is_object( $product ) ) {
				continue;
			}

			// Variable product
			if ( $product->is_type( array( 'variable', 'variation' ) ) ) {

				/* @type \WC_Product_Variable|\WC_Product_Variation $product */
				$variation_id = $product->get_id();
				// Get variation data (attributes) for variable product
				$attributes   = str_replace( 'attribute_', '', $product->get_variation_attributes() );

				// Add to cart validation
				if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, 1, $variation_id, $attributes ) ) {
					continue;
				}

				WC()->cart->add_to_cart( $product_id, 1, $variation_id, $attributes );

			// Simple product
			} else {

				// Should be simple product,
				// unless admin made a mistake and selected a variation parent,
				// in which case don't add it
				if ( ! $product->is_type( 'variable' ) ) {

					// Add to cart validation
					if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, 1 ) ) {
						continue;
					}

					WC()->cart->add_to_cart( $product_id );
				}
			}
		}
	}


	/**
	 * Hides the coupon code field based on user settings.
	 *
	 * @see wc_coupons_enabled()
	 * @see \WC_Cart::remove_coupon()
	 *
	 * @internal
	 *
	 * @since 1.2
	 *
	 * @param bool $maybe_enabled enabled/disabled state of coupons
	 * @return bool
	 */
	public function hide_coupon_field( $maybe_enabled ) {
		global $wp_query;

		// don't bother if the WP_Query object isn't set: later we need to check for cart/checkout pages and that would trigger a WordPress notice when not in frontend context
		// this is because some plugins might access wc_coupons_enabled() in other contexts such as WC API, where our handling here doesn't really matter
		if ( empty( $wp_query ) ) {
			return $maybe_enabled;
		}

		/** @see \WC_AJAX::add_ajax_events() */
		$actions = [
			'wp_ajax_woocommerce_remove_coupon',
			'wp_ajax_nopriv_woocommerce_remove_coupon',
			'wc_ajax_remove_coupon',
		];

		// small workaround to allow removal of a coupon while clicking
		// on the remove coupon link while hiding the coupon field
		// in cart or checkout pages
		$removing_coupon = false;

		/** @see \WC_AJAX::remove_coupon() */
		foreach ( $actions as $action ) {

			if ( doing_action( $action ) ) {

				$removing_coupon = true;
				break;
			}
		}

		// sanity check: just return the default value if we are removing a coupon
		if ( ! $removing_coupon ) {

			// otherwise, handle the return value based on settings
			if ( is_cart() && 'yes' === get_option( 'wc_url_coupons_hide_coupon_field_cart' ) ) {

				$maybe_enabled = false;

				// allow WC to auto-remove invalid coupons even if the admin has opted not to display the field
				// the field will only persist for one page load
				foreach ( WC()->cart->get_applied_coupons() as $code ) {

					$coupon = new \WC_Coupon( $code );

					try {
						$maybe_enabled = ! $coupon->is_valid();
					} catch ( Exception $e ) {
						$maybe_enabled = false;
					}
				}
			}

			if ( is_checkout() && 'yes' === get_option( 'wc_url_coupons_hide_coupon_field_checkout' ) ) {
				$maybe_enabled = false;
			}
		}

		return $maybe_enabled;
	}


}
