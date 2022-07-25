<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Dimensions;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Weight;
use GoDaddy\WordPress\MWC\Common\Traits\HasDimensionsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasWeightTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Package contract.
 *
 * Implementations using this contract could use the following traits:
 * @see HasDimensionsTrait
 * @see HasWeightTrait
 *
 * @since 0.1.0
 */
interface PackageContract
{
    /**
     * Gets the package ID.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Sets the package ID.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value) : PackageContract;

    /**
     * Gets the package items.
     *
     * @since 0.1.0
     *
     * @return LineItem[] associative array of items indexed by their IDs
     */
    public function getItems() : array;

    /**
     * Adds an item to the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @param float|int $quantity
     * @return self
     */
    public function addItem(LineItem $item, float $quantity) : PackageContract;

    /**
     * Removes an item from the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @param float|int $quantityToRemove
     *
     * @return self
     */
    public function removeItem(LineItem $item, float $quantityToRemove) : PackageContract;

    /**
     * Gets the quantity of an item in the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item item to get the quantity of in the package
     * @return float|int
     */
    public function getItemQuantity(LineItem $item) : float;

    /**
     * Determines whether a given item is present in the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @return bool
     */
    public function hasItem(LineItem $item) : bool;

    /**
     * Gets the package status.
     *
     * @since 0.1.0
     *
     * @return PackageStatusContract
     */
    public function getStatus() : PackageStatusContract;

    /**
     * Sets the package status.
     *
     * @since 0.1.0
     *
     * @param PackageStatusContract $status
     * @return self
     */
    public function setStatus(PackageStatusContract $status) : PackageContract;

    /**
     * Determines whether the items in the package can be fulfilled.
     *
     * @see PackageStatusContract
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function canFulfillItems() : bool;

    /**
     * Gets the package dimensions.
     *
     * @see HasDimensionsTrait
     *
     * @since 0.1.0
     *
     * @return Dimensions
     */
    public function getDimensions() : Dimensions;

    /**
     * Sets the package dimensions.
     *
     * @see HasDimensionsTrait
     *
     * @since 0.1.0
     *
     * @param Dimensions $dimensions
     * @return self
     */
    public function setDimensions(Dimensions $dimensions);

    /**
     * Gets the package weight.
     *
     * @see HasWeightTrait
     *
     * @since 0.1.0
     *
     * @return Weight
     */
    public function getWeight() : Weight;

    /**
     * Sets the package weight.
     *
     * @see HasWeightTrait
     *
     * @since 0.1.0
     *
     * @param Weight $weight
     * @return self
     */
    public function setWeight(Weight $weight);

    /**
     * Gets the shipping rate associated with the package.
     *
     * @since 0.1.0
     *
     * @return ShippingRate|null
     */
    public function getShippingRate();

    /**
     * Sets a shipping rate to associate with the package.
     *
     * @since 0.1.0
     *
     * @param ShippingRate $shippingRate
     * @return self
     */
    public function setShippingRate(ShippingRate $shippingRate) : PackageContract;

    /**
     * Gets the shipping label associated with the package.
     *
     * @since 0.1.0
     *
     * @return ShippingLabel|null
     */
    public function getShippingLabel();

    /**
     * Sets a shipping label to associate with the package.
     *
     * @since 0.1.0
     *
     * @param ShippingLabel $shippingLabel
     * @return self
     */
    public function setShippingLabel(ShippingLabel $shippingLabel) : PackageContract;

    /**
     * Gets the package tracking number.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getTrackingNumber();

    /**
     * Sets the package tracking number.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setTrackingNumber(string $value) : PackageContract;

    /**
     * Gets the package tracking URL.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getTrackingUrl();

    /**
     * Sets the package tracking URL.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setTrackingUrl(string $value) : PackageContract;
}
