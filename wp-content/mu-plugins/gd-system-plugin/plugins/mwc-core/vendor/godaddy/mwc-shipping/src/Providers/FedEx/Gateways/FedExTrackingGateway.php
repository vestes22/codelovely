<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\Tracking\FedExTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the FedEx shipping provider.
 *
 * @since 0.1.0
 */
class FedExTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = FedExTrackingUrlBuilder::class;
    }
}
