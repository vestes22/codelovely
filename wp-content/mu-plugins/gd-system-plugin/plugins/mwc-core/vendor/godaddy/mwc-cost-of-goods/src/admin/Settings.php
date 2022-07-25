<?php
/**
 * WooCommerce Cost of Goods
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Cost of Goods to newer
 * versions in the future. If you wish to customize WooCommerce Cost of Goods for your
 * needs please refer to http://docs.woocommerce.com/document/cost-of-goods/ for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2020, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\CostOfGoods\Admin;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\CostOfGoods\WC_COG;
use WC_Settings_Page;

/**
 * Settings page handler.
 *
 * @since 3.0.0
 */
class Settings extends WC_Settings_Page {

	/** @var WC_COG instance */
	private $plugin;


	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_COG $plugin
	 */
	public function __construct( WC_COG $plugin ) {

		$this->plugin = $plugin;
		$this->id     = WC_COG::SETTINGS_TAB_ID;
		$this->label  = __( 'Orders', 'mwc-cost-of-goods' );

		// add a apply costs woocommerce_admin_fields() field type
		add_action( 'woocommerce_admin_field_wc_cog_apply_costs_to_previous_orders', [ $this, 'render_apply_costs_section' ] );

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'load_styles_scripts' ] );

		// WordPress would automatically convert some HTML entities into emoji in the settings page
		add_action( 'init', [ $this, 'disable_settings_wp_emoji' ] );

		parent::__construct();
	}


	/**
	 * Get settings array.
	 *
	 * @since 3.0.1
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = $this->get_settings_for_default_section();

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}


	/**
	 * Gets own sections for this page.
	 *
	 * @since 3.0.0
	 *
	 * @return array An associative array where keys are section identifiers and the values are translated section names.
	 */
	protected function get_own_sections() {

		return [
			// WC_Settings_Page requires a default section with an empty ID, so we cannot set the ID here
			'' => __( 'Cost of Goods', 'mwc-cost-of-goods' ),
		];
	}


	/**
	 * Gets settings for the Cost of Goods section.
	 *
	 * WC_Settings_Page requires a default section with an empty ID, so we cannot use "cost_of_goods" as the section ID.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() : array {

		return WC_COG_Admin::get_global_settings();
	}


	/**
	 * Adds this page to settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $pages The settings array where we'll add ourselves.
	 *
	 * @return array
	 */
	public function add_settings_page( $pages ) {

		if ( isset( $pages[ $this->id ] ) ) {
			return $pages;
		}

		$new_pages = [];

		foreach ( $pages as $id => $label ) {
			$new_pages[ $id ] = $label;

			// make sure to add the page after Payments
			if ( 'checkout' === $id ) {
				$new_pages[ $this->id ] = $this->label;
			}
		}

		return $new_pages;
	}


	/**
	 * Renders the "Apply Costs to Previous Orders" setting section HTML.
	 *
	 * @internal
	 *
	 * @since 3.0.0 moved from WC_COG_Admin
	 *
	 * @param array $field associative array of field parameters
	 */
	public function render_apply_costs_section( $field ) {

		if ( ! $this->store_has_orders() ) {
			return;
		}

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>">
					<?php esc_html_e( 'Apply Costs to Previous Orders', 'mwc-cost-of-goods' ); ?>
				</label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_html_class( $field['type'] ) ?>">
				<?php

				/**
				 * Fires when outputting options to apply costs to previous orders.
				 *
				 * This allows third parties or integrations to insert more options before the action button.
				 *
				 * @since 2.8.0
				 *
				 * @param array $field parameters of custom WooCommerce field being output
				 */
				do_action( 'wc_cost_of_goods_apply_costs_to_previous_orders_options_html', $field );

				$job_in_progress = $this->plugin->get_previous_orders_handler_instance()->get_job();

				?>
				<fieldset>
					<input
						type="hidden"
						name="wc_cog_apply_costs_job_id"
						value="<?php echo $job_in_progress ? esc_attr( $job_in_progress->id ) : ''; ?>"
					/>
					<span class="description"><?php esc_html_e( 'Apply costs to all orders, overriding previous costs, this action is not reversible!', 'mwc-cost-of-goods' ); ?></span>
					<br /><br />
					<button
						id="<?php echo esc_attr( $field['id'] ); ?>"
						class="button"
						<?php disabled( (bool) $job_in_progress, true, true ); ?>><?php
						esc_html_e( 'Apply Costs', 'mwc-cost-of-goods' ); ?></button>
					<span class="spinner applying-costs-progress <?php echo (bool) $job_in_progress ? 'is-active' : ''; ?>" style="float: none;"></span>
					<p></p><?php // holds job progress updates ?>
					<br />
					<span class="description">
						<?php
						printf(
							/* translators: Placeholders: %1$s - <a>, %2$s - </a> */
							esc_html__( 'Need to bulk update some orders but not all? Use your included Customer/Order/Coupon Export and Customer/Order/Coupon CSV Import Suite extensions to update costs in bulk. %1$sGet extensions%2$s', 'mwc-cost-of-goods' ),
							'<a href="' . admin_url( 'admin.php?page=wc-addons&tab=available_extensions' ) . '" target="_blank">',
							'</a>'
						);
						?>
					</span>
				</fieldset>
			</td>
		</tr>
		<?php
	}


	/**
	 * Determines whether the store has at least one order.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function store_has_orders() : bool {

		$orders_count = count( wc_get_orders( [ 'return', 'ids' ] ) );

		return $orders_count && $orders_count > 0;
	}


	/**
	 * Loads admin styles and scripts.
	 *
	 * @since 3.0.0 moved from WC_COG_Admin
	 */
	public function load_styles_scripts() {

		if ( $this->plugin->is_plugin_settings() ) {

			if ( $background_job = $this->plugin->get_previous_orders_handler_instance()->get_job() ) {
				$background_job_id = $background_job->id;
			} else {
				$background_job_id = false;
			}

			wp_enqueue_script( 'wc-cog-admin-settings', $this->plugin->get_plugin_url() . '/assets/js/admin/wc-cog-admin-settings.min.js', [ 'jquery', 'woocommerce_admin' ], WC_COG::VERSION );

			wp_localize_script( 'wc-cog-admin-settings', 'wc_cog_admin_settings', [

				'ajax_url'                                => admin_url( 'admin-ajax.php' ),
				'existing_background_job_id'              => $background_job_id,
				'get_cost_of_goods_nonce'                 => wp_create_nonce( 'get-cost-of-goods' ),
				'apply_cost_of_goods_nonce'               => wp_create_nonce( 'apply-cost-of-goods' ),
				'get_applying_cost_of_goods_status_nonce' => wp_create_nonce( 'get-applying-cost-of-goods-status' ),

				'i18n' => [
					'apply_costs_confirm_message_all' => __( 'Are you sure you want to apply costs to ALL previous orders, overriding those with existing costs? This cannot be reversed! Note that this can take some time in shops with a large number of orders.', 'mwc-cost-of-goods' ),
					'apply_costs_error'               => __( 'Oops! Something went wrong. Please try again.', 'mwc-cost-of-goods' ),
					'apply_costs_success'             => __( 'Cost of goods applied to previous orders.', 'mwc-cost-of-goods' ),
					'apply_costs_in_progress'         => __( 'Applying costs of goods to previous orders...', 'mwc-cost-of-goods' ),
					'apply_costs_notice'              => __( 'Process is running in the background, you can safely navigate away from this page without disrupting the process.', 'mwc-cost-of-goods' ),
				],
			] );

			wp_enqueue_style( 'wc-cog-admin', $this->plugin->get_plugin_url() . '/assets/css/admin/wc-cog-admin.min.css', [], WC_COG::VERSION );
		}
	}


	/**
	 * Prevents the conversion of some HTML entities used in the plugin settings page into emojis.
	 *
	 * @internal
	 *
	 * @since 3.0.0 moved from WC_COG_Admin
	 */
	public function disable_settings_wp_emoji() {

		if ( $this->plugin->is_plugin_settings() ) {

			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		}
	}


}
