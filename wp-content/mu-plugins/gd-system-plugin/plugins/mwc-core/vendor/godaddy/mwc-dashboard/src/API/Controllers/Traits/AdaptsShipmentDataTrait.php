<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits;

use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\Request\Adapters\ShipmentAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;

trait AdaptsShipmentDataTrait
{
    /**
     * Converts the given data into an instance of ShipmentContract.
     *
     * @since x.y.z
     *
     * @param array $data
     * @return ShipmentContract
     */
    public function getShipmentFromRequestData(array $data): ShipmentContract
    {
        return $this->getShipmentAdapter($data)->convertFromSource();
    }

    /**
     * Gets the data used to build the JSON representation of the given shipment object.
     *
     * @since x.y.z
     *
     * @param ShipmentContract $shipment
     * @return array
     */
    public function getShipmentData(ShipmentContract $shipment): array
    {
        return $this->getShipmentAdapter()->convertToSource($shipment);
    }

    /**
     * Creates an instance of the ShipmentAdapter.
     *
     * @since x.y.z
     *
     * @param array $data
     * @return ShipmentAdapter
     */
    protected function getShipmentAdapter(array $data = []) : ShipmentAdapter
    {
        return new ShipmentAdapter($data);
    }
}
