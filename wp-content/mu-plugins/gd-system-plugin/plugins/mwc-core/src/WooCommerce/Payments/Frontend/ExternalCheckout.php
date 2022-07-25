<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\ExternalCheckout\AbstractButtonView;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\ExternalCheckout\ApplePayButtonView;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use WC_Product;

/**
 * External checkout handler.
 *
 * Handles the views for external checkout buttons, such as Apple Pay.
 */
class ExternalCheckout implements ConditionalComponentContract
{
    /** @var AbstractButtonView[] */
    protected $buttons = [];

    /**
     * Loads the external checkout components.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->buttons = [
            new ApplePayButtonView(),
        ];

        $this->addHooks();
    }

    /**
     * Adds hooks to output external checkout components in WooCommerce pages.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        // grouped products
        Register::action()
            ->setGroup('woocommerce_before_add_to_cart_button')
            ->setHandler([$this, 'renderSingleProductButtons'])
            ->execute();

        // other products
        Register::action()
            ->setGroup('woocommerce_after_add_to_cart_quantity')
            ->setHandler([$this, 'renderSingleProductButtons'])
            ->execute();

        // cart page
        Register::action()
            ->setGroup('woocommerce_proceed_to_checkout')
            ->setHandler([$this, 'renderCartButtons'])
            ->execute();

        // checkout page
        Register::action()
            ->setGroup('woocommerce_before_checkout_form')
            ->setHandler([$this, 'renderCheckoutButtons'])
            ->execute();
    }

    /**
     * Renders the external checkout buttons according to context.
     *
     * @param string $context
     */
    protected function renderButtons(string $context)
    {
        $hasButtons = false;

        foreach ($this->buttons as $button) {
            if ($button->isAvailable($context)) {
                if (! $hasButtons) {
                    echo '<div class="mwc-external-checkout-buttons">';
                }

                $button->render();
                $hasButtons = true;
            }
        }

        if ($hasButtons) {
            echo '</div>';
            /* translators: Divider between buttons like "Pay with Apple Pay" and "Add to Cart" */
            echo '<span class="mwc-external-checkout-buttons-divider">&mdash; '.esc_html__('or', 'mwc-core').' &mdash;</span>';
        }
    }

    /**
     * Renders the external checkout buttons on the cart page.
     *
     * @internal callback
     */
    public function renderCartButtons()
    {
        $wc = WooCommerceRepository::getInstance();

        // do not display buttons on empty cart
        if (! $wc || empty($wc->cart) || $wc->cart->is_empty()) {
            return;
        }

        $this->renderButtons(ApplePayGateway::BUTTON_PAGE_CART);
    }

    /**
     * Renders the external checkout buttons on the checkout page.
     *
     * @internal callback
     */
    public function renderCheckoutButtons()
    {
        $this->renderButtons(ApplePayGateway::BUTTON_PAGE_CHECKOUT);
    }

    /**
     * Renders the external checkout buttons on single product pages.
     *
     * @internal callback
     */
    public function renderSingleProductButtons()
    {
        $id = get_the_ID();

        if (! $id) {
            return;
        }

        $product = ProductsRepository::get($id);

        if (! $product || ! $this->shouldRenderSingleProductButtons($product)) {
            return;
        }

        $this->renderButtons(ApplePayGateway::BUTTON_PAGE_SINGLE_PRODUCT);
    }

    /**
     * Determines whether buttons should display for a product.
     *
     * @param WC_Product $product
     * @return bool
     */
    protected function shouldRenderSingleProductButtons(WC_Product $product) : bool
    {
        if (! $product->is_purchasable() || ! $product->is_in_stock()) {
            return false;
        }

        $actionHook = current_action();
        $productType = $product->get_type();

        // grouped products use a different action hook, so we make sure we only output buttons once per product type
        if (('grouped' !== $productType && 'woocommerce_before_add_to_cart_button' === $actionHook) || ('grouped' === $productType && 'woocommerce_after_add_to_cart_quantity' === $actionHook)) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether the component should load.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return true === Configuration::get('features.apple_pay');
    }
}
