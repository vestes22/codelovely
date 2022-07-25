<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\DHL;

use GoDaddy\WordPress\MWC\Common\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Shipping\Providers\DHL\Gateways\DHLTrackingGateway;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShippingTrackingTrait;

/**
 * DHL shipping provider.
 */
class DHLProvider extends AbstractProvider
{
    use HasShippingTrackingTrait;

    /** @var string the name for the shipping provider */
    protected $name = 'dhl';

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->label = _x('DHL', 'shipping provider name', 'mwc-shipping');

        $this->trackingGateway = DHLTrackingGateway::class;
    }
}
