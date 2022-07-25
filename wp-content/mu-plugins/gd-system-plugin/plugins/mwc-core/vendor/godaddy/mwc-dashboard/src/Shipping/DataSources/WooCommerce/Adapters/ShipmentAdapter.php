<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;

class ShipmentAdapter implements DataSourceAdapterContract
{
    /** @var array source data */
    protected $source;

    /**
     * ShipmentAdapter constructor.
     *
     * @since x.y.z
     *
     * @param array $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts from Data Source format.
     *
     * @since x.y.z
     *
     * @return ShipmentContract
     */
    public function convertFromSource() : ShipmentContract
    {
        $shipment = (new Shipment())
            ->setId(ArrayHelper::get($this->source, 'id', ''))
            ->setProviderName((string) ArrayHelper::get($this->source, 'providerName'))
            ->setProviderLabel((string) ArrayHelper::get($this->source, 'providerLabel'));

        if ($createdAt = $this->timestampToDateTime(ArrayHelper::get($this->source, 'createdAt', ''))) {
            $shipment->setCreatedAt($createdAt);
        }

        if ($updateAt = $this->timestampToDateTime(ArrayHelper::get($this->source, 'updatedAt', ''))) {
            $shipment->setUpdatedAt($updateAt);
        }

        $convertedPackages = array_map(function ($packageSource) {
            return (new PackageAdapter($packageSource))->convertFromSource();
        }, ArrayHelper::get($this->source, 'packages', []));

        $shipment->addPackages($convertedPackages);

        return $shipment;
    }

    /**
     * Converts to Data Source format.
     *
     * @param ShipmentContract $shipment
     * @return array
     */
    public function convertToSource(ShipmentContract $shipment = null) : array
    {
        if (! $shipment) {
            return [];
        }

        $convertedShipment = [
            'id' => $shipment->getId(),
            'providerName' => $shipment->getProviderName(),
            'providerLabel' => $shipment->getProviderLabel() ?: '',
            'createdAt' => $shipment->getCreatedAt() ? $shipment->getCreatedAt()->format('c') : '',
            'updatedAt' => $shipment->getUpdatedAt() ? $shipment->getUpdatedAt()->format('c') : '',
        ];

        foreach (array_values($shipment->getPackages()) as $package) {
            $convertedShipment['packages'][] = $this->convertPackageToSource($shipment, $package);
        }

        return $convertedShipment;
    }

    /**
     * Converts package data to Data Source format.
     *
     * @since x.y.z
     *
     * @param ShipmentContract $shipment
     * @param PackageContract $package
     * @return array
     */
    protected function convertPackageToSource(ShipmentContract $shipment, PackageContract $package) : array
    {
        $packageSource = (new PackageAdapter([]))->convertToSource($package);
        $packageSource['generatedTrackingUrl'] = $shipment->getPackageTrackingUrl($package);

        return $packageSource;
    }

    /**
     * Converts string timestamp into DateTime object.
     *
     * @param string $timestamp
     * @return DateTime|null
     */
    protected function timestampToDateTime(string $timestamp)
    {
        try {
            return new DateTime($timestamp);
        } catch (Exception $e) {
            return null;
        }
    }
}
