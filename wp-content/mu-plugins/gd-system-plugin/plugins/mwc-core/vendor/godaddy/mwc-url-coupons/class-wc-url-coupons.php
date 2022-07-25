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

use GoDaddy\WordPress\MWC\UrlCoupons\Admin\WC_URL_Coupons_Admin;
use GoDaddy\WordPress\MWC\UrlCoupons\API\REST_API;
use GoDaddy\WordPress\MWC\UrlCoupons\Frontend\WC_URL_Coupons_Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * URL Coupons main plugin class.
 *
 * @since 1.0
 */
class WC_URL_Coupons extends Framework\SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '3.0.0';

	/** @var WC_URL_Coupons single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'url_coupons';

	/** @var WC_URL_Coupons_Frontend instance */
	protected $frontend;

	/** @var WC_URL_Coupons_Admin instance */
	protected $admin;

	/** @var WC_URL_Coupons_Ajax instance */
	protected $ajax;

	/** @var WC_URL_Coupons_Import_Export_Handler instance */
	protected $import_export_handler;


	/**
	 * Bootstrap plugin
	 *
	 * @since 1.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain' => 'woocommerce-url-coupons',
			)
		);

		$this->includes();

		add_action( 'admin_footer', [ $this->get_admin_instance(), 'render_admin_notice_js' ], 20 );
	}


	/**
	 * Adds admin notices upon initialization.
	 *
	 * @since 2.13.0
	 */
	public function add_admin_notices() {

		$this->get_admin_instance()->maybe_add_first_coupon_notice();
		$this->maybe_add_plugin_users_notice();
	}


	/**
	 * Adds a notice for community plugin users.
	 *
	 * @since 3.0.0
	 */
	protected function maybe_add_plugin_users_notice() {

		$current_screen_id = get_current_screen()->id;

		// only show on Plugins or Coupons pages, and only if the option is set
		if ( ! in_array( $current_screen_id, [ 'plugins', 'edit-shop_coupon' ] )
		     || 'yes' !== get_option( 'mwc_url_coupons_show_notice_plugin_users' ) ) {
			return;
		}

		$notice_id = $this->get_id_dasherized() . '-plugin-users';

		ob_start();

		?>
		<p id="<?php echo esc_attr( "woocommerce-{$notice_id}-notice-buttons" ); ?>">
			<a class="button button-primary" href="<?php echo esc_url( '/wp-admin/edit.php?post_type=shop_coupon' ); ?>"><?php esc_html_e( "View coupons", 'woocommerce-url-coupons' ); ?></a>
		</p>
		<?php

		$notice_buttons = ob_get_clean();

		$this->get_admin_notice_handler()->add_admin_notice(
			sprintf(
			    /* translators: Placeholders: %1$s - opening <p><strong> tag, %2$s - closing </strong></p> tag */
			    __( '%1$sShare discount links%2$sThe URL Coupons plugin is now included natively in your hosting plan! The plugin has been deactivated, and your existing settings and coupons have been migrated successfully.', 'woocommerce-url-coupons' ) . $notice_buttons,
			    '<p><strong>',
			    '</strong></p>'
			),
			$notice_id,
			[
				'always_show_on_settings' => false,
				'notice_class'            => 'notice-info',
			]
		);
	}


	/**
	 * Loads and initializes the plugin lifecycle handler.
	 *
	 * @since 2.7.0
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/src/class-wc-url-coupons-lifecycle.php' );

		$this->lifecycle_handler = new Lifecycle( $this );
	}


	/**
	 * Builds the REST API handler instance.
	 *
	 * @since 2.7.3
	 */
	protected function init_rest_api_handler() {

		require_once( $this->get_plugin_path() . '/src/api/class-wc-url-coupons-rest-api.php' );

		$this->rest_api_handler = new REST_API( $this );
	}


	/**
	 * Includes required files.
	 *
	 * @since 2.0.0
	 */
	public function includes() {

		if ( is_admin() ) {

			// admin
			$this->admin = $this->get_admin_instance();

			if ( is_ajax() ) {
				$this->ajax = $this->load_class( '/src/class-wc-url-coupons-ajax.php', 'GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons_AJAX' );
			}
		}

		// if performing AJAX or not the admin at all
		if ( is_ajax() || ! is_admin() ) {

			// frontend
			$this->frontend = $this->load_class( '/src/frontend/class-wc-url-coupons-frontend.php', 'GoDaddy\WordPress\MWC\UrlCoupons\Frontend\WC_URL_Coupons_Frontend' );
		}

		// import/export handler
		$this->import_export_handler = $this->load_class( '/src/class-wc-url-coupons-import-export-handler.php', 'GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons_Import_Export_Handler' );
	}


	/**
	 * Returns the Admin instance.
	 *
	 * May be loaded in API context:
	 * @see \GoDaddy\WordPress\MWC\UrlCoupons\REST_API::handle_insert_shop_coupon_data()
	 *
	 * @since 2.3.0
	 *
	 * @return WC_URL_Coupons_Admin
	 */
	public function get_admin_instance() {

		if ( null === $this->admin ) {
			$this->admin = $this->load_class( '/src/admin/class-wc-url-coupons-admin.php', '\GoDaddy\WordPress\MWC\UrlCoupons\Admin\WC_URL_Coupons_Admin' );
		}

		return $this->admin;
	}


	/**
	 * Returns the Front End instance.
	 *
	 * @since 2.3.0
	 *
	 * @return WC_URL_Coupons_Frontend
	 */
	public function get_frontend_instance() {

		return $this->frontend;
	}


	/**
	 * Returns the AJAX instance.
	 *
	 * @since 2.3.0
	 *
	 * @return WC_URL_Coupons_Ajax
	 */
	public function get_ajax_instance() {

		return $this->ajax;
	}


	/**
	 * Returns the import/export handler instance.
	 *
	 * @since 2.4.0
	 *
	 * @return WC_URL_Coupons_Import_Export_Handler
	 */
	public function get_import_export_handler_instance() {

		return $this->import_export_handler;
	}


	/**
	 * Gets the prefix to use for URL coupons
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	public function get_url_coupons_url_prefix() {

		$prefix = get_option( 'wc_url_coupons_url_prefix', '' );

		return is_string( $prefix ) ? trim( $prefix ) : '';
	}


	/**
	 * Gets the URL for a given object.
	 *
	 * @since 2.13.0
	 *
	 * @param int $object_id
	 * @param string $object_type
	 * @return string
	 */
	public function get_object_url( $object_id, $object_type ) {

		switch ( $object_type ) {

			case 'page':
				$url = ( -1 === $object_id ) ? home_url() : get_permalink( $object_id );
				break;

			case 'product':

				$product  = wc_get_product( $object_id );
				$url = $product->get_permalink();

				break;

			case 'category':
			case 'post_tag':
			case 'product_cat':
			case 'product_tag':
				$url = get_term_link( $object_id, $object_type );
				break;

			default:
				$url = get_permalink( $object_id );
				break;
		}

		return $url;
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.2
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce URL Coupons', 'woocommerce-url-coupons' );
	}


	/**
	 * Returns the plugin main class file.
	 *
	 * @since 1.2
	 *
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Returns the plugin sales page URL.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return 'https://woocommerce.com/products/url-coupons/';
	}


	/**
	 * Returns the plugin documentation URL.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_documentation_url() {

		return 'https://godaddy.com/help/-40741';
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Returns the plugin configuration URL.
	 *
	 * @since 2.3.1
	 *
	 * @param string $_ unused
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $_ = null ) {

		return Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4.0' ) ? admin_url( 'admin.php?page=wc-settings&tab=general' ) : admin_url( 'admin.php?page=wc-settings&tab=checkout' );
	}


	/**
	 * Returns the main plugin class instance.
	 *
	 * Ensures only one instance is/can be loaded.
	 *
	 * @see wc_url_coupons()
	 *
	 * @since 1.3.0
	 *
	 * @return \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}


}


/**
 * Returns the One True Instance of URL Coupons.
 *
 * @since 1.3.0
 *
 * @return WC_URL_Coupons
 */
function wc_url_coupons() {

	return WC_URL_Coupons::instance();
}
