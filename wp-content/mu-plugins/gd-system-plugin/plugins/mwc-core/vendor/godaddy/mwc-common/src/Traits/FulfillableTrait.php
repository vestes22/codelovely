<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;

/**
 * A trait for objects that handle fulfillment.
 *
 * @since 3.4.1
 */
trait FulfillableTrait
{
    /** @var FulfillmentStatusContract fulfillment status */
    protected $fulfillmentStatus;

    /** @var bool whether the represented entity needs shipping or not */
    protected $needsShipping;

    /**
     * Gets the fulfillment status.
     *
     * @since 3.4.1
     *
     * @return FulfillmentStatusContract|null
     */
    public function getFulfillmentStatus()
    {
        return $this->fulfillmentStatus;
    }

    /**
     * Sets the fulfillment status.
     *
     * @since 3.4.1
     *
     * @param FulfillmentStatusContract $fulfillmentStatus
     * @return self
     */
    public function setFulfillmentStatus(FulfillmentStatusContract $fulfillmentStatus)
    {
        $this->fulfillmentStatus = $fulfillmentStatus;

        return $this;
    }

    /**
     * Determines whether the represented entity needs shipping or not.
     *
     * @since 3.4.1
     *
     * @return bool
     */
    public function getNeedsShipping() : bool
    {
        return $this->needsShipping ?? false;
    }

    /**
     * Sets the "needs shipping" property.
     *
     * @since 3.4.1
     *
     * @param bool $needsShipping
     * @return self
     */
    public function setNeedsShipping(bool $needsShipping)
    {
        $this->needsShipping = $needsShipping;

        return $this;
    }
}
