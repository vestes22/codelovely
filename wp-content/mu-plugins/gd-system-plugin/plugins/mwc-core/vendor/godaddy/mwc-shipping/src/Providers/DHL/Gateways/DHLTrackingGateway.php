<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\DHL\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\DHL\Tracking\DHLTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the DHL shipping provider.
 *
 * @since 0.1.0
 */
class DHLTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = DHLTrackingUrlBuilder::class;
    }
}
