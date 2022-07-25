<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping;

use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Fulfillment as ShippingFulfillment;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;

/**
 * Fulfillment handler.
 *
 * @since x.y.z
 */
class Fulfillment extends ShippingFulfillment
{
    // TODO: update the IsSingletonTrait to use static instead of self, so it works on sub-classes too {wvega 2021-06-17}
    use IsSingletonTrait;

    /**
     * Updates the given order fulfillment shipments statuses.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     */
    public function update(OrderFulfillment $fulfillment)
    {
        parent::update($fulfillment);

        $this->getOrderFulfillmentDataStore()->save($fulfillment);
    }

    /**
     * Gets an instance of OrderFulfillmentDataStore.
     *
     * @return OrderFulfillmentDataStore
     */
    protected function getOrderFulfillmentDataStore() : OrderFulfillmentDataStore
    {
        return new OrderFulfillmentDataStore();
    }
}
