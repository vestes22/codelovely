<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Shipping zone method added event class.
 *
 * @since 2.10.0
 */
class ShippingZoneMethodAddedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var int The ID of the shipping zone that the shipping method was added to */
    protected $shippingZoneId;

    /** @var string The type of the shipping method */
    protected $shippingMethodType;

    /**
     * ShippingZoneMethodAddedEvent constructor.
     *
     * @param int $shippingZoneId
     * @param string $shippingMethodType
     */
    public function __construct(int $shippingZoneId, string $shippingMethodType)
    {
        $this->resource = 'shipping_zone_method';
        $this->action = 'create';
        $this->shippingZoneId = $shippingZoneId;
        $this->shippingMethodType = $shippingMethodType;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'shippingZone' => [
                'id' => $this->shippingZoneId,
            ],
            'shippingMethod' => [
                'type' => $this->shippingMethodType,
            ],
        ];
    }
}
