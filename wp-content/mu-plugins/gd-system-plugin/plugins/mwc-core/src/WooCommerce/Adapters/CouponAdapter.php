<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CouponAdapter as CommonCouponAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Coupon as CommonCoupon;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Coupon;

/**
 * Coupon adapter.
 *
 * Converts between a native core coupon object and a WooCommerce coupon object.
 */
class CouponAdapter extends CommonCouponAdapter
{
    /** @var string overrides the common coupon class with the core coupon class */
    protected $couponClass = Coupon::class;

    /**
     * Converts a WooCommerce coupon object into a native core coupon object.
     *
     * @return CommonCoupon
     * @throws Exception
     */
    public function convertFromSource() : CommonCoupon
    {
        /** @var Coupon $coupon */
        $coupon = parent::convertFromSource();

        $coupon->setUniqueUrl($this->source->get_meta('_wc_url_coupons_unique_url'));
        $coupon->setRedirectPageId((int) $this->source->get_meta('_wc_url_coupons_redirect_page'));
        $coupon->setRedirectPageType($this->source->get_meta('_wc_url_coupons_existing_page_type'));
        $coupon->setProductsToAddToCart(ArrayHelper::wrap($this->source->get_meta('_wc_url_coupons_product_ids')));
        $coupon->setDeferApply('yes' === $this->source->get_meta('_wc_url_coupons_defer_apply'));

        return $coupon;
    }
}
