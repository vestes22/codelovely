<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

/**
 * Allows objects to have shipping labels.
 *
 * @since 0.1.0
 */
trait HasShippingLabelsTrait
{
    /** @var string shipping labels gateway class name */
    protected $labelsGateway;

    /**
     * Gets an instance of the shipping labels gateway.
     *
     * @since 0.1.0
     *
     * @return GatewayContract
     */
    public function labels() : GatewayContract
    {
        return new $this->labelsGateway();
    }
}
