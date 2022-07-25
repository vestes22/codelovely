<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods;

use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Card payment method.
 */
class CardPaymentMethod extends AbstractPaymentMethod
{
    /** @var string the bank identification number */
    protected $bin;

    /** @var CardBrandContract the card brand */
    protected $brand;

    /** @var string the month the card will expire */
    protected $expirationMonth;

    /** @var string the year the card will expire */
    protected $expirationYear;

    /** @var string the last four digits in the card number */
    protected $lastFour;

    /**
     * Gets the bank identification number.
     *
     * @return string|null
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * Sets the bank identification number.
     *
     * @param string $bin
     * @return CardPaymentMethod
     */
    public function setBin(string $bin) : CardPaymentMethod
    {
        $this->bin = $bin;

        return $this;
    }

    /**
     * Gets the card brand.
     *
     * @return CardBrandContract|null
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Sets the card brand.
     *
     * @param CardBrandContract $brand
     * @return CardPaymentMethod
     */
    public function setBrand(CardBrandContract $brand) : CardPaymentMethod
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Gets the month the card will expire.
     *
     * @return string|null
     */
    public function getExpirationMonth()
    {
        return $this->expirationMonth;
    }

    /**
     * Sets the month the card will expire.
     *
     * @param string $expirationMonth
     * @return CardPaymentMethod
     */
    public function setExpirationMonth(string $expirationMonth) : CardPaymentMethod
    {
        $this->expirationMonth = $expirationMonth;

        return $this;
    }

    /**
     * Gets the year the card will expire.
     *
     * @return string|null
     */
    public function getExpirationYear()
    {
        return $this->expirationYear;
    }

    /**
     * Sets the year the card will expire.
     *
     * @param string $expirationYear
     * @return CardPaymentMethod
     */
    public function setExpirationYear(string $expirationYear) : CardPaymentMethod
    {
        $this->expirationYear = $expirationYear;

        return $this;
    }

    /**
     * Gets the last four digits in the card number.
     *
     * @return string|null
     */
    public function getLastFour()
    {
        return $this->lastFour;
    }

    /**
     * Sets the last four digits in the card number.
     *
     * @param string $lastFour
     * @return CardPaymentMethod
     */
    public function setLastFour(string $lastFour) : CardPaymentMethod
    {
        $this->lastFour = $lastFour;

        return $this;
    }
}
