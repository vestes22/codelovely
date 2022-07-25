<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Order;

/**
 * Repository for handling WooCommerce orders.
 */
class OrdersRepository
{
    /**
     * Gets a WooCommerce order object.
     *
     * @param int order ID
     * @return WC_Order|null
     * @throws Exception
     */
    public static function get(int $id)
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return null;
        }

        return wc_get_order($id) ?: null;
    }

    /**
     * Gets an array of WooCommerce order objects.
     *
     * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query for accepted args and extended usage
     *
     * @param array $args
     * @return WC_Order[]
     * @throws Exception
     */
    public static function query(array $args = []) : array
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return [];
        }

        return (array) wc_get_orders($args);
    }

    /**
     * Gets a list of WooCommerce statuses which are considered "paid".
     *
     * @return string[] array of status slugs
     */
    public static function getPaidStatuses() : array
    {
        return (array) wc_get_is_paid_statuses();
    }
}
