<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;

/**
 * Order fulfillment class.
 *
 * Helps updating the fulfillment status of an order as packages are created to ship the different items.
 *
 * @since 0.1.0
 */
class OrderFulfillment extends AbstractModel
{
    /** @var Order the order object */
    private $order;

    /** @var ShipmentContracti[] associative array of shipment arrays indexed by the shipment ID */
    private $shipments = [];

    /**
     * Gets the order.
     *
     * @since 0.1.0
     *
     * @return Order
     */
    public function getOrder() : Order
    {
        return $this->order;
    }

    /**
     * Sets the order.
     *
     * @since 0.1.0
     *
     * @param Order $value
     * @return $this
     */
    public function setOrder(Order $value) : OrderFulfillment
    {
        $this->order = $value;

        return $this;
    }

    /**
     * Looks up a shipment with a given shipment ID.
     *
     * @since 0.1.0
     *
     * @param string $shipmentId
     * @return ShipmentContract|null
     */
    public function getShipment(string $shipmentId)
    {
        if (ArrayHelper::exists($this->shipments, $shipmentId)) {
            return $this->shipments[$shipmentId];
        }

        return null;
    }

    /**
     * Gets the shipments.
     *
     * @since 0.1.0
     *
     * @param string|null $providerName optional, to return shipments belonging to a provider only
     * @return ShipmentContract[] associative array of shipments sorted by provider or shipments for a given provider
     */
    public function getShipments($providerName = null) : array
    {
        if (null !== $providerName) {
            return ArrayHelper::where($this->shipments, function (ShipmentContract $shipment) use ($providerName) {
                return $shipment->getProviderName() === $providerName;
            });
        }

        return $this->shipments;
    }

    /**
     * Determines whether a shipment is present.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $shipment
     * @return bool
     */
    public function hasShipment(ShipmentContract $shipment) : bool
    {
        return isset($this->shipments[$shipment->getId()]);
    }

    /**
     * Determines whether a shipment provider is present.
     *
     * @since 0.1.0
     *
     * @param string $providerName
     * @return bool
     */
    public function hasProvider(string $providerName) : bool
    {
        return ! empty($this->getShipments($providerName));
    }

    /**
     * Gets the shipments that can fulfill items.
     *
     * @since 0.1.0
     *
     * @param string|null $providerName optional provider name to retrieve shipments for
     * @return array|ShipmentContract[] associative array of shipments sorted by provider or shipments for a given provider
     */
    public function getShipmentsThatCanFulfillItems($providerName = null) : array
    {
        $fulfillableShipments = [];

        foreach ($this->getShipments() as $shipment) {
            foreach ($shipment->getPackages() as $package) {
                if ($package->canFulfillItems()) {
                    $fulfillableShipments[$shipment->getProviderName()][$shipment->getId()] = $shipment;
                    break;
                }
            }
        }

        if (null !== $providerName) {
            return $fulfillableShipments[$providerName] ?? [];
        }

        return $fulfillableShipments;
    }

    /**
     * Gets the line items that need shipping.
     *
     * @since 0.1.0
     *
     * @return LineItem[] array of line items that require shipping
     */
    public function getLineItemsThatNeedShipping() : array
    {
        return ArrayHelper::where($this->getOrder()->getLineItems(), function (LineItem $lineItem) {
            return $lineItem->getNeedsShipping();
        });
    }

    /**
     * Gets the fulfilled line items.
     *
     * @since 0.1.0
     *
     * @return LineItem[] array of line items that have fulfilled status
     */
    public function getFulfilledLineItems() : array
    {
        return ArrayHelper::where($this->getOrder()->getLineItems(), function (LineItem $lineItem) {
            return $lineItem->getFulfillmentStatus() instanceof FulfilledFulfillmentStatus;
        });
    }

    /**
     * Gets the fulfilled quantity for a given line item.
     *
     * @since 0.1.0
     *
     * @param LineItem $lineItem
     * @return float|int
     */
    public function getFulfilledQuantityForLineItem(LineItem $lineItem) : float
    {
        $quantity = 0;

        foreach ($this->getShipments() as $shipment) {
            foreach ($shipment->getPackages() as $package) {
                if ($package->canFulfillItems()) {
                    $quantity += $package->getItemQuantity($lineItem);
                }
            }
        }

        return $quantity;
    }

    /**
     * Adds a shipment.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $shipment
     * @return self
     */
    public function addShipment(ShipmentContract $shipment) : OrderFulfillment
    {
        $this->shipments[$shipment->getId()] = $shipment;

        return $this;
    }

    /**
     * Removes a shipment.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $shipment
     * @return self
     */
    public function removeShipment(ShipmentContract $shipment) : OrderFulfillment
    {
        unset($this->shipments[$shipment->getId()]);

        return $this;
    }
}
