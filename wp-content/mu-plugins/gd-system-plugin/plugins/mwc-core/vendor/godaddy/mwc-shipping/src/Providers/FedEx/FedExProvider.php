<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\FedEx;

use GoDaddy\WordPress\MWC\Common\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\Gateways\FedExTrackingGateway;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShippingTrackingTrait;

/**
 * FedEx shipping provider.
 */
class FedExProvider extends AbstractProvider
{
    use HasShippingTrackingTrait;

    /** @var string the name for the shipping provider */
    protected $name = 'fedex';

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->label = _x('FedEx', 'shipping provider name', 'mwc-shipping');

        $this->trackingGateway = FedExTrackingGateway::class;
    }
}
