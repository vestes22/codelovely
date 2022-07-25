<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\AustraliaPost\Gateways;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;
use GoDaddy\WordPress\MWC\Shipping\Providers\AustraliaPost\Tracking\AustraliaPostTrackingUrlBuilder;
use GoDaddy\WordPress\MWC\Shipping\Traits\CanBuildTrackingUrlTrait;

/**
 * Tracking gateway for the Australia Post shipping provider.
 *
 * @since 0.1.0
 */
class AustraliaPostTrackingGateway implements GatewayContract
{
    use CanBuildTrackingUrlTrait;

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->trackingUrlBuilder = AustraliaPostTrackingUrlBuilder::class;
    }
}
