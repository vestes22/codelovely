<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * An representation of fee item in an Order.
 *
 * @since 3.4.1
 */
class FeeItem extends AbstractOrderItem
{
    /** @var CurrencyAmount total tax amount */
    protected $taxAmount;

    /**
     * Gets the tax total amount object.
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
     * Sets tax total amount object.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $taxAmount
     *
     * @return FeeItem
     */
    public function setTaxAmount(CurrencyAmount $taxAmount) : FeeItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }
}
