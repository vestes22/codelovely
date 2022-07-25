<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\DHL\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the DHL shipping provider.
 */
class DHLTrackingUrlBuilder extends AbstractTrackingUrlBuilder
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
        return 'https://www.dhl.com/us-en/home/tracking/tracking-ecommerce.html?tracking-id={tracking_number}';
    }
}
