<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\ShipmentTracking\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;

class ShipmentAdapter implements DataSourceAdapterContract
{
    /** @var array an array of data for a single shipment tracking item as stored by the Shipment Tracking plugin */
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
     * Converts the data from the Shipment Tracking plugin to a shipment object.
     *
     * @since x.y.z
     *
     * @return ShipmentContract
     * @throws Exception
     */
    public function convertFromSource() : ShipmentContract
    {
        $givenProviderLabel = ArrayHelper::get($this->source, 'tracking_provider') ?: ArrayHelper::get($this->source, 'custom_tracking_provider', '');
        $providerName = $this->getProviderName($givenProviderLabel);

        $createdAt = (new DateTime())->setTimestamp(ArrayHelper::get($this->source, 'date_shipped'));

        return (new Shipment())
            ->setId(ArrayHelper::get($this->source, 'tracking_id'))
            ->setProviderName($providerName ?: 'other')
            ->setProviderLabel(empty($providerName) ? $givenProviderLabel : '') // only set the label for custom/unknown providers
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt)
            ->addPackage($this->getPackageFromData($this->source));
    }

    /**
     * Get provider name for given tracking provider.
     *
     * @since x.y.z
     *
     * @param string $trackingProvider
     * @return string|null
     */
    protected function getProviderName(string $trackingProvider)
    {
        $providerNames = [
            'Australia Post' => 'australia-post',
            'Canada Post' => 'canada-post',
            'Fedex' => 'fedex',
            'OnTrac' => 'ontrac',
            'UPS' => 'ups',
            'USPS' => 'usps',
            'DHL US' => 'dhl',
        ];

        return ArrayHelper::get($providerNames, $trackingProvider);
    }

    /**
     * Creates a package object using the given data.
     *
     * @since x.y.z
     *
     * @param array $data
     * @return PackageContract
     */
    protected function getPackageFromData(array $data) : PackageContract
    {
        return (new Package())
            ->setId(StringHelper::generateUuid4())
            ->setTrackingNumber(ArrayHelper::get($data, 'tracking_number', ''))
            ->setTrackingUrl(ArrayHelper::get($data, 'custom_tracking_link', ''))
            ->setStatus(new LabelCreatedPackageStatus());
    }

    /**
     * Converts to Data Source format.
     *
     * @since x.y.z
     */
    public function convertToSource(ShipmentContract $shipment = null)
    {
        $providerName = $shipment->getProviderName();

        if ('other' === strtolower($providerName)) {
            $trackingProvider = $shipment->getProviderLabel() ? $this->getTrackingProvider($shipment->getProviderLabel()) : null;
        } else {
            $trackingProvider = $this->getTrackingProvider($providerName);
        }

        $packages = $shipment->getPackages();

        if (! empty($packages)) {
            $firstPackage = reset($packages);
        }

        return [
            'tracking_provider' => $trackingProvider ?? '',
            'custom_tracking_provider' => empty($trackingProvider) ? $shipment->getProviderLabel() : '',
            'custom_tracking_link' => (empty($trackingProvider) && ! empty($firstPackage)) ? (string) $firstPackage->getTrackingUrl() : '',
            'tracking_number' => ! empty($firstPackage) ? $firstPackage->getTrackingNumber() : '',
            'date_shipped' => $shipment->getCreatedAt()->getTimestamp(),
            'tracking_id' => $shipment->getId(),
        ];
    }

    /**
     * Gets the Shipment Tracking tracking provider for the given provider name.
     *
     * @since x.y.z
     *
     * @param string $providerName
     * @return string|null
     */
    protected function getTrackingProvider(string $providerName)
    {
        $providers = [
            // supported shipping providers

            // Australia
            'australia-post' => 'Australia Post',
            // Canada
            'canada-post' => 'Canada Post',
            // United States
            'fedex' => 'Fedex',
            'ontrac' => 'OnTrac',
            'ups' => 'UPS',
            'usps' => 'USPS',
            'dhl' => 'DHL US',

            // currently not supported providers

            // Australia
            'Fastway Couriers' => 'Fastway Couriers',
            // Austria
            'post.at' => 'post.at',
            'dhl.at'  => 'dhl.at',
            'DPD.at'  => 'DPD.at',
            // Brazil
            'Correios' => 'Correios',
            // Belgium
            'bpost' => 'bpost',
            // Canada
            'Purolator' => 'Purolator',
            // Czech Republic
            'PPL.cz' => 'PPL.cz',
            'Česká pošta' => 'Česká pošta',
            'DHL.cz' => 'DHL.cz',
            'DPD.cz' => 'DPD.cz',
            // Finland
            'Itella' => 'Itella',
            // France
            'Colissimo' => 'Colissimo',
            // Germany
            'DHL Intraship (DE)' => 'DHL Intraship (DE)',
            'Hermes' => 'Hermes',
            'Deutsche Post DHL' => 'Deutsche Post DHL',
            'UPS Germany' => 'UPS Germany',
            'DPD.de' => 'DPD.de',
            // Ireland
            'DPD.ie' => 'DPD.ie',
            'An Post' => 'An Post',
            // Italy
            'BRT (Bartolini)' => 'BRT (Bartolini)',
            'DHL Express' => 'DHL Express',
            // India
            'DTDC' => 'DTDC',
            // Netherlands
            'PostNL' => 'PostNL',
            'DPD.NL' => 'DPD.NL',
            'UPS Netherlands' => 'UPS Netherlands',
            // New Zealand
            'Courier Post' => 'Courier Post',
            'NZ Post' => 'NZ Post',
            'Aramex' => 'Aramex',
            'PBT Couriers' => 'PBT Couriers',
            // Poland
            'InPost' => 'InPost',
            'DPD.PL' => 'DPD.PL',
            'Poczta Polska' => 'Poczta Polska',
            // Romania
            'Fan Courier' => 'Fan Courier',
            'DPD Romania' => 'DPD Romania',
            'Urgent Cargus' => 'Urgent Cargus',
            // South African
            'SAPO' => 'SAPO',
            'Fastway' => 'Fastway',
            // Sweden
            'PostNord Sverige AB' => 'PostNord Sverige AB',
            'DHL.se' => 'DHL.se',
            'Bring.se' => 'Bring.se',
            'UPS.se' => 'UPS.se',
            'DB Schenker' => 'DB Schenker',
            // United Kingdom
            'DHL' => 'DHL',
            'DPD.co.uk' => 'DPD.co.uk',
            'InterLink' => 'InterLink',
            'ParcelForce' => 'ParcelForce',
            'Royal Mail' => 'Royal Mail',
            'TNT Express (consignment)' => 'TNT Express (consignment)',
            'TNT Express (reference)' => 'TNT Express (reference)',
            'DHL Parcel UK' => 'DHL Parcel UK',
            // United States
            'FedEx Sameday' => 'FedEx Sameday',
        ];

        // can't use ArrayHelper::get() because the dots in some of the keys confuse the helper
        return $providers[$providerName] ?? null;
    }
}
