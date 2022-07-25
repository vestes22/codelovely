<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use GoDaddy\WordPress\MWC\Shipping\Shipping;
use WC_Order_Item_Product;

class AbstractShipmentsTable implements RenderableContract
{
    /** @var string The name of the template that should be used to render this instance of the component */
    protected $templateName;

    /** @var OrderFulfillment */
    protected $fulfillment;

    /** @var array packages data */
    private $packagesData;

    /**
     * AbstractShipmentsTable constructor.
     *
     * @param OrderFulfillment $fulfillment
     */
    public function __construct(OrderFulfillment $fulfillment)
    {
        $this->fulfillment = $fulfillment;
    }

    /**
     * Renders component markup.
     *
     * @since 2.10.0
     */
    public function render()
    {
        if (! $this->getFulfillment()->getShipments()) {
            return;
        }

        // allow merchants to overwrite the template by placing a copy of it the mwc subfolder inside their themes folder
        wc_get_template(
            $this->getTemplateName(),
            $this->getTemplateData(),
            'mwc/',
            Configuration::get('mwc.directory').'/templates/woocommerce/'
        );
    }

    /**
     * Gets the name of the template that should be used to render this instance of the component.
     *
     * @since 2.10.0
     *
     * @return string
     */
    protected function getTemplateName() : string
    {
        return $this->templateName;
    }

    /**
     * Gets the fulfillment.
     *
     * @since 2.10.0
     *
     * @return OrderFulfillment
     */
    public function getFulfillment(): OrderFulfillment
    {
        return $this->fulfillment;
    }

    /**
     * Gets the data for the template.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    protected function getTemplateData() : array
    {
        return ['columns' => $this->getColumnsData(), 'packages' => $this->getPackagesData()];
    }

    /**
     * Gets an array with column IDs as keys and translated column names as values.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    protected function getColumnsData() : array
    {
        $columns = [
            'carrier' => __('Carrier', 'mwc-core'),
            'tracking-number' => __('Tracking number', 'mwc-core'),
        ];

        if ($this->hasAnyPackagesWithItems()) {
            $columns['items'] = __('Items', 'mwc-core');
        }

        return $columns;
    }

    /**
     * Gets an array of data for the packages that can fulfill items using the structure that the order-shipments template expects.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getPackagesData() : array
    {
        if (ArrayHelper::accessible($this->packagesData)) {
            return $this->packagesData;
        }

        $this->packagesData = array_reduce($this->getPackagesDataByProvider(), function ($result, $packagesData) {
            return array_merge($result, $packagesData);
        }, []);

        return $this->packagesData;
    }

    /**
     * Gets an array of arrays of data for the packages that can fulfill items grouped by provider.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getPackagesDataByProvider() : array
    {
        return array_reduce($this->getFulfillment()->getShipments(), function (array $result, ShipmentContract $shipment) {
            ArrayHelper::set(
                $result,
                $shipment->getProviderName(),
                ArrayHelper::combine(
                    ArrayHelper::get($result, $shipment->getProviderName(), []),
                    $this->getPackagesDataFromShipment($shipment)
                )
            );

            return $result;
        }, []);
    }

    /**
     * Gets package data for the packages included in the given shipment.
     *
     * @since 2.10.0
     *
     * @param ShipmentContract $shipment shipment object
     *
     * @return array
     */
    protected function getPackagesDataFromShipment(ShipmentContract $shipment) : array
    {
        $providerLabel = $this->getShipmentProviderLabel($shipment);

        return array_map(function (PackageContract $package) use ($providerLabel) {
            return [
                'providerLabel'  => $providerLabel,
                'trackingUrl'    => $package->getTrackingUrl() ?: '',
                'trackingNumber' => $package->getTrackingNumber() ?: '',
                'items'          => $this->getLineItemsData($package),
            ];
        }, $shipment->getPackagesThatCanFulfillItems());
    }

    /**
     * Gets the label for the provider associated with the given shipment.
     *
     * @since 2.10.0
     *
     * @param ShipmentContract $shipment shipment object
     *
     * @return string
     */
    protected function getShipmentProviderLabel(ShipmentContract $shipment) : string
    {
        try {
            $provider = Shipping::provider($shipment->getProviderName());
        } catch (Exception $exception) {
            return $shipment->getProviderLabel() ?: $shipment->getProviderName();
        }

        return $provider->getLabel() ?: $provider->getName();
    }

    /**
     * Gets an array of arrays of data for the the line items included in the given package.
     *
     * @since 2.10.0
     *
     * @param PackageContract $package package object
     *
     * @return array
     */
    protected function getLineItemsData(PackageContract $package) : array
    {
        return array_map(function (LineItem $item) use ($package) {
            return [
                'name'     => $item->getLabel(),
                'quantity' => $package->getItemQuantity($item),
                'url'      => $this->getLineItemProductUrl($item),
            ];
        }, $package->getItems());
    }

    /**
     * Gets the product URL for the given line item.
     *
     * @since 2.10.0
     *
     * @param LineItem $lineItem a line item object
     *
     * @return string
     */
    protected function getLineItemProductUrl(LineItem $lineItem) : string
    {
        if (! $wcOrderItem = $this->getWooCommerceOrderItemProduct($lineItem->getId())) {
            return '#';
        }

        if (! $wcProduct = $wcOrderItem->get_product()) {
            return '#';
        }

        return $wcProduct->get_permalink($wcOrderItem);
    }

    /**
     * Gets an instance of WC_Order_Item_Product for the given item ID.
     *
     * TODO: move this method to a repository {wvega 2021-06-22}
     *
     * @since 2.10.0
     *
     * @param int $itemId the ID of the order item
     *
     * @return WC_Order_Item_Product|null
     */
    protected function getWooCommerceOrderItemProduct(int $itemId)
    {
        try {
            return new WC_Order_Item_Product($itemId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Returns true if any packages associated with the order fulfillment record contain items.
     *
     * @since 2.10.0
     *
     * @return bool
     */
    private function hasAnyPackagesWithItems() : bool
    {
        foreach ($this->getPackagesData() as $packageData) {
            if (ArrayHelper::get($packageData, 'items', [])) {
                return true;
            }
        }

        return false;
    }
}
