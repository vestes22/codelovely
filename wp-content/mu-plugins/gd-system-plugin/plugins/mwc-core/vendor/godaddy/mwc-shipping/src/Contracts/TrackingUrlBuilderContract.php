<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

/**
 * The tracking URL builder contract.
 *
 * @since 0.1.0
 */
interface TrackingUrlBuilderContract
{
    /**
     * Gets a tracking URL from a tracking number.
     *
     * @since 0.1.0
     *
     * @param string $trackingNumber the tracking number
     * @return string
     */
    public function getTrackingUrl(string $trackingNumber) : string;

    /**
     * Gets template used to build the tracking URL for a tracking number.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getTrackingUrlTemplate() : string;
}
