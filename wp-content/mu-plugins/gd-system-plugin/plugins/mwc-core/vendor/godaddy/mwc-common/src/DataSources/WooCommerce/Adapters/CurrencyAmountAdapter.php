<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * Currency amount adapter.
 *
 * @since 1.0.0
 */
class CurrencyAmountAdapter implements DataSourceAdapterContract
{
    /** @var float currency amount */
    private $amount;

    /** @var string currency code */
    private $currency;

    /**
     * Currency amount adapter constructor.
     *
     * @since 3.4.1
     *
     * @param float $amount
     * @param string $currency
     */
    public function __construct(float $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Converts a currency amount into a native object.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount
     */
    public function convertFromSource() : CurrencyAmount
    {
        $currencyAmount = new CurrencyAmount();

        return $currencyAmount
            ->setAmount((int) wc_add_number_precision($this->amount))
            ->setCurrencyCode($this->currency);
    }

    /**
     * Converts a currency amount to a float.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $currencyAmount
     *
     * @return float
     */
    public function convertToSource(CurrencyAmount $currencyAmount = null) : float
    {
        if ($currencyAmount) {
            $this->amount = (float) wc_remove_number_precision($currencyAmount->getAmount());
            $this->currency = $currencyAmount->getCurrencyCode();
        }

        return $this->amount;
    }
}
