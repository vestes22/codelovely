<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait for objects that handle payments.
 */
trait PayableTrait
{
    /** @var string payment method */
    protected $paymentMethod;

    /** @var string payment status */
    protected $paymentStatus;

    /**
     * Gets the payment method.
     *
     * @return string
     */
    public function getPaymentMethod() : string
    {
        return is_string($this->paymentMethod) ? $this->paymentMethod : '';
    }

    /**
     * Gets the payment status.
     *
     * @return string
     */
    public function getPaymentStatus() : string
    {
        return is_string($this->paymentStatus) ? $this->paymentStatus : '';
    }

    /**
     * Sets the payment method.
     *
     * @param string $value
     * @return self
     */
    public function setPaymentMethod(string $value)
    {
        $this->paymentMethod = $value;

        return $this;
    }

    /**
     * Sets the payment status.
     *
     * @param string $value
     * @return self
     */
    public function setPaymentStatus(string $value)
    {
        $this->paymentStatus = $value;

        return $this;
    }
}
