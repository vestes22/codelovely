<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\BillableTrait;
use GoDaddy\WordPress\MWC\Common\Traits\FulfillableTrait;
use GoDaddy\WordPress\MWC\Common\Traits\PayableTrait;
use GoDaddy\WordPress\MWC\Common\Traits\ShippableTrait;

/**
 * Native order object.
 */
class Order extends AbstractModel
{
    use BillableTrait;
    use FulfillableTrait;
    use PayableTrait;
    use ShippableTrait;
    /** @var int|null */
    protected $id;

    /** @var string|null */
    protected $number;

    /** @var OrderStatusContract|null */
    protected $status;

    /** @var DateTime|null date created */
    protected $createdAt;

    /** @var DateTime|null date updated */
    protected $updatedAt;

    /** @var int|null */
    protected $customerId;

    /** @var string|null */
    protected $customerIpAddress;

    /** @var LineItem[] */
    protected $lineItems = [];

    /** @var CurrencyAmount|null */
    protected $lineAmount;

    /** @var ShippingItem[] */
    protected $shippingItems = [];

    /** @var CurrencyAmount|null */
    protected $shippingAmount;

    /** @var FeeItem[] */
    protected $feeItems = [];

    /** @var CurrencyAmount|null */
    protected $feeAmount;

    /** @var TaxItem[] */
    protected $taxItems = [];

    /** @var CurrencyAmount|null */
    protected $taxAmount;

    /** @var CurrencyAmount|null */
    protected $totalAmount;

    /**
     * Gets the order ID.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the order number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Gets the order status.
     *
     * @return OrderStatusContract|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the date when the order was created.
     *
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Gets the date when the order was last updated.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Gets the ID of the customer associated with the order.
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Gets the IP of the customer associated with the order.
     *
     * @return string|null
     */
    public function getCustomerIpAddress()
    {
        return $this->customerIpAddress;
    }

    /**
     * Gets the order line items.
     *
     * @return LineItem[]
     */
    public function getLineItems() : array
    {
        return $this->lineItems;
    }

    /**
     * Gets the line items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getLineAmount()
    {
        return $this->lineAmount;
    }

    /**
     * Gets the order shipping items.
     *
     * @return ShippingItem[]
     */
    public function getShippingItems() : array
    {
        return $this->shippingItems;
    }

    /**
     * Gets the shipping items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getShippingAmount()
    {
        return $this->shippingAmount;
    }

    /**
     * Gets the order fee items.
     *
     * @return FeeItem[]
     */
    public function getFeeItems() : array
    {
        return $this->feeItems;
    }

    /**
     * Gets the fee items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getFeeAmount()
    {
        return $this->feeAmount;
    }

    /**
     * Gets the order tax items.
     *
     * @return TaxItem[]
     */
    public function getTaxItems() : array
    {
        return $this->taxItems;
    }

    /**
     * Gets the tax items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * Gets the order total amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Sets the order ID.
     *
     * @param int $value
     * @return self
     */
    public function setId(int $value) : Order
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the order number.
     *
     * @param string $value
     * @return self
     */
    public function setNumber(string $value) : Order
    {
        $this->number = $value;

        return $this;
    }

    /**
     * Sets the order status.
     *
     * @param OrderStatusContract $value
     * @return self
     */
    public function setStatus(OrderStatusContract $value) : Order
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Sets the date when the order was created.
     *
     * @param DateTime $value
     * @return self
     */
    public function setCreatedAt(DateTime $value) : Order
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Sets the date when the order was last updated.
     *
     * @param DateTime $value
     * @return self
     */
    public function setUpdatedAt(DateTime $value) : Order
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Sets the ID of the customer associated with the order.
     *
     * @param int $value
     * @return self
     */
    public function setCustomerId(int $value) : Order
    {
        $this->customerId = $value;

        return $this;
    }

    /**
     * Sets the IP address of the customer associated with the order.
     *
     * @param string $value IP address
     * @return self
     */
    public function setCustomerIpAddress(string $value) : Order
    {
        $this->customerIpAddress = $value;

        return $this;
    }

    /**
     * Sets the order line items.
     *
     * @param LineItem[] $value
     *
     * @return self
     */
    public function setLineItems(array $value) : Order
    {
        $this->lineItems = $value;

        return $this;
    }

    /**
     * Sets the line items amount.
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setLineAmount(CurrencyAmount $value) : Order
    {
        $this->lineAmount = $value;

        return $this;
    }

    /**
     * Sets the order shipping items.
     *
     * @param ShippingItem[] $value
     * @return self
     */
    public function setShippingItems(array $value) : Order
    {
        $this->shippingItems = $value;

        return $this;
    }

    /**
     * Sets the shipping items amount.
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setShippingAmount(CurrencyAmount $value) : Order
    {
        $this->shippingAmount = $value;

        return $this;
    }

    /**
     * Sets the order fee items.
     *
     * @param FeeItem[] $value
     * @return self
     */
    public function setFeeItems(array $value) : Order
    {
        $this->feeItems = $value;

        return $this;
    }

    /**
     * Sets the fee items amount.
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setFeeAmount(CurrencyAmount $value) : Order
    {
        $this->feeAmount = $value;

        return $this;
    }

    /**
     * Sets the order tax items.
     *
     * @param TaxItem[] $value
     * @return self
     */
    public function setTaxItems(array $value) : Order
    {
        $this->taxItems = $value;

        return $this;
    }

    /**
     * Sets the tax items amount.
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setTaxAmount(CurrencyAmount $value) : Order
    {
        $this->taxAmount = $value;

        return $this;
    }

    /**
     * Sets the order total amount.
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setTotalAmount(CurrencyAmount $value) : Order
    {
        $this->totalAmount = $value;

        return $this;
    }
}
