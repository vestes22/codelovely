<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;

/**
 * Native coupon object.
 */
class Coupon extends AbstractModel
{
    use CanBulkAssignPropertiesTrait, CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }

    /** @var int|null unique ID */
    protected $id;

    /** @var string|null code */
    protected $code;

    /** @var string|null discount type */
    protected $discountType;

    /** @var float|null discount amount */
    protected $discountAmount;

    /** @var bool|null whether the coupon grants free shipping or not */
    protected $allowsFreeShipping;

    /** @var DateTime|null expiration date */
    protected $expiryDate;

    /**
     * Gets the coupon ID.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the coupon code.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Gets the coupon discount type.
     *
     * @return string|null
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Gets the coupon discount amount.
     *
     * @return float|null
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Returns whether the coupon grants free shipping or not.
     *
     * @return bool|null
     */
    public function getAllowsFreeShipping()
    {
        return $this->allowsFreeShipping;
    }

    /**
     * Gets the coupon expiration date.
     *
     * @return DateTime|null
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Sets the coupon ID.
     *
     * @param int $value
     * @return self
     */
    public function setId(int $value) : Coupon
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the coupon code.
     *
     * @param string $value
     * @return self
     */
    public function setCode(string $value) : Coupon
    {
        $this->code = $value;

        return $this;
    }

    /**
     * Sets the coupon discount type.
     *
     * @param string $value
     * @return self
     */
    public function setDiscountType(string $value) : Coupon
    {
        $this->discountType = $value;

        return $this;
    }

    /**
     * Sets the coupon discount amount.
     *
     * @param float $value
     * @return self
     */
    public function setDiscountAmount(float $value) : Coupon
    {
        $this->discountAmount = $value;

        return $this;
    }

    /**
     * Sets whether the coupon grants free shipping or not.
     *
     * @param bool $value
     * @return self
     */
    public function setAllowsFreeShipping(bool $value) : Coupon
    {
        $this->allowsFreeShipping = $value;

        return $this;
    }

    /**
     * Sets the coupon expiration date.
     *
     * @param DateTime $value
     * @return self
     */
    public function setExpiryDate(DateTime $value) : Coupon
    {
        $this->expiryDate = $value;

        return $this;
    }

    /**
     * Converts all model data properties to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        if ($expiryDate = $this->getExpiryDate()) {
            $data['expiryDate'] = $expiryDate->format('Y-m-d');
        }

        return $data;
    }
}
