<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\AbstractShipmentEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentCreatedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentDeletedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentUpdatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\AbstractShipmentEvent as ShippingAbstractShipmentEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent as ShippingShipmentCreatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentDeletedEvent as ShippingShipmentDeletedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent as ShippingShipmentUpdatedEvent;

/**
 * EventBridge subscriber to shipment events.
 *
 * @since 2.10.0
 */
class EventBridgeShipmentEventsSubscriber implements SubscriberContract
{
    /**
     * Broadcasts EventBridge events whenever a shipment event is triggered.
     *
     * @since 2.10.0
     *
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $event instanceof ShippingAbstractShipmentEvent) {
            return;
        }

        $eventBridgeEvent = $this->getEventBridgeEvent($event);

        if ($eventBridgeEvent) {
            Events::broadcast($eventBridgeEvent);
        }
    }

    /**
     * Gets the EventBridge event related to a shipment event.
     *
     * @since 2.10.0
     *
     * @param ShippingAbstractShipmentEvent $event
     * @return AbstractShipmentEvent|null
     */
    protected function getEventBridgeEvent(ShippingAbstractShipmentEvent $event)
    {
        if ($event instanceof ShippingShipmentCreatedEvent) {
            return new ShipmentCreatedEvent($event->getOrderFulfillment(), $event->getShipment());
        }
        if ($event instanceof ShippingShipmentDeletedEvent) {
            return new ShipmentDeletedEvent($event->getOrderFulfillment(), $event->getShipment());
        }
        if ($event instanceof ShippingShipmentUpdatedEvent) {
            return new ShipmentUpdatedEvent($event->getOrderFulfillment(), $event->getShipment());
        }

        return null;
    }
}
