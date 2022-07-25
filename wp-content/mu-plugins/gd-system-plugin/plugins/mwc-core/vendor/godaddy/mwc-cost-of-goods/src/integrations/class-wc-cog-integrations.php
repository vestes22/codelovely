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

namespace GoDaddy\WordPress\MWC\CostOfGoods\Integrations;

defined( 'ABSPATH' ) or exit;

use function GoDaddy\WordPress\MWC\CostOfGoods\wc_cog;

/**
 * The Cost of Goods integration handler class.
 *
 * @since 2.7.0
 */
class WC_COG_Integrations {


	/** @var WC_COG_MPC_Integration Measurement Price Calculator integration instance */
	protected $measurement_price_calculator;

	/** @var Subscriptions Subscriptions integration instance */
	protected $subscriptions;


	/**
	 * Constructs the class.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {

		if ( $this->is_measurement_price_calculator_active() ) {
			$this->measurement_price_calculator = wc_cog()->load_class( '/src/integrations/class-wc-cog-mpc-integration.php', '\GoDaddy\WordPress\MWC\CostOfGoods\Integrations\WC_COG_MPC_Integration' );
		}

		if ( $this->is_subscriptions_active() ) {
			$this->subscriptions = wc_cog()->load_class( '/src/integrations/Subscriptions.php', '\GoDaddy\WordPress\MWC\CostOfGoods\Integrations\Subscriptions' );
		}
	}


	/**
	 * Gets the Measurement Price Calculator integration instance.
	 *
	 * @since 2.7.0
	 *
	 * @return WC_COG_MPC_Integration|null
	 */
	public function get_measurement_price_calculator() {

		return $this->measurement_price_calculator;
	}


	/**
	 * Gets the Subscriptions integration instance.
	 *
	 * @since 2.8.2
	 *
	 * @return Subscriptions
	 */
	public function get_subscriptions() : Subscriptions {

		return $this->subscriptions;
	}


	/**
	 * Determines if Measurement Price Calculator is active.
	 *
	 * @since 2.7.0
	 *
	 * @return bool
	 */
	protected function is_measurement_price_calculator_active() : bool {

		return wc_cog()->is_plugin_active( 'woocommerce-measurement-price-calculator.php' );
	}


	/**
	 * Determines if Subscriptions is active.
	 *
	 * @since 2.8.2
	 *
	 * @return bool
	 */
	protected function is_subscriptions_active() : bool {

		return wc_cog()->is_plugin_active( 'woocommerce-subscriptions.php' );
	}


}
