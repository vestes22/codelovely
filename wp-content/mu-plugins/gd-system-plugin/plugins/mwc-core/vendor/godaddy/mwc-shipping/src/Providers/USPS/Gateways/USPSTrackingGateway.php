<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\USPS\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\USPS\Tracking\USPSTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the USPS shipping provider.
 *
 * @since 0.1.0
 */
class USPSTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = USPSTrackingUrlBuilder::class;
    }
}
