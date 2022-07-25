<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Coupon;
use WC_Coupon;

/**
 * Coupon adapter.
 *
 * Converts between a native coupon object and a WooCommerce coupon object.
 */
class CouponAdapter implements DataSourceAdapterContract
{
    /** @var WC_Coupon WooCommerce coupon object */
    protected $source;

    /** @var string the coupon class name */
    protected $couponClass = Coupon::class;

    /**
     * Coupon adapter constructor.
     *
     * @param WC_Coupon $coupon WooCommerce coupon object
     */
    public function __construct(WC_Coupon $coupon)
    {
        $this->source = $coupon;
    }

    /**
     * Converts a WooCommerce coupon object into a native coupon object.
     *
     * @return Coupon
     * @throws Exception
     */
    public function convertFromSource() : Coupon
    {
        $coupon = (new $this->couponClass())
            ->setId($this->source->get_id())
            ->setCode($this->source->get_code())
            ->setDiscountType($this->source->get_discount_type())
            ->setDiscountAmount($this->source->get_amount())
            ->setAllowsFreeShipping($this->source->get_free_shipping());

        if ($expiryDate = $this->source->get_date_expires()) {
            $coupon->setExpiryDate(new DateTime($expiryDate->format('c')));
        }

        return $coupon;
    }

    /**
     * Converts a native coupon object into a WooCommerce coupon object.
     *
     * @param Coupon|null $coupon native coupon object to convert
     * @return WC_Coupon WooCommerce coupon object
     * @throws Exception
     */
    public function convertToSource(Coupon $coupon = null) : WC_Coupon
    {
        if (! $coupon instanceof Coupon) {
            return $this->source;
        }

        $this->source->set_id($coupon->getId());
        $this->source->set_code($coupon->getCode());
        $this->source->set_discount_type($coupon->getDiscountType());
        $this->source->set_amount($coupon->getDiscountAmount());
        $this->source->set_free_shipping($coupon->getAllowsFreeShipping());

        if ($expiryDate = $coupon->getExpiryDate()) {
            $this->source->set_date_expires($expiryDate->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
        }

        return $this->source;
    }
}
