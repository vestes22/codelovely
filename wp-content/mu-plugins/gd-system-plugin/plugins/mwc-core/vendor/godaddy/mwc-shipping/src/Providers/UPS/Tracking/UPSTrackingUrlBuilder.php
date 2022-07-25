<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\UPS\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the UPS shipping provider.
 */
class UPSTrackingUrlBuilder extends AbstractTrackingUrlBuilder
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
        return 'https://www.ups.com/track?loc=en_US&tracknum={tracking_number}';
    }
}
