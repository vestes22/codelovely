<?php

namespace GoDaddy\WordPress\MWC\Shipping\Providers;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Shipping\Contracts\TrackingUrlBuilderContract;

/**
 * Base class for Tracking URL Builder classes.
 */
abstract class AbstractTrackingUrlBuilder implements TrackingUrlBuilderContract
{
    /**
     * Gets a tracking URL from a tracking number.
     *
     * @since 0.1.0
     *
     * @param string $trackingNumber the tracking number
     *
     * @return string
     */
    public function getTrackingUrl(string $trackingNumber) : string
    {
        return StringHelper::replaceFirst($this->getTrackingUrlTemplate(), '{tracking_number}', rawurlencode(trim($trackingNumber)));
    }
}
