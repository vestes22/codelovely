<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions;

use GoDaddy\WordPress\MWC\Payments\Models\Transactions\PaymentTransaction as PaymentsPaymentTransaction;

/**
 * Payment transaction.
 *
 * @since 2.10.0
 */
class PaymentTransaction extends PaymentsPaymentTransaction
{
    /** @var bool */
    private $tokenize = false;

    /** @var string */
    private $avsResult;

    /** @var string */
    private $cvvResult;

    /**
     * Get the AVS result.
     *
     * @return string|null
     */
    public function getAvsResult()
    {
        return $this->avsResult;
    }

    /**
     * Get the CVV result.
     *
     * @return string|null
     */
    public function getCvvResult()
    {
        return $this->cvvResult;
    }

    /**
     * Get the value of tokenize.
     *
     * @since 2.10.0
     *
     * @return bool
     */
    public function shouldTokenize() : bool
    {
        return $this->tokenize;
    }

    /**
     * Set the value of tokenize.
     *
     * @since 2.10.0
     *
     * @param bool $tokenize
     *
     * @return PaymentTransaction
     */
    public function setShouldTokenize(bool $tokenize) : PaymentTransaction
    {
        $this->tokenize = $tokenize;

        return $this;
    }

    /**
     * Set the AVS result.
     *
     * @param string $value
     * @return PaymentTransaction
     */
    public function setAvsResult(string $value) : PaymentTransaction
    {
        $this->avsResult = $value;

        return $this;
    }

    /**
     * Set the CVV result.
     *
     * @param string $value
     * @return PaymentTransaction
     */
    public function setCvvResult(string $value) : PaymentTransaction
    {
        $this->cvvResult = $value;

        return $this;
    }
}
