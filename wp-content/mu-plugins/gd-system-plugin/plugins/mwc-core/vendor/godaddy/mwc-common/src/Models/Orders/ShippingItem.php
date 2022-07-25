<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * An representation of shipping item in an Order.
 *
 * @since 3.4.1
 */
class ShippingItem extends AbstractOrderItem
{
    /**
     * shipping item's total tax amount.
     *
     * @since 3.4.1
     *
     * @var CurrencyAmount
     */
    protected $taxAmount;

    /**
     * Gets shipping item tax total amount object.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount
     */
    public function getTaxAmount() : CurrencyAmount
    {
        return $this->taxAmount;
    }

    /**
     * Sets shipping item tax total amount object.
     *
     * @param CurrencyAmount $taxAmount
     *
     * @since 3.4.1
     *
     * @return ShippingItem
     */
    public function setTaxAmount(CurrencyAmount $taxAmount) : ShippingItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }
}
