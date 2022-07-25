<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\AustraliaPost\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the Australia Post shipping provider.
 */
class AustraliaPostTrackingUrlBuilder extends AbstractTrackingUrlBuilder
{
    /**
     * Gets the template used to build the tracking URL for a tracking number.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getTrackingUrlTemplate() : string
    {
        return 'https://auspost.com.au/mypost/track/#/details/{tracking_number}';
    }
}
