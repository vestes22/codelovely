<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the FedEx shipping provider.
 */
class FedExTrackingUrlBuilder extends AbstractTrackingUrlBuilder
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
        return 'https://www.fedex.com/apps/fedextrack/?action=track&tracknumbers={tracking_number}';
    }
}
