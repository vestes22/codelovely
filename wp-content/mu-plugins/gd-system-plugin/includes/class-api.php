<?php

namespace WPaaS;

use \WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

interface API_Interface {

	public function get_blacklist();

	public function is_valid_sso_hash( $hash );

	public function user_changed_domain( $domain = '' );

	public function get_woocommerce_products( $product_type );

	public function refresh_blog_title( $blogname = null );

	public function refresh_nextgen_compatibility( $is_nextgen_compat = false );

	public function toggle_rum( $enabled = true );

}

final class API implements API_Interface {

	/**
	 * Return an array of blacklisted plugins.
	 *
	 * Note: The transient used here is persistent, meaning it
	 * will not be short-circuited by object cache and it will
	 * always be set to a non-false value regardless of the API
	 * response.
	 *
	 * @return array
	 */
	public function get_blacklist() {

		$transient = Plugin::get_persistent_site_transient( 'gd_system_blacklist' );

		if ( false !== $transient ) {

			return (array) $transient;

		}

		$response  = $this->call( 'blacklistapi/' );
		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$blacklist = ! empty( $body['data'] ) ? (array) $body['data'] : [];

		Plugin::set_persistent_site_transient( 'gd_system_blacklist', $blacklist, HOUR_IN_SECONDS );

		return $blacklist;

	}

	/**
	 * Validate an SSO hash.
	 *
	 * @param  string $hash
	 *
	 * @return bool
	 */
	public function is_valid_sso_hash( $hash ) {

		$response = $this->call(
			sprintf( 'ssoauthenticationapi/%s?AllowSsoLogin', $hash ),
			sprintf( '"%s"', DB_NAME ),
			'POST'
		);

		$body = wp_remote_retrieve_body( $response );

		return ( $body ) ? ( 'true' === strtolower( $body ) ) : false;

	}

	/**
	 * Check if a user has changed their domain.
	 *
	 * It isn't reflected here yet because we're waiting on the
	 * DNS TTL to take effect.
	 *
	 * Note: The transient used here is persistent, meaning it
	 * will not be short-circuited by object cache and it will
	 * always be set to a non-false value regardless of the API
	 * response.
	 *
	 * @param  string $cname (optional)
	 *
	 * @return bool
	 */
	public function user_changed_domain( $cname = '' ) {

		$transient = Plugin::get_persistent_site_transient( 'gd_system_domain_changed' );

		if ( false !== $transient ) {

			return (
				1 === (int) $transient
				||
				'Y' === $transient // Back compat
			);

		}

		$cname    = ( $cname ) ? $cname : Plugin::domain();
		$response = $this->call( 'domains/' . $cname );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$changed  = ! empty( $body['domainChanged'] ) ? 1 : 0;
		$timeout  = Plugin::config( 'cname_timeout' ) ? Plugin::config( 'cname_timeout' ) : 300;

		Plugin::set_persistent_site_transient( 'gd_system_domain_changed', $changed, absint( $timeout ) );

		return ( 1 === $changed );

	}

	/**
	 * Retreive WooCommerce product
	 *
	 * @param string $product_type The type of product to achieve.
	 *
	 * @return array $products The retreived products from the WooCommerce API.
	 */
	public function get_woocommerce_products( $product_type ) {

		$product_cache = get_transient( 'wpaas_woocommerce_' . $product_type );

		if ( ! WP_DEBUG && false !== $product_cache ) {

			return $product_cache;

		}

		add_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'timeout_30s_http_args' ] );

		$request = $this->call( 'partner/a8c/woocommerce/info' );

		remove_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'timeout_30s_http_args' ] );

		if ( 200 !== wp_remote_retrieve_response_code( $request ) || is_wp_error( $request ) ) {

			set_transient( 'wpaas_woocommerce_' . $product_type, [], 15 * MINUTE_IN_SECONDS );

			return [];

		}

		$products = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( empty( $products ) || ! is_array( $products ) || ! isset( $products['products'] ) ) {

			set_transient( 'wpaas_woocommerce_' . $product_type, [], 15 * MINUTE_IN_SECONDS );

			return [];

		}

		$type = 'extensions' === $product_type ? 'plugin' : 'theme';

		// Return the proper product type only.
		$products = array_filter(
			$products['products'],
			function( $extension ) use ( $type ) { return $type === $extension['type']; }
		);

		set_transient( 'wpaas_woocommerce_' . $product_type, $products, 8 * HOUR_IN_SECONDS );

		return $products;

	}

	/**
	 * Request that the API refresh its copy of the blog title.
	 *
	 * @param string $blogname (optional)
	 *
	 * @return void
	 */
	public function refresh_blog_title( $blogname = null ) {

		add_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = $blogname ? [ 'blogname' => htmlspecialchars_decode( $blogname, ENT_QUOTES ) ] : [];

		$this->call( 'refreshBlogTitle', $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Request that the API refresh wether or not the site is nextgen compatible.
	 *
	 * @param boolean $is_nextgen_compat (optional)
	 *
	 * @return void
	 */
	public function refresh_nextgen_compatibility( $is_nextgen_compat = false ) {

		add_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [ 'nextgenEnabled' => (bool) $is_nextgen_compat ];

		$this->call( 'nextgen', $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Send RAD data to the API.
	 *
	 * @param string $name
	 * @param array  $metadata (optional)
	 *
	 * @return void
	 */
	public function log_rad_event( $name, $metadata = [] ) {

		add_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [
			'datetime' => current_time( 'mysql', 1 ),
			'metadata' => (array) $metadata,
			'name'     => $name,
		];

		$this->call( 'rad/event', $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Toggle RUM for this site.
	 *
	 * @param bool $enabled True or false
	 *
	 * @return void
	 */
	public function toggle_rum( $enabled = true ) {

		add_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [
			'rumEnabled' => (bool) $enabled,
		];

		$this->call( 'rum', $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'v3_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Filter the API URL when using the V3 API.
	 *
	 * @param string $api_url
	 *
	 * @return string
	 */
	public function v3_api_url( $api_url ) {

		if ( ! defined( 'GD_ACCOUNT_UID' ) || ! GD_ACCOUNT_UID ) {

			return;

		}

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";

		return sprintf(
			"https://mwp.api.phx3.{$prefix}godaddy.com/api/v1/mwp/sites/%s/",
			GD_ACCOUNT_UID
		);

	}

	/**
	 * Filter the HTTP request args when using the V3 API.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function v3_api_http_args( $http_args ) {

		if ( defined( 'GD_SITE_TOKEN' ) && GD_SITE_TOKEN ) {

			$http_args['headers']['X-Site-Token'] = GD_SITE_TOKEN;

		}

		return $http_args;

	}

	/**
	 * Filter the HTTP request args to use a 30s timeout.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function timeout_30s_http_args( $http_args ) {

		$http_args['timeout'] = 30;

		return $http_args;

	}

	/**
	 * Filter the HTTP request args to make calls non-blocking.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function non_blocking_http_args( $http_args ) {

		$http_args['blocking'] = false;
		$http_args['timeout']  = 5;

		return $http_args;

	}

	/**
	 * Make an API call.
	 *
	 * @param  string        $method
	 * @param  array|string  $method_args (optional)
	 * @param  string        $http_verb   (optional)
	 *
	 * @return array|WP_Error
	 */
	private function call( $method, $method_args = [], $http_verb = 'GET' ) {

		$api_url = (string) apply_filters( 'wpaas_api_url', Plugin::config( 'api_url' ) );

		if ( ! $api_url ) {

			return new WP_Error( 'wpaas_api_url_not_found' );

		}

		$http_args = (array) apply_filters(
			'wpaas_api_http_args',
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);

		$url = trailingslashit( $api_url ) . $method;

		$retries     = 0;
		$max_retries = 1;

		add_filter( 'https_ssl_verify', '__return_false' );

		while ( $retries <= $max_retries ) {

			$retries++;

			switch ( $http_verb ) {

				case 'GET':
					if ( ! empty( $method_args ) ) {

						$url .= '?' . build_query( $method_args );

					}

					$response = wp_remote_get( $url, $http_args );

					break;

				case 'POST':
					$http_args['body'] = (bool) apply_filters( 'wpaas_api_http_post_body_json', false ) ? json_encode( $method_args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : $method_args;

					$response = wp_remote_post( $url, $http_args );

					break;

				default:
					return new WP_Error( 'wpaas_api_invalid_http_verb' );

			}

			$response_code = wp_remote_retrieve_response_code( $response );

			// Check if we aren't on the last iteration and we can try the request again
			if (
				$retries <= $max_retries
				&&
				$this->is_retryable( $response, $response_code )
			) {

				// Give some time for the API to recover
				sleep( (int) apply_filters( 'wpaas_api_retry_delay', 1 ) );

				continue;

			}

			break;

		}

		remove_filter( 'https_ssl_verify', '__return_false' );

		if ( 200 !== $response_code ) {

			return new WP_Error( 'wpaas_api_bad_status', $response_code );

		}

		return $response;

	}

	/**
	 * Check if a response is an error and retryable.
	 *
	 * @param  array|WP_Error $response
	 * @param  int   $response_code
	 *
	 * @return bool
	 */
	private function is_retryable( $response, $response_code ) {

		if ( 200 === $response_code ) {

			return false;

		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
			isset( $body['status'], $body['type'], $body['code'] )
			&&
			503 === absint( $body['status'] )
			&&
			'error' === $body['type']
			&&
			'RetryRequest' === $body['code']
		) {

			return true;

		}

		return false;

	}

}
