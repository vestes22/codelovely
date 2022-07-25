<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use WC_Coupon;

/**
 * Repository for handling WooCommerce coupons.
 */
class CouponsRepository
{
    /**
     * Determines if coupons are enabled.
     *
     * @return bool
     */
    public static function couponsEnabled() : bool
    {
        return wc_coupons_enabled();
    }

    /**
     * Gets a WooCommerce coupon object.
     *
     * @param int|string $identifier coupon identifier, like an ID or code
     * @return WC_Coupon|null
     */
    public static function get($identifier)
    {
        $coupon = new WC_Coupon($identifier);

        return is_callable([$coupon, 'get_id']) && $coupon->get_id() ? $coupon : null;
    }
}
