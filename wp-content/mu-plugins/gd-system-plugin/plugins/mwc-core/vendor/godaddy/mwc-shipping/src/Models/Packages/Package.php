<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Packages;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\HasDimensionsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasWeightTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Object representation of a package.
 *
 * @since 0.1.0
 */
class Package extends AbstractModel implements PackageContract
{
    use HasDimensionsTrait;
    use HasWeightTrait;

    /** @var string package identifier */
    protected $id;

    /** @var LineItem[] items in package, indexed by their IDs */
    protected $items = [];

    /** @var array associative array of item IDs (int) and their quantities (float) */
    protected $itemQuantities = [];

    /** @var ShippingRate shipping rate of the package */
    protected $shippingRate;

    /** @var ShippingLabel associated label */
    protected $shippingLabel;

    /** @var PackageStatusContract package status */
    protected $status;

    /** @var string|null tracking number, if available */
    protected $trackingNumber;

    /** @var string|null tracking URL, if available */
    protected $trackingUrl;

    /**
     * Gets the package ID.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the package ID.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value) : PackageContract
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the items in the package.
     *
     * @since 0.1.0
     *
     * @return LineItem[] associative array of items indexed by their IDs
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Adds an item in the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @param float $quantity
     * @return self
     */
    public function addItem(LineItem $item, float $quantity) : PackageContract
    {
        if ($quantity <= 0) {
            return $this;
        }

        if ($this->hasItem($item)) {
            $quantity += (float) $this->itemQuantities[$item->getId()];
        }

        $this->items[$item->getId()] = $item;
        $this->itemQuantities[$item->getId()] = $quantity;

        return $this;
    }

    /**
     * Removes an item from the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @param float $quantityToRemove
     * @return self
     */
    public function removeItem(LineItem $item, float $quantityToRemove) : PackageContract
    {
        if ($quantityToRemove <= 0) {
            return $this;
        }

        $quantityInPackage = $this->hasItem($item) ? (float) $this->itemQuantities[$item->getId()] : 0;
        $newQuantity = $quantityInPackage - $quantityToRemove;

        if ($newQuantity <= 0) {
            unset($this->items[$item->getId()], $this->itemQuantities[$item->getId()]);

            return $this;
        }

        $this->itemQuantities[$item->getId()] = $newQuantity;

        return $this;
    }

    /**
     * Gets the quantity of an item in the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item item to get the quantity of in the package
     * @return float|int
     */
    public function getItemQuantity(LineItem $item) : float
    {
        return $this->hasItem($item) ? max(0, $this->itemQuantities[$item->getId()] ?? 0) : 0;
    }

    /**
     * Determines if a given item is in the package.
     *
     * @since 0.1.0
     *
     * @param LineItem $item
     * @return bool
     */
    public function hasItem(LineItem $item): bool
    {
        return isset($this->items[$item->getId()], $this->itemQuantities[$item->getId()]);
    }

    /**
     * Gets the package status.
     *
     * @since 0.1.0
     *
     * @return PackageStatusContract
     */
    public function getStatus(): PackageStatusContract
    {
        return $this->status;
    }

    /**
     * Sets the package status.
     *
     * @since 0.1.0
     *
     * @param PackageStatusContract $status
     * @return self
     */
    public function setStatus(PackageStatusContract $status): PackageContract
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Determines whether the items in the package can be fulfilled.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function canFulfillItems(): bool
    {
        return $this->getStatus()->canFulfillItems();
    }

    /**
     * Gets the package shipping rate.
     *
     * @since 0.1.0
     *
     * @return ShippingRate|null
     */
    public function getShippingRate()
    {
        return $this->shippingRate;
    }

    /**
     * Sets the package shipping rate.
     *
     * @since 0.1.0
     *
     * @param ShippingRate $shippingRate
     * @return self
     */
    public function setShippingRate(ShippingRate $shippingRate): PackageContract
    {
        $this->shippingRate = $shippingRate;

        return $this;
    }

    /**
     * Gets the package shipping label.
     *
     * @since 0.1.0
     *
     * @return ShippingLabel|null
     */
    public function getShippingLabel()
    {
        return $this->shippingLabel;
    }

    /**
     * Sets the package shipping label.
     *
     * @since 0.1.0
     *
     * @param ShippingLabel $shippingLabel
     * @return self
     */
    public function setShippingLabel(ShippingLabel $shippingLabel): PackageContract
    {
        $this->shippingLabel = $shippingLabel;

        return $this;
    }

    /**
     * Gets the tracking number, if available.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * Sets a tracking number for the package.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setTrackingNumber(string $value): PackageContract
    {
        $this->trackingNumber = $value;

        return $this;
    }

    /**
     * Gets the tracking URL, if available.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }

    /**
     * Sets a tracking URL for the package.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setTrackingUrl(string $value): PackageContract
    {
        $this->trackingUrl = $value;

        return $this;
    }
}
