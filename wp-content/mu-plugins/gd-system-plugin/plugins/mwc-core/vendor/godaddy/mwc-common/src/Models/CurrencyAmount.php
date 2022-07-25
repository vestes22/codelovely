<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

/**
 * An object representation of a currency amount.
 *
 * @since 3.4.1
 */
class CurrencyAmount extends AbstractModel
{
    /** @var int in cents */
    protected $amount;

    /** @var string 2-letter Unicode CLDR currency code */
    protected $currencyCode;

    /**
     * Gets the amount.
     *
     * @since 3.4.1
     *
     * @return int cents
     */
    public function getAmount() : int
    {
        return is_int($this->amount) ? $this->amount : 0;
    }

    /**
     * Gets the currency code.
     *
     * @since 3.4.1
     *
     * @return string 3-letter Unicode CLDR currency code
     */
    public function getCurrencyCode() : string
    {
        return is_string($this->currencyCode) ? $this->currencyCode : '';
    }

    /**
     * Sets the amount.
     *
     * @since 3.4.1
     *
     * @param int $amount
     *
     * @return self
     */
    public function setAmount(int $amount) : CurrencyAmount
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the currency code.
     *
     * @since 3.4.1
     *
     * @param string $code 3-letter Unicode CLDR currency code
     *
     * @return self
     */
    public function setCurrencyCode(string $code) : CurrencyAmount
    {
        $this->currencyCode = $code;

        return $this;
    }
}
