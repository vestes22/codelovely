<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Order_Item;

/**
 * Order item adapter abstract.
 */
abstract class AbstractOrderItemAdapter
{
    /** @var WC_Order_Item */
    protected $source;

    /**
     * Gets the currency associated with the item.
     *
     * @since 3.4.1
     *
     * @return string
     */
    protected function getCurrency() : string
    {
        if (! $order = $this->source->get_order()) {
            return (string) WooCommerceRepository::getCurrency();
        }

        return (string) ($order->get_currency() ?: WooCommerceRepository::getCurrency());
    }

    /**
     * Converts an order item amount from source.
     *
     * @since 3.4.1
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    protected function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, $this->getCurrency()))->convertFromSource();
    }

    /**
     * Converts a currency amount to float for the order item.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $amount
     * @return float
     */
    protected function convertCurrencyAmountToSource(CurrencyAmount $amount) : float
    {
        return (float) (new CurrencyAmountAdapter(0.0, $this->getCurrency()))->convertToSource($amount);
    }
}
