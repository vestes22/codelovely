<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

/**
 * An representation of tax item in an Order.
 *
 * @since 3.4.1
 */
class TaxItem extends AbstractOrderItem
{
    /**
     * tax item's rate.
     *
     * @since 3.4.1
     *
     * @var float
     */
    protected $rate;

    /**
     * Gets tax item rate.
     *
     * @since 3.4.1
     *
     * @return float
     */
    public function getRate() : float
    {
        return $this->rate;
    }

    /**
     * Sets tax item rate.
     *
     * @param float $rate
     *
     * @since 3.4.1
     *
     * @return TaxItem
     */
    public function setRate(float $rate) : TaxItem
    {
        $this->rate = $rate;

        return $this;
    }
}
