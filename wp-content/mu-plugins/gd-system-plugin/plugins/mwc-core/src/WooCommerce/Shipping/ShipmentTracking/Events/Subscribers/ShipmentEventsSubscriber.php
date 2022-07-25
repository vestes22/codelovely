<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent;

class ShipmentEventsSubscriber implements SubscriberContract
{
    /**
     * Handles the AbstractShipmentEvent.
     *
     * @since 2.10.0
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if ($this->shouldFireTrackingInformationAddedAction($event)) {
            $this->fireTrackingInformationAddedAction($event->getOrderFulfillment()->getOrder()->getId());
        }
    }

    /**
     * Determines whether the mwc_shipment_tracking_information_added action should be fired.
     *
     * @since 2.10.0
     *
     * @param EventContract $event event object
     *
     * @return bool
     */
    protected function shouldFireTrackingInformationAddedAction(EventContract $event) : bool
    {
        return $event instanceof ShipmentCreatedEvent || $event instanceof ShipmentUpdatedEvent;
    }

    /**
     * Fires the mwc_shipment_tracking_information_added action.
     *
     * @since 2.10.0
     *
     * @param int $orderId
     */
    protected function fireTrackingInformationAddedAction(int $orderId)
    {
        /*
         * Fires when shipment tracking information is added to an order.
         *
         * @param int $orderId
         */
        do_action('mwc_shipment_tracking_information_added', $orderId);
    }
}
