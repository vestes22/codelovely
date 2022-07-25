<?php

namespace GoDaddy\WordPress\MWC\Payments\Models;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Traits\BillableTrait;
use GoDaddy\WordPress\MWC\Common\Traits\ShippableTrait;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * Customer model.
 *
 * @since 0.1.0
 */
class Customer extends AbstractModel
{
    use BillableTrait;
    use ShippableTrait;

    /** @var bool convert Private Properties to Array Output */
    protected $toArrayIncludePrivate = true;

    /** @var int primary key */
    private $id;

    /** @var AbstractPaymentMethod[] owned payment methods */
    private $paymentMethods = [];

    /** @var string primary key in remote system */
    private $remoteId;

    /** @var User native user object */
    private $user;

    /**
     * Sets the ID.
     *
     * @since 0.1.0
     *
     * @param int $id
     *
     * @return self
     */
    public function setId(int $id) : Customer
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the payment methods.
     *
     * @since 0.1.0
     *
     * @param AbstractPaymentMethod[] $paymentMethods
     *
     * @return self
     */
    public function setPaymentMethods(array $paymentMethods) : Customer
    {
        $this->paymentMethods = $paymentMethods;

        return $this;
    }

    /**
     * Sets the remote ID.
     *
     * @since 0.1.0
     *
     * @param string $remoteId
     *
     * @return self
     */
    public function setRemoteId(string $remoteId) : Customer
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * Set the user object.
     *
     * @since 0.1.0
     *
     * @param User $user
     *
     * @return self
     */
    public function setUser(User $user) : Customer
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets the ID.
     *
     * @since 0.1.0
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the payment methods.
     *
     * @since 0.1.0
     *
     * @return AbstractPaymentMethod[]
     */
    public function getPaymentMethods() : array
    {
        return $this->paymentMethods;
    }

    /**
     * Gets the remote ID.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * Gets the user object.
     *
     * @since 0.1.0
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Saves a new customer.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : Customer
    {
        parent::save();

        Events::broadcast($this->buildEvent('customer', 'create'));

        return $this;
    }

    /**
     * Updates the customer.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function update() : Customer
    {
        parent::update();

        Events::broadcast($this->buildEvent('customer', 'update'));

        return $this;
    }
}
