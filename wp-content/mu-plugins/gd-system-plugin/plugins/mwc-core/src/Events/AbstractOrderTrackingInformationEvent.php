<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Abstract order tracking information event class.
 *
 * @since 2.10.0
 */
abstract class AbstractOrderTrackingInformationEvent extends AbstractOrderEvent
{
    /** @var array the tracking items */
    protected $trackingItems = [];

    /**
     * AbstractOrderTrackingInformationEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->resource = 'order_tracking_information';
    }

    /**
     * Sets the tracking items for this event.
     *
     * @param array $trackingItems
     * @return self
     */
    public function setTrackingItems(array $trackingItems = []) : self
    {
        $this->trackingItems = $trackingItems;

        return $this;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     */
    public function getData() : array
    {
        return $this->order ? [
            'order' => $this->getOrderData($this->order),
            'trackingItems' => $this->trackingItems,
        ] : [];
    }
}
