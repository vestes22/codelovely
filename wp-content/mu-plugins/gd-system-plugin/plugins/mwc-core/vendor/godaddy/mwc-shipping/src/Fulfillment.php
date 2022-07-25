<?php

namespace GoDaddy\WordPress\MWC\Shipping;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentDeletedEvent;
use GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use TypeError;

/**
 * Fulfillment handler.
 *
 * @since 0.1.0
 */
class Fulfillment
{
    use IsSingletonTrait;

    /**
     * Updates the given order fulfillment shipments statuses.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     */
    public function update(OrderFulfillment $fulfillment)
    {
        foreach ($fulfillment->getLineItemsThatNeedShipping() as $item) {
            $this->updateShippableItemFulfillmentStatus($fulfillment, $item);
        }

        $this->updateOrderFulfillmentStatus($fulfillment);
    }

    /**
     * Updates the shipment from the order fulfillment object.
     *
     *  @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     * @param ShipmentContract $shipment
     */
    public function updateShipment(OrderFulfillment $fulfillment, string $shipmentId, ShipmentContract $shipment)
    {
        $foundShipment = $this->findShipment($fulfillment, $shipmentId);

        $this->updateShipmentProperties($foundShipment, $shipment);
        $this->update($fulfillment);

        try {
            Events::broadcast(new ShipmentUpdatedEvent($fulfillment, $foundShipment));
        } catch (Exception $exception) {
            // Events::broadcast() indicates that it throws an exception inherited from Configuration::get(), but
            // that should probably not throw an exception. Lets catch that exception here to stop the chain.
            //
            // TODO: update Configuration::get() to stop throwing an exception {wvega 2021-06-23}
        }
    }

    /**
     * Updates the shipment from the order fulfillment object using data from a new shipment object.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract $oldShipment
     * @param ShipmentContract $newShipment
     */
    protected function updateShipmentProperties(ShipmentContract $oldShipment, ShipmentContract $newShipment)
    {
        $oldShipment->setProviderName($newShipment->getProviderName());
        $oldShipment->setProviderLabel($newShipment->getProviderLabel() ?: '');
        $oldShipment->setPackages($newShipment->getPackages());
        $oldShipment->setUpdatedAt(new DateTime());
    }

    /**
     * Adds a shipment to the given order fulfillment.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param ShipmentContract $shipment
     * @throws BaseException
     */
    public function addShipment(OrderFulfillment $fulfillment, ShipmentContract $shipment)
    {
        $this->addShipments($fulfillment, [$shipment]);
    }

    /**
     * Adds an array of Shipments to a given order fulfillment and then updates the Fulfillment.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param array $shipments
     * @throws BaseException
     */
    public function addShipments(OrderFulfillment $fulfillment, array $shipments)
    {
        foreach ($shipments as $shipment) {
            $this->addShipmentToOrderFulfillment($fulfillment, $shipment);
        }
        $this->update($fulfillment);

        foreach ($shipments as $shipment) {
            Events::broadcast(new ShipmentCreatedEvent($fulfillment, $shipment));
        }
    }

    /**
     * Adds a shipment to the given order fulfillment if it passes validation.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param ShipmentContract $shipment
     * @throws BaseException
     */
    protected function addShipmentToOrderFulfillment(OrderFulfillment $fulfillment, ShipmentContract $shipment)
    {
        try {
            $shipment->getProviderName();
        } catch (TypeError $e) {
            throw new BaseException('The Shipment provided did not include a provider name.');
        }

        $fulfillment->addShipment($shipment);
    }

    /**
     * Updates the fulfillment status of the given line item.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param LineItem $item
     */
    protected function updateShippableItemFulfillmentStatus(OrderFulfillment $fulfillment, LineItem $item)
    {
        $fulfilledQuantity = $fulfillment->getFulfilledQuantityForLineItem($item);

        if ($fulfilledQuantity === (float) $item->getQuantity()) {
            $item->setFulfillmentStatus(new FulfilledFulfillmentStatus());

            return;
        }

        if (! $fulfilledQuantity) {
            $item->setFulfillmentStatus(new UnfulfilledFulfillmentStatus());

            return;
        }

        $item->setFulfillmentStatus(new PartiallyFulfilledFulfillmentStatus());
    }

    /**
     * Updates the fulfillment status for the given order as a whole.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     */
    protected function updateOrderFulfillmentStatus(OrderFulfillment $fulfillment)
    {
        if ($this->areAllLineItemsThatNeedShippingFulfilled($fulfillment)) {
            $fulfillment->getOrder()->setFulfillmentStatus(new FulfilledFulfillmentStatus());
        } elseif (! empty($fulfillment->getShipmentsThatCanFulfillItems())) {
            $fulfillment->getOrder()->setFulfillmentStatus(new PartiallyFulfilledFulfillmentStatus());
        } else {
            $fulfillment->getOrder()->setFulfillmentStatus(new UnfulfilledFulfillmentStatus());
        }
    }

    /**
     * Determines whether all line items that need shipping are already fulfilled.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     *
     * @return bool
     */
    protected function areAllLineItemsThatNeedShippingFulfilled(OrderFulfillment $fulfilment) : bool
    {
        foreach ($fulfilment->getLineItemsThatNeedShipping() as $item) {
            if (! $item->getFulfillmentStatus() instanceof FulfilledFulfillmentStatus) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes the shipment from the order fulfillment object.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     */
    public function deleteShipment(OrderFulfillment $fulfillment, string $shipmentId)
    {
        $shipment = $this->findShipment($fulfillment, $shipmentId);

        $fulfillment->removeShipment($shipment);

        $this->update($fulfillment);

        Events::broadcast(new ShipmentDeletedEvent($fulfillment, $shipment));
    }

    /**
     * Tries to find a shipment with the given shipment ID in the order fulfillment object.
     *
     * @since 0.1.0
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     * @return ShipmentContract|null
     * @throws BaseException
     */
    private function findShipment(OrderFulfillment $fulfillment, string $shipmentId)
    {
        if (! $shipment = $fulfillment->getShipment($shipmentId)) {
            throw new BaseException("Shipment not found with ID {$shipmentId}");
        }

        return $shipment;
    }
}
