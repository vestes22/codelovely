<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order as CommonOrder;

/**
 * Core order object.
 */
class Order extends CommonOrder
{
    /** @var bool whether the payment for the order is captured */
    protected $captured = false;

    /** @var bool whether the payment for the order is refunded */
    protected $refunded = false;

    /** @var bool whether the payment for the order is voided */
    protected $voided = false;

    /** @var string customer email address */
    protected $emailAddress;

    /** @var CurrencyAmount|null */
    private $discountAmount;

    /** @var string customer order notes */
    protected $orderNotes;

    /** @var bool whether the order is ready to have a payment captured */
    protected $readyForCapture = false;

    /** @var string the order source */
    protected $source;

    /** @var string the remote order id */
    protected $remoteId;

    /**
     * Gets the customer's email address.
     *
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Sets the customer's email address.
     *
     * @param string $value
     * @return self
     */
    public function setEmailAddress(string $value) : Order
    {
        $this->emailAddress = $value;

        return $this;
    }

    /**
     * Gets the order notes.
     *
     * @return string|null
     */
    public function getOrderNotes()
    {
        return $this->orderNotes;
    }

    /**
     * Sets the order address.
     *
     * @param string $value
     * @return $this
     */
    public function setOrderNotes(string $value) : Order
    {
        $this->orderNotes = $value;

        return $this;
    }

    /**
     * Gets the order discount amount.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount|null
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Sets the order discount amount.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setDiscountAmount(CurrencyAmount $value) : Order
    {
        $this->discountAmount = $value;

        return $this;
    }

    /**
     * Sets a flag whether the payment for the order has been captured.
     *
     * @param bool $value
     * @return self
     */
    public function setCaptured(bool $value) : Order
    {
        $this->captured = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was captured.
     *
     * @return bool
     */
    public function isCaptured() : bool
    {
        return $this->captured;
    }

    /**
     * Sets whether the order is ready to have its payment captured.
     *
     * @param bool $value
     * @return self
     */
    public function setReadyForCapture(bool $value) : Order
    {
        $this->readyForCapture = $value;

        return $this;
    }

    /**
     * Determines whether the order is ready to have its payment captured.
     *
     * @return bool
     */
    public function isReadyForCapture() : bool
    {
        return $this->readyForCapture;
    }

    /**
     * Sets a flag whether the payment for the order has been refunded.
     *
     * @param bool $value
     * @return self
     */
    public function setRefunded(bool $value) : Order
    {
        $this->refunded = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was refunded.
     *
     * @return bool
     */
    public function isRefunded() : bool
    {
        return $this->refunded;
    }

    /**
     * Sets a flag whether the payment for the order has been voided.
     *
     * @param bool $value
     * @return self
     */
    public function setVoided(bool $value) : Order
    {
        $this->voided = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was voided.
     *
     * @return bool
     */
    public function isVoided() : bool
    {
        return $this->voided;
    }

    /**
     * Gets the order source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets the order source.
     *
     * @param string $value
     * @return self
     */
    public function setSource(string $value) : Order
    {
        $this->source = $value;

        return $this;
    }

    /**
     * Gets the order remote id, if any.
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * Sets the order remote id.
     *
     * @param string $value
     * @return $this
     */
    public function setRemoteId(string $value) : Order
    {
        $this->remoteId = $value;

        return $this;
    }

    /**
     * Check if the order has a certain shipping method. Accepts a string or
     * array of strings and returns true if the order uses at least *one* of
     * the provided $methods.
     *
     * @param string|array $methods
     * @return bool
     */
    public function hasShippingMethod($methods): bool
    {
        foreach (ArrayHelper::wrap($this->getShippingItems()) as $shippingItem) {
            if (ArrayHelper::contains(ArrayHelper::wrap($methods), $shippingItem->getName())) {
                return true;
            }
        }

        return false;
    }
}
