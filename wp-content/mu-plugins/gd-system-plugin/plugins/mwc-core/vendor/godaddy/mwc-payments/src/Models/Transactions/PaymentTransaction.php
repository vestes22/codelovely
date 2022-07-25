<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Payments\Traits\HasCashbackAmountTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\HasTipAmountTrait;

/**
 * Payment Transaction class.
 *
 * @since 0.1.0
 */
class PaymentTransaction extends AbstractTransaction
{
    use HasCashbackAmountTrait;
    use HasTipAmountTrait;

    /** @var CurrencyAmount transaction amount */
    private $amount;

    /** @var bool */
    private $authOnly = false;

    /** @var CurrencyAmount */
    private $capturedAmount;

    /** @var CurrencyAmount */
    private $refundedAmount;

    /** @var string[] */
    private $remoteCaptureIds = [];

    /** @var string[] */
    private $remoteRefundIds = [];

    /** @var string[] */
    private $remoteVoidIds = [];

    /** @var string type */
    protected $type = 'payment';

    /**
     * Gets payment amount.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Gets authentication mode.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function isAuthOnly() : bool
    {
        return $this->authOnly;
    }

    /**
     * Gets the captured amount.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount|null
     */
    public function getCapturedAmount()
    {
        return $this->capturedAmount;
    }

    /**
     * Gets the refunded amount.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount|null
     */
    public function getRefundedAmount()
    {
        return $this->refundedAmount;
    }

    /**
     * Gets remote capture IDs.
     *
     * @since 0.1.0
     *
     * @return string[]
     */
    public function getRemoteCaptureIds() : array
    {
        return $this->remoteCaptureIds;
    }

    /**
     * Gets remote refund IDs.
     *
     * @since 0.1.0
     *
     * @return string[]
     */
    public function getRemoteRefundIds() : array
    {
        return $this->remoteRefundIds;
    }

    /**
     * Gets remote void IDs.
     *
     * @since 0.1.0
     *
     * @return string[]
     */
    public function getRemoteVoidIds() : array
    {
        return $this->remoteVoidIds;
    }

    /**
     * Sets the payment amount.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $amount
     *
     * @return PaymentTransaction
     */
    public function setAmount(CurrencyAmount $amount) : PaymentTransaction
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the authentication mode.
     *
     * @since 0.1.0
     *
     * @param bool $authOnly
     *
     * @return PaymentTransaction
     */
    public function setAuthOnly(bool $authOnly) : PaymentTransaction
    {
        $this->authOnly = $authOnly;

        return $this;
    }

    /**
     * Sets the captured amount.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $capturedAmount
     *
     * @return PaymentTransaction
     */
    public function setCapturedAmount(CurrencyAmount $capturedAmount) : PaymentTransaction
    {
        $this->capturedAmount = $capturedAmount;

        return $this;
    }

    /**
     * Sets the refunded amount.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $refundedAmount
     *
     * @return PaymentTransaction
     */
    public function setRefundedAmount(CurrencyAmount $refundedAmount) : PaymentTransaction
    {
        $this->refundedAmount = $refundedAmount;

        return $this;
    }

    /**
     * Sets the remote capture IDs.
     *
     * @since 0.1.0
     *
     * @param string[] $remoteCaptureIds
     *
     * @return PaymentTransaction
     */
    public function setRemoteCaptureIds(array $remoteCaptureIds) : PaymentTransaction
    {
        $this->remoteCaptureIds = $remoteCaptureIds;

        return $this;
    }

    /**
     * Sets the remote refund IDs.
     *
     * @since 0.1.0
     *
     * @param string[] $remoteRefundIds
     *
     * @return PaymentTransaction
     */
    public function setRemoteRefundIds(array $remoteRefundIds) : PaymentTransaction
    {
        $this->remoteRefundIds = $remoteRefundIds;

        return $this;
    }

    /**
     * Sets the remote void IDs.
     *
     * @since 0.1.0
     *
     * @param string[] $remoteVoidIds
     *
     * @return PaymentTransaction
     */
    public function setRemoteVoidIds(array $remoteVoidIds) : PaymentTransaction
    {
        $this->remoteVoidIds = $remoteVoidIds;

        return $this;
    }
}
