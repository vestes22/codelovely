<?php

namespace WPaaS;

use WPaaS\Helpers;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Debug_Mode {

	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	const COOKIE = 'wpaas-debug-mode';

	/**
	 * Default theme slug.
	 *
	 * Note: The `WP_DEFAULT_THEME` core constant is not available early
	 * enough in the load order to be referenced during debug mode.
	 *
	 * @var string
	 */
	const DEFAULT_THEME = 'twentytwenty';

	/**
	 * Session data.
	 *
	 * @var array
	 */
	private $session = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( is_admin() ) {

			return;

		}

		if ( self::is_start() ) {

			add_action( 'wp_loaded', function () {

					$this->start_session();

			} );

		}

		if ( $this->is_exit() ) {

			$this->stop_session(); // Reload.

		}

		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {

			return;

		}

		if ( ! $this->is_valid_cookie() ) {

			$this->stop_session(); // Reload.

		}

		if ( $this->is_update() ) {

			$this->update_session(); // Reload.

		}

		add_action( 'muplugins_loaded', [ $this, 'filter_plugins' ], PHP_INT_MAX );
		add_action( 'muplugins_loaded', [ $this, 'filter_theme' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], PHP_INT_MAX );
		add_action( 'wp_head', [ $this, 'display_notice' ], PHP_INT_MAX );
		add_action( 'wp_footer', [ $this, 'display' ], PHP_INT_MAX );

		add_filter( 'wp_headers', function ( $headers ) {

			return array_merge( $headers, wp_get_nocache_headers() );

		}, PHP_INT_MAX );

		add_filter( 'body_class', function ( $classes ) {

			$classes[] = 'wpaas-debug-mode';

			return $classes;

		}, PHP_INT_MAX );

	}

	/**
	 * Whether to start (or restart) a session.
	 *
	 * @return bool
	 */
	public static function is_start() {

		$action   = filter_input( INPUT_GET, 'GD_COMMAND', FILTER_SANITIZE_STRING );
		$sso_hash = filter_input( INPUT_GET, 'SSO_HASH', FILTER_SANITIZE_STRING );

		if ( 'DEBUG' !== $action || ! $sso_hash ) {

			return false;

		}

		return ( new API )->is_valid_sso_hash( $sso_hash );

	}

	/**
	 * Whether to exit the session.
	 *
	 * @return bool
	 */
	private function is_exit() {

		return ( 'DEBUG_EXIT' === filter_input( INPUT_GET, 'GD_COMMAND', FILTER_SANITIZE_STRING ) );

	}

	/**
	 * Whether the session cookie is valid.
	 *
	 * @return bool
	 */
	private function is_valid_cookie() {

		$this->session = isset( $_COOKIE[ self::COOKIE ] ) ? json_decode( $_COOKIE[ self::COOKIE ], true ) : null;

		$nonce = isset( $this->session['_nonce'] ) ? $this->session['_nonce'] : null;

		return ( isset( $this->session['plugins'] ) && ! empty( $this->session['themes'] ) && false !== $this->wp_verify_nonce( $nonce, self::COOKIE ) );

	}

	/**
	 * Whether to update the session.
	 *
	 * @return bool
	 */
	private function is_update() {

		$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );

		return ( $nonce && false !== $this->wp_verify_nonce( $nonce, 'wpaas-debug-mode-update' ) );

	}

	/**
	 * Start a new session.
	 */
	private function start_session() {

		$default = self::DEFAULT_THEME;
		$themes  = array_fill_keys( array_keys( $this->wp_get_themes() ), false );

		if ( ! isset( $themes[ $default ] ) || ! $this->install_default_theme() ) {

			$default = get_stylesheet();

		}

		$this->session = [
			'_nonce'  => $this->wp_create_nonce( self::COOKIE ),
			'plugins' => array_fill_keys( array_keys( $this->get_plugins() ), false ),
			'themes'  => array_merge( $themes, [ $default => true ] ),
		];

		$this->set_cookie( wp_json_encode( $this->session ) );

		do_action( 'wpaas_debug_mode_session' );

		$this->reload();

	}

	/**
	 * Install the default theme from WordPress.org
	 *
	 * @return bool True when theme is installed, else false
	 */
	private function install_default_theme() {

		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			WP_Filesystem();

		}

		$temp_file = download_url( sprintf( 'https://downloads.wordpress.org/theme/%s.zip', self::DEFAULT_THEME ) );

		if ( is_wp_error( $temp_file ) ) {

			return false;

		}

		$unzip = unzip_file( $temp_file, WP_CONTENT_DIR . '/themes' );

		@unlink( $temp_file );

		return ! is_wp_error( $unzip );

	}

	/**
	 * Stop a session.
	 */
	private function stop_session() {

		$this->set_cookie( null, 0 );

		$this->reload();

	}

	/**
	 * Update a session.
	 */
	private function update_session() {

		$active_plugins = (array) filter_input( INPUT_POST, 'wpaas-debug-mode-plugins', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		foreach ( $this->session['plugins'] as $plugin => &$active ) {

			$active = in_array( $plugin, $active_plugins, true );

		}

		$active_theme = filter_input( INPUT_POST, 'wpaas-debug-mode-theme', FILTER_SANITIZE_STRING );

		if ( 'installed' === $active_theme ) {

			$active_theme = filter_input( INPUT_POST, 'wpaas-debug-mode-installed-theme', FILTER_SANITIZE_STRING );

		}

		foreach ( $this->session['themes'] as $theme => &$active ) {

			$active = ( $theme === $active_theme );

		}

		$this->set_cookie( wp_json_encode( $this->session ) );

		$this->reload();

	}

	/**
	 * Reload the current view.
	 */
	private function reload() {

		if ( ! function_exists( 'wp_safe_redirect' ) ) {

			require_once ABSPATH . WPINC . '/pluggable.php';

		}

		wp_safe_redirect( remove_query_arg( [ 'GD_COMMAND', 'SSO_HASH' ] ) );

		exit;

	}

	/**
	 * Set a session cookie.
	 *
	 * @param string $value
	 * @param int    $expire (optional)
	 */
	private function set_cookie( $value, $expire = DAY_IN_SECONDS ) {

		wp_cookie_constants();

		setcookie( self::COOKIE, $value, time() + $expire, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		$_COOKIE[ self::COOKIE ] = $value; // Set in current request.

	}

	/**
	 * Get plugins helper.
	 *
	 * @return array
	 */
	private function get_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		return get_plugins();

	}

	/**
	 * Get themes helper.
	 *
	 * @return array
	 */
	private function wp_get_themes() {

		// Global not available early in the load order, so we will define it manually when needed.
		if ( empty( $GLOBALS['wp_theme_directories'] ) ) {

			$GLOBALS['wp_theme_directories'][] = WP_CONTENT_DIR . get_theme_roots(); // WPCS: override ok.

		}

		return wp_get_themes();

	}

	/**
	 * Create a nonce helper.
	 *
	 * @return string
	 */
	private function wp_create_nonce( ...$args ) {

		if ( ! function_exists( 'wp_create_nonce' ) ) {

			require_once ABSPATH . WPINC . '/pluggable.php';

		}

		wp_cookie_constants(); // Nonces require `SECURE_AUTH_COOKIE` to be defined.

		return wp_create_nonce( ...$args );

	}

	/**
	 * Verify a nonce helper.
	 *
	 * @return int|false
	 */
	private function wp_verify_nonce( ...$args ) {

		if ( ! function_exists( 'wp_verify_nonce' ) ) {

			require_once ABSPATH . WPINC . '/pluggable.php';

		}

		wp_cookie_constants(); // Nonces require `SECURE_AUTH_COOKIE` to be defined.

		return wp_verify_nonce( ...$args );

	}

	/**
	 * Filter the active plugins.
	 *
	 * @action muplugins_loaded
	 */
	public function filter_plugins() {

		$this->session['options']['active_plugins'] = (array) get_option( 'active_plugins', [] );

		add_filter( 'option_active_plugins', function( $option_value ) {

			return array_keys( array_filter( $this->session['plugins'] ) );

		}, PHP_INT_MAX );

	}

	/**
	 * Filter the active theme.
	 *
	 * @action muplugins_loaded
	 */
	public function filter_theme() {

		$theme = wp_get_theme( array_search( true, $this->session['themes'], true ) );

		$template = function () use ( $theme ) {

			return $theme->template;

		};

		$stylesheet = function () use ( $theme ) {

			return $theme->stylesheet;

		};

		add_filter( 'template', $template, PHP_INT_MAX );
		add_filter( 'option_template', $template, PHP_INT_MAX );

		$this->session['options']['stylesheet'] = get_stylesheet();

		add_filter( 'stylesheet', $stylesheet, PHP_INT_MAX );
		add_filter( 'option_stylesheet', $stylesheet, PHP_INT_MAX );

	}

	/**
	 * Enqueue the session control panel scripts and styles.
	 *
	 * @action wp_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$rtl    = ! is_rtl() ? '' : '-rtl';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-debug-mode', Helpers::assets_url() . "css/debug-mode{$rtl}{$suffix}.css", [], Helpers::version() );

		wp_enqueue_script( 'jquery' );

	}

	/**
	 * Display a notice to the user about debug mode public visibility.
	 *
	 * @action wp_head
	 */
	public function display_notice() {

		?>

		<div class="wpaas-debug-mode-notice">
			<p>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20">
					<rect x="0" fill="none" width="20" height="20"/>
					<g>
						<path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1 4c0-.55-.45-1-1-1s-1 .45-1 1 .45 1 1 1 1-.45 1-1zm0 9V9H9v6h2z"/>
					</g>
				</svg>
				<?php esc_html_e( 'Changes in safe mode are not saved and are not visible by visitors to your site.', 'gd-system-plugin' ); ?>
			</p>
		</div>

		<?php
	}

	/**
	 * Display the session control panel.
	 *
	 * @action wp_footer
	 */
	public function display() {

		$themes = $this->wp_get_themes();

		$active_plugins   = ! empty( $this->session['options']['active_plugins'] ) ? $this->session['options']['active_plugins'] : [];
		$debug_mode_theme = array_search( true, $this->session['themes'], true );

		if ( ! $debug_mode_theme ) {

			$debug_mode_theme = $this->session['options']['stylesheet'];

		}

		$active_list   = [];
		$inactive_list = [];

		foreach ( $this->session['plugins'] as $plugin => $active ) {

			if ( $active ) {

				$active_list[ $plugin ] = $active;

				continue;

			}

			$inactive_list[ $plugin ] = $active;

		}

		?>
		<script type="text/javascript">
			jQuery( document ).on( "keyup", function( e ) {
				if ( 27 == e.keyCode ) {
					jQuery( "body" ).toggleClass( "wpaas-debug-mode" );
				}
			} );

			jQuery( document ).ready( function() {

				jQuery( 'body' ).on( 'click', '.tab', function( e ) {

					e.preventDefault();

					jQuery( '.tab' ).removeClass( 'active' );
					jQuery( e.currentTarget ).addClass( 'active' );

					jQuery( '.panel__body' ).addClass( 'hidden' );
					jQuery( '.panel__body:nth-child(' + parseInt( jQuery( this ).index() + 2 ) + ')' ).removeClass( 'hidden' );

				} );

				jQuery( 'body' ).on( 'change', 'select[name="wpaas-debug-mode-installed-theme"]', function() {
					jQuery( 'input[value="installed"]' ).prop( 'checked', true );
				} );

				jQuery( 'body' ).on( 'mouseenter', '.close-debug-mode', function() {
					jQuery( this ).find( 'svg' ).css( 'fill', '#ffffff' );
				} );

				jQuery( 'body' ).on( 'mouseleave', '.close-debug-mode', function() {
					jQuery( this ).find( 'svg' ).css( 'fill', '#c6c6c6' );
				} );

				jQuery( 'body' ).on( 'click', '.close-debug-mode', function() {
					window.location.href = jQuery( this ).data( 'url' );
				} );
			} );
		</script>

		<div id="wpaas-debug-mode" class="cleanslate">

			<h5>
				<?php esc_html_e( 'Safe Mode', 'gd-system-plugin' ); ?>
				<span class="close-debug-mode" data-url="<?php echo esc_url( add_query_arg( 'GD_COMMAND', 'DEBUG_EXIT' ) ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="25" height="25" viewBox="0 0 50 50" style="fill: #c6c6c6;">
						<g id="surface1">
							<path style=" " d="M 15.125 12.28125 L 12.28125 15.125 L 22.21875 25 L 12.28125 34.875 L 15.125 37.71875 L 25.0625 27.84375 L 35 37.71875 L 37.8125 34.875 L 27.90625 25 L 37.8125 15.125 L 35 12.28125 L 25.0625 22.15625 Z "></path>
						</g>
					</svg>
				</span>
			</h5>

			<hr />

			<div>

				<form method="POST">

					<h4><?php esc_html_e( 'Themes' ); ?></h4>

					<div class="section">

						<ul>

							<li>
								<input type="radio" id="safe-mode" name="wpaas-debug-mode-theme" value="<?php echo esc_attr( self::DEFAULT_THEME ); ?>" <?php if ( self::DEFAULT_THEME === $debug_mode_theme ) { echo 'checked'; } ?>>
								<label for="safe-mode"><?php esc_html_e( 'Safe mode', 'gd-system-plugin' ); ?></label>
								<div><?php echo esc_html( $themes[ self::DEFAULT_THEME ]->get( 'Name' ) ); ?></div>
							</li>

							<?php

							if ( isset( $this->session['themes'][ self::DEFAULT_THEME ] ) ) {

								unset( $this->session['themes'][ self::DEFAULT_THEME ] );

							}

							?>

							<li>
								<input type="radio" id="installed" name="wpaas-debug-mode-theme" value="installed" <?php if ( self::DEFAULT_THEME !== $debug_mode_theme ) { echo 'checked'; } ?>>
								<label for="installed"><?php /* translators: total number of themes installed. */ printf( esc_html__( 'Installed (%d)', 'gd-system-plugin' ), count( $this->session['themes'] ) ); ?></label>
								<span class="plain-select">
									<select name="wpaas-debug-mode-installed-theme">
										<option disabled selected value><?php esc_html_e( 'Select one', 'gd-system-plugin' ); ?></option>
										<?php
										foreach ( $this->session['themes'] as $theme => $active ) {
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $theme ),
												selected( $debug_mode_theme, $theme, false ),
												esc_html( $themes[ $theme ]->get( 'Name' ) )
											);
										}
										?>
									</select>
								</span>
							</li>
						</ul>

					</div>

					<h4><?php esc_html_e( 'Plugins', 'gd-system-plugin' ); ?></h4>

					<div class="section">

						<div class="panel">
							<nav>
								<ul class="tabs">
									<li class="tabs__tab active tab"><a href="#"><?php esc_html_e( 'Active', 'gd-system-plugin' ); ?></a></li>
									<li class="tabs__tab tab"><a href="#"><?php esc_html_e( 'Inactive', 'gd-system-plugin' ); ?></a></li>
									<li class="tabs__presentation-slider" role="presentation"></li>
								</ul>
								<hr />
							</nav>
							<div class="panel__body active">
								<?php $this->plugin_list( $active_list, 'active' ); ?>
							</div>
							<div class="panel__body inactive hidden">
								<?php $this->plugin_list( $inactive_list, 'inactive' ); ?>
							</div>
						</div>

					</div>

					<?php wp_nonce_field( 'wpaas-debug-mode-update' ); ?>

					<div class="actions">
						<input type="submit" value="<?php esc_attr_e( 'Preview', 'gd-system-plugin' ); ?>">&nbsp;
						<a class="button secondary" href="<?php echo esc_url( add_query_arg( 'GD_COMMAND', 'DEBUG_EXIT' ) ); ?>" id="wpaas-debug-mode-exit"><?php esc_html_e( 'Cancel', 'gd-system-plugin' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php

	}

	/**
	 * Generate the plugin list
	 *
	 * @param  [type] $plugins [description]
	 * @param  [type] $type    [description]
	 *
	 * @return [type]          [description]
	 */
	private function plugin_list( $plugins, $type ) {

		if ( empty( $plugins ) ) {

			$message = 'active' === $type ? __( 'No active plugins.', 'gd-system-plugin' ) : __( 'No inactive plugins.', 'gd-system-plugin' );

			return printf(
				'<p>%s</p>',
				esc_html( $message )
			);

		}

		$all_plugins = $this->get_plugins();

		print( '<ul>' );

		foreach ( $plugins as $plugin => $active ) {

			?>

			<li>
				<input type="checkbox" id="plugin-<?php echo esc_attr( crc32( $plugin ) ); ?>" name="wpaas-debug-mode-plugins[]" value="<?php echo esc_attr( $plugin ); ?>" <?php checked( $active ); ?>>
				<label for="plugin-<?php echo esc_attr( crc32( $plugin ) ); ?>"><strong><?php echo esc_html( $all_plugins[ $plugin ]['Name'] ); ?></strong></label>
			</li>

			<?php

		}

		print( '</ul>' );

	}

}
