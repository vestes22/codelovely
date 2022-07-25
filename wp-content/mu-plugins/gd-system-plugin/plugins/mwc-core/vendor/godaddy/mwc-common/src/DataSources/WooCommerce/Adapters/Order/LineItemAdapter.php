<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use WC_Order_Item_Product;

/**
 * Order line item adapter.
 *
 * Converts between a native order line item object and a WooCommerce order product item object.
 *
 * @since 3.4.1
 *
 * @property WC_Order_Item_Product $source
 */
class LineItemAdapter extends AbstractOrderItemAdapter implements DataSourceAdapterContract
{
    /**
     * Order line item adapter constructor.
     *
     * @since 3.4.1
     *
     * @param WC_Order_Item_Product $source
     */
    public function __construct(WC_Order_Item_Product $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce order product item to a native order line item.
     *
     * @since 3.4.1
     *
     * @return LineItem
     */
    public function convertFromSource() : LineItem
    {
        return (new LineItem())
            ->setId($this->source->get_id())
            ->setLabel($this->source->get_name())
            ->setQuantity($this->source->get_quantity())
            ->setProduct($this->source->get_product())
            ->setVariationId($this->source->get_variation_id())
            ->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()))
            ->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()))
            ->setSubTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal()))
            ->setSubTotalTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal_tax()))
            ->setNeedsShipping($this->getProductNeedsShipping($this->source));
    }

    /**
     * Determines whether the given product order item needs shipping.
     *
     * @since 3.4.1
     *
     * @param WC_Order_Item_Product $wcOrderItem product order item
     *
     * @return bool
     */
    protected function getProductNeedsShipping(WC_Order_Item_Product $wcOrderItem) : bool
    {
        if (! $product = $wcOrderItem->get_product()) {
            return false;
        }

        return (bool) $product->needs_shipping();
    }

    /**
     * Converts a native order line item into a WooCommerce order product item.
     *
     * @since 3.4.1
     *
     * @param LineItem|null $lineItem
     * @return WC_Order_Item_Product
     */
    public function convertToSource($lineItem = null) : WC_Order_Item_Product
    {
        if (! $lineItem instanceof LineItem) {
            return $this->source;
        }

        $this->source->set_id($lineItem->getId());
        $this->source->set_name($lineItem->getLabel());
        $this->source->set_quantity($lineItem->getQuantity());
        $this->source->set_product($lineItem->getProduct());
        $this->source->set_variation_id($lineItem->getVariationId());
        $this->source->set_total_tax($this->convertCurrencyAmountToSource($lineItem->getTaxAmount()));
        $this->source->set_total($this->convertCurrencyAmountToSource($lineItem->getTotalAmount()));
        $this->source->set_subtotal($this->convertCurrencyAmountToSource($lineItem->getSubTotalAmount()));
        $this->source->set_subtotal_tax($this->convertCurrencyAmountToSource($lineItem->getSubTotalTaxAmount()));

        return $this->source;
    }
}
