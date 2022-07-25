<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Cart;

/**
 * A trait for API controllers that need to initialize the WooCommerce cart.
 */
trait InitializesCartTrait
{
    /**
     * Gets the current cart instance.
     *
     * @see initializeCart() for ensuring the cart instance is ready
     *
     * @return WC_Cart
     * @throws Exception
     */
    protected function getCartInstance() : WC_Cart
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
    protected function initializeCart()
    {
        $this->maybeLoadCart();

        $cart = $this->getCartInstance();

        // ensure all properties are initialized
        $cart->get_cart();
        $cart->calculate_fees();
        $cart->calculate_shipping();
        $cart->calculate_totals();
    }

    /**
     * Loads the WooCommerce cart functionality if not already loaded.
     */
    protected function maybeLoadCart()
    {
        // if this action was fired WooCommerce has already taken care of it
        if (did_action('woocommerce_load_cart_from_session')) {
            return;
        }

        wc_load_cart();
    }
}
