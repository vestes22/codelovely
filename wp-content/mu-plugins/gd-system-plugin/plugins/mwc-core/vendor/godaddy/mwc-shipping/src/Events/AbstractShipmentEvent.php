<?php

namespace GoDaddy\WordPress\MWC\Shipping\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;

/**
 * Shipment event abstract class.
 *
 * @since 0.1.0
 */
abstract class AbstractShipmentEvent implements EventContract
{
    /** @var OrderFulfillment */
    protected $orderFulfillment;

    /** @var ShipmentContract */
    protected $shipment;

    /**
     * AbstractShipmentEvent constructor.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $orderFulfillment
     * @param ShipmentContract $shipment
     */
    public function __construct(OrderFulfillment $orderFulfillment, ShipmentContract $shipment)
    {
        $this->orderFulfillment = $orderFulfillment;
        $this->shipment = $shipment;
    }

    /**
     * Gets the order fulfillment object that contains the shipment object associated with this event.
     *
     * @since 0.1.0
     *
     * @return OrderFulfillment
     */
    public function getOrderFulfillment(): OrderFulfillment
    {
        return $this->orderFulfillment;
    }

    /**
     * Sets the order fulfillment object that contains the shipment object associated with this event.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $orderFulfillment
     * @return AbstractShipmentEvent
     */
    public function setOrderFulfillment(OrderFulfillment $orderFulfillment): AbstractShipmentEvent
    {
        $this->orderFulfillment = $orderFulfillment;

        return $this;
    }

    /**
     * Gets the shipment object associated with this event.
     *
     * @since 0.1.0
     *
     * @return ShipmentContract
     */
    public function getShipment(): ShipmentContract
    {
        return $this->shipment;
    }

    /**
     * Sets the shipment object associated with this event.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $shipment
     * @return AbstractShipmentEvent
     */
    public function setShipment(ShipmentContract $shipment): AbstractShipmentEvent
    {
        $this->shipment = $shipment;

        return $this;
    }
}
