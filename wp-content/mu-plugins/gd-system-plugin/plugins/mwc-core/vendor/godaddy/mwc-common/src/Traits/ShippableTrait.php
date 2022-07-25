<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * A trait for objects that are shippable.
 *
 * @since 3.4.1
 */
trait ShippableTrait
{
    /** @var Address the shipping address */
    protected $shippingAddress;

    /**
     * Gets the shipping address.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getShippingAddress() : Address
    {
        return $this->shippingAddress;
    }

    /**
     * Sets the shipping address.
     *
     * @since 3.4.1
     *
     * @param Address $address
     * @return self
     */
    public function setShippingAddress(Address $address)
    {
        $this->shippingAddress = $address;

        return $this;
    }
}
