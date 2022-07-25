<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class SSO {

	/**
	 * Query arg to identify sso problems
	 */
	const INVALID_SSO_QARG = 'wpaas_invalid_sso';

	/**
	 * Instance of the API.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * Class constructor.
	 *
	 * @param API_Interface $api
	 */
	public function __construct( API_Interface $api ) {

		$this->api = $api;

		/**
		 * We must + 1 the minimum integer when hooking into SSO to ensure
		 * that the Log class captures these events properly.
		 *
		 * Note: In WordPress 4.7 the SORT_NUMERIC flag was added to ksort()
		 * for sorting filter priorities. There is also a bug in PHP 5 that
		 * treats ~PHP_INT_MAX as greater than -PHP_INT_MAX. For this reason,
		 * ~PHP_INT_MAX should never be used as a filter priority in WP.
		 *
		 * Note: Must hook into setup_theme for customize.php to work.
		 *
		 * @link https://gist.github.com/fjarrett/d2d1d60930d2ca4e67d35cf672ac9b13
		 */
		add_action( 'setup_theme',       [ $this, 'init' ], -PHP_INT_MAX + 1 );
		add_action( 'login_init',        [ $this, 'login_init' ], PHP_INT_MAX );
		add_action( 'shake_error_codes', [ $this, 'shake_error_codes' ] );

		add_filter( 'wp_login_errors', [ $this, 'wp_login_errors' ] );

	}

	/**
	 * Initialize script.
	 *
	 * @action setup_theme
	 */
	public function init() {

		// @codingStandardsIgnoreStart
		$action = ! empty( $_REQUEST['GD_COMMAND'] ) ? strtolower( $_REQUEST['GD_COMMAND'] ) : filter_input( INPUT_GET, 'wpaas_action' ); // Backward compat.
		$hash   = ! empty( $_REQUEST['SSO_HASH'] ) ? $_REQUEST['SSO_HASH'] : filter_input( INPUT_GET, 'wpaas_sso_hash' ); // Backward compat.
		// @codingStandardsIgnoreEnd

		if ( 'sso_login' !== $action || ! $hash ) {

			return;

		}

		$uri      = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
		$redirect = remove_query_arg( [ 'GD_COMMAND', 'SSO_HASH', 'wpaas_action', 'wpaas_sso_hash', 'nocache' ], home_url( $uri ) );
		$redirect = preg_match( '~^/wp-login\.php~', $uri ) ? admin_url() : $redirect;

		// Go theme users should go straight to the Colors panel.
		if ( urldecode( $redirect ) === admin_url( 'customize.php' ) && 'go' === get_option( 'stylesheet' ) ) {

			$redirect = admin_url( 'customize.php?autofocus[section]=colors' );

		}

		if ( is_user_logged_in() ) {

			wp_safe_redirect( esc_url_raw( $redirect ) );

			exit;

		}

		$user_id = $this->user_id();

		if ( is_int( $user_id ) ) {

			if ( $hash && $this->api->is_valid_sso_hash( $hash ) ) {

				@wp_set_auth_cookie( $user_id ); // @codingStandardsIgnoreLine

				wp_safe_redirect( esc_url_raw( $redirect ) );

				exit;

			}

		}

		wp_safe_redirect( add_query_arg( static::INVALID_SSO_QARG, '', wp_login_url( admin_url() ) ) );

		exit;

	}

	/**
	 * Initialize the GD SSO login button.
	 *
	 * @action login_init
	 */
	public function login_init() {

		/**
		 * Filter to forcefully disable the SSO login functionality.
		 *
		 * @var bool
		 */
		$enabled = (bool) apply_filters( 'wpaas_gd_sso_button_enabled', true );

		// Only show if all conditions are met. Bail if any other plugin is customizing the login form.
		if ( ! $enabled || WP_DEBUG || ! Plugin::is_gd() || ! Plugin::use_nextgen() || ! defined( 'GD_ACCOUNT_UID' ) || ! GD_ACCOUNT_UID || (bool) filter_input( INPUT_GET, 'wpaas-standard-login' ) || has_action( 'login_form' ) || has_action( 'login_enqueue_scripts' ) ) {

			return;

		}

		// Add a body class for our SSO login form styles.
		add_filter( 'login_body_class', function ( $classes ) {

			$classes[] = 'wpaas-show-sso-login';

			return $classes;

		} );

		add_action( 'login_head',            [ $this, 'login_head' ] );
		add_action( 'login_form',            [ $this, 'login_form' ] );
		add_action( 'login_footer',          [ $this, 'login_footer' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'login_enqueue_scripts' ] );

	}

	/**
	 * Load fonts for SSO login button.
	 *
	 * @action login_head
	 */
	public function login_head() {

		?>
		<link rel="preload" href="//img1.wsimg.com/ux/fonts/sherpa/1.1/gdsherpa-bold.woff2" as="font" type="font/woff2" crossorigin=""/>
		<style>
		@font-face {
			font-family: gdsherpa;
			src: url(//img1.wsimg.com/ux/fonts/sherpa/1.1/gdsherpa-bold.woff2) format("woff2"),
			     url(//img1.wsimg.com/ux/fonts/sherpa/1.1/gdsherpa-bold.woff) format("woff");
			font-weight: 500;
			font-display: swap;
		}
		</style>
		<?php

	}

	/**
	 * Display the SSO login button.
	 *
	 * @action login_form
	 */
	public function login_form() {

		$env = Plugin::get_env();

		$sso_url = sprintf(
			'https://%s/#/hosting/mwp/v1/site/%s/sso?path=/wp-admin&type=wp&origin=wp-login',
			( 'prod' === $env ) ? 'myh.godaddy.com' : "myh.{$env}-godaddy.com",
			GD_ACCOUNT_UID
		);

		?>
		<div class="wpaas-sso-login-wrapper">

			<div class="wpaas-sso-login-button">
				<a href="<?php echo esc_url( $sso_url ); ?>" rel="nofollow" class="button button-primary">
					<svg width="42" height="37" viewBox="0 0 42 37" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><path d="M37.633 18.13c-.562 2.115-1.472 4.177-2.705 6.13a22.93 22.93 0 0 1-2.649 3.435c1.112-4.482.36-9.973-2.367-15.122a.69.69 0 0 0-.985-.265l-8.49 5.25a.683.683 0 0 0-.221.946l1.245 1.97c.203.322.631.42.956.22l5.503-3.403c.184.523.35 1.05.49 1.58.53 1.991.727 3.936.587 5.778-.262 3.429-1.673 6.101-3.974 7.524-1.149.71-2.484 1.086-3.934 1.127h-.177c-1.451-.04-2.786-.417-3.936-1.128-2.3-1.422-3.711-4.094-3.973-7.523-.14-1.842.057-3.787.586-5.779.562-2.114 1.472-4.177 2.706-6.13a22.321 22.321 0 0 1 4.382-5.093c1.578-1.344 3.258-2.372 4.993-3.054 3.23-1.271 6.275-1.187 8.576.235 2.3 1.422 3.712 4.094 3.973 7.524.141 1.842-.056 3.786-.586 5.778m-30.56 6.13c-1.234-1.953-2.144-4.015-2.706-6.13-.53-1.992-.727-3.936-.586-5.778.261-3.43 1.672-6.102 3.973-7.524 2.3-1.422 5.347-1.506 8.576-.235.487.191.968.413 1.444.66a26.242 26.242 0 0 0-4.649 5.528C9.562 16.422 8.48 22.689 9.721 27.696a22.939 22.939 0 0 1-2.649-3.436M36.227 1.692C31.86-1.007 26.115-.365 20.999 2.822 15.883-.363 10.138-1.005 5.773 1.693-1.122 5.955-1.96 16.937 3.903 26.22 8.226 33.064 14.983 37.074 21 36.999c6.017.074 12.774-3.935 17.097-10.78 5.863-9.282 5.025-20.264-1.87-24.527" id="a"/></defs><use fill="#FFF" xlink:href="#a" fill-rule="evenodd"/></svg>
					<?php esc_html_e( 'Log in with GoDaddy', 'gd-system-plugin' ); ?>
				</a>
			</div>

			<div class="wpaas-sso-login-divider">
				<span><?php esc_html_e( 'Or', 'gd-system-plugin' ); ?></span>
			</div>

			<a href="<?php echo esc_url( add_query_arg( 'wpaas-standard-login', 1 ) ); ?>" rel="nofollow" class="wpaas-sso-login-toggle">
				<?php esc_html_e( 'Log in with username and password', 'gd-system-plugin' ); ?>
			</a>
		</div>
		<?php

	}

	/**
	 * Remove target="_blank" from the login links
	 *
	 * @action login_footer
	 */
	public function login_footer() {

		if ( ! isset( $_REQUEST['interim-login'] ) ) {

			return;

		}

		?>
		<script type="text/javascript">
		( function() {
			try {
				var i, links = document.getElementsByTagName( 'a' );
				for ( i in links ) {
					if ( links[i].href && 'wpaas-sso-login-toggle' === links[i].className ) {
						links[i].target = '';
						links[i].rel = '';
					}
				}
			} catch( er ) {}
		}());
		</script>
		<?php

	}

	/**
	 * Enqueue scripts and styles for SSO login button.
	 *
	 * @action login_enqueue_scripts
	 */
	public function login_enqueue_scripts() {

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-sso-login', Plugin::assets_url( "css/sso-login{$rtl}{$suffix}.css" ), [], Plugin::version() );

	}

	/**
	 * Return the SSO user ID.
	 *
	 * @return int|false
	 */
	private function user_id() {

		$user_id = ! empty( $_REQUEST['SSO_USER_ID'] ) ? $_REQUEST['SSO_USER_ID'] : filter_input( INPUT_GET, 'wpaas_sso_user_id', FILTER_VALIDATE_INT ); // Backwards compat - @codingStandardsIgnoreLine

		if ( $user_id ) {

			return absint( $user_id );

		}

		$user = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
			]
		);

		return isset( $user[0]->ID ) ? $user[0]->ID : false;

	}

	/**
	 * Add our custom error message to the shaking messages
	 *
	 * @action shake_error_codes
	 * @param $shake_error_codes
	 *
	 * @return array
	 */
	public function shake_error_codes( $shake_error_codes ) {

		$shake_error_codes[] = static::INVALID_SSO_QARG;

		return $shake_error_codes;

	}

	/**
	 * Check if there were any SSO problems.
	 *
	 * @filter wp_login_errors
	 * @param $errors
	 *
	 * @return mixed
	 */
	public function wp_login_errors( $errors ) {

		if ( ! isset( $_GET[ static::INVALID_SSO_QARG ] ) ) {  // @codingStandardsIgnoreLine

			return $errors;

		}

		$errors->add( static::INVALID_SSO_QARG, __( 'We were unable to log you in automatically. Please enter your WordPress username and password.', 'gd-system-plugin' ), 'error' );

		return $errors;

	}

}
