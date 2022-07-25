<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * Adds a tip amount property and its setter/getter.
 */
trait HasTipAmountTrait
{
    /** @var CurrencyAmount */
    protected $tipAmount;

    /**
     * Gets the tip amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTipAmount()
    {
        return $this->tipAmount;
    }

    /**
     * Sets the tip amount.
     *
     * @param CurrencyAmount $tipAmount
     *
     * @return self
     */
    public function setTipAmount(CurrencyAmount $tipAmount)
    {
        $this->tipAmount = $tipAmount;

        return $this;
    }
}
