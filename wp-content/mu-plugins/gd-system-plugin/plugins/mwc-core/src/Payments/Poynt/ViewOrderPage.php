<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

class ViewOrderPage
{
    use IsConditionalFeatureTrait;

    /**
     * ViewOrderPage constructor.
     */
    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * Register actions and filters hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
                ->setGroup('woocommerce_order_details_before_order_table_items')
                ->setHandler([$this, 'maybeAddReadyForPickup'])
                ->execute();
    }

    /**
     * Maybe add some text informing the user that their order is ready for pickup.
     *
     * @param \WC_Order $order
     */
    public function maybeAddReadyForPickup(\WC_Order $order)
    {
        // don't show if order was not marked as ready for pickup
        if (empty($order->get_meta('_poynt_order_status_ready_at'))) {
            return;
        }

        // don't show if order is not in a status where it makes sense
        if (! in_array($order->get_status(), ['pending', 'processing', 'on-hold'])) {
            return;
        } ?><p><?php echo esc_html__('Order is ready for pickup.', 'mwc-core'); ?></p><?php
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature(): bool
    {
        return WooCommerceRepository::isWooCommerceActive() && Configuration::get('features.bopit', false);
    }
}
