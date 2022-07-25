<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost\Tracking\CanadaPostTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the Canada Post shipping provider.
 *
 * @since 0.1.0
 */
class CanadaPostTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = CanadaPostTrackingUrlBuilder::class;
    }
}
