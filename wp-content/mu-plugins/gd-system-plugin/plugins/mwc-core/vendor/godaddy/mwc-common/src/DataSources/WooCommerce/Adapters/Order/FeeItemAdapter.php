<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\FeeItem;
use WC_Order_Item_Fee;

/**
 * Order fee item adapter.
 *
 * Converts between a native order fee item object and a WooCommerce order fee item object.
 *
 * @since 3.4.1
 *
 * @property WC_Order_Item_Fee $source
 */
class FeeItemAdapter extends AbstractOrderItemAdapter implements DataSourceAdapterContract
{
    /**
     * Order fee item adapter constructor.
     *
     * @since 3.4.1
     *
     * @param WC_Order_Item_Fee $source
     */
    public function __construct(WC_Order_Item_Fee $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce order fee item to a native order fee item.
     *
     * @since 3.4.1
     *
     * @return FeeItem
     */
    public function convertFromSource() : FeeItem
    {
        return (new FeeItem())
            ->setId($this->source->get_id())
            ->setLabel($this->source->get_name())
            ->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()))
            ->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()));
    }

    /**
     * Converts a native order line item into a WooCommerce order fee item.
     *
     * @since 3.4.1
     *
     * @param FeeItem|null $feeItem
     * @return WC_Order_Item_Fee
     */
    public function convertToSource($feeItem = null) : WC_Order_Item_Fee
    {
        if (! $feeItem instanceof FeeItem) {
            return $this->source;
        }

        $this->source->set_id($feeItem->getId());
        $this->source->set_name($feeItem->getLabel());
        $this->source->set_total_tax($this->convertCurrencyAmountToSource($feeItem->getTaxAmount()));
        $this->source->set_total($this->convertCurrencyAmountToSource($feeItem->getTotalAmount()));

        return $this->source;
    }
}
