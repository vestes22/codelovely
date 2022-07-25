<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\TransactionStatusContract;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * The abstract transaction model.
 */
abstract class AbstractTransaction extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var DateTime timestamp record was created */
    protected $createdAt;

    /** @var Customer */
    protected $customer;

    /** @var string */
    protected $notes;

    /** @var Order */
    protected $order;

    /** @var string */
    protected $parentType;

    /** @var AbstractPaymentMethod */
    protected $paymentMethod;

    /** @var string */
    protected $providerName;

    /** @var string */
    protected $remoteId;

    /** @var string */
    protected $remoteParentId;

    /** @var string */
    protected $resultCode;

    /** @var string */
    protected $resultMessage;

    /** @var string */
    protected $source;

    /** @var TransactionStatusContract */
    protected $status;

    /** @var CurrencyAmount */
    protected $totalAmount;

    /** @var string */
    protected $type;

    /** @var DateTime timestamp record was updated */
    protected $updatedAt;

    /**
     * Gets the date at which the transaction was created.
     *
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Gets the customer object.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Gets the transaction notes.
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Gets the order object.
     *
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Gets the payment method object.
     *
     * @return AbstractPaymentMethod|null
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Gets the date at which the transaction was last updated.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Gets the remote ID.
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * Gets the remote parent ID.
     *
     * @return string|null
     */
    public function getRemoteParentId()
    {
        return $this->remoteParentId;
    }

    /**
     * Gets the parent type.
     *
     * @return string|null
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * Gets the result code.
     *
     * @return string|null
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }

    /**
     * Gets the result message.
     *
     * @return string|null
     */
    public function getResultMessage()
    {
        return $this->resultMessage;
    }

    /**
     * Gets the source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Gets the status.
     *
     * @return TransactionStatusContract|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the total amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Gets the transaction type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the provider name.
     *
     * @return string|null
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * Sets the date at which the transaction was created.
     *
     * @param DateTime $createdAt
     * @return AbstractTransaction
     */
    public function setCreatedAt(DateTime $createdAt) : AbstractTransaction
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Sets the customer object.
     *
     * @param Customer $customer
     * @return AbstractTransaction
     */
    public function setCustomer(Customer $customer) : AbstractTransaction
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Sets the notes.
     *
     * @param string $notes
     * @return AbstractTransaction
     */
    public function setNotes(string $notes) : AbstractTransaction
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Sets the order object.
     *
     * @param Order $order
     * @return AbstractTransaction
     */
    public function setOrder(Order $order) : AbstractTransaction
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Sets the payment method object.
     *
     * @param AbstractPaymentMethod $paymentMethod
     * @return AbstractTransaction
     */
    public function setPaymentMethod(AbstractPaymentMethod $paymentMethod) : AbstractTransaction
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Sets the date at which the transaction was last updated.
     *
     * @param DateTime $updatedAt
     * @return AbstractTransaction
     */
    public function setUpdatedAt(DateTime $updatedAt) : AbstractTransaction
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Sets the remote ID.
     *
     * @param string $remoteId
     * @return AbstractTransaction
     */
    public function setRemoteId(string $remoteId) : AbstractTransaction
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * Sets the remote parent ID.
     *
     * @param string $remoteParentId
     * @return AbstractTransaction
     */
    public function setRemoteParentId(string $remoteParentId) : AbstractTransaction
    {
        $this->remoteParentId = $remoteParentId;

        return $this;
    }

    /**
     * Sets the parent type.
     *
     * @param string $value
     * @return self
     */
    public function setParentType(string $value) : AbstractTransaction
    {
        $this->parentType = $value;

        return $this;
    }

    /**
     * Sets the result code.
     *
     * @param string $resultCode
     * @return AbstractTransaction
     */
    public function setResultCode(string $resultCode) : AbstractTransaction
    {
        $this->resultCode = $resultCode;

        return $this;
    }

    /**
     * Sets the result message.
     *
     * @param string $resultMessage
     * @return AbstractTransaction
     */
    public function setResultMessage(string $resultMessage) : AbstractTransaction
    {
        $this->resultMessage = $resultMessage;

        return $this;
    }

    /**
     * Sets the source.
     *
     * @param string $source
     * @return AbstractTransaction
     */
    public function setSource(string $source) : AbstractTransaction
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets the status.
     *
     * @param TransactionStatusContract $status
     * @return AbstractTransaction
     */
    public function setStatus(TransactionStatusContract $status) : AbstractTransaction
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Sets the total amount.
     *
     * @param CurrencyAmount $totalAmount
     * @return AbstractTransaction
     */
    public function setTotalAmount(CurrencyAmount $totalAmount) : AbstractTransaction
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Sets the provider name.
     *
     * @param string $value
     * @return AbstractTransaction
     */
    public function setProviderName(string $value) : AbstractTransaction
    {
        $this->providerName = $value;

        return $this;
    }
}
