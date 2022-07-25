<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Provides methods to an object to estimate shipping rates.
 *
 * @see ShippingRate
 *
 * @since 0.1.0
 */
trait CanEstimateShippingRatesTrait
{
    use AdaptsRequestsTrait;

    /** @var string class name of the adapter */
    protected $estimateRatesShipmentAdapter;

    /**
     * Estimates shipping rates for shipments.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract[] $shipments
     * @return array
     * @throws BaseException
     */
    public function estimate(array $shipments) : array
    {
        /** @var DataSourceAdapterContract $adapter */
        $adapter = new $this->estimateRatesShipmentAdapter($shipments);

        return $this->doAdaptedRequest($adapter);
    }
}
