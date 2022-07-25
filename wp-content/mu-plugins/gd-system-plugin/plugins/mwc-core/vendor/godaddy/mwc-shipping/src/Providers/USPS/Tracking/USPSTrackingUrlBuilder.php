<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\USPS\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the USPS shipping provider.
 */
class USPSTrackingUrlBuilder extends AbstractTrackingUrlBuilder
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
        return 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={tracking_number}';
    }
}
