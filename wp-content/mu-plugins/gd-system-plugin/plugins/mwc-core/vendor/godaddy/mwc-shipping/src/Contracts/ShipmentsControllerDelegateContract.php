<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;

/**
 * The Shipments Controller Delegate contract.
 *
 * @since 0.1.0
 */
interface ShipmentsControllerDelegateContract
{
    /**
     * Uses an instance of ShipmentAdapter to convert the given data into an instance of ShipmentContract.
     *
     * @since 0.1.0
     *
     * @param array $data
     * @return ShipmentContract
     */
    public function getShipmentFromRequestData(array $data) : ShipmentContract;

    /**
     * Gets the data used to build the JSON representation of the given shipment object.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $shipment
     * @return array
     */
    public function getShipmentData(ShipmentContract $shipment) : array;
}
