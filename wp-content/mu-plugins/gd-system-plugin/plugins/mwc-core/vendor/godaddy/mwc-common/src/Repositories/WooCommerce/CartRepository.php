<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Cart;

/**
 * Repository for handling the WooCommerce cart.
 */
class CartRepository
{
    /**
     * Gets the cart instance.
     *
     * @return WC_Cart
     * @throws Exception
     */
    public static function getInstance() : WC_Cart
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->cart) || ! $wc->cart instanceof WC_Cart) {
            throw new Exception(__('WooCommerce cart is not available', 'mwc-core'));
        }

        return $wc->cart;
    }

    /**
     * Initializes the WooCommerce cart.
     *
     * @throws Exception
     */
    public static function initialize() : WC_Cart
    {
        static::maybeLoad();

        $cart = static::getInstance();

        // ensure all properties are initialized
        $cart->get_cart();
        $cart->calculate_fees();
        $cart->calculate_shipping();
        $cart->calculate_totals();

        return $cart;
    }

    /**
     * Loads the WooCommerce cart functionality if not already loaded.
     */
    protected static function maybeLoad()
    {
        // if this action was fired WooCommerce has already taken care of it
        if (did_action('woocommerce_load_cart_from_session')) {
            return;
        }

        wc_load_cart();
    }
}
