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
 * @copyright   Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\SequentialOrderNumbers\Admin;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\SequentialOrderNumbers\WC_Seq_Order_Number_Pro;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;
use WC_Settings_Page;

/**
 * Settings page handler.
 *
 * @since 2.0.0
 */
class Settings extends WC_Settings_Page {

	/** @var WC_Seq_Order_Number_Pro instance */
	private $plugin;


	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param WC_Seq_Order_Number_Pro $plugin
	 */
	public function __construct( WC_Seq_Order_Number_Pro $plugin ) {

		$this->plugin = $plugin;
		$this->id     = WC_Seq_Order_Number_Pro::SETTINGS_TAB_ID;
		$this->label  = __( 'Orders', 'woocommerce-sequential-order-numbers-pro' );

		add_action( 'woocommerce_admin_field_info', [ $this, 'render_info_field' ] );
		add_action( 'woocommerce_admin_field_order_number_format', [ $this, 'render_number_format_field' ] );

		parent::__construct();
	}


	/**
	 * Renders custom woocommerce admin form field via woocommerce_admin_field_* action.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data associative array of field parameters
	 */
	public function render_info_field( $data ) {

		if ( empty( $data['id'] ) ) {
			return;
		}

		$data = wp_parse_args( $data, [
			'title' => '',
			'class' => '',
			'css'   => '',
			'name'  => '',
			'desc'  => '',
		] );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $data['id'] ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<span class="<?php echo esc_attr( $data['class'] ); ?>"
					      id="<?php echo esc_attr( $data['id'] ); ?>"
					      style="<?php echo esc_attr( $data['css'] ); ?>">
                        <?php echo esc_html( $data['name'] ); ?>
                    </span>
				</fieldset>
			</td>
		</tr>
		<?php
	}


	/**
	 * Renders order number format woocommerce admin form fields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data associative array of field parameters
	 */
	public function render_number_format_field( $data ) {

		if ( empty( $data['id'] ) ) {
			return;
		}

		$data = (array) wp_parse_args( $data, [
			'title'    => '',
			'class'    => '',
			'css'      => '',
			'name'     => '',
			'desc'     => '',
			'desc_tip' => '',
		] );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo wc_help_tip( $data['desc_tip'] ); ?>
			</th>
			<td class="forminp">
				<fieldset style="display: flex; flex-wrap: wrap; gap: 12px;">
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>

					<label class="order-number-format-prefix">
						<input type="text" name="woocommerce_order_number_prefix" id="woocommerce_order_number_prefix"
						       style="width: 104px; display: block; margin-bottom: 4px;"
						       value="<?php echo esc_attr( $this->plugin->get_order_number_prefix() ) ?>"
						       placeholder="<?php esc_attr_e( '{mm}', 'woocommerce-sequential-order-numbers-pro' ); ?>">
						<span class="description"><?php esc_html_e( 'Prefix', 'woocommerce-sequential-order-numbers-pro' ); ?></span>
					</label>
					<label class="order-number-format-start">
						<input type="text" name="woocommerce_order_number_start" id="woocommerce_order_number_start"
						       style="width: 104px; display: block; margin-bottom: 4px;"
						       value="<?php echo esc_attr( $this->plugin->get_order_number_start() ) ?>">
						<span class="description"><?php esc_html_e( 'Number', 'woocommerce-sequential-order-numbers-pro' ); ?></span>
					</label>
					<label class="order-number-format-suffix">
						<input type="text" name="woocommerce_order_number_suffix" id="woocommerce_order_number_suffix"
						       style="width: 104px; display: block; margin-bottom: 4px;"
						       value="<?php echo esc_attr( $this->plugin->get_order_number_suffix() ) ?>"
						       placeholder="<?php esc_attr_e( '{yyyy}', 'woocommerce-sequential-order-numbers-pro' ); ?>">
						<span class="description"><?php esc_html_e( 'Suffix', 'woocommerce-sequential-order-numbers-pro' ); ?></span>
					</label>
				</fieldset>
				<p class="description"><?php echo wp_kses_post( $data['desc'] ); ?></p>
			</td>
		</tr>
		<?php
	}


	/**
	 * Get settings array.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = $this->get_settings_for_default_section();

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}


	/**
	 * Get own sections for this page.
	 *
	 * @since 2.0.0
	 *
	 * @return array An associative array where keys are section identifiers and the values are translated section names.
	 */
	protected function get_own_sections() {

		return [
			// WC_Settings_Page requires a default section with an empty ID, so we cannot set the ID here
			'' => __( 'Order Numbers', 'woocommerce-sequential-order-numbers-pro' ),
		];
	}


	/**
	 * Get settings for the Order Numbers section.
	 *
	 * WC_Settings_Page requires a default section with an empty ID, so we cannot use "order_numbers" as the section ID.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() : array {

		return [
			[
				'name' => __( 'Order Numbers', 'woocommerce-sequential-order-numbers-pro' ),
				'type' => 'title',
				'desc' => __( 'Use sequential, formatted order numbers to track your orders. Change the starting number, or add prefixes and suffixes for accounting.', 'woocommerce-sequential-order-numbers-pro' ),
				'id'   => 'order_numbers_options',
			],
			[
				'title' => __( 'Sample Order Number', 'woocommerce-sequential-order-numbers-pro' ),
				'type'  => 'info',
				'id'    => 'sample_order_number',
				'css'   => 'font-size: 1.16em;',
				'name'  => $this->plugin->format_order_number( $this->plugin->get_order_number_start(), $this->plugin->get_order_number_prefix(), $this->plugin->get_order_number_suffix(), $this->plugin->get_order_number_length() ),
			],
			[
				'name'     => __( 'Order Number Format', 'woocommerce-sequential-order-numbers-pro' ),
				'desc_tip' => __( 'Enter prefixes, suffixes, or patterns for order numbers. You can use leading 0s to control order number length.', 'woocommerce-sequential-order-numbers-pro' ),
				'id'       => 'woocommerce_order_number_format',
				'type'     => 'order_number_format',
				/* translators: Placeholders: %1$s - opening <a> link tag, %2$s - closing </a> link tag */
				'desc'     => sprintf( __( 'See the %1$sdocumentation%2$s for the full set of available patterns.', 'woocommerce-sequential-order-numbers-pro' ), '<a target="_blank" href="' . esc_url( $this->plugin->get_documentation_url() ) . '">', '</a>' ),
			],
			[
				'name'     => __( 'Skip Free Orders', 'woocommerce-sequential-order-numbers-pro' ),
				'desc'     => __( 'Skip order numbers for free orders', 'woocommerce-sequential-order-numbers-pro' ),
				'desc_tip' => __( 'Use a different sequence and prefix for free orders. Example: FREE-123.', 'woocommerce-sequential-order-numbers-pro' ),
				'id'       => 'woocommerce_order_number_skip_free_orders',
				'type'     => 'checkbox',
				'css'      => 'min-width:300px;',
				'default'  => 'no',
			],
			[
				'name'     => __( 'Free Order Identifier', 'woocommerce-sequential-order-numbers-pro' ),
				/* translators: Placeholders: %s - sample order number */
				'desc'     => sprintf( __( 'Example free order identifier: %s', 'woocommerce-sequential-order-numbers-pro' ), '<span id="sample_free_order_number">' . $this->plugin->format_order_number( $this->plugin->get_free_order_number_start(), $this->plugin->get_free_order_number_prefix() ) . '</span>' ),
				'desc_tip' => __( 'The text to display in place of the order number for free orders. This will be displayed anywhere an order number would otherwise be shown: to the customer, in emails, and in the admin.', 'woocommerce-sequential-order-numbers-pro' ),
				'id'       => 'woocommerce_free_order_number_prefix',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
				/* translators: FREE- as in free purchase order */
				'default'  => __( 'FREE-', 'woocommerce-sequential-order-numbers-pro' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'order_number_options',
			],
		];
	}


	/**
	 * Output the HTML for the settings.
	 *
	 * @since 2.0.0
	 */
	public function output() {

		parent::output();

		ob_start();

		?>
		( function( $ ) {

			var free_order_number_start = <?php echo $this->plugin->get_free_order_number_start(); ?>;

			$( '#woocommerce_order_number_skip_free_orders' ).change( function() {
				if ( ! $( this ).is( ':checked' ) ) {
					$( '#woocommerce_free_order_number_prefix' ).closest( 'tr' ).hide();
				} else {
					$( '#woocommerce_free_order_number_prefix' ).closest( 'tr' ).show();
				}
			} ).change();

			$( '#woocommerce_free_order_number_prefix' ).on( 'keyup change input', function() {
				$( '#sample_free_order_number' ).text( formatOrderNumber( free_order_number_start, $( this ).val() ) );
			} ).change();

			$('#woocommerce_order_number_start, #woocommerce_order_number_prefix, #woocommerce_order_number_suffix').on('keyup change input', function() {
				$( '#sample_order_number' ).text( formatOrderNumber( $( '#woocommerce_order_number_start' ).val(), $( '#woocommerce_order_number_prefix' ).val(), $( '#woocommerce_order_number_suffix' ).val() ) );
			} ).change();

			function formatOrderNumber( orderNumber, orderNumberPrefix, orderNumberSuffix ) {

				// Ensure the prefix and suffix are set to uppercase.
				orderNumberPrefix = ( typeof orderNumberPrefix === "undefined" ) ? "" : orderNumberPrefix;
				orderNumberSuffix = ( typeof orderNumberSuffix === "undefined" ) ? "" : orderNumberSuffix;

				var formatted = orderNumberPrefix + orderNumber + orderNumberSuffix;
				var formattedUpper = formatted.toUpperCase();

				var d = new Date();
				if ( formattedUpper.indexOf( '{D}' )    > -1) formatted = formatted.replace( /{D}/gi,    d.getDate() );
				if ( formattedUpper.indexOf( '{DD}' )   > -1) formatted = formatted.replace( /{DD}/gi,   leftPad( d.getDate().toString(), 2, '0' ) );
				if ( formattedUpper.indexOf( '{M}' )    > -1) formatted = formatted.replace( /{M}/gi,    d.getMonth() + 1 );
				if ( formattedUpper.indexOf( '{MM}' )   > -1) formatted = formatted.replace( /{MM}/gi,   leftPad( ( d.getMonth() + 1 ).toString(), 2, '0' ) );
				if ( formattedUpper.indexOf( '{YY}' )   > -1) formatted = formatted.replace( /{YY}/gi,   ( d.getFullYear() ).toString().substr( 2 ) );
				if ( formattedUpper.indexOf( '{YYYY}' ) > -1) formatted = formatted.replace( /{YYYY}/gi, d.getFullYear() );
				if ( formattedUpper.indexOf( '{H}' )    > -1) formatted = formatted.replace( /{H}/gi,    d.getHours() );
				if ( formattedUpper.indexOf( '{HH}' )   > -1) formatted = formatted.replace( /{HH}/gi,   leftPad( d.getHours().toString(), 2, '0' ) );
				if ( formattedUpper.indexOf( '{N}' )    > -1) formatted = formatted.replace( /{N}/gi,    leftPad( d.getMinutes().toString(), 2, '0' ) );
				if ( formattedUpper.indexOf( '{S}' )    > -1) formatted = formatted.replace( /{S}/gi,    leftPad( d.getSeconds().toString(), 2, '0' ) );

				return formatted;
			}

			function leftPad( value, count, char ) {
				while ( value.length < count ) {
					value = char + value;
				}
				return value;
			}

		} ) ( jQuery );
		<?php

		wc_enqueue_js( ob_get_clean() );
	}


	/**
	 * Add this page to settings.
	 *
	 * @since 2.0.0
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
	 * Save settings and trigger the 'woocommerce_update_options_'.id action.
	 *
	 * @since 2.0.0
	 */
	public function save() {

		foreach ( [ 'prefix', 'start', 'suffix' ] as $field ) {

			$value = sanitize_text_field( Framework\SV_WC_Helper::get_posted_value( 'woocommerce_order_number_' . $field ) );

			update_option( 'woocommerce_order_number_' . $field, $value );
		}

		parent::save();

		do_action( 'wc_sequential_order_numbers_settings_updated' );
	}


}
