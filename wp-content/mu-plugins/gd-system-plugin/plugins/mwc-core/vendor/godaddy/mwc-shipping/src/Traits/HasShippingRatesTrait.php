<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayContract;

/**
 * Allows objects to have shipping rates.
 *
 * @since 0.1.0
 */
trait HasShippingRatesTrait
{
    /** @var string shipping rates gateway class name */
    protected $ratesGateway;

    /**
     * GVets an instance of the shipping rates gateway.
     *
     * @since 0.1.0
     *
     * @return GatewayContract
     */
    public function rates() : GatewayContract
    {
        return new $this->ratesGateway();
    }
}
