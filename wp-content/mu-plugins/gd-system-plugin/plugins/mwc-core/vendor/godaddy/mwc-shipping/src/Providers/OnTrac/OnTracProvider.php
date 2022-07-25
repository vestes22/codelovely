<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac;

use GoDaddy\WordPress\MWC\Common\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\Gateways\OnTracTrackingGateway;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShippingTrackingTrait;

/**
 * OnTrac shipping provider.
 */
class OnTracProvider extends AbstractProvider
{
    use HasShippingTrackingTrait;

    /** @var string the name for the shipping provider */
    protected $name = 'ontrac';

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->label = _x('OnTrac', 'shipping provider name', 'mwc-shipping');

        $this->trackingGateway = OnTracTrackingGateway::class;
    }
}
