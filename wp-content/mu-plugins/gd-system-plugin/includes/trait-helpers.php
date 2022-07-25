<?php

namespace WPaaS;
use GoDaddy\WordPress\Plugins\NextGen;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

trait Helpers {

	/**
	 * Return the plugin version.
	 *
	 * @return string|false
	 */
	public static function version() {

		return Plugin::$data['version'];

	}

	/**
	 * Return the plugin basename.
	 *
	 * @return string|false
	 */
	public static function basename() {

		return Plugin::$data['basename'];

	}

	/**
	 * Return the plugin base directory path (with trailing slash).
	 *
	 * @return string|false
	 */
	public static function base_dir() {

		return Plugin::$data['base_dir'];

	}

	/**
	 * Return the plugin assets URL (with trailing slash).
	 *
	 * @param  string $path (optional)
	 *
	 * @return string|false
	 */
	public static function assets_url( $path = '' ) {

		$path = ( 0 === strpos( $path, '/' ) ) ? $path : '/' . $path;

		return ( Plugin::$data['assets_url'] ) ? untrailingslashit( Plugin::$data['assets_url'] ) . $path : false;

	}

	/**
	 * Return the plugin assets directory path (with trailing slash).
	 *
	 * @param  string $path (optional)
	 *
	 * @return string|false
	 */
	public static function assets_dir( $path = '' ) {

		$path = ( 0 === strpos( $path, '/' ) ) ? $path : '/' . $path;

		return ( Plugin::$data['assets_url'] ) ? untrailingslashit( Plugin::$data['assets_dir'] ) . $path : false;

	}

	/**
	 * Return an array of bundled plugins that have been loaded.
	 *
	 * @return array
	 */
	public static function bundled_plugins_loaded() {

		return ! empty( Plugin::$data['bundled_plugins_loaded'] ) ? (array) Plugin::$data['bundled_plugins_loaded'] : [];

	}

	/**
	 * Return a plugin config.
	 *
	 * @param  string $config
	 *
	 * @return mixed|false
	 */
	public static function config( $config ) {

		return self::$configs->get( $config );

	}

	/**
	 * Check if the site locale is English.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_english() {

		$result = ( 'en' === substr( get_locale(), 0, 2 ) );

		/**
		 * Filter if the site locale is English.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_english', $result );

	}

	/**
	 * Return an array of supported brands.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function brands() {

		$brands = [ 'gd', 'mt', 'reseller' ];

		/**
		 * Filter the array of supported brands.
		 *
		 * @since 3.1.0
		 *
		 * @var array
		 */
		return (array) apply_filters( 'wpaas_brands', $brands );

	}

	/**
	 * Return the current brand.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function brand() {

		$brand  = ( self::reseller_id() ) ? 'reseller' : null; // Default
		$brands = array_diff( self::brands(), [ 'reseller' ] ); // Non-default

		foreach ( $brands as $brandname ) {

			$callback = 'is_' . trim( $brandname );

			if ( is_callable( [ __CLASS__, $callback ] ) && self::$callback() ) {

				$brand = $brandname;

				break;

			}

		}

		/**
		 * Filter the current brand.
		 *
		 * @since 3.1.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_brand', $brand );

	}

	/**
	 * Return the value whose array key matches the current brand.
	 *
	 * @since 3.1.0
	 *
	 * @param  array $values
	 * @param  mixed $default (optional)
	 *
	 * @return mixed
	 */
	public static function use_brand_value( $values, $default = null ) {

		return isset( $values[ self::brand() ] ) ? $values[ self::brand() ] : $default;

	}

	/**
	 * Check if this is a reseller site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_reseller() {

		$result = ( 'reseller' === self::brand() );

		/**
		 * Filter if this is a reseller site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_reseller', $result );

	}

	/**
	 * Check if this is a GD site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_gd() {

		$result = ( 1 === self::reseller_id() );

		/**
		 * Filter if this is a GD site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_gd', $result );

	}

	/**
	 * Check if this is a MT site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_mt() {

		$result = ( 495469 === self::reseller_id() );

		/**
		 * Filter if this is a MT site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_mt', $result );

	}

	/**
	 * Check if a given (or current) URL is using 'www' in the domain.
	 *
	 * @param  string $url (optional)
	 *
	 * @return bool
	 */
	public static function is_www_url( $url = '' ) {

		$url = $url ? $url : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' );

		return ( 0 === strpos( wp_parse_url( $url, PHP_URL_HOST ), 'www.' ) );

	}

	/**
	 * Check if the WP Admin should be forced SSL.
	 *
	 * @return bool
	 */
	public static function is_ssl_admin() {

		return ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ); // @codingStandardsIgnoreLine

	}

	/**
	 * Check if the login should be forced SSL.
	 *
	 * @return bool
	 */
	public static function is_ssl_login() {

		return ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN ); // @codingStandardsIgnoreLine

	}

	/**
	 * Check if this is a staging site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_staging_site() {

		$result = defined( 'GD_STAGING_SITE' ) ? GD_STAGING_SITE : false;

		/**
		 * Filter if this is a staging site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_staging_site', $result );

	}

	/**
	 * Get the current environment type.
	 *
	 * @return string
	 */
	public static function get_env() {

		if ( $env = getenv( 'SERVER_ENV' ) ) {

			return $env;

		}

		preg_match( '/\.(.*?)\-/', wp_parse_url( self::config( 'cname_link' ), PHP_URL_HOST ), $matches );

		$result = empty( $matches[1] ) ? 'prod' : $matches[1];

		/**
		 * Filter the current environment type.
		 *
		 * @since 2.0.1
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_get_env', $result );

	}

	/**
	 * Check for a specific environment.
	 *
	 * @param  string|array $env
	 *
	 * @return bool
	 */
	public static function is_env( $env ) {

		$current = self::get_env();
		$result  = is_array( $env ) ? in_array( $current, $env, true ) : ( $env === $current );

		/**
		 * Filter the check for a specific environment.
		 *
		 * @since 2.0.1
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_env', $result );

	}

	/**
	 * Check if this is a temporary domain.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_temp_domain() {

		$result = false;

		if ( self::is_staging_site() ) {

			$result = true;

		}

		foreach ( (array) self::config( 'cname_domains' ) as $domain ) {

			if ( 0 === strcasecmp( substr( self::domain(), 0 - strlen( $domain ) ), $domain ) ) {

				$result = true;

			}

		}

		/**
		 * Filter if this is a temporary domain.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_temp_domain', $result );

	}

	/**
	 * Check if this site is in multiple domain mode.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_multi_domain_mode() {

		$result = get_option( 'gd_system_multi_domain' );

		/**
		 * Filter if this site is in multiple domain mode.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_multi_domain_mode', ( false !== $result ) );

	}

	/**
	 * Check if this site is hosted on WPaaS.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_wpaas() {

		$result = self::$configs->exist() || false !== strpos( gethostname(), '.secureserver.net' ) || ! empty($_SERVER['WPAAS_SITE_ID']) || getenv('WPAAS_SITE_ID');

		/**
		 * Filter if this site is hosted on WPaaS.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'is_wpaas', $result );

	}

	/**
	 * Check if the log is enabled.
	 *
	 * @return bool
	 */
	public static function is_log_enabled() {

		/**
		 * Filter if the log is enabled.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_log_enabled', true );

	}

	/**
	 * Check if the file editor has been enabled.
	 *
	 * @return bool
	 */
	public static function is_file_editor_enabled() {

		return ( 1 === (int) get_site_option( 'wpaas_file_editor_enabled' ) );

	}

	/**
	 * Return the date this site was created.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return int|string
	 */
	public static function site_created_date( $format = 'U' ) {

		// Use when this constant was introduced as default (Tue, 22 Dec 2015 00:00:00 GMT)
		$time   = defined( 'GD_SITE_CREATED' ) ? (int) GD_SITE_CREATED : 1450742400;
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( 'U' === $format ) ? $time : gmdate( $format, $time );

		/**
		 * Filter the date this site was created.
		 *
		 * @since 2.0.0
		 *
		 * @var int|string
		 */
		return apply_filters( 'wpaas_site_created_date', $date );

	}

	/**
	 * Return the date of the first Administrator login.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function first_login_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_first_login' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the last Administrator login.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_login_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_login' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the first publish activity.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function first_publish_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_first_publish' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the last publish activity.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_publish_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_publish' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the last cache flush date.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_cache_flush_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_cache_flush' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		/**
		 * Filter the last cache flush date.
		 *
		 * @since 2.0.0
		 *
		 * @var mixed
		 */
		return apply_filters( 'wpaas_last_cache_flush_date', $date );

	}

	/**
	 * Check if this site has used WPEM (not opted-out).
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function has_used_wpem() {

		$result = ( defined( 'GD_EASY_MODE' ) && GD_EASY_MODE && get_option( 'wpem_done' ) && ! get_option( 'wpem_opt_out' ) );

		/**
		 * Filter if this site has used WPEM (not opted-out).
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_used_wpem', $result );

	}

	/**
	 * Check if this site used WPNUX starter template on-boarding.
	 *
	 * @since 3.11.0
	 *
	 * @return bool
	 */
	public static function has_used_wpnux() {

		$result = ! empty( get_option( 'wpnux_imported' ) );

		/**
		 * Filter if this site used WPNUX starter template on-boarding.
		 *
		 * @since 3.11.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_used_wpnux', $result );

	}

	/**
	 * Check if this site should use our simplified UX.
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	public static function use_simple_ux() {

		$result = defined( 'GD_SIMPLE_UX' ) ? GD_SIMPLE_UX : ( ! WP_DEBUG && 'go' === get_option( 'stylesheet' ) && self::has_used_wpnux() );

		/**
		 * Filter if this site should use our simplified UX.
		 *
		 * @since 3.12.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_use_simple_ux', $result );

	}

	/**
	 * Check if this site should use our NextGen UX.
	 *
	 * @since 3.17.0
	 *
	 * @return bool
	 */
	public static function use_nextgen() {

		return (bool) NextGen\Plugin::load()->is_user_session_enabled();

	}

	/**
	 * Check if a plugin is currently active.
	 *
	 * @since 3.17.0
	 *
	 * @param  string $basename
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $basename ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		return is_plugin_active( $basename );

	}

	/**
	 * Check if a plugin is currently activating.
	 *
	 * @since 3.17.0
	 *
	 * @param  string $basename
	 *
	 * @return bool
	 */
	public static function is_plugin_activating( $basename ) {

		return ( is_admin() && filter_input( INPUT_GET, 'plugin' ) === $basename && in_array( filter_input( INPUT_GET, 'action' ), [ 'error_scrape', 'activate' ], true ) );

	}

	/**
	 * Check if this site has a particular plan.
	 *
	 * @since 3.11.2
	 *
	 * @param string $plan
	 *
	 * @return bool
	 */
	public static function has_plan( $plan ) {

		$result = defined( 'GD_PLAN_NAME' ) ? ( GD_PLAN_NAME == $plan ) : false; // Loose comparison OK.

		/**
		 * Filter if this site has a particular plan.
		 *
		 * @since 3.11.2
		 *
		 * @param string $plan
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_plan', $result, $plan );

	}

	/**
	 * Return the reseller ID.
	 *
	 * @since 2.0.0
	 *
	 * @return int|false
	 */
	public static function reseller_id() {

		return defined( 'GD_RESELLER' ) ? (int) GD_RESELLER : false;

	}

	/**
	 * Return the site domain.
	 *
	 * @return string
	 */
	public static function domain() {

		return wp_parse_url( home_url(), PHP_URL_HOST );

	}

	/**
	 * Return an external URL useful for hosting account management.
	 *
	 * @return string
	 */
	public static function account_url( $path = 'overview' ) {

		$domain = self::domain();

		if ( self::is_mt() ) {

			return in_array( $path, [ 'overview', 'settings' ], true ) ? 'https://ac.mediatemple.net/services/wordpress/plugin-callback/index.mt' : "https://ac.mediatemple.net/services/wordpress/plugin-callback/index.mt?domain={$domain}&action={$path}";

		}

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";
		$tld    = self::is_gd() ? 'godaddy.com' : 'secureserver.net';

		return "https://myh.{$prefix}{$tld}/#/hosting/mwp/v1/sitelookup/?domain={$domain}&path={$path}";

	}

	/**
	 * Return the VIP.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	public static function vip() {

		return defined( 'GD_VIP' ) ? (string) GD_VIP : false;

	}

	/**
	 * Return the account ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function account_id() {

		$account_id = self::is_wp_cli() ? basename( dirname( ABSPATH ) ) : ( ! empty( $_SERVER['REAL_USERNAME'] ) ? $_SERVER['REAL_USERNAME'] : false );

		/**
		 * Filter the account ID.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_account_id', $account_id );

	}

	/**
	 * Return the ASAP key.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	public static function asap_key() {

		$asap_key = defined( 'GD_ASAP_KEY' ) ? (string) GD_ASAP_KEY : false;

		/**
		 * Filter the ASAP key.
		 *
		 * @since 2.0.0
		 *
		 * @var string|false
		 */
		return apply_filters( 'wpaas_asap_key', $asap_key );

	}

	/**
	 * Return the XID.
	 *
	 * @since 2.0.0
	 *
	 * @return int|false
	 */
	public static function xid() {

		$xid = self::is_wp_cli() ? (int) substr( substr( self::account_id(), 4 ), 0, -3 ) : ( isset( $_SERVER['XID'] ) ? (int) $_SERVER['XID'] : 0 );
		$xid = ( $xid > 1000000 ) ? $xid : false;

		/**
		 * Filter the XID.
		 *
		 * @since 2.0.0
		 *
		 * @var int|false
		 */
		return apply_filters( 'wpaas_xid', $xid );

	}

	/**
	 * Check if the current process is using WP-CLI.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_wp_cli() {

		return ( defined( 'WP_CLI' ) && WP_CLI );

	}

	/**
	 * Base WP-CLI command.
	 *
	 * @return string
	 */
	public static function cli_base_command() {

		$commands = [
			'gd' => 'godaddy',
			'mt' => 'mt',
		];

		$command = self::use_brand_value( $commands, 'wpaas' );

		/**
		 * Filter the base WP-CLI command.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_cli_base_command', $command );

	}

	/**
	 * Return a WP-CLI command.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $subcommand
	 * @param  array  $options (optional)
	 * @param  bool   $wp (optional)
	 *
	 * @return string
	 */
	public static function cli_command( $subcommand, array $options = [], $wp = true ) {

		foreach ( $options as $key => &$value ) {

			$value = is_bool( $value ) ? sprintf( '--%s', $key ) : sprintf( '--%s=%s', $key, is_int( $value ) ? $value : escapeshellarg( $value ) );

		}

		return trim(
			sprintf(
				'%s %s %s %s',
				( $wp ) ? 'wp' : null,
				escapeshellcmd( self::cli_base_command() ),
				escapeshellcmd( $subcommand ),
				implode( ' ', $options )
			)
		);

	}

	/**
	 * Return an asyncronous WP-CLI command.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $subcommand
	 * @param  array  $options (optional)
	 * @param  bool   $wp (optional)
	 *
	 * @return string
	 */
	public static function async_cli_command( $subcommand, array $options = [], $wp = true ) {

		return self::cli_command( $subcommand, $options, $wp ) . ' > /dev/null 2>/dev/null &'; // Non-blocking

	}

	/**
	 * Set/update the value of a site transient using a persistent manner. Uses options API.
	 *
	 * You do not need to serialize values, if the value needs to be serialize, then
	 * it will be serialized before it is set.
	 *
	 * @since 2.0.2
	 *
	 * @see set_site_transient()
	 *
	 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
	 *                           40 characters or fewer in length.
	 * @param mixed  $value      Transient value. Expected to not be SQL-escaped.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool False if value was not set and true if value was set.
	 */
	public static function set_persistent_site_transient( $transient, $value, $expiration = 0 ) {

		$transient_timeout = '_site_transient_timeout_' . $transient;
		$option            = '_site_transient_' . $transient;

		if ( false === get_site_option( $option ) ) {

			if ( $expiration ) {

				add_site_option( $transient_timeout, time() + $expiration );

			}

			return add_site_option( $option, $value );

		}

		if ( $expiration ) {

			update_site_option( $transient_timeout, time() + $expiration );

		}

		return update_site_option( $option, $value );

	}

	/**
	 * Transient function that skips object cache check and fallback to db instead.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 *
	 * @since 2.0.2
	 *
	 * @see get_site_transient()
	 *
	 * @return bool|mixed
	 */
	public static function get_persistent_site_transient( $transient ) {

		$transient_option  = '_site_transient_' . $transient;
		$transient_timeout = '_site_transient_timeout_' . $transient;
		$timeout           = get_site_option( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {

			delete_site_option( $transient_option );
			delete_site_option( $transient_timeout );

			$value = false;

		}

		if ( ! isset( $value ) ) {

			$value = get_site_option( $transient_option );

		}

		return $value;

	}

	/**
	 * Get the WooCommerce extension basename from its slug.
	 *
	 * Sometimes extensions have a non-standard basename so we need this
	 * helper method to ensure those are dealt with appropriately.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function get_woo_extension_basename( $slug ) {

		$slug_to_basename = [
			'woocommerce-product-enquiry-form' => 'product-enquiry-form',
		];

		$filename = isset( $slug_to_basename[ $slug ] ) ? $slug_to_basename[ $slug ] : $slug;

		return "{$slug}/{$filename}.php";

	}

	/**
	 * Determine which builder platform was used to create the current page.
	 *
	 * Possible values:
	 *
	 * -- beaver-builder
	 * -- brizy
	 * -- divy
	 * -- elementor
	 * -- oxygen
	 * -- themify-builder
	 * -- visual-composer
	 * -- wp-block-editor (Gutenberg)
	 * -- wp-classic-editor
	 *
	 * Will return NULL when:
	 *
	 * -- The builder plugin used to construct the page is inactive.
	 * -- The WP Block Editor is being used but the page contains no blocks.
	 * -- A page builder platform can't be detected.
	 *
	 * @param WP_Post $post
	 *
	 * @return string|null
	 */
	public static function get_page_builder( $post ) {

		if ( ! is_a( $post, 'WP_Post' ) ) {

			global $post;

		}

		if ( ! isset( $post->post_content ) ) {

			return;

		}

		switch ( true ) {

			case ( class_exists( 'FLBuilderLoader' ) && 1 === (int) get_post_meta( $post->ID, '_fl_builder_enabled', true ) ):

				$builder = 'beaver-builder';

				break;

			case ( defined( 'BRIZY_VERSION' ) && get_post_meta( $post->ID, 'brizy_post_uid', true ) ):

				$builder = 'brizy';

				break;

			case ( defined( 'ET_BUILDER_VERSION' ) && 'on' === get_post_meta( $post->ID, '_et_pb_use_builder', true ) ):

				$builder = 'divi';

				break;

			case ( defined( 'ELEMENTOR_VERSION' ) && 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true ) ):

				$builder = 'elementor';

				break;

			case ( defined( 'CT_VERSION' ) && get_post_meta( $post->ID, 'ct_builder_shortcodes', true ) ):

				$builder = 'oxygen';

				break;

			case ( defined( 'THEMIFY_VERSION' ) && get_post_meta( $post->ID, '_themify_builder_settings_json', true ) ):

				$builder = 'themify-builder';

				break;

			case ( defined( 'VCV_VERSION' ) && 'vc' === get_post_meta( $post->ID, '_vcv-page-template-type', true ) ):

				$builder = 'visual-composer';

				break;

			case class_exists( 'Classic_Editor' ):

				// Normalize old options: https://plugins.trac.wordpress.org/browser/classic-editor/trunk/classic-editor.php?rev=2084072#L254
				$default = in_array( get_option( 'classic-editor-replace' ), [ 'block', 'no-replace' ], true ) ? 'block-editor' : 'classic-editor';
				$builder = ( 'allow' === get_option( 'classic-editor-allow-users' ) ) ? get_post_meta( $post->ID, 'classic-editor-remember', true ) : $default;
				$builder = in_array( $builder, [ 'block-editor', 'classic-editor' ], true ) ? $builder : $default;
				$builder = 'wp-' . $builder;

				break;

			default:

				$builder = ( false !== strpos( $post->post_content, '<!-- wp:' ) ) ? 'wp-block-editor' : null;

		}

		return $builder;

	}

	/**
	 * Forcefully unregister an action or filter by its hooked class name and method.
	 *
	 * For those times when a plugin has registered a hook to a class method
	 * without also storing the instance of that class object in a global variable.
	 *
	 * @param string       $hook_name
	 * @param string       $class_name
	 * @param string|array $method_name
	 *
	 * @return void
	 */
	public static function force_remove_hook( $hook_name, $class_name, $method_name ) {

		global $wp_filter;

		if ( empty( $wp_filter[ $hook_name ] ) ) {

			return;

		}

		$wp_hook = (array) $wp_filter[ $hook_name ];

		if ( empty( $wp_hook['callbacks'] ) ) {

			return;

		}

		foreach ( $wp_hook['callbacks'] as $priority => $filters ) {

			foreach ( $filters as $unique_id => $filter ) {

				if ( is_object( $filter['function'] ) || empty( $filter['function'][0] ) || empty( $filter['function'][1] ) ) {

					continue;

				}

				if ( ! is_object( $filter['function'][0] ) || $class_name !== get_class( $filter['function'][0] ) || ! in_array( $filter['function'][1], (array) $method_name, true ) ) {

					continue;

				}

				if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {

					unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );

				} else {

					unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );

				}

			}

		}

	}

}
