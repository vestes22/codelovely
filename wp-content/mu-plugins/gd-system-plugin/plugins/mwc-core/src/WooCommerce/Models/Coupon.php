<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Models;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Models\Coupon as CommonCoupon;
use function GoDaddy\WordPress\MWC\UrlCoupons\wc_url_coupons;

/**
 * Core coupon object.
 */
class Coupon extends CommonCoupon
{
    /** @var string the unique URL that a customer can visit to have this coupon applied to their cart */
    protected $uniqueUrl = '';

    /** @var int the ID of the page to redirect the customer to after applying the coupon */
    protected $redirectPageId;

    /** @var string the type of page to redirect the customer to after applying the coupon */
    protected $redirectPageType;

    /** @var array products that are added to the cart when the coupon is applied */
    protected $productsToAddToCart = false;

    /** @var bool whether to defer applying the coupon until the customer's cart meets the coupon's requirements */
    protected $deferApply = false;

    /**
     * Gets the coupon unique URL.
     *
     * @return string
     */
    public function getUniqueUrl() : string
    {
        return $this->uniqueUrl;
    }

    /**
     * Sets the coupon unique URL.
     *
     * @param string $value
     * @return self
     */
    public function setUniqueUrl(string $value) : Coupon
    {
        $this->uniqueUrl = $value;

        return $this;
    }

    /**
     * Gets the coupon redirect page ID.
     *
     * @return int|null
     */
    public function getRedirectPageId()
    {
        return $this->redirectPageId;
    }

    /**
     * Sets the coupon redirect page ID.
     *
     * @param int $value
     * @return self
     */
    public function setRedirectPageId(int $value) : Coupon
    {
        $this->redirectPageId = $value;

        return $this;
    }

    /**
     * Gets the coupon redirect page type.
     *
     * @return string|null
     */
    public function getRedirectPageType()
    {
        return $this->redirectPageType;
    }

    /**
     * Sets the coupon redirect page type.
     *
     * @param string $value
     * @return self
     */
    public function setRedirectPageType(string $value) : Coupon
    {
        $this->redirectPageType = $value;

        return $this;
    }

    /**
     * Gets the products to add to the cart when the coupon is applied.
     *
     * @return array
     */
    public function getProductsToAddToCart()
    {
        return $this->productsToAddToCart;
    }

    /**
     * Sets the products to add to the cart when the coupon is applied.
     *
     * @param array $values
     * @return self
     */
    public function setProductsToAddToCart(array $values) : Coupon
    {
        $this->productsToAddToCart = $values;

        return $this;
    }

    /**
     * Gets whether to defer applying the coupon until the customer's cart meets the coupon's requirements.
     *
     * @return bool
     */
    public function getDeferApply() : bool
    {
        return $this->deferApply;
    }

    /**
     * Sets whether to defer applying the coupon until the customer's cart meets the coupon's requirements.
     *
     * @param bool $value
     * @return self
     */
    public function setDeferApply(bool $value) : Coupon
    {
        $this->deferApply = $value;

        return $this;
    }

    /**
     * Gets the redirect page URL based on its ID and type.
     *
     * @return string
     */
    public function getRedirectPageUrl()
    {
        // TODO: Update this method in Native URL Coupons V2 {@acastro1 2021-08-10}
        if (! is_callable('GoDaddy\WordPress\MWC\UrlCoupons\wc_url_coupons') || empty($this->redirectPageId)) {
            return '';
        }

        return wc_url_coupons()->get_object_url((int) $this->redirectPageId, $this->redirectPageType);
    }

    /**
     * Updates the coupon.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function update() : Coupon
    {
        parent::update();

        Events::broadcast($this->buildEvent('coupon', 'update'));

        return $this;
    }

    /**
     * Saves a new coupon.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : Coupon
    {
        parent::save();

        Events::broadcast($this->buildEvent('coupon', 'create'));

        return $this;
    }

    /**
     * Converts all model data properties to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $data = parent::toArray();

        unset($data['redirectPageId']);
        unset($data['redirectPageType']);
        $data['redirectPage'] = $this->getRedirectPageUrl();

        return $data;
    }
}
