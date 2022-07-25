<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

/**
 * Repository for handling WooCommerce products.
 *
 * @since 3.4.1
 */
class ProductsRepository
{
    /**
     * Gets a WooCommerce product object.
     *
     * @since 3.4.1
     *
     * @param int $id product ID
     * @return \WC_Product|null
     */
    public static function get(int $id)
    {
        return wc_get_product($id) ?: null;
    }
}
