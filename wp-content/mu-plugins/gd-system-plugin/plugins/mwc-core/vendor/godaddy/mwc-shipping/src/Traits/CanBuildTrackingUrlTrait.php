<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\TrackingUrlBuilderContract;

/**
 * Allow classes to build a tracking URL.
 *
 * @since 0.1.0
 */
trait CanBuildTrackingUrlTrait
{
    /** @var string the tracking URL builder class name */
    protected $trackingUrlBuilder;

    /**
     * Gets the tracking URL.
     *
     * @since 0.1.0
     *
     * @param string $trackingNumber
     * @return string|null
     */
    public function getTrackingUrl(string $trackingNumber)
    {
        $instance = $this->getTrackingUrlBuilder();

        return $instance ? $instance->getTrackingUrl($trackingNumber) : null;
    }

    /**
     * Gets template used to build the tracking URL for a tracking number.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getTrackingUrlTemplate()
    {
        if (! $instance = $this->getTrackingUrlBuilder()) {
            return null;
        }

        return $instance->getTrackingUrlTemplate();
    }

    /**
     * Gets an instance of the tracking URL builder.
     *
     * @since 0.1.0
     *
     * @return TrackingUrlBuilderContract|null
     */
    protected function getTrackingUrlBuilder()
    {
        if (! class_exists($this->trackingUrlBuilder)) {
            return null;
        }

        $instance = new $this->trackingUrlBuilder();

        return $instance instanceof TrackingUrlBuilderContract ? $instance : null;
    }
}
