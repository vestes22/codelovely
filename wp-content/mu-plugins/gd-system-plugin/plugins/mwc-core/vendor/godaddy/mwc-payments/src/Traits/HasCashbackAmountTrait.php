<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * Adds a cashback amount property and its setter/getter.
 */
trait HasCashbackAmountTrait
{
    /** @var CurrencyAmount */
    protected $cashbackAmount;

    /**
     * Gets the cashback amount.
     *
     * @return CurrencyAmount|null
     */
    public function getCashbackAmount()
    {
        return $this->cashbackAmount;
    }

    /**
     * Sets the cashback amount.
     *
     * @param CurrencyAmount $value
     *
     * @return self
     */
    public function setCashbackAmount(CurrencyAmount $value)
    {
        $this->cashbackAmount = $value;

        return $this;
    }
}
