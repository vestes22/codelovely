<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\Request\Adapters;

use DateTime;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;
use WC_Order_Item_Product;

class ShipmentAdapter implements DataSourceAdapterContract
{
    /** @var array source data */
    protected $data;

    /**
     * ShipmentAdapter constructor.
     *
     * @since x.y.z
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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
            ->setId($this->getShipmentId())
            ->setProviderName(ArrayHelper::get($this->data, 'shippingProvider', ''))
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());

        if (strtolower($shipment->getProviderName()) === 'other') {
            $shipment->setProviderLabel(ArrayHelper::get($this->data, 'otherShippingProviderDescription', 'Other'));
        }

        $package = (new Package())
            ->setId(StringHelper::generateUuid4())
            ->setStatus(new LabelCreatedPackageStatus())
            ->setTrackingNumber(ArrayHelper::get($this->data, 'trackingNumber', ''))
            ->setTrackingUrl(ArrayHelper::get($this->data, 'trackingUrl', ''));

        foreach (ArrayHelper::get($this->data, 'items', []) as $item) {
            $lineItem = $this->createLineItem($item);
            $package->addItem($lineItem, ArrayHelper::get($item, 'quantity', 0));
        }

        $shipment->addPackage($package);

        return $shipment;
    }

    /**
     * Converts to Data Source format.
     *
     * @since x.y.z
     *
     * @param ShipmentContract|null $shipment
     * @return array
     */
    public function convertToSource(ShipmentContract $shipment = null) : array
    {
        if (empty($shipment)) {
            return [];
        }

        // Assume that each Shipment only has one package.
        $package = array_values($shipment->getPackages())[0];
        $items = $package->getItems();

        $data = [
            'id' => $shipment->getId(),
            'createdAt' => $shipment->getCreatedAt() ? $shipment->getCreatedAt()->format('c') : '',
            'updatedAt' => $shipment->getUpdatedAt() ? $shipment->getUpdatedAt()->format('c') : '',
            'shippingProvider' => $shipment->getProviderName(),
            'otherShippingProviderDescription' => 'other' === strtolower($shipment->getProviderName()) ? $shipment->getProviderLabel() : '',
            'trackingNumber' => $package->getTrackingNumber(),
            'trackingUrl' => $shipment->getPackageTrackingUrl($package) ?: '',
            'items' => [],
        ];

        foreach ($items as $item) {
            $data['items'][] = [
                'id' => $item->getId(),
                'quantity' => $package->getItemQuantity($item),
            ];
        }

        return $data;
    }

    /**
     * Takes an array of data about a line item and converts it to a LineItem object using the LineItemAdapter.
     *
     * @since x.y.z
     *
     * @param array $itemData
     * @return LineItem
     */
    private function createLineItem(array $itemData) : LineItem
    {
        $orderItem = new WC_Order_Item_Product(ArrayHelper::get($itemData, 'id', 0));

        return (new LineItemAdapter($orderItem))->convertFromSource();
    }

    /**
     * Gets the Shipment ID from the source data, or generates a UUID.
     *
     * @since x.y.z
     *
     * @return string
     */
    private function getShipmentId() : string
    {
        return $this->data['id'] ?? StringHelper::generateUuid4();
    }
}
