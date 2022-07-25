<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\Tracking\OnTracTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the OnTrac shipping provider.
 *
 * @since 0.1.0
 */
class OnTracTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = OnTracTrackingUrlBuilder::class;
    }
}
