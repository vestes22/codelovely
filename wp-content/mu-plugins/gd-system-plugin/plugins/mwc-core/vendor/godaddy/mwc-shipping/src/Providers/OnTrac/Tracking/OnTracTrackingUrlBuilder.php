<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\Tracking;

use GoDaddy\WordPress\MWC\Shipping\Providers\AbstractTrackingUrlBuilder;

/**
 * Tracking URL Builder for the OnTrac shipping provider.
 */
class OnTracTrackingUrlBuilder extends AbstractTrackingUrlBuilder
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
        /* @NOTE some of us had issues connecting to this provider, especially via HTTPS, and had to use a VPN - adding a note here in case someone bumps into similar problems in the future {unfulvio 2021-06-15} */
        return 'https://www.ontrac.com/trackingdetail.asp?tracking={tracking_number}';
    }
}
