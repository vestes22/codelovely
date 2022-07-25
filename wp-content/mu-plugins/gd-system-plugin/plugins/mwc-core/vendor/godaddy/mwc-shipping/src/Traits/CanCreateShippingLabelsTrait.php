<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;

/**
 * Provides methods to an object to create shipping labels.
 *
 * @see ShippingLabel
 *
 * @since 0.1.0
 */
trait CanCreateShippingLabelsTrait
{
    use AdaptsRequestsTrait;

    /** @var string class name of the adapter */
    protected $createLabelShipmentAdapter;

    /**
     * Creates shipping labels for shipments.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract[] $shipments
     * @return array
     * @throws BaseException
     */
    public function create(array $shipments) : array
    {
        /** @var DataSourceAdapterContract $adapter */
        $adapter = new $this->createLabelShipmentAdapter($shipments);

        return $this->doAdaptedRequest($adapter);
    }
}
