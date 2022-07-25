<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceEmailOutputTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use WC_Order;

/**
 * A provider to handle data for email notification components that represent the hooks that are normally triggered from WooCommerce order email templates.
 *
 * See the admin new order WooCommerce template for hooks that this provider intends to retrieve.
 */
class EmailOrderHooksDataProvider extends OrderDataProvider
{
    use CanGetWooCommerceEmailOutputTrait;

    /**
     * {@inheritdoc}
     */
    protected function getWooCommerceEmail()
    {
        return $this->emailNotification->getWooCommerceEmail();
    }

    /**
     * Gets hooks data for the given order.
     *
     * @param Order $order
     * @return array
     * @throws Exception
     */
    protected function getOrderData(Order $order): array
    {
        $this->setConfigurationFromEmailTemplate($this->emailNotification->getTemplate());
        $this->temporarilyOverrideWooCommerceTemplateOptions();

        $output = $this->getOutputFromHooks($order);

        $this->restoreWooCommerceTemplateOptions();

        return ['internal' => $output];
    }

    /**
     * Gets the output from the configured WooCommerce email hooks for the given order.
     *
     * @param Order $order
     * @return array
     * @throws Exception
     */
    public function getOutputFromHooks(Order $order)
    {
        $wooCommerceOrder = $this->getWooCommerceOrder($order);
        $output = [];

        foreach ($this->getHookNames() as $hook) {
            if ($placeholder = StringHelper::after($hook, 'woocommerce_email_')) {
                $output[$placeholder] = $this->getOutputFromHook($hook, $wooCommerceOrder);
            }
        }

        return $output;
    }

    /**
     * Triggers the given action hook and adds inline styles to the resulting HTML code.
     *
     * TODO: add tests for this method {wvega 2021-10-13}
     *
     * @param string $hook the name of the hook
     * @param WC_Order $wooCommerceOrder a WooCommerce order object
     * @return string
     */
    protected function getOutputFromHook(string $hook, WC_Order $wooCommerceOrder) : string
    {
        $output = $this->getOutputFromCallback(function () use ($hook, $wooCommerceOrder) {
            do_action(
                $hook,
                $wooCommerceOrder,
                $this->emailNotification->isSentToAdministrator(),
                false,
                $this->emailNotification->getWooCommerceEmail()
            );
        });

        return $this->addInlineStyles($output);
    }

    /**
     * Gets the name of the email order hooks.
     *
     * @return string[]
     */
    protected function getHookNames() : array
    {
        return [
            'woocommerce_email_order_details',
            'woocommerce_email_customer_details',
            'woocommerce_email_order_meta',
        ];
    }

    /**
     * Gets order hooks placeholders.
     *
     * @return string[]
     */
    public function getPlaceholders() : array
    {
        return [];
    }
}
