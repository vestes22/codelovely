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

use GoDaddy\WordPress\MWC\SequentialOrderNumbers\WC_Seq_Order_Number_Pro;

defined( 'ABSPATH' ) or exit;

/**
 * Onboarding tips handler.
 *
 * @see \WC_Admin_Pointers
 * @see \WP_Internal_Pointers
 *
 * @since 1.16.0
 */
class Onboarding_Tips {


	/** @var string onboarding yet to start */
	const ONBOARDING_START = 'onboard';

	/** @var string onboarding completed by the merchant */
	const ONBOARDING_COMPLETED = 'complete';

	/** @var string onboarding dismissed by the merchant */
	const ONBOARDING_DISMISSED = 'dismissed';

	/** @var string onboarding marked as complete by a plugin update */
	const ONBOARDING_UPDATED = 'updated';

	/** @var string onboarding marked as complete with the user subscribing to the mailing list */
	const ONBOARDING_SUBSCRIBED = 'subscribed';


	/** @var WC_Seq_Order_Number_Pro instance */
	private $plugin;


	/**
	 * Constructor.
	 *
	 * @since 1.16.0
	 *
	 * @param WC_Seq_Order_Number_Pro $plugin
	 */
	public function __construct( WC_Seq_Order_Number_Pro $plugin ) {

		$this->plugin = $plugin;

		add_action( 'admin_enqueue_scripts', [ $this, 'add_onboarding_tips' ] );

		add_action( 'wp_ajax_' . $plugin->get_id() . '_onboarding_dismiss',  [ $this, 'dismiss_onboarding' ] );
		add_action( 'wp_ajax_' . $plugin->get_id() . '_onboarding_complete', [ $this, 'complete_onboarding' ] );
	}


	/**
	 * Gets the plugin main instance.
	 *
	 * @return WC_Seq_Order_Number_Pro
	 */
	private function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets the onboarding option status key.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	private function get_onboarding_status_option_key() {

		return sprintf( 'wc_%s_onboarding_status', $this->get_plugin()->get_id() );
	}


	/**
	 * Sets the onboarding status.
	 *
	 * @since 1.16.0
	 *
	 * @param string $status
	 */
	private function set_onboarding_status( $status ) {

		$prompt_notice_id = sprintf( '%s_prompt', $this->get_onboarding_status_option_key() );

		$this->get_plugin()->get_admin_notice_handler()->dismiss_notice( $prompt_notice_id );

		update_option( $this->get_onboarding_status_option_key(), $status );
	}


	/**
	 * Gets the onboarding status.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	private function get_onboarding_status() {

		return (string) get_option( $this->get_onboarding_status_option_key(), self::ONBOARDING_START );
	}


	/**
	 * Sets the onboarding status to complete.
	 *
	 * @since 1.16.0
	 */
	public function complete_onboarding() {

		if ( isset( $_POST['subscribed'] ) && 'yes' === $_POST['subscribed'] ) {

			// automatically dismiss Onboarding Completed notice when the user subscribes
			$prompt_notice_id = sprintf( '%s_completed', $this->get_onboarding_status_option_key() );

			$this->get_plugin()->get_admin_notice_handler()->dismiss_notice( $prompt_notice_id );

			$status = self::ONBOARDING_SUBSCRIBED;

		} else {

			$status = self::ONBOARDING_COMPLETED;
		}

		$this->set_onboarding_status( $status );
	}


	/**
	 * Sets the onboarding status to dismissed.
	 *
	 * @since 1.16.0
	 */
	public function dismiss_onboarding() {

		$this->set_onboarding_status( self::ONBOARDING_DISMISSED );
	}


	/**
	 * Determines whether we should onboard the user.
	 *
	 * @since 1.16.0
	 *
	 * @return bool
	 */
	private function should_onboard() {

		$onboarding_screen = sprintf( '%s_onboarding_tips', $this->get_plugin()->get_id() );

		return isset( $_GET[ $onboarding_screen ] )
			&& self::ONBOARDING_START === $_GET[ $onboarding_screen ]
			&& current_user_can( 'manage_woocommerce' )
			&& self::ONBOARDING_START === $this->get_onboarding_status()
			&& $this->get_plugin()->is_plugin_settings();
	}


	/**
	 * Initializes onboarding tips if on the plugin's general settings page.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 */
	public function add_onboarding_tips() {

		if ( $this->should_onboard()  ) {

			$this->init_onboarding_tips( [
				'pointers' => [
					'order_number_start' => [
						'target'       => '#woocommerce_order_number_start',
						'next'         => 'order_number_prefix',
						'next_trigger' => [
							'target' => '#woocommerce_order_number_start',
							'event'  => 'input',
						],
						'options'      => [
							'content'  =>
								'<h3>' . esc_html__( 'Order Number Start', 'woocommerce-sequential-order-numbers-pro' ) . '</h3>' .
								'<p>'  . esc_html__( 'Set your starting order number for new orders. You can use leading zeroes to control the required order number length (e.g., “0001” for order numbers that must be four digits long).', 'woocommerce-sequential-order-numbers-pro' ) . '</p>',
							'position' => [
								'edge'  => 'top',
								'align' => 'left',
							],
						],
					],
					'order_number_prefix' => [
						'target'       => '#woocommerce_order_number_prefix',
						'next'         => 'order_number_suffix',
						'next_trigger' => [
							'target' => '#woocommerce_order_number_prefix',
							'event'  => 'input',
						],
						'options'      => [
							'content'  =>
								'<h3>' . esc_html__( 'Order Number Prefix', 'woocommerce-sequential-order-numbers-pro' ) . '</h3>' .
								'<p>'  . sprintf(
									/* translators: Placeholders: %1$s - opening <a> link HTML tag, %2$s closing </a> link HTML tag */
									esc_html__( 'Add a custom prefix to your order numbers for new orders. %1$sClick here for prefix patterns%2$s that can add special values, such as the month or year, to your order numbers.', 'woocommerce-sequential-order-numbers-pro' ),
									'<a target="_blank" href="https://docs.woocommerce.com/document/sequential-order-numbers/#section-6">', '</a>'
									) .
								'</p>',
							'position' => [
								'edge'  => 'top',
								'align' => 'left',
							],
						],
					],
					'order_number_suffix' => [
						'target'       => '#woocommerce_order_number_suffix',
						'next'         => 'skip_free_orders',
						'next_trigger' => [
							'target' => '#woocommerce_order_number_suffix',
							'event'  => 'input',
						],
						'options'      => [
							'content'  =>
								'<h3>' . esc_html__( 'Order Number Suffix', 'woocommerce-sequential-order-numbers-pro' ) . '</h3>' .
								'<p>'  . sprintf(
									/* translators: Placeholders: %1$s - opening <a> link HTML tag, %2$s closing </a> link HTML tag */
									esc_html__( 'Add a custom suffix to your order numbers for new orders. %1$sClick here for suffix%2$s patterns that can add special values, such as the month or year, to your order numbers.', 'woocommerce-sequential-order-numbers-pro' ),
									'<a target="_blank" href="https://docs.woocommerce.com/document/sequential-order-numbers/#section-6">', '</a>'
								) .
								'</p>',
							'position' => [
								'edge'  => 'top',
								'align' => 'left',
							],
						],
					],
					'skip_free_orders' => [
						'target'       => '#woocommerce_order_number_skip_free_orders',
						'next'         => 'save_changes',
						'next_trigger' => [
							'target' => '#woocommerce_order_number_skip_free_orders',
							'event'  => 'change blur click',
						],
						'options'      => [
							'content'  =>
								'<h3>' . esc_html__( 'Skip Free Orders', 'woocommerce-sequential-order-numbers-pro' ) . '</h3>' .
								'<p>'  . sprintf(
									/* translators: Placeholders: %1$s - opening <strong> HTML tag, %2$s closing </strong> link HTML tag */
									esc_html__( 'Select this setting to skip free orders in your order number sequence. When enabled, you can set a %1$sFree Order Identifier%2$s to add a prefix to free orders.', 'woocommerce-sequential-order-numbers-pro' ),
									'<strong>','</strong>'
								) .
								'</p>',
							'position' => [
								'edge'  => 'top',
								'align' => 'left',
							],
						],
					],
					'save_changes' => [
						'target'       => '.woocommerce-save-button',
						'next'         => '',
						'options'      => [
							'content'  =>
								'<h3>' . esc_html__( 'Save Changes', 'woocommerce-sequential-order-numbers-pro' ) . '</h3>' .
								'<p>'  . sprintf(
									/* translators: Placeholders: %1$s - opening <strong> HTML tag, %2$s closing </strong> link HTML tag */
									esc_html__( 'Click %1$sSave Changes%2$s to save your settings.', 'woocommerce-sequential-order-numbers-pro' ),
									'<strong>','</strong>'
								) .
								'</p>',
							'position' => [
								'edge'  => 'bottom',
								'align' => 'left',
							],
						],
					],
				],
			] );
		}
	}


	/**
	 * Displays the onboarding tips via WP Pointers JavaScript.
	 *
	 * @since 1.16.0
	 *
	 * @param array $pointers associative array of data
	 */
	private function init_onboarding_tips( array $pointers ) {

		$pointers = rawurlencode( wp_json_encode( $pointers ) );

		// load WordPress Pointers assets
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		// init our pointers
		wc_enqueue_js( "
			( function( $ ) {

				var data = JSON.parse( decodeURIComponent( '{$pointers}' ) );

				setTimeout( initPointers, 800 );

				function initPointers() {
					$.each( data.pointers, function( i ) {
						showPointer( i );
						return false;
					} );
				}

				function showPointer( id ) {

					var pointer = data.pointers[ id ],
					    options = $.extend( pointer.options, {

						pointerClass: 'wp-pointer woocommerce-sequential-order-numbers-pro-pointer',

						close: function() {
							if ( pointer.next ) {
								showPointer( pointer.next );
							}
						},

						buttons: function( event, t ) {

							var next     = '',
							    dataStep = '',
							    close    = '" . esc_js( __( 'Dismiss', 'woocommerce-sequential-order-numbers-pro' ) ) . "';

							if ( pointer.next ) {
								dataStep = 'next';
								next     = '" . esc_js( __( 'Next', 'woocommerce-sequential-order-numbers-pro' ) ) . "';
							} else {
								dataStep = 'last';
								next     = '" . esc_js( __( 'Finish', 'woocommerce-sequential-order-numbers-pro' ) ) . "';
							}

							var dismissButton  = $( '<a class=\"close\" href=\"#\" style=\"float: none; margin: 0 20px;\" data-step=\"dismiss\">' + close + '</a>' ),
							    nextButton     = $( '<a class=\"button button-primary\" href=\"#\" data-step=\"' + dataStep + '\">' + next + '</a>' ),
							    buttonsWrapper = $( '<div class=\"woocommerce-sequential-order-numbers-pointer-buttons\" />' );

							dismissButton.bind( 'click.pointer', function( e ) {
								e.preventDefault();
								t.element.pointer( 'destroy' );
							} );

							nextButton.bind( 'click.pointer', function( e ) {
								e.preventDefault();
								t.element.pointer( 'close' );
							} );

							buttonsWrapper.append( dismissButton );
							buttonsWrapper.append( nextButton );

							return buttonsWrapper;
						},

					} );

					var thisPointer = $( pointer.target ).pointer( options );

					thisPointer.pointer( 'open' );

					$( 'html, body' ).animate( { scrollTop: thisPointer.offset().top - 30 }, 300, function() {} );

					if ( pointer.next_trigger ) {

						$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
							setTimeout( function() { thisPointer.pointer( 'close' ); }, 400 );
						});
					}

					$( '.woocommerce-sequential-order-numbers-pointer-buttons a' ).on( 'click', function() {

						var step   = $( this ).data( 'step' ),
						    action = '';

						if ( 'dismiss' === step ) {
							action ='" . $this->get_plugin()->get_id() . "_onboarding_dismiss';
						} else if ( 'last' === step ) {
							action ='" . $this->get_plugin()->get_id() . "_onboarding_complete';
						} else {
							return true;
						}

						$.post( ajaxurl, { action: action }, function() { } );
					} );
				}

				$( '#mainform' ).on( 'submit', function( e ) {
					var completeOnboarding ='" . $this->get_plugin()->get_id() . "_onboarding_complete';
					$.post( ajaxurl, { action: completeOnboarding }, function() { return true; } );
				} );

			} ) ( jQuery );
		" );
	}


}
