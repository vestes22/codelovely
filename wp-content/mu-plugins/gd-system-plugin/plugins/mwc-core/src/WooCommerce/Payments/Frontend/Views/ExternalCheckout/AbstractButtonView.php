<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\ExternalCheckout;

/**
 * The abstract button view.
 *
 * This represents a button, generally for external payment methods.
 */
abstract class AbstractButtonView
{
    /**
     * Decides if the button should be available.
     *
     * @param string $context used in concrete implementations
     * @return bool
     */
    public function isAvailable(string $context) : bool
    {
        return true;
    }

    /**
     * Renders the button.
     */
    abstract public function render();
}
