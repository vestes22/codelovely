<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use BadMethodCallException;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Shipping\Shipping;

/**
 * Object representation of a shipment.
 *
 * @since 0.1.0
 */
class Shipment extends AbstractModel implements ShipmentContract
{
    use CanBulkAssignPropertiesTrait;

    /** string $id shipment identifier */
    protected $id;

    /** @var Address shipment origin address */
    protected $originAddress;

    /** @var Address shipment destination address */
    protected $destinationAddress;

    /** @var string shipment provider's name */
    protected $providerName;

    /** @var string shipment provider's label */
    protected $providerLabel;

    /** @var ShippingService shipping service for the shipment */
    protected $service;

    /** @var PackageContract[] array of packages indexed by their IDs */
    protected $packages = [];

    /** @var ShippingRate associated shipping rate */
    protected $shippingRate;

    /** @var DateTime timestamp record was created */
    protected $createdAt;

    /** @var DateTime timestamp record was updated */
    protected $updatedAt;

    /**
     * Gets the shipment ID.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Sets the shipment ID.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value): ShipmentContract
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the shipment origin address.
     *
     * @since 0.1.0
     *
     * @return Address
     */
    public function getOriginAddress(): Address
    {
        return $this->originAddress;
    }

    /**
     * Sets the shipment origin address.
     *
     * @since 0.1.0
     *
     * @param Address $value
     * @return self
     */
    public function setOriginAddress(Address $value): ShipmentContract
    {
        $this->originAddress = $value;

        return $this;
    }

    /**
     * Gets the shipment destination address.
     *
     * @since 0.1.0
     *
     * @return Address
     */
    public function getDestinationAddress(): Address
    {
        return $this->destinationAddress;
    }

    /**
     * Sets the shipment destination address.
     *
     * @since 0.1.0
     *
     * @param Address $value
     * @return self
     */
    public function setDestinationAddress(Address $value): ShipmentContract
    {
        $this->destinationAddress = $value;

        return $this;
    }

    /**
     * Gets the shipment provider's name.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }

    /**
     * Sets the shipment provider's name.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setProviderName(string $value): ShipmentContract
    {
        $this->providerName = $value;

        return $this;
    }

    /**
     * Gets the label for the shipping provider associated with this shipment object.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getProviderLabel()
    {
        return $this->providerLabel;
    }

    /**
     * Sets the shipment provider's label.
     *
     * @since 0.1.0
     *
     * @param string $value
     * @return self
     */
    public function setProviderLabel(string $value): ShipmentContract
    {
        $this->providerLabel = $value;

        return $this;
    }

    /**
     * Gets the shipping service.
     *
     * @since 0.1.0
     *
     * @return ShippingService
     */
    public function getService(): ShippingService
    {
        return $this->service;
    }

    /**
     * Sets the shipping service.
     *
     * @since 0.1.0
     *
     * @param ShippingService $value
     * @return self
     */
    public function setService(ShippingService $value): ShipmentContract
    {
        $this->service = $value;

        return $this;
    }

    /**
     * Gets the shipping rate.
     *
     * @since 0.1.0
     *
     * @return ShippingRate
     */
    public function getShippingRate(): ShippingRate
    {
        return $this->shippingRate;
    }

    /**
     * Sets the shipping rate.
     *
     * @since 0.1.0
     *
     * @param ShippingRate $value
     * @return self
     */
    public function setShippingRate(ShippingRate $value): ShipmentContract
    {
        $this->shippingRate = $value;

        return $this;
    }

    /**
     * Gets the packages in the shipment.
     *
     * @since 0.1.0
     *
     * @return PackageContract[] array of packages indexed by their IDs
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * Adds a package in the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return self
     */
    public function addPackage(PackageContract $package): ShipmentContract
    {
        $this->packages[$package->getId()] = $package;

        return $this;
    }

    /**
     * Adds multiple packages to the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract[] $packages
     * @return self
     */
    public function addPackages(array $packages): ShipmentContract
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        return $this;
    }

    /**
     * Sets the packages in the shipment.
     *
     * This method replaces the list of packages currently in the shipment with the given list of packages.
     *
     * @param PackageContract[] $packages
     *
     * @return self
     */
    public function setPackages(array $packages) : ShipmentContract
    {
        $this->packages = [];

        $this->addPackages($packages);

        return $this;
    }

    /**
     * Removes a package from the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return self
     */
    public function removePackage(PackageContract $package): ShipmentContract
    {
        unset($this->packages[$package->getId()]);

        return $this;
    }

    /**
     * Removes multiple packages from the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract[] $packages
     * @return self
     */
    public function removePackages(array $packages): ShipmentContract
    {
        foreach ($packages as $package) {
            $this->removePackage($package);
        }

        return $this;
    }

    /**
     * Determines whether a package is in the shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return bool
     */
    public function hasPackage(PackageContract $package): bool
    {
        return isset($this->packages[$package->getId()]);
    }

    /**
     * Gets an array of packages where canFulfillItems() returns true.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getPackagesThatCanFulfillItems(): array
    {
        return ArrayHelper::where($this->getPackages(), function (PackageContract $package) {
            return $package->canFulfillItems();
        });
    }

    /**
     * Gets the tracking URL for the given package.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return string | null
     */
    public function getPackageTrackingUrl(PackageContract $package)
    {
        if (! $this->hasPackage($package)) {
            return null;
        }

        return $package->getTrackingUrl() ?: $this->getPackageTrackingUrlUsingProvider($package);
    }

    /**
     * Gets the tracking URL for the given package using the instance of the shipping provider associated with this shipment.
     *
     * @since 0.1.0
     *
     * @param PackageContract $package
     * @return string | null
     */
    protected function getPackageTrackingUrlUsingProvider(PackageContract $package)
    {
        $provider = $this->getProvider();

        if (! $provider) {
            return null;
        }

        try {
            $tracking = $provider->tracking();
        } catch (BadMethodCallException $e) {
            return null;
        }

        if (! is_callable([$tracking, 'getTrackingUrl'])) {
            return null;
        }

        return $tracking->getTrackingUrl($package->getTrackingNumber());
    }

    /**
     * Sets created at.
     *
     * @since 0.1.0
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setCreatedAt(DateTime $value) : ShipmentContract
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Gets created at.
     *
     * @since 0.1.0
     *
     * @return DateTime | null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updated at.
     *
     * @since 0.1.0
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setUpdatedAt(DateTime $value) : ShipmentContract
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Gets updated at.
     *
     * @since 0.1.0
     *
     * @return DateTime | null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Gets an instance of the shipping provider associated with this shipment object.
     *
     * @since 0.1.0
     *
     * @return ProviderContract|null
     */
    protected function getProvider()
    {
        /** @var Shipping $shipping */
        $shipping = Shipping::getInstance();

        try {
            return $shipping->provider($this->getProviderName());
        } catch (Exception $e) {
            return null;
        }
    }
}
