<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Order_Refund;
use WP_Error;

/**
 * Repository for handling WooCommerce refunds.
 */
class RefundsRepository
{
    /**
     * Gets a WooCommerce refund object.
     *
     * @param int refund ID
     * @return WC_Order_Refund|null
     * @throws Exception
     */
    public static function get(int $id)
    {
        $refund = OrdersRepository::get($id);

        return $refund instanceof WC_Order_Refund ? $refund : null;
    }

    /**
     * Gets an array of WooCommerce order refund objects.
     *
     * @see OrdersRepository::query()
     * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query for accepted args and extended usage
     *
     * @param array $args
     * @return WC_Order_Refund[]
     * @throws Exception
     */
    public static function query(array $args = []) : array
    {
        $args['type'] = 'shop_order_refund';

        return OrdersRepository::query($args);
    }

    /**
     * Creates a WooCommerce refund.
     *
     * @param array $args
     * @return WC_Order_Refund
     * @throws Exception
     */
    public static function create(array $args = []) : WC_Order_Refund
    {
        /* translators: Placeholder: %s - error message */
        $errorMessage = __('Could not create refund: %s', 'mwc-core');

        if (! WooCommerceRepository::isWooCommerceActive()) {
            throw new Exception(sprintf($errorMessage, __('WooCommerce is not active', 'mwc-core')));
        }

        $refund = wc_create_refund($args);

        if (is_a($refund, WP_Error::class)) {
            throw new Exception(sprintf($errorMessage, $refund->get_error_message()));
        }

        return $refund;
    }
}
