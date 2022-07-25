<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Hotfixes {

	/**
	 * Instance of the API.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * Class constructor.
	 */
	public function __construct( API_Interface $api ) {

		$this->api = $api;

		/**
		 * Define the WP101 API key from the WPaaS config.
		 */
		if ( ! defined( 'GD_WP101_API_KEY' ) ) {

			define( 'GD_WP101_API_KEY', Plugin::config( 'wp101_key' ) );

		}

		/**
		 * WP Popular Posts.
		 *
		 * This makes it perform much better especially on high traffic sites.
		 */
		add_filter( 'wpp_data_sampling', '__return_true' );

		/**
		 * Limit Login Attempts.
		 */
		add_filter( 'pre_update_option_limit_login_lockouts', [ $this, 'clean_limit_login_attempts' ], PHP_INT_MAX );
		add_filter( 'pre_update_option_limit_login_retries_valid', [ $this, 'clean_limit_login_attempts' ], PHP_INT_MAX );
		add_filter( 'pre_update_option_limit_login_retries', [ $this, 'clean_limit_login_attempts' ], PHP_INT_MAX );
		add_filter( 'pre_update_option_limit_login_logged', [ $this, 'clean_limit_login_attempts' ], PHP_INT_MAX );

		/**
		 * Jetpack.
		 */
		if ( Plugin::is_staging_site() ) {

			// Prevent identity crisis from triggering on staging sites.
			add_filter( 'jetpack_has_identity_crisis', '__return_false', PHP_INT_MAX );

		}

		// Use Photon CDN for images when module is active.
		add_action( 'plugins_loaded', function () {

			if ( class_exists( 'Jetpack' ) && \Jetpack::is_module_active( 'photon' ) ) {

				add_filter( 'wpaas_cdn_file_ext', function ( $file_ext ) {

					return array_diff( $file_ext, [ 'gif', 'jpeg', 'jpg', 'png' ] );

				} );

			}

		} );

		// Hide the Jetpack updates screen nag.
		add_filter( 'option_jetpack_options', [ $this, 'remove_jetpack_nag' ], PHP_INT_MAX );

		/**
		 * Disable sslverify for remote requests on non-production environments.
		 */
		add_filter(
			'http_request_args',
			function( array $args ) {

				if ( ! Plugin::is_env( 'prod' ) ) {

					$args['sslverify'] = false;

				}

				return $args;

			},
			PHP_INT_MAX
		);

		/**
		 * Override the GEM API base URL on non-production environments.
		 */
		if ( ! Plugin::is_env( 'prod' ) ) {

			add_filter(
				'gem_api_base_url',
				function( $url ) {

					return sprintf( 'https://gem.%s-godaddy.com/', Plugin::get_env() );

				},
				PHP_INT_MAX
			);

		}

		/**
		 * Remove the author credit from GoDaddy themes for other brands.
		 */
		if ( ! Plugin::is_gd() ) {

			add_filter( 'primer_author_credit', '__return_false' );
			add_filter( 'primer_show_site_identity_settings', '__return_false' );

		}

		/**
		 * Change the terms of service URL depending on the brand.
		 */
		$tos_urls = [
			'gd'       => 'https://www.godaddy.com/agreements/showdoc.aspx?pageid=Hosting_SA',
			'mt'       => 'https://mediatemple.net/legal/terms-of-service/',
			'reseller' => sprintf(
				'https://www.secureserver.net/agreements/showdoc.aspx?pageid=Hosting_SA&prog_id=%d',
				Plugin::reseller_id()
			),
		];

		$tos_url = Plugin::use_brand_value( $tos_urls );

		if ( $tos_url ) {

			$return_tos_url = function() use ( $tos_url ) { return $tos_url; };

			add_filter( 'stock_photos_tos_url', $return_tos_url );

		}

		/**
		 * Set default options for LLAR.
		 */
		add_action( 'plugins_loaded', function () {

			if ( ! defined( 'LLA_PLUGIN_DIR' ) ) {

				return;

			}

			if ( null === get_option( 'limit_login_gdpr', null ) ) {

				update_option( 'limit_login_gdpr', 1 );

			}

			if ( null === get_option( 'limit_login_review_notice_shown', null ) ) {

				update_option( 'limit_login_review_notice_shown', 1 );

			}

			if ( null === get_option( 'limit_login_whitelist_usernames', null ) ) {

				$user = get_users(
					[
						'role'   => 'administrator',
						'number' => 1,
					]
				);

				update_option( 'limit_login_whitelist_usernames', [ $user[0]->data->user_login ] );

			}

		} );

		/**
		 * Sucuri Scanner plugin.
		 */
		add_filter( 'sucuriscan_sitecheck_details_hosting', function () {

			$labels = [
				'gd' => __( 'GoDaddy', 'gd-system-plugin' ),
				'mt' => __( 'Media Temple', 'gd-system-plugin' ),
			];

			return Plugin::use_brand_value( $labels, __( 'Managed WordPress', 'gd-system-plugin' ) );

		} );

		/**
		 * Set default CDN URL in Autoptimize plugin settings.
		 */
		add_action( 'plugins_loaded', function () {

			if ( ! defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) ) {

				return;

			}

			add_filter( 'option_autoptimize_cdn_url', function ( $value ) {

				return ( ! $value && CDN::is_enabled( true ) && CDN::get_base_url() ) ? CDN::get_base_url() : $value;

			} );

		} );

		/**
		 * Direct PHP upgrade URL for core.
		 *
		 * See: https://core.trac.wordpress.org/changeset/44815
		 */
		add_filter( 'wp_direct_php_update_url', function ( $direct_update_url ) {

			// MT cannot change PHP.
			if ( ! Plugin::is_mt() ) {

				$direct_update_url = Plugin::account_url( 'changephp' );

			}

			return $direct_update_url;

		} );

		/**
		 * Prevent temp domain from redirecting to primary domain when debugging.
		 */
		if ( 1 === (int) filter_input( INPUT_GET, 'gddebug' ) ) {

			remove_filter( 'template_redirect', 'redirect_canonical' );

		}

		/**
		 * Filter the HMT service key before the bundled Worker is loaded.
		 */
		add_action( 'wpaas_before_bundled_plugins_loaded', function () {

			add_filter( 'option_mwp_service_key', function ( $value ) {

				return defined( 'GD_HMT_SERVICE_KEY' ) ? GD_HMT_SERVICE_KEY : $value;

			}, PHP_INT_MAX );

		} );

		/**
		 * Tell the API to refresh blog title when the blogname option changes.
		 *
		 * Note: Should not fire when changed via WP-CLI.
		 */
		add_action( 'update_option_blogname', function ( $old_value, $new_value ) {

			if ( ! Plugin::is_wp_cli() && $old_value !== $new_value ) {

				$this->api->refresh_blog_title( $new_value );

			}

		}, PHP_INT_MAX, 2 );

		if ( is_admin() ) {

			add_filter( 'gettext', [ $this, 'filter_core_invalid_php_version_notice' ], 20, 3 );

		}

		/**
		 * Edit the front page without knowing the page ID.
		 *
		 * Via: wp-admin/post.php?post=page_on_front&action=edit
		 */
		add_action( 'admin_init', function () {

			global $pagenow;

			if ( 'post.php' !== $pagenow || 'page_on_front' !== filter_input( INPUT_GET, 'post' ) ) {

				return;

			}

			$page_on_front = get_option( 'page_on_front' );

			if ( $page_on_front && is_numeric( $page_on_front ) ) {

				wp_safe_redirect( esc_url_raw( add_query_arg( 'post', (int) $page_on_front ) ) );

			} else {

				wp_safe_redirect( esc_url_raw( admin_url( 'edit.php?post_type=page' ) ) );

			}

			exit;

		} );

		add_action( 'plugins_loaded', function () {

			global $wp_version;

			// Do not concatenate admin scripts due to WP 5.5 bug.
			// @see https://core.trac.wordpress.org/ticket/50999
			if ( '5.5' === $wp_version && is_admin() && ! defined( 'CONCATENATE_SCRIPTS' ) ) {

				define( 'CONCATENATE_SCRIPTS', false );

			}

		}, PHP_INT_MAX );

		/**
		 * NextGen API reconciliation action.
		 */
		add_action( 'nextgen_compatibility_change', function( $is_nextgen_compat ) {

			$this->api->refresh_nextgen_compatibility( (bool) $is_nextgen_compat );

		} );

		/**
		 * Run the application passwords cleanup event.
		 */
		add_action( 'wpaas_cleanup_application_passwords', function () {

			$passwords = (array) get_option( 'gd_system_application_passwords', [] );

			foreach ( $passwords as $i => $password ) {

				if ( isset( $password['created'], $password['user_id'], $password['uuid'] ) && time() - (int) $password['created'] > WEEK_IN_SECONDS ) {

					\WP_Application_Passwords::delete_application_password( $password['user_id'], $password['uuid'] );

					unset( $passwords[ $i ] );

				}

			}

			if ( $passwords ) {

				update_option( 'gd_system_application_passwords', $passwords );

				return;

			}

			delete_option( 'gd_system_application_passwords' );

			wp_clear_scheduled_hook( current_action() );

		} );

		/**
		 * Allow application passwords for NextGen in DEV/TEST.
		 */
		if ( defined( 'GD_NEXTGEN_ENABLED' ) && GD_NEXTGEN_ENABLED && ! Plugin::is_env( 'prod' ) ) {

			add_filter( 'wp_is_application_passwords_available', '__return_true' );

		}

	}

	/**
	 * Hide the Jetpack updates screen nag.
	 *
	 * @filter option_jetpack_options
	 *
	 * @param  array $options
	 *
	 * @return array
	 */
	public function remove_jetpack_nag( $options ) {

		if ( $options && empty( $options['hide_jitm']['manage'] ) || 'hide' !== $options['hide_jitm']['manage'] ) {

			$options['hide_jitm']['manage'] = 'hide';

		}

		return $options;

	}

	/**
	 * Clean up options for Limit Login Attempts.
	 *
	 * On very active sites these can become massive
	 * arrays that turn into massive strings and break
	 * MySQL because of packet size limitations.
	 *
	 * @filter pre_update_option_limit_login_lockouts
	 * @filter pre_update_option_limit_login_retries_valid
	 * @filter pre_update_option_limit_login_retries
	 * @filter pre_update_option_limit_login_logged
	 *
	 * @param  array|null $value
	 *
	 * @return array
	 */
	public function clean_limit_login_attempts( $value ) {

		if ( is_null( $value ) ) {

			return [];

		}

		if ( count( $value ) < 250 ) {

			return $value;

		}

		$sorting_func = function( $a, $b ) {

			if ( is_array( $b ) ) {

				if ( count( $a ) === count( $b ) ) {

					return 0;

				}

				return ( count( $a ) < count( $b ) ) ? - 1 : 1;

			}

			if ( $a === $b ) {

				return 0;

			}

			return ( $a < $b ) ? -1 : 1;

		};

		uasort( $value, $sorting_func );

		return array_slice( $value, -200 );

	}

	/**
	 * Filter the Core PHP incompatability notice.
	 *
	 * @param  string $translated_text   Translated sring.
	 * @param  string $untranslated_text Untranslated, original, string.
	 * @param  string $domain            Text domain.
	 *
	 * @return string Filtred notice text.
	 */
	public function filter_core_invalid_php_version_notice( $translated_text, $untranslated_text, $domain ) {

		if ( '<strong>Error:</strong> Current PHP version does not meet minimum requirements for %s.' === $untranslated_text ) {

			$translated_text = $translated_text . ' ' . sprintf(
				/* translators: 1. Original translated error text. 2. URL to the hosting environment where users can update the PHP version. */
				__( 'You can update to the most recent version of PHP in your hosting settings <a href="%1$s" target="_blank" title="%2$s">here</a>.', 'gd-system-plugin' ),
				esc_url( Plugin::account_url( 'changephp' ) ),
				esc_attr__( 'Update PHP version', 'gd-system-plugin' )
			);

		}

		return $translated_text;

	}

}
