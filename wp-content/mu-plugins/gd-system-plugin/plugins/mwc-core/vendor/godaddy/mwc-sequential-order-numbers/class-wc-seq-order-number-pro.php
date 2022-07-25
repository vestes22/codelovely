<?php
/**
 * WooCommerce Sequential Order Numbers Pro
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Social Login to newer
 * versions in the future. If you wish to customize WooCommerce Social Login for your
 * needs please refer to http://docs.woocommerce.com/document/woocommerce-social-login/ for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2021, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\SequentialOrderNumbers;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\SequentialOrderNumbers\Admin\Onboarding_Tips;
use GoDaddy\WordPress\MWC\SequentialOrderNumbers\Admin\Settings;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * WooCommerce Sequential Order Numbers Main Plugin Class.
 *
 * @since 1.0
 */
class WC_Seq_Order_Number_Pro extends Framework\SV_WC_Plugin {


	/** version number */
	const VERSION = '2.0.0';

	/** @var WC_Seq_Order_Number_Pro single instance of this plugin */
	protected static $instance;

	/** The plugins id, used for various slugs and such */
	const PLUGIN_ID = 'sequential_order_numbers_pro';

	/** The plugins settings page tab id */
	const SETTINGS_TAB_ID = 'orders';

	/** @var string Order number custom prefix*/
	private $order_number_prefix;

	/** @var string Order number custom suffix */
	private $order_number_suffix;

	/** @var int Order number length */
	private $order_number_length;

	/** @var int Maximum order number */
	private $max_order_number;

	/** @var bool Whether performance mode is enabled */
	private $performance_mode_enabled;


	/**
	 * Sets up the plugin.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain' => 'woocommerce-sequential-order-numbers-pro',
			)
		);

		// Set the custom order number on the new order.
		// We hook into 'woocommerce_checkout_update_order_meta' for orders which are created from the frontend, and we hook into 'woocommerce_process_shop_order_meta' for admin-created orders.
		// Note we use these actions rather than the more generic 'wp_insert_post' action because we want to run after the order meta (including totals) are set so we can detect whether this is a free order.
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_sequential_order_number' ), 10, 2 );
		add_action( 'woocommerce_process_shop_order_meta',    array( $this, 'set_sequential_order_number' ), 35, 2 );
		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'set_sequential_order_number' ), 10, 1 );

		// set the custom order number for orders created by WooCommerce Deposits
		add_action( 'woocommerce_deposits_create_order', array( $this, 'set_sequential_order_number' ), 10, 1 );

		// return our custom order number for display
		add_filter( 'woocommerce_order_number', array( $this, 'get_order_number' ), 10, 2);

		// order tracking page search by order number: keep this early before WC tries to hook into this filter to "sanitize" the order ID, stripping any letters
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( $this, 'find_order_by_order_number' ), 1 );

		// Subscriptions support
		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {

			// don't copy over SONP meta from the original order to the subscription (subscription objects should not have an order number set)
			add_filter( 'wcs_subscription_meta', array( $this, 'subscriptions_remove_subscription_order_meta' ), 10, 3 );

			// don't copy over SONP meta to subscription object during upgrade from 1.5.x to 2.0
			add_filter( 'wcs_upgrade_subscription_meta_to_copy', array( $this, 'subscriptions_remove_subscription_order_meta_during_upgrade' ) );

			// don't copy over SONP meta from the subscription to the renewal order
			add_filter( 'wcs_renewal_order_meta', array( $this, 'subscriptions_remove_renewal_order_meta' ) );

			// set order number on renewals
			add_filter( 'wcs_renewal_order_created', array( $this, 'subscriptions_set_sequential_order_number' ), 9 );
		}

		if ( is_admin() ) {

			// keep the admin order search/order working properly
			add_filter( 'request',                              array( $this, 'woocommerce_custom_shop_order_orderby' ), 20 );
			add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'custom_search_fields' ) );

			// sort by underlying _order_number on the Pre-Orders table
			add_filter( 'wc_pre_orders_edit_pre_orders_request', array( $this, 'custom_orderby' ) );
			add_filter( 'wc_pre_orders_search_fields',           array( $this, 'custom_search_fields' ) );

			// inject our admin options
			add_filter( 'pre_update_option_woocommerce_order_number_start', [ $this, 'validate_order_number_start_setting' ], 10, 2 );

			// add support for the CSV export plugin
			add_filter( 'woocommerce_export_csv_extra_columns', array( $this, 'export_csv_extra_columns' ) );

			add_action( 'woocommerce_settings_start', array( $this, 'add_settings_errors' ) );

			add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings_page' ] );
		}

		// WooCommerce Admin handling
		if ( class_exists( 'Automattic\WooCommerce\Admin\Install', false ) ||
		     class_exists( 'WC_Admin_Install', false ) ) {
			add_filter( 'woocommerce_rest_orders_prepare_object_query', [ $this, 'filter_downloads_analytics_search_by_order' ], 10, 2 );
		}
	}


	/**
	 * Adds admin notices upon initialization.
	 *
	 * @since 2.0.0
	 */
	public function add_admin_notices()
	{
		$this->maybe_add_new_users_notice();
		$this->maybe_add_son_sonp_users_notice();
	}


	/**
	 * Adds a notice for new users.
	 *
	 * @since 2.0.0
	 */
	protected function maybe_add_new_users_notice() {

		$current_screen_id = get_current_screen()->id;

		// only show in WC Settings and Orders pages, and only if the option is set
		if ( ! in_array( $current_screen_id, [ 'woocommerce_page_wc-settings', 'edit-shop_order' ] )
			 || 'yes' !== get_option( 'mwc_sequential_order_numbers_show_notice_new_users' ) ) {
			return;
		}

		$notice_id = $this->get_id_dasherized() . '-new-users';

		ob_start();

		?>
		<p id="<?php echo esc_attr( "woocommerce-{$notice_id}-notice-buttons" ); ?>">
			<a class="button button-primary" href="<?php echo esc_url( $this->get_onboarding_url() ); ?>"><?php esc_html_e( "View settings", 'woocommerce-sequential-order-numbers-pro' ); ?></a>
		</p>
		<?php

		$notice_buttons = ob_get_clean();

		$this->get_admin_notice_handler()->add_admin_notice(
			__( 'Your hosting plan now offers formatted order numbers, no plugin needed! Use a prefix or suffix, change your starting number, and differentiate free orders. Configure your settings now.', 'woocommerce-sequential-order-numbers-pro' ) . $notice_buttons,
			$notice_id,
			[
				'always_show_on_settings' => false,
				'notice_class'            => 'notice-info',
			]
		);
	}


	/**
	 * Adds a notice for Sequential Order Number or Sequential Order Number Pro users.
	 *
	 * @since 2.0.0
	 */
	protected function maybe_add_son_sonp_users_notice() {

		$current_screen_id = get_current_screen()->id;

		// only show in WC Settings, WC Orders, and Plugins pages, and only if the option is set
		if ( ! in_array( $current_screen_id, [ 'woocommerce_page_wc-settings', 'edit-shop_order', 'plugins' ] )
			 || 'yes' !== get_option( 'mwc_sequential_order_numbers_show_notice_son_sonp_users' ) ) {
			return;
		}

		$notice_id = $this->get_id_dasherized() . '-son-sonp-users';

		ob_start();

		?>
		<p id="<?php echo esc_attr( "woocommerce-{$notice_id}-notice-buttons" ); ?>">
			<a class="button button-primary" href="<?php echo esc_url( $this->get_settings_url() ); ?>"><?php esc_html_e( 'View settings', 'woocommerce-sequential-order-numbers-pro' ); ?></a>
		</p>
		<?php

		$notice_buttons = ob_get_clean();

		$this->get_admin_notice_handler()->add_admin_notice(
			__( 'Your order numbers are now sequential, no plugin needed! We\'ve migrated your order number settings and deactivated the Sequential Order Numbers plugin. Review your settings here.', 'woocommerce-sequential-order-numbers-pro' ) . $notice_buttons,
			$notice_id,
			[
				'always_show_on_settings' => false,
				'notice_class'            => 'notice-info',
			]
		);
	}


	/**
	 * Registers module settings page with WooCommerce.
	 *
	 * @since 2.0.0
	 *
	 * @param array $pages
	 *
	 * @return array
	 */
	public function register_settings_page( $pages ) {

		require_once( $this->get_plugin_path() . '/src/Admin/Settings.php' );

		$pages[] = new Settings( $this );

		return $pages;
	}

	/**
	 * Initializes admin handlers.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 */
	public function init_admin() {

		require_once( $this->get_plugin_path() . '/src/Admin/Onboarding_Tips.php' );

		new Onboarding_Tips( $this );
	}


	/**
	 * Builds the lifecycle handler instance.
	 *
	 * @since 1.13.0
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/src/Lifecycle.php' );

		$this->lifecycle_handler = new Lifecycle( $this );
	}


	/**
	 * Builds the REST API handler instance.
	 *
	 * @since 1.13.0
	 */
	protected function init_rest_api_handler() {

		require_once( $this->get_plugin_path() . '/src/REST_API.php' );

		$this->rest_api_handler = new REST_API( $this );
	}


	/**
	 * Searches for an order by order number.
	 *
	 * This method can be useful for 3rd party plugins that want to rely on the Sequential Order Numbers plugin and perform lookups by custom order number.
	 *
	 * @internal
	 *
	 * @param string $order_number order number to search for
	 * @return int post_id for the order identified by $order_number, or 0
	 */
	public function find_order_by_order_number( $order_number ) {

		// because we're hooked in early, this may not have been trimmed properly
		$order_number = ltrim( $order_number, '#' );

		// search for the order by custom order number
		$query_args = array(
			'numberposts' => 1,
			'meta_key'    => '_order_number_formatted',
			'meta_value'  => $order_number,
			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'fields'      => 'ids',
		);

		$posts            = get_posts( $query_args );
		list( $order_id ) = ! empty( $posts ) ? $posts : null;

		// order was found
		if ( null !== $order_id ) {
			return $order_id;
		}

		// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
		$order = wc_get_order( $order_number );

		if ( empty( $order ) || '' !== $order->get_meta( 'order_number_formatted' ) ) {

			// _order_number was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
			return 0;
		}

		return $order->get_id();
	}


	/**
	 * Sets the _order_number/_order_number_formatted field for newly created orders.
	 *
	 * @internal
	 *
	 * TODO: We'll need to update the WP_Post property checks here with WC 4.0 updates {BR 2018-01-16}
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Order $post_id order identifier or order object
	 * @param mixed $post this is going to be an array of the POST values when
	 *                     the order is created from the checkout page in the frontend,
	 *                     null when the checkout method is PayPal Express, and a
	 *                     post object when the order is created in the admin.
	 *                     Defaults to an array so that other actions can be hooked in
	 */
	public function set_sequential_order_number( $post_id, $post = array() ) {

		// when creating an order from the admin don't create order numbers for auto-draft
		//  orders, because these are not linked to from the admin and so difficult to delete
		if ( is_array( $post ) || is_null( $post ) || ( 'shop_order' === $post->post_type && 'auto-draft' !== $post->post_status ) ) {

			$order        = $post_id instanceof \WC_Order ? $post_id : wc_get_order( $post_id );
			$order_number = $order ? $order->get_meta( '_order_number' ) : '';

			// if no order number has been assigned, this will be an empty array
			if ( empty( $order_number ) ) {

				if ( $this->skip_free_orders() && $this->is_free_order( $order ) ) {

					// assign sequential free order number
					if ( $this->generate_sequential_order_number( $order, '_order_number_free', $this->get_free_order_number_start(), $this->get_free_order_number_prefix() ) ) {

						// so that sorting still works in the admin
						$order->update_meta_data( '_order_number', -1 );
						$order->save_meta_data();
					}

				} else {

					// normal operation
					$this->generate_sequential_order_number( $order, '_order_number', get_option( 'woocommerce_order_number_start' ), $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length() );
				}
			}
		}
	}


	/**
	 * Safely generates and assigns a sequential order number to an order.
	 *
	 * @internal
	 *
	 * @since 1.3
	 *
	 * @param int|\WC_Order $order order ID or object
	 * @param string $order_number_meta_name order number meta name, ie _order_number or _order_number_free
	 * @param int $order_number_start order number starting point
	 * @param string $order_number_prefix optional order number prefix
	 * @param string $order_number_suffix optional order number suffix
	 * @param int $order_number_length optional order number length
	 * @return bool true if a sequential order number was successfully generated and assigned
	 */
	private function generate_sequential_order_number( $order, $order_number_meta_name, $order_number_start, $order_number_prefix = '', $order_number_suffix = '', $order_number_length = 1 ) {
		global $wpdb;

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$success      = false;
		$order_number = null;
		$srtm_orig    = null;
		$post_id      = $order->get_id();

		for ( $i = 0; $i < 3 && ! $success; $i++ ) {

			// "legacy mode": calculate the next sequential order number based on
			// the existing meta records. This is more accurate, but more intensive
			// on sites with large databases
			if ( ! $this->is_performance_mode_enabled() ) {

				delete_option( "woocommerce{$order_number_meta_name}_current" );  // ensure the performance option doesn't exist

				// add $order_number_meta_name equal to $order_number_start if there are no existing orders with an $order_number_meta_name meta
				//  or $order_number_start is larger than the max existing $order_number_meta_name meta.  Otherwise, $order_number_meta_name
				//  will be set to the max $order_number_meta_name + 1
				$query = $wpdb->prepare( "
					INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
					SELECT %d,'{$order_number_meta_name}',IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
						FROM {$wpdb->postmeta}
						WHERE meta_key='{$order_number_meta_name}'",
					$post_id, $order_number_start, $order_number_start );

				$success = $wpdb->query( $query );

				if ( $success ) {

					// on success, get the newly created order number
					$order_number = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d", $wpdb->insert_id ) );
				}

				// "performance mode": calculate the next order number based on
				// a db option, this is useful for sites with huge databases
			} else {

				// initialize the current order number option if needed
				$this->get_order_number_current( $order_number_meta_name );

				// HyperDB compatibility: make sure that the following couple statements run on the same db server.
				if ( method_exists( $wpdb, 'send_reads_to_masters' ) ) {
					$srtm_orig = ! empty( $wpdb->srtm ) ? $wpdb->srtm : array();
					$wpdb->send_reads_to_masters();
				}

				// do our best to compensate for mysql not having an UPDATE... RETURNING facility
				$query = $wpdb->prepare( "
					UPDATE {$wpdb->options}
					SET option_value = @option_value := IF(CAST(option_value AS UNSIGNED) < %d, %d, CAST(option_value AS UNSIGNED) + 1)
					WHERE option_name='woocommerce{$order_number_meta_name}_current'",
					$order_number_start, $order_number_start );
				$success = $wpdb->query( $query );

				if ( $success ) {

					// get our updated order number
					$order_number = (int) $wpdb->get_var( 'SELECT @option_value' );
				}

				// Stop sending all HyperDB reads to master.
				if ( ! empty( $wpdb->srtm ) && ! is_null( $srtm_orig ) ) {
					$wpdb->srtm = $srtm_orig;
				}
			}

			if ( null !== $order_number ) {

				$order->update_meta_data( '_order_number_formatted', $this->format_order_number( $order_number, $order_number_prefix, $order_number_suffix, $order_number_length, $post_id ) );

				// Unlike legacy mode, performance mode doesn't write to postmeta,
				// so we need to do this manually after obtaining an order number.
				if ( $this->is_performance_mode_enabled() ) {
					$order->update_meta_data( $order_number_meta_name, $order_number );
				}

				// save the order number configuration at the time of creation, so the integer part can be renumbered at a later date if needed
				$order_number_meta = [
					'prefix' => $order_number_prefix,
					'suffix' => $order_number_suffix,
					'length' => $order_number_length,
				];

				$order->update_meta_data( '_order_number_meta', $order_number_meta );

				$order->save_meta_data();
			}
		}

		return $success;
	}


	/**
	 * Filters to return our _order_number_formatted field rather than the post ID, for display.
	 *
	 * @internal
	 *
	 * @param string $order_number the order id with a leading hash
	 * @param \WC_Order $order the order object
	 * @return string custom order number, with leading hash
	 */
	public function get_order_number( $order_number, $order ) {

		// don't display an order number for subscription objects
		if ( $order instanceof \WC_Subscription ) {
			return $order_number;
		}

		// can't trust $order->order_custom_fields object
		$order_number_formatted = $order->get_meta( '_order_number_formatted' );

		if ( $order_number_formatted ) {
			return $order_number_formatted;
		}

		// return a 'draft' order number that will not be saved to the db (this
		//  means that when adding an order from the admin, the order number you
		//  first see may not be the one you end up with, but it's better than the
		//  alternative of showing the underlying post id)
		// TODO: This must be updated with WC 4.0 compat (whenever orders are not WP_Post objects) {BR 2018-01-16}
		$post_status = get_post_status( $order->get_id() );

		if ( 'auto-draft' === $post_status ) {
			global $wpdb;

			$order_number_start = get_option( 'woocommerce_order_number_start' );

			if ( ! $this->is_performance_mode_enabled() ) {

				// "legacy mode"
				$order_number = $wpdb->get_var( $wpdb->prepare( "
					SELECT IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
					FROM {$wpdb->postmeta}
					WHERE meta_key='_order_number'",
					$order_number_start, $order_number_start ) );

			} else {

				// "performance mode": this just needs to be a best effort attempt,
				// since this 'draft' order number is just for informational purposes
				$order_number = max( $this->get_order_number_current() + 1, get_option( 'woocommerce_order_number_start' ) );
			}

			return $this->format_order_number( $order_number, $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length(), $order->get_id() ) . ' (' . __( 'Draft', 'woocommerce-sequential-order-numbers-pro' ) . ')';
		}

		return $order_number;
	}


	/**
	 * Admin order table orderby ID operates on our meta integral _order_number.
	 *
	 * @internal
	 *
	 * @param array $vars associative array of orderby parameters
	 * @return array associative array of orderby parameters
	 */
	public function woocommerce_custom_shop_order_orderby( $vars ) {
		global $typenow;

		if ( 'shop_order' !== $typenow ) {
			return $vars;
		}

		return $this->custom_orderby( $vars );
	}


	/**
	 * Modifies the given $args argument to sort on our meta integral _order_number.
	 *
	 * @internal
	 *
	 * @since 1.5
	 *
	 * @param array $args associative array of orderby parameters
	 * @return array associative array of orderby parameters
	 */
	public function custom_orderby( $args ) {

		// Sorting
		if ( isset( $args['orderby'] ) && 'ID' == $args['orderby'] ) {

			$args = array_merge( $args, array(
				'meta_key' => '_order_number',  // sort on numerical portion for better results
				'orderby'  => 'meta_value_num',
			) );
		}

		return $args;
	}


	/**
	 * Adds our custom _order_number_formatted to the set of search fields.
	 *
	 * @internal
	 *
	 * @param array $search_fields array of post meta fields to search by
	 * @return array of post meta fields to search by
	 */
	public function custom_search_fields( $search_fields ) {

		array_push( $search_fields, '_order_number_formatted' );

		return $search_fields;
	}


	/**
	 * Validates the order number start setting.
	 *
	 * Verifies that the new value to set is an integer and that is bigger than the greatest existing order number.
	 *
	 * @internal
	 *
	 * @param string $newvalue the new value to set
	 * @param string $oldvalue the previous value
	 * @return string $newvalue if it is a positive integer, $oldvalue otherwise
	 */
	public function validate_order_number_start_setting( $newvalue, $oldvalue ) {

		// no change to starting order number
		if ( (int) $newvalue === (int) $oldvalue ) {

			// $newvalue can include left hand zero padding to set a number length, update the value if that is all that's changed
			update_option( 'woocommerce_order_number_length', strlen( $newvalue ) );

			return $newvalue;
		}

		if ( $this->is_order_number_start_invalid( $newvalue ) || $this->is_order_number_start_in_use( $newvalue ) ) {

			// bad value
			return $oldvalue;
		}

		// $newvalue can include left hand zero padding to set a number length, update this value first in case nothing else changed
		update_option( 'woocommerce_order_number_length', strlen( $newvalue ) );

		// good value, and remove any padding zeroes
		return $newvalue;
	}


	/**
	 * Adds any settings error.
	 *
	 * @internal
	 *
	 * @since 1.6
	 */
	public function add_settings_errors() {
		global $wpdb;

		// nothing doing
		if ( ! isset( $_POST['woocommerce_order_number_start'] ) ) {
			return;
		}

		$newvalue = $_POST['woocommerce_order_number_start'];
		$oldvalue = get_option( 'woocommerce_order_number_start' );

		// no change to starting order number
		if ( (int) $newvalue === (int) $oldvalue ) {
			return;
		}

		if ( $this->is_order_number_start_invalid( $newvalue ) ) {

			// bad value
			WC_Admin_Settings::add_error( __( 'Order Number Start must be a number greater than or equal to 0.', 'woocommerce-sequential-order-numbers-pro' ) );
			return;
		}

		if ( $this->is_order_number_start_in_use( $newvalue ) ) {

			// existing order number with a greater incrementing value
			$post_id = (int) $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key='_order_number' AND meta_value = %d
			", $this->get_max_order_number() ) );

			$order                = wc_get_order( $post_id );
			$highest_order_number = $order->get_order_number();

			\WC_Admin_Settings::add_error( sprintf(
			/* translators: Placeholders: %1$s - highest order number, %2$s - current order number set */
				__( 'There is an existing order (%1$s) with a number greater than or equal to %2$s. To set a new order number start please choose a higher number or permanently delete the relevant order(s).', 'woocommerce-sequential-order-numbers-pro' ), $highest_order_number, (int) $newvalue
			) );
		}
	}


	/**
	 * Adds an additional column to CSV Customer/Order Export exports.
	 *
	 * @since 1.5
	 *
	 * @param array $ret array of columns and data values
	 * @return array $ret
	 */
	public function export_csv_extra_columns( $ret ) {

		// the "formatted" order number is already exported by the CSV plugin as the "Order ID"
		// TODO: support the free orders
		$ret['columns'][] = 'Order Number';
		$ret['data'][]    = '_order_number';

		return $ret;
	}


	/**
	 * Removes sequential order numbers meta when creating a subscription object from an order at checkout.
	 *
	 * Subscriptions aren't true orders so they shouldn't have a sequential order number assigned.
	 *
	 * @internal
	 *
	 * @since 1.8.1
	 *
	 * @param array $order_meta meta on order
	 * @param \WC_Subscription $to_order order meta is being copied to
	 * @param \WC_Order $from_order order meta is being copied from
	 * @return array
	 */
	public function subscriptions_remove_subscription_order_meta( $order_meta, $to_order, $from_order ) {

		// only when copying from an order to a subscription
		if ( $to_order instanceof \WC_Subscription && $from_order instanceof \WC_Order ) {

			$meta_keys = $this->subscriptions_get_order_meta_keys();

			foreach ( $order_meta as $index => $meta ) {

				if ( in_array( $meta['meta_key'], $meta_keys ) ) {
					unset( $order_meta[ $index ] );
				}
			}
		}

		return $order_meta;
	}


	/**
	 * Removes the sequential order number meta in subscriptions during an upgrade from 1.5 to 2.x.
	 *
	 * Don't copy over the sequential order number meta during the upgrade from Subscriptions 1.5.x to 2.0.
	 * This prevents subscriptions being being displayed with the same sequential order number as their original orders.
	 *
	 * @internal
	 *
	 * @since 1.8.1
	 *
	 * @param array $order_meta meta to copy
	 * @return array
	 */
	public function subscriptions_remove_subscription_order_meta_during_upgrade( $order_meta ) {

		foreach ( $this->subscriptions_get_order_meta_keys() as $meta_key ) {

			if ( isset( $order_meta[ $meta_key ] ) ) {
				unset( $order_meta[ $meta_key ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Removes the order number meta from a subscription object.
	 *
	 * Don't copy the sequential order number meta to renewal orders from the Subscription object, as the sequential order number data is generated prior to this.
	 *
	 * @internal
	 *
	 * @since 1.8.1
	 *
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function subscriptions_remove_renewal_order_meta( $order_meta ) {

		$meta_keys = $this->subscriptions_get_order_meta_keys();

		foreach ( $order_meta as $index => $meta ) {

			if ( in_array( $meta['meta_key'], $meta_keys ) ) {
				unset( $order_meta[ $index ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Returns an array of meta keys used by Sequential Order Numbers Pro.
	 *
	 * @since 1.8.1
	 *
	 * @return string[]
	 */
	protected function subscriptions_get_order_meta_keys() {

		return array(
			'_order_number',
			'_order_number_formatted',
			'_order_number_free',
			'_order_number_meta',
		);
	}


	/**
	 * Sets an order number on a subscriptions-created order.
	 *
	 * @internal
	 *
	 * @since 1.8.1
	 *
	 * @param \WC_Order $renewal_order renewal order object
	 * @return \WC_Order
	 */
	public function subscriptions_set_sequential_order_number( $renewal_order ) {

		$order_post = get_post( $renewal_order->get_id() );

		$this->set_sequential_order_number( $order_post->ID, $order_post );

		return $renewal_order;
	}


	/**
	 * Adds support to WooCommerce Admin downloads search to include results by sequential order number.
	 *
	 * Download analytics include an order number advanced search filter, but it would only parse order IDs without further handling.
	 *
	 * @internal
	 *
	 * @since 1.13.2
	 *
	 * @param array $args associative array of arguments to be passed to WC_Order_Query
	 * @param \WP_REST_Request $request REST API request being made
	 * @return array
	 */
	public function filter_downloads_analytics_search_by_order( $args, $request ) {
		global $wpdb;

		if ( $wpdb && isset( $request['number'] ) && '/wc/v4/orders' === $request->get_route() ) {

			// handles 'number' value here and modify $args
			$number_search = trim( $request['number'] );    // order number to search
			$order_sql     = esc_sql( $args['order'] );     // order defaults to DESC
			$limit         = (int) $args['posts_per_page']; // posts per page defaults to 10

			// search order number meta value instead of post ID
			$order_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT post_id
					FROM {$wpdb->prefix}postmeta
					WHERE meta_key = '_order_number'
					AND meta_value LIKE %s
					ORDER BY post_id {$order_sql}
					LIMIT %d
				", $wpdb->esc_like( preg_replace( '~\D~', '', $number_search ) ) . '%', $limit )
			);

			$args['post__in'] = empty( $order_ids ) ? [ 0 ] : $order_ids;

			// remove the 'number' parameter to short circuit WooCommerce Admin's handling
			unset( $request['number'] );
		}

		return $args;
	}


	/**
	 * Returns the main Sequential Order Numbers Pro instance.
	 *
	 * Ensures only one instance is/can be loaded.
	 *
	 * @since 1.7.0
	 *
	 * @return \WC_Seq_Order_Number_Pro
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Returns true if "performance mode" is enabled for the plugin.  If
	 * enabled, a better performing (but potentially less accurate) means is
	 * used to generate the order number sequence.  This is useful for shops
	 * with huge databases.
	 *
	 * The main potential drawback to having performance mode enabled is that
	 * when deleting the most recent order, that order number will not be
	 * automatically re-used.  Which, is somewhat of an edge case anyways, and
	 * this behavior could actually be a positive or negative, depending on
	 * the country's accounting laws
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	private function is_performance_mode_enabled() {

		if ( ! is_null( $this->performance_mode_enabled ) ) {
			return $this->performance_mode_enabled;
		}

		return $this->performance_mode_enabled = apply_filters( 'wc_sequential_order_numbers_performance_mode', false );
	}


	/**
	 * Gets/creates the current order number (used for "performance mode").
	 *
	 * @since 1.7.0
	 *
	 * @param string $order_number_meta_name order number meta name, ie _order_number or _order_number_free
	 * @return int current order number
	 */
	private function get_order_number_current( $order_number_meta_name = '_order_number' ) {

		$order_number_current = get_option( "woocommerce{$order_number_meta_name}_current", null );

		if ( null === $order_number_current ) {
			$order_number_current = $this->calculate_max_order_number( $order_number_meta_name );
			update_option( "woocommerce{$order_number_meta_name}_current", (int) $order_number_current );
		}

		return $order_number_current;
	}


	/**
	 * Returns the max order number currently in use.
	 *
	 * @since 1.6
	 *
	 * @param string $order_number_meta_name order number meta name, ie _order_number or _order_number_free
	 * @return int maximum order number
	 */
	private function get_max_order_number( $order_number_meta_name = '_order_number' ) {

		if ( ! is_null( $this->max_order_number ) ) {
			return $this->max_order_number;
		}

		if ( $this->is_performance_mode_enabled() ) {
			return $this->max_order_number = $this->get_order_number_current( $order_number_meta_name );
		} else {
			return $this->max_order_number = $this->calculate_max_order_number( $order_number_meta_name );
		}
	}


	/**
	 * Calculates the maximum order number.
	 *
	 * Finds the max of the existing order numbers (this can be intensive on large datasets).
	 *
	 * @since 1.7.0
	 *
	 * @param string $order_number_meta_name order number meta name, ie _order_number or _order_number_free
	 * @return int maximum order number
	 */
	private function calculate_max_order_number( $order_number_meta_name = '_order_number' ) {
		global $wpdb;

		return (int) $wpdb->get_var( "
			SELECT MAX( CAST( meta_value AS SIGNED ) )
			FROM $wpdb->postmeta
			WHERE meta_key='{$order_number_meta_name}'
		" );
	}


	/**
	 * Returns true if the given order number start value is already in use, false otherwise.
	 *
	 * @since 1.6
	 *
	 * @param string $value order number start
	 * @return bool
	 */
	private function is_order_number_start_in_use( $value ) {

		// check for an existing order number with a greater incrementing value
		$order_number = $this->get_max_order_number();

		return ! is_null( $order_number ) && (int) $order_number >= $value;
	}


	/**
	 * Returns false if the given order number start value is invalid, true otherwise.
	 *
	 * @since 1.6
	 *
	 * @param string $value order number start value
	 * @return boolean true if the given order number start value is invalid
	 */
	private function is_order_number_start_invalid( $value ) {
		return ! ctype_digit( $value ) || (int) $value != $value;
	}


	/**
	 * Returns $order_number formatted with the order number prefix/postfix, if set.
	 *
	 * @param int $order_number incrementing portion of the order number
	 * @param string $order_number_prefix optional order number prefix string
	 * @param string $order_number_suffix optional order number suffix string
	 * @param int $order_number_length optional order number length
	 * @param int $order_id the order ID
	 * @return string formatted order number
	 */
	public function format_order_number( $order_number, $order_number_prefix = '', $order_number_suffix = '', $order_number_length = 1, $order_id = 0 ) {

		$order_number = (int) $order_number;

		// any order number padding?
		if ( $order_number_length && ctype_digit( $order_number_length ) ) {
			$order_number = sprintf( "%0{$order_number_length}d", $order_number );
		}

		$formatted = $order_number_prefix . $order_number . $order_number_suffix;

		// pattern substitution
		$replacements = array(
			'{D}'    => date_i18n( 'j' ),
			'{DD}'   => date_i18n( 'd' ),
			'{M}'    => date_i18n( 'n' ),
			'{MM}'   => date_i18n( 'm' ),
			'{YY}'   => date_i18n( 'y' ),
			'{YYYY}' => date_i18n( 'Y' ),
			'{H}'    => date_i18n( 'G' ),
			'{HH}'   => date_i18n( 'H' ),
			'{N}'    => date_i18n( 'i' ),
			'{S}'    => date_i18n( 's' ),
		);

		// Return $replacements as case insensitive.
		$formatted_order_number = str_ireplace( array_keys( $replacements ), $replacements, $formatted );

		/**
		 * Filters the formatted, generated order number.
		 *
		 * @since 1.12.0
		 *
		 * @param string $formatted_order_number the formatted order number
		 * @param string $order_number the padded sequential order number (numeric portion)
		 * @param int $order_id the ID of the order for the sequential order number
		 * @param string $order_number_prefix the order number configured prefix (no replacements done!)
		 * @param string $order_number_suffix the order number configured suffix (no replacements done!)
		 */
		return apply_filters( 'wc_sequential_order_numbers_formatted_order_number', $formatted_order_number, $order_number, $order_id, $order_number_prefix, $order_number_suffix );
	}


	/**
	 * Returns the order number prefix, if set.
	 *
	 * @return string order number prefix
	 */
	public function get_order_number_prefix() {

		if ( ! isset( $this->order_number_prefix ) ) {
			$this->order_number_prefix = get_option( 'woocommerce_order_number_prefix', "" );
		}

		return $this->order_number_prefix;
	}


	/**
	 * Returns the order number suffix, if set.
	 *
	 * @return string order number suffix
	 */
	public function get_order_number_suffix() {

		if ( ! isset( $this->order_number_suffix ) ) {
			$this->order_number_suffix = get_option( 'woocommerce_order_number_suffix', "" );
		}

		return $this->order_number_suffix;
	}


	/**
	 * Returns the order number length, defaulting to 1 if not set.
	 *
	 * @since 1.3
	 *
	 * @return string order number length
	 */
	public function get_order_number_length() {

		if ( ! isset( $this->order_number_length ) ) {
			$this->order_number_length = get_option( 'woocommerce_order_number_length', 1 );
		}

		return $this->order_number_length;
	}


	/**
	 * Returns the order number start.
	 *
	 * @since 2.0.0
	 *
	 * @return string order number start
	 */
	public function get_order_number_start() {

		return get_option( 'woocommerce_order_number_start' );
	}


	/**
	 * Returns true if order numbers should be skipped for orders consisting solely of free products.
	 *
	 * @since 1.3
	 *
	 * @return bool
	 */
	public function skip_free_orders() {

		return 'yes' === get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}


	/**
	 * Returns the value to use in place of the order number for free orders when 'skip free orders' is enabled
	 *
	 * @since 1.3
	 *
	 * @return string text to use in place of the order number for free orders
	 */
	public function get_free_order_number_prefix() {

		/* translators: FREE- as in free purchase order */
		return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-', 'woocommerce-sequential-order-numbers-pro' ) );
	}


	/**
	 * Gets the free order number incrementing piece.
	 *
	 * @since 1.3
	 *
	 * @return int free order number incrementing portion
	 */
	public function get_free_order_number_start() {

		return get_option( 'woocommerce_free_order_number_start' );
	}


	/**
	 * Returns true if this order consists entirely of free products AND has a total of 0 (so no shipping charges or other fees).
	 *
	 * @since 1.3
	 *
	 * @param \WC_Order $order
	 * @return bool
	 */
	private function is_free_order( $order ) {

		$is_free = true;

		// easy check: order total
		if ( $order->get_total() > 0 ) {
			$is_free = false;
		}

		// free order
		return (bool) apply_filters( 'wc_sequential_order_numbers_is_free_order', $is_free, $order->get_id() );
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.6
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Sequential Order Numbers Pro', 'woocommerce-sequential-order-numbers-pro' );
	}


	/**
	 * Returns __FILE__.
	 *
	 * @since 1.6
	 *
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the plugin documentation URL.
	 *
	 * @since 1.6
	 *
	 * @return string
	 */
	public function get_documentation_url() {

		return 'https://help.godaddy.com/help/-40714';
	}


	/**
	 * Gets the plugin support URL.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Gets the plugin sales page URL.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return 'https://woocommerce.com/products/sequential-order-numbers-pro/';
	}


	/**
	 * Gets the plugin configuration URL.
	 *
	 * @since 1.6
	 *
	 * @param string $_ unused
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $_ = null ) {

		return admin_url( 'admin.php?page=wc-settings&tab=' . static::SETTINGS_TAB_ID );
	}


	/**
	 * Gets the onboarding URL, used on notices.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_onboarding_url() {

		return add_query_arg(
			[ sprintf( '%s_onboarding_tips', $this->get_id() ) => Onboarding_Tips::ONBOARDING_START ],
			$this->get_settings_url()
		);
	}


	/**
	 * Returns true if on the admin tab configuration page.
	 *
	 * @since 1.6
	 *
	 * @return bool
	 */
	public function is_plugin_settings() {

		return 'wc-settings' === filter_input( INPUT_GET, 'page' ) &&
		       static::SETTINGS_TAB_ID === filter_input( INPUT_GET, 'tab' );
	}


}


/**
 * Returns the One True Instance of Sequential Order Numbers Pro.
 *
 * @since 1.7.0
 *
 * @return WC_Seq_Order_Number_Pro
 */
function wc_seq_order_number_pro() {

	return WC_Seq_Order_Number_Pro::instance();
}

