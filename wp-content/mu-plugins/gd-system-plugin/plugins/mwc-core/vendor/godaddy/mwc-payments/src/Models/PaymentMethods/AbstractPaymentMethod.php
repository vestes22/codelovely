<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Traits\BillableTrait;

/**
 * Class AbstractPaymentMethod
 */
abstract class AbstractPaymentMethod
{
    use BillableTrait;

    /** @var DateTime timestamp record was created */
    protected $createdAt;

    /** @var string customer foreign key */
    protected $customerId;

    /** @var int primary key */
    protected $id;

    /** @var string payment method label */
    protected $label;

    /** @var string payment provider name */
    protected $providerName;

    /** @var string primary key in remote system */
    protected $remoteId;

    /** @var DateTime timestamp record was updated */
    protected $updatedAt;

    /**
     * Gets created at.
     *
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Gets the customer ID.
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Gets the ID.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets label.
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Gets the payment provider name.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getProviderName()
    {
        return $this->providerName;
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
     * Gets updated at.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets created at.
     *
     * @param DateTime $createdAt
     *
     * @return AbstractPaymentMethod
     */
    public function setCreatedAt(DateTime $createdAt) : AbstractPaymentMethod
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Sets the customer ID.
     *
     * @param string $customerId
     *
     * @return AbstractPaymentMethod
     */
    public function setCustomerId(string $customerId) : AbstractPaymentMethod
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * Sets the ID.
     *
     * @param int $id
     *
     * @return AbstractPaymentMethod
     */
    public function setId(int $id) : AbstractPaymentMethod
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets label.
     *
     * @param string $label
     *
     * @return AbstractPaymentMethod
     */
    public function setLabel(string $label) : AbstractPaymentMethod
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Sets the payment provider name.
     *
     * @param string $value
     *
     * @return AbstractPaymentMethod
     */
    public function setProviderName(string $value) : AbstractPaymentMethod
    {
        $this->providerName = $value;

        return $this;
    }

    /**
     * Sets the remote ID.
     *
     * @param string $remoteId
     *
     * @return AbstractPaymentMethod
     */
    public function setRemoteId(string $remoteId) : AbstractPaymentMethod
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * Sets updated at.
     *
     * @param DateTime $updatedAt
     *
     * @return AbstractPaymentMethod
     */
    public function setUpdatedAt(DateTime $updatedAt) : AbstractPaymentMethod
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
