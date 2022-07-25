<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

/**
 * Allows objects to have shipping tracking capability.
 *
 * @since 0.1.0
 */
trait HasShippingTrackingTrait
{
    /** @var string tracking gateway class name */
    protected $trackingGateway;

    /**
     * @since 0.1.0
     *
     * @return GatewayContract
     */
    public function tracking() : GatewayContract
    {
        return new $this->trackingGateway();
    }
}
