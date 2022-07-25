<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CancelledPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\NotTrackedPackageStatus;
use WC_Order_Item_Product;

/**
 * Used to convert shipment data stored as order meta into instances of PackageContract.
 *
 * @since x.y.z
 */
class PackageAdapter implements DataSourceAdapterContract
{
    /** @var array source data */
    protected $source = [];

    /**
     * Sets the value for the source property.
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
     * Creates an instance of PackageContract using the source data.
     *
     * @since x.y.z
     *
     * @return PackageContract
     */
    public function convertFromSource() : PackageContract
    {
        $package = new Package();
        $package->setId(ArrayHelper::get($this->source, 'id', StringHelper::generateUuid4()))
                ->setStatus($this->getStatusFromSource(ArrayHelper::get($this->source, 'status', '')))
                ->setTrackingNumber(ArrayHelper::get($this->source, 'trackingNumber', ''))
                ->setTrackingUrl(ArrayHelper::get($this->source, 'trackingUrl', ''));

        foreach (ArrayHelper::get($this->source, 'items', []) as $item) {
            if ($lineItem = $this->getLineItemFromSource((int) ArrayHelper::get($item, 'id', 0))) {
                $package->addItem($lineItem, ArrayHelper::get($item, 'quantity', 0));
            }
        }

        return $package;
    }

    /**
     * Gets a package status instance that matches the given status name.
     *
     * @since x.y.z
     *
     * @param string $statusName status name
     *
     * @return PackageStatusContract
     */
    protected function getStatusFromSource(string $statusName) : PackageStatusContract
    {
        foreach ($this->getStatusOptions() as $status) {
            if ($statusName === $status->getName()) {
                return $status;
            }
        }

        return new LabelCreatedPackageStatus();
    }

    /**
     * Gets an array of possible package status instances.
     *
     * @since x.y.z
     *
     * @return PackageStatusContract[]
     */
    protected function getStatusOptions()
    {
        return [
            new NotTrackedPackageStatus(),
            new LabelCreatedPackageStatus(),
            new CancelledPackageStatus(),
        ];
    }

    /**
     * Returns an array representation of the package object properties.
     *
     * @since x.y.z
     *
     * @param PackageContract $package
     * @return array|mixed
     */
    public function convertToSource(PackageContract $package = null) : array
    {
        $items = [];
        foreach ($package->getItems() as $item) {
            $array['id'] = $item->getId();
            $array['quantity'] = $package->getItemQuantity($item);
            $items[] = $array;
        }

        return [
            'id' => $package->getId(),
            'status' => $package->getStatus()->getName(),
            'trackingNumber' => $package->getTrackingNumber() ?: '',
            'trackingUrl' => $package->getTrackingUrl() ?: '',
            'items' => $items,
        ];
    }

    /**
     * Gets a {@see LineItem} instance for the given ID.
     *
     * @param int $itemId WooCommerce item ID
     *
     * @return LineItem|null
     */
    protected function getLineItemFromSource(int $itemId)
    {
        try {
            $wcOrderItem = $this->getWooCommerceOrderItem($itemId);
        } catch (Exception $e) {
            return null;
        }

        return $this->getLineItemAdapter($wcOrderItem)->convertFromSource();
    }

    /**
     * Gets the new WC_Order_Item_Product instance for the given id.
     *
     * @since x.y.z
     *
     * @param int $id
     * @return WC_Order_Item_Product
     */
    protected function getWooCommerceOrderItem(int $id = null) : WC_Order_Item_Product
    {
        return new WC_Order_Item_Product($id);
    }

    /**
     * Gets a new instance of {@see LineItemAdapter}.
     *
     * @param WC_Order_Item_Product $wcOrderItem WooCommerce order item object
     *
     * @return LineItemAdapter
     */
    protected function getLineItemAdapter(WC_Order_Item_Product $wcOrderItem) : LineItemAdapter
    {
        return new LineItemAdapter($wcOrderItem);
    }
}
