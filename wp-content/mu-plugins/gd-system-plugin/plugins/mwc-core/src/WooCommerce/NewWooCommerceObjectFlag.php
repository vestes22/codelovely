<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Traits\HasWooCommerceMetaTrait;

/**
 * Represents a flag for an associated object.
 *
 * @since 2.10.0
 */
class NewWooCommerceObjectFlag
{
    use HasWooCommerceMetaTrait;

    /**
     * NewWooCommerceObjectFlag constructor.
     *
     * @param \WC_Data|int data object instance or ID of the object that owns the meta data
     */
    public function __construct($objectOrObjectId)
    {
        $this->objectOrObjectId = $objectOrObjectId;

        $this->metaKey = '_gd_mwc_is_new_object';

        $this->loadWooCommerceMeta('no');
    }

    /**
     * Determines whether the flag is enabled for the associated object.
     *
     * @return bool
     */
    public function isOn() : bool
    {
        return 'yes' === $this->metaValue;
    }

    /**
     * Determines whether the flag is disabled for the associated object.
     *
     * @return bool
     */
    public function isOff() : bool
    {
        return ! $this->isOn();
    }

    /**
     * Enables the flag for the associated object.
     *
     * @return self
     */
    public function turnOn() : self
    {
        return $this
            ->setWooCommerceMeta('yes')
            ->saveWooCommerceMeta();
    }

    /**
     * Deletes the flag for the associated object.
     *
     * @return self
     */
    public function turnOff() : self
    {
        return $this
            ->setWooCommerceMeta('no')
            ->deleteWooCommerceMeta();
    }
}
