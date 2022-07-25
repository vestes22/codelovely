<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost;

use GoDaddy\WordPress\MWC\Common\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost\Gateways\CanadaPostTrackingGateway;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasShippingTrackingTrait;

/**
 * Canada Post shipping provider.
 */
class CanadaPostProvider extends AbstractProvider
{
    use HasShippingTrackingTrait;

    /** @var string the name for the shipping provider */
    protected $name = 'canada-post';

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->label = _x('Canada Post', 'shipping provider name', 'mwc-shipping');

        $this->trackingGateway = CanadaPostTrackingGateway::class;
    }
}
