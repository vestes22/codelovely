<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Represents an item of a shipping rate object.
 *
 * @since 0.1.0
 */
class ShippingRateItem extends AbstractModel
{
    /** @var CurrencyAmount the item price */
    private $price;

    /** @var bool whether the item is included or not */
    private $isIncluded;

    /**
     * Gets the item price.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount
     */
    public function getPrice() : CurrencyAmount
    {
        return $this->price;
    }

    /**
     * Sets the item price.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $price
     * @return self
     */
    public function setPrice(CurrencyAmount $price) : ShippingRateItem
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Determines whether the item is included or not.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function getIsIncluded() : bool
    {
        return $this->isIncluded;
    }

    /**
     * Sets the flag to determine whether the item is included or not.
     *
     * @since 0.1.0
     *
     * @param bool $isIncluded
     * @return self
     */
    public function setIsIncluded(bool $isIncluded) : ShippingRateItem
    {
        $this->isIncluded = $isIncluded;

        return $this;
    }
}
