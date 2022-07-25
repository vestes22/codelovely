<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

/**
 * The tracking number validator contract.
 *
 * @since 0.1.0
 */
interface TrackingNumberValidatorContract
{
    /**
     * Determines whether the tracking number is valid.
     *
     * @since 0.1.0
     *
     * @param string $trackingNumber the tracking number to be validated
     * @return bool
     */
    public function isValidTrackingNumber(string $trackingNumber) : bool;
}
