<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\UPS\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\UPS\Tracking\UPSTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the UPS shipping provider.
 *
 * @since 0.1.0
 */
class UPSTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = UPSTrackingUrlBuilder::class;
    }
}
