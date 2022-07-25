<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods;

use GoDaddy\WordPress\MWC\Payments\Contracts\BankAccountTypeContract;

/**
 * Bank account payment method.
 */
class BankAccountPaymentMethod extends AbstractPaymentMethod
{
    /** @var BankAccountTypeContract the bank account type */
    protected $type;

    /** @var string the last four digits of the bank account number */
    protected $lastFour;

    /**
     * Gets the bank account type.
     *
     * @return BankAccountTypeContract|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the bank account type.
     *
     * @param BankAccountTypeContract $type
     * @return BankAccountPaymentMethod
     */
    public function setType(BankAccountTypeContract $type) : BankAccountPaymentMethod
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the last four digits of the bank account number.
     *
     * @return string|null
     */
    public function getLastFour()
    {
        return $this->lastFour;
    }

    /**
     * Sets the last four digits of the bank account number.
     *
     * @param string $lastFour
     * @return BankAccountPaymentMethod
     */
    public function setLastFour(string $lastFour) : BankAccountPaymentMethod
    {
        $this->lastFour = $lastFour;

        return $this;
    }
}
