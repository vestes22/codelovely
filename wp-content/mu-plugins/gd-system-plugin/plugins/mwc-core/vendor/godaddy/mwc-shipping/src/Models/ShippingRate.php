<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Represents a shipping rate.
 *
 * @since 0.1.0
 */
class ShippingRate extends AbstractModel
{
    use HasLabelTrait;

    /** @var string */
    private $id;

    /** @var ShippingRateItem[] */
    private $items = [];

    /** @var CurrencyAmount */
    private $total;

    /**
     * Gets the shipping rate ID.
     *
     * @since 0.1.0
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the shipping rate ID.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value) : ShippingRate
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the shipping rate items.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getItems() : array
    {
        return $this->items;
    }

    /**
     * Sets the shipping rate items.
     *
     * @since 0.1.0
     *
     * @param ShippingRateItem[] $value
     * @return self
     */
    public function setItems(array $value) : ShippingRate
    {
        $this->items = $value;

        return $this;
    }

    /**
     * Adds an item to the shipping rate items.
     *
     * @since 0.1.0
     *
     * @param ShippingRateItem $item
     * @return self
     */
    public function addItem(ShippingRateItem $item) : ShippingRate
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Gets the shipping rate total amount.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount
     */
    public function getTotal() : CurrencyAmount
    {
        return $this->total;
    }

    /**
     * Sets the shipping rate total amount.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setTotal(CurrencyAmount $value) : ShippingRate
    {
        $this->total = $value;

        return $this;
    }
}
