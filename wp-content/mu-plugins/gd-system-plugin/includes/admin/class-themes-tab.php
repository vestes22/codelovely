<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Themes_Tab {

	/**
	 * Tab slug name.
	 *
	 * @var string
	 */
	const SLUG = 'gd-recommended';

	/**
	 * API Instance
	 *
	 * @var object
	 */
	private $api;

	/**
	 * GoDaddy recommended themes on WordPress.org
	 *
	 * @var array
	 */
	private static $recommended_themes = [
		'go',
	];

	/**
	 * Class constructor.
	 *
	 * @param API_Interface $api
	 */
	public function __construct( \WPaaS\API $api ) {

		if ( ! Plugin::is_gd() ) {

			return;

		}

		$this->api = $api;

		add_action( 'admin_init', [ $this, 'redirect' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 1 );
		add_action( 'wp_ajax_gd_render_themes', [ $this, 'render_themes' ] );
		add_action( 'wp_ajax_gd_install_theme', 'wp_ajax_install_theme' );

		add_filter( 'themes_api_result', function( $res, $action, $args ) {

			if ( 'theme_information' !== $action || ! is_wp_error( $res ) ) {

				if ( isset( $_POST['action'] ) && 'gd_install_theme' === sanitize_text_field( $_POST['action'] ) && ! in_array( $_POST['slug'], wp_list_pluck( $this->get_themes(), 'theme' ) ) ) {

					unset( $_POST['action'] );

				} else {

					remove_filter( 'themes_api', [ $this, 'filter_themes_api' ], 10, 3 );

					return $res;

				}

			}

			add_filter( 'themes_api', [ $this, 'filter_themes_api' ], 10, 3 );

			return themes_api( $action, $args );

		}, 10, 3 );

	}

	/**
	 * Redirect the default page to our tab.
	 */
	public function redirect() {

		global $pagenow;

		if ( 'theme-install.php' === $pagenow && ! filter_input( INPUT_GET, 'browse', FILTER_SANITIZE_STRING ) ) {

			wp_safe_redirect( add_query_arg( 'browse', self::SLUG, admin_url( 'theme-install.php' ) ) );

			exit;

		}

	}

	/**
	 * Filter where themes are installed from
	 *
	 * @param  boolean|object $override Short circuit themes API
	 * @param  string         $action   Query action
	 * @param  array          $args     Argument array
	 *
	 * @return object Return the
	 *
	 * @since 3.6.0
	 */
	public function filter_themes_api( $override, $action, $args ) {

		return (object) [ 'download_link' => esc_url_raw( filter_input( INPUT_POST, 'gd_package', FILTER_SANITIZE_URL ) ) ];

	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @action admin_enqueue_scripts
	 * @since  3.6.0
	 *
	 * @param string $hook_suffix Current admin page base.
	 */
	public function enqueue_scripts( $hook_suffix ) {

		if ( 'theme-install.php' !== $hook_suffix ) {

			return;

		}

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'gd-recommended-tab', Plugin::assets_url( "js/themes-tab{$rtl}{$suffix}.js" ), [ 'jquery', 'media-views', 'thickbox' ], Plugin::version(), false );

		wp_localize_script(
			'gd-recommended-tab',
			'gdThemesTab',
			[
				'tab'          => esc_html__( 'GoDaddy Recommended', 'gd-system-plugin' ),
				'installed'    => esc_html__( 'Installed' ), // Use core l10n.
				'error'        => esc_html__( 'Error Installing Theme', 'gd-system-plugin' ),
				'activate'     => esc_html__( 'Activate' ), // Use core l10n.
				'live_preview' => esc_html__( 'Live Preview' ), // Use core l10n.
				'popup_title'  => esc_html__( 'Theme Install' ), // Use core l10n.
			]
		);

	}

	/**
	 * Return an array of GoDaddy themes.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	private function get_themes() {

		$themes = get_site_transient( 'gd_themes' );

		if ( ! WP_DEBUG && false !== $themes ) {

			return $themes;

		}

		foreach ( self::$recommended_themes as $key => $recommended_theme_slug ) {

			$theme_data_transient = get_site_transient( 'gd_theme_data_' . $recommended_theme_slug );

			if ( ! WP_DEBUG && false !== $theme_data_transient ) {

				$themes[ $key ] = $theme_data_transient;

				continue;

			}

			$theme_data     = json_decode( wp_remote_retrieve_body( wp_remote_get( sprintf( 'https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=%s', $recommended_theme_slug ) ) ), true );
			$themes[ $key ] = $theme_data;

			set_site_transient( 'gd_theme_data_' . $recommended_theme_slug, $theme_data, DAY_IN_SECONDS );

		}

		set_site_transient( 'gd_themes', $themes, DAY_IN_SECONDS );

		return $themes;

	}

	/**
	 * Return an array of WooCommerce themes.
	 *
	 * @since 3.11.2
	 *
	 * @return array
	 */
	private function get_woo_themes() {

		if ( ! Plugin::has_plan( 'eCommerce Managed WordPress' ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			return [];

		}

		$woo_themes = $this->api->get_woocommerce_products( 'themes' );

		if ( ! $woo_themes || ! is_array( $woo_themes ) ) {

			return [];

		}

		// Add necessary data to the WooCommerce theme data
		foreach ( $woo_themes as $key => $theme ) {

			$woo_themes[ $key ]['name']        = $theme['name'];
			$woo_themes[ $key ]['new_version'] = $theme['version'];
			$woo_themes[ $key ]['package']     = $theme['download_link'];
			$woo_themes[ $key ]['theme']       = $theme['slug'];
			$woo_themes[ $key ]['url']         = $theme['homepage'];
			$woo_themes[ $key ]['screenshot']  = $theme['icons']['2x'];

		}

		return $woo_themes;

	}

	/**
	 * Display themes to browse.
	 *
	 * @action wp_ajax_render_themes
	 * @since  3.6.0
	 */
	public function render_themes() {

		$themes = array_filter(
			[
				'gd_themes'  => $this->get_themes(),
				'woo_themes' => $this->get_woo_themes(),
			]
		);

		if ( empty( $themes ) ) {

			wp_send_json_error();

		}

		ob_start();

		foreach ( $themes as $theme_type => $theme_data ) {

			uasort( $theme_data, function( $a, $b ) {
				return $a['name'] > $b['name'];
			} );

			foreach ( $theme_data as $theme ) {

				$slug    = ! empty( $theme['theme'] ) ? $theme['theme'] : ( ! empty( $theme['slug'] ) ? $theme['slug'] : null );
				$url     = ! empty( $theme['url'] ) ? $theme['url'] : ( ! empty( $theme['homepage'] ) ? $theme['homepage'] : null );
				$package = ! empty( $theme['package'] ) ? $theme['package'] : ( ! empty( $theme['download_link'] ) ? $theme['download_link'] : null );
				$name    = ! empty( $theme['name'] ) ? $theme['name'] : $slug;

				if ( ! $slug || ! $url || ! $package ) {

					continue;

				}

				$installed_theme = [
					'notice' => '',
					'button' => sprintf(
						'<div class="theme-actions">
							<a class="button button-primary theme-install" data-name="%1$s" data-slug="%2$s" data-package="%3$s" data-nonce="%4$s" aria-label="%5$s">%6$s</a>
						</div>',
						esc_attr( $name ),
						esc_attr( $slug ),
						esc_url( $package ),
						wp_create_nonce( 'updates' ),
						sprintf(
							esc_attr__( 'Install %s' ), // Use core l10n. - @codingStandardsIgnoreLine
							esc_html( $name )
						),
						esc_html__( 'Install' ) // Use core l10n.
					),
				];

				$wp_theme = wp_get_theme( $slug );

				// Prefer a local screenshot if the theme is installed and one exists.
				$screenshot = $wp_theme->exists() ? (string) $wp_theme->get_screenshot() : null;
				$screenshot = ( $screenshot ) ? $screenshot : ( ! empty( $theme['screenshot'] ) ? $theme['screenshot'] : ( ! empty( $theme['screenshot_url'] ) ? $theme['screenshot_url'] : null ) );

				if ( $wp_theme->exists() ) {

					$installed_theme = [
						'notice' => sprintf( '<div class="notice notice-success notice-alt"><p>%s</p></div>', esc_html__( 'Installed' ) ),
						'button' => sprintf(
							'<div class="theme-actions"><a class="button button-primary activate-theme" href="%s">%s</a><a class="button button-secondary live-preview-theme" href="%s">%s</a></div>',
							esc_url( $this->get_activate_url( $slug ) ),
							esc_html__( 'Activate' ), // Use core l10n.
							esc_url( wp_customize_url( $slug ) ),
							esc_html__( 'Live Preview' ) // Use core l10n.
						),
					];

				}

				?>

				<div class="theme godaddy" tabindex="0" aria-describedby="<?php echo esc_attr( $slug ); ?>-action <?php echo esc_attr( $slug ); ?>-name" data-slug="<?php echo esc_attr( $slug ); ?>" data-demo-url="<?php echo esc_url( $url ); ?>">
					<div class="theme-screenshot">
						<img src="<?php echo esc_url( $screenshot ); ?>">
					</div>
					<span class="more-details"><?php esc_html_e( 'View Demo', 'gd-system-plugin' ); ?></span>
					<div class="theme-author">
						<?php echo ( 'gd_themes' === $theme_type ) ? 'GoDaddy' : 'WooCommerce'; ?>
					</div>
					<div class="theme-id-container">
						<h3 class="theme-name"><?php echo esc_html( $name ); ?></h3>
						<?php
						echo wp_kses(
							$installed_theme['button'],
							[
								'div' => [
									'class' => [],
								],
								'a'   => [
									'href'         => [],
									'class'        => [],
									'data-name'    => [],
									'data-slug'    => [],
									'data-package' => [],
									'data-nonce'   => [],
									'aria-label'   => [],
								],
							]
						);
						?>
					</div>
					<?php echo wp_kses_post( $installed_theme['notice'] ); ?>
				</div>

				<?php

			}

		}

		wp_send_json_success( ob_get_clean() );

		wp_die();

	}

	/**
	 * Return the activation URL for a theme.
	 *
	 * @since 3.6.0
	 *
	 * @param  string Theme slug.
	 *
	 * @return string|null
	 */
	private function get_activate_url( $slug ) {

		$wp_theme = wp_get_theme( $slug );

		if ( ! $slug || ! $wp_theme->exists() || ! $wp_theme->is_allowed() ) {

			return;

		}

		$args = [
			'action'     => 'activate',
			'stylesheet' => $slug,
			'_wpnonce'   => wp_create_nonce( "switch-theme_{$slug}" ),
		];

		if ( is_multisite() ) {

			$args = [
				'action'   => 'enable',
				'theme'    => $slug,
				'_wpnonce' => wp_create_nonce( "enable-theme_{$slug}" ),
			];

		}

		return add_query_arg( $args, self_admin_url( 'themes.php' ) );

	}

}
