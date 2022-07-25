<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Coupon as CommonCoupon;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CouponsRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\CouponAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\Contracts\InterceptorContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\NewWooCommerceObjectFlag;
use WC_Coupon;
use WP_Post;

/**
 * A WooCommerce interceptor to hook on coupon actions and filters.
 */
class CouponInterceptor implements InterceptorContract
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
                ->setGroup('wp_insert_post')
                ->setHandler([$this, 'onWpInsertPost'])
                ->setArgumentsCount(3)
                ->execute();

        Register::action()
                ->setGroup('woocommerce_coupon_options_save')
                ->setHandler([$this, 'onWooCommerceUpdateCoupon'])
                ->execute();
    }

    /**
     * Turns the new coupon flag on if the post created was a coupon.
     *
     * @param int|string $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     */
    public function onWpInsertPost($postId, $post, $isUpdate)
    {
        $this->maybeFlagNewCoupon($postId, $post, $isUpdate);
    }

    /**
     * Calls the core coupon CRUD methods.
     *
     * @param int $postId
     *
     * @throws Exception
     */
    public function onWooCommerceUpdateCoupon($postId)
    {
        if (! ($wcCoupon = CouponsRepository::get((int) $postId))) {
            return;
        }

        $newCouponFlag = $this->getNewCouponFlag($wcCoupon->get_id());

        $coupon = $this->getConvertedCoupon($wcCoupon);

        if ($newCouponFlag->isOn()) {
            $coupon->save();

            $newCouponFlag->turnOff();
        } else {
            $coupon->update();
        }
    }

    /**
     * Turns the new coupon flag on if the post created was a coupon.
     *
     * @param int|string $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     */
    protected function maybeFlagNewCoupon($postId, $post, $isUpdate)
    {
        if (! $isUpdate && $post->post_type === 'shop_coupon') {
            $this->getNewCouponFlag((int) $postId)->turnOn();
        }
    }

    /**
     * Gets the new coupon flag instance for the given coupon id.
     *
     * @param int $couponId
     * @return NewWooCommerceObjectFlag
     */
    protected function getNewCouponFlag(int $couponId) : NewWooCommerceObjectFlag
    {
        return new NewWooCommerceObjectFlag($couponId);
    }

    /**
     * Converts a WooCommerce coupon object into a native coupon object.
     *
     * @param WC_Coupon $coupon
     * @return CommonCoupon
     * @throws Exception
     */
    protected function getConvertedCoupon(WC_Coupon $coupon) : CommonCoupon
    {
        return (new CouponAdapter($coupon))->convertFromSource();
    }
}
