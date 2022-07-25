<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\TrackingNumberValidatorContract;

/**
 * Allow classes to validate a tracking number.
 *
 * @since 0.1.0
 */
trait CanValidateTrackingNumberTrait
{
    /** @var string the tracking number validator class name */
    protected $trackingNumberValidator;

    /**
     * Determines whether a tracking number is valid or not.
     *
     * @since 0.1.0
     *
     * @param string $trackingNumber
     * @return bool
     */
    public function isValidTrackingNumber(string $trackingNumber) : bool
    {
        $instance = $this->getTrackingNumberValidator();

        return $instance && $instance->isValidTrackingNumber($trackingNumber);
    }

    /**
     * Gets an instance of the tracking number validator.
     *
     * @since 0.1.0
     *
     * @return TrackingNumberValidatorContract|null
     */
    protected function getTrackingNumberValidator()
    {
        if (! class_exists($this->trackingNumberValidator)) {
            return null;
        }

        $instance = new $this->trackingNumberValidator();

        return $instance instanceof TrackingNumberValidatorContract ? $instance : null;
    }
}
