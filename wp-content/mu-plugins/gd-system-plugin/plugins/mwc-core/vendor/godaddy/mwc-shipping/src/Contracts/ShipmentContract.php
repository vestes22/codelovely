<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingService;

/**
 * Shipment contract.
 *
 * @since 0.1.0
 */
interface ShipmentContract
{
    /**
     * Gets the shipment ID.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Sets the shipment ID.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return ShipmentContract
     */
    public function setId(string $value) : ShipmentContract;

    /**
     * Gets the shipment origin address.
     *
     * @since 0.1.0
     *
     * @return Address
     */
    public function getOriginAddress() : Address;

    /**
     * Sets the shipment origin address.
     *
     * @since 0.1.0
     *
     * @param Address $value
     * @return self
     */
    public function setOriginAddress(Address $value) : ShipmentContract;

    /**
     * Gets the shipment destination address.
     *
     * @since 0.1.0
     *
     * @return Address
     */
    public function getDestinationAddress() : Address;

    /**
     * Sets the shipment destination address.
     *
     * @since 0.1.0
     *
     * @param Address $value
     * @return self
     */
    public function setDestinationAddress(Address $value) : ShipmentContract;

    /**
     * Gets the shipment provider's name.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getProviderName() : string;

    /**
     * Sets the shipment provider's name.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setProviderName(string $value) : ShipmentContract;

    /**
     * Gets the label for the shipping provider associated with this shipment object.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getProviderLabel();

    /**
     * Sets the shipment provider's label.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setProviderLabel(string $value) : ShipmentContract;

    /**
     * Gets the shipping service.
     *
     * @since 0.1.0
     *
     * @return ShippingService
     */
    public function getService() : ShippingService;

    /**
     * Sets the shipping service.
     *
     * @since 0.1.0
     *
     * @param ShippingService $value
     * @return self
     */
    public function setService(ShippingService $value) : ShipmentContract;

    /**
     * Gets the shipping rate.
     *
     * @since 0.1.0
     *
     * @return ShippingRate
     */
    public function getShippingRate() : ShippingRate;

    /**
     * Sets the shipping rate.
     *
     * @since 0.1.0
     *
     * @param ShippingRate $value
     * @return self
     */
    public function setShippingRate(ShippingRate $value) : ShipmentContract;

    /**
     * Gets the packages in the shipment.
     *
     * @since 0.1.0
     *
     * @return PackageContract[] array of packages
     */
    public function getPackages() : array;

    /**
     * Sets the packages in the shipment.
     *
     * @param PackageContract[] $packages
     *
     * @return self
     */
    public function setPackages(array $packages) : ShipmentContract;

    /**
     * Adds a package to the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return self
     */
    public function addPackage(PackageContract $package) : ShipmentContract;

    /**
     * Adds multiple packages to the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract[] $packages
     * @return self
     */
    public function addPackages(array $packages) : ShipmentContract;

    /**
     * Removes a package from the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return self
     */
    public function removePackage(PackageContract $package) : ShipmentContract;

    /**
     * Removes multiple packages from the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract[] $packages
     * @return ShipmentContract
     */
    public function removePackages(array $packages) : ShipmentContract;

    /**
     * Determines whether a package is in the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return bool
     */
    public function hasPackage(PackageContract $package) : bool;

    /**
     * Gets an array of packages where canFulfillItems() returns true.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getPackagesThatCanFulfillItems() : array;

    /**
     * Gets the tracking URL for the given package.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return string|null
     */
    public function getPackageTrackingUrl(PackageContract $package);

    /**
     * Sets the value of the properties included in the given array.
     *
     * NOTE: this method doesn't define a return type because it causes a conflict with the implementation from the CanBulkAssignPropertiesTrait trait {wvega 2021-06-22}
     *
     * @since 0.1.0
     *
     * @param array $data
     * @return ShipmentContract
     */
    public function setProperties(array $data);

    /**
     * Converts all class properties to an array.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Sets the created at date for the shipment.
     *
     * @since 0.1.0
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setCreatedAt(DateTime $value) : ShipmentContract;

    /**
     * Gets the created at date for the shipment.
     *
     * @since 0.1.0
     *
     * @return DateTime|null
     */
    public function getCreatedAt();

    /**
     * Sets the updated at date for the shipment.
     *
     * @since 0.1.0
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setUpdatedAt(DateTime $value) : ShipmentContract;

    /**
     * Gets the updated at date for the shipment.
     *
     * @since 0.1.0
     *
     * @return DateTime|null
     */
    public function getUpdatedAt();
}
