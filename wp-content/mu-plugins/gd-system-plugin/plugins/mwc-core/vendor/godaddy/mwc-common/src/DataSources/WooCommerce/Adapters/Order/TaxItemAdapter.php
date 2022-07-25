<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\TaxItem;
use WC_Order_Item_Tax;

/**
 * Order tax item adapter.
 *
 * Converts between a native order tax item object and a WooCommerce order tax item object.
 *
 * @since 3.4.1
 *
 * @property WC_Order_Item_Tax $source
 */
class TaxItemAdapter extends AbstractOrderItemAdapter implements DataSourceAdapterContract
{
    /**
     * Order tax item adapter constructor.
     *
     * @since 3.4.1
     *
     * @param WC_Order_Item_Tax $source
     */
    public function __construct(WC_Order_Item_Tax $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce order tax item to a native order tax item.
     *
     * @since 3.4.1
     *
     * @return TaxItem
     */
    public function convertFromSource() : TaxItem
    {
        return (new TaxItem())
            ->setId($this->source->get_id())
            ->setLabel($this->source->get_label())
            ->setName($this->source->get_rate_code())
            ->setRate($this->source->get_rate_percent())
            ->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_tax_total()));
    }

    /**
     * Converts a native order tax item into a WooCommerce order tax item.
     *
     * @since 3.4.1
     *
     * @param TaxItem|null $TaxItem
     * @return WC_Order_Item_Tax
     */
    public function convertToSource($TaxItem = null) : WC_Order_Item_Tax
    {
        if (! $TaxItem instanceof TaxItem) {
            return $this->source;
        }

        $this->source->set_id($TaxItem->getId());
        $this->source->set_label($TaxItem->getLabel());
        $this->source->set_rate_code($TaxItem->getName());
        $this->source->set_rate_percent($TaxItem->getRate());
        $this->source->set_tax_total($this->convertCurrencyAmountToSource($TaxItem->getTotalAmount()));

        return $this->source;
    }
}
