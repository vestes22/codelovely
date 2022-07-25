<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use WooCommerce;

/**
 * WooCommerce repository class.
 */
class WooCommerceRepository
{
    /**
     * Retrieve the current WooCommerce instance.
     *
     * @return WooCommerce|null
     */
    public static function getInstance()
    {
        if (! static::isWooCommerceActive()) {
            return null;
        }

        return WC();
    }

    /**
     * Retrieves the configured WooCommerce country code.
     *
     * @return string
     */
    public static function getBaseCountry(): string
    {
        if (! empty($wc = static::getInstance()) && $wc->countries) {
            return $wc->countries->get_base_country();
        }

        return '';
    }

    /**
     * Retrieves the configured WooCommerce currency code.
     *
     * @return string
     */
    public static function getCurrency(): string
    {
        return static::isWooCommerceActive() ? get_woocommerce_currency() : '';
    }

    /**
     * Retrieves the current WooCommerce access token.
     *
     * @return string|null
     */
    public static function getWooCommerceAccessToken()
    {
        $authorization = self::getWooCommerceAuthorization();

        return ArrayHelper::get($authorization, 'access_token');
    }

    /**
     * Retrieves the current WooCommerce Authorization Object.
     *
     * @return array|null
     */
    public static function getWooCommerceAuthorization()
    {
        if (class_exists('WC_Helper_Options')) {
            return \WC_Helper_Options::get('auth');
        }

        return null;
    }

    /**
     * Checks if the WooCommerce plugin is active.
     *
     * @return bool
     */
    public static function isWooCommerceActive() : bool
    {
        return null !== Configuration::get('woocommerce.version') && class_exists(WooCommerce::class);
    }

    /**
     * Checks if the site is connected to WooCommerce.com.
     *
     * @return bool
     */
    public static function isWooCommerceConnected() : bool
    {
        return self::isWooCommerceActive() && self::getWooCommerceAccessToken();
    }

    /**
     * Checks whether the current page is a WooCommerce admin page.
     *
     * This method should return true for all admin pages that have a URL like
     * /wp-admin/admin.php?page=wc-admin&path={somepath} (where path is optional).
     *
     * @param string|null $path optional string to compare with the path query parameter
     * @return bool
     */
    public static function isWooCommerceAdminPage(string $path = null) : bool
    {
        if (! $screen = get_current_screen()) {
            return false;
        }

        if ($screen->base !== 'woocommerce_page_wc-admin') {
            return false;
        }

        return ! $path || $path === ArrayHelper::get($_REQUEST, 'path', '');
    }

    /**
     * Determines if the current page is the WooCommerce cart page.
     *
     * @return bool
     */
    public static function isCartPage() : bool
    {
        return static::isWooCommerceActive() && is_cart();
    }

    /**
     * Determines if the current page is the WooCommerce checkout page.
     *
     * @return bool
     */
    public static function isCheckoutPage() : bool
    {
        return static::isWooCommerceActive() && is_checkout();
    }

    /**
     * Determines if the current page is the WooCommerce checkout pay page.
     *
     * @return bool
     */
    public static function isCheckoutPayPage() : bool
    {
        return static::isWooCommerceActive() && is_checkout_pay_page();
    }

    /**
     * Determines if the current page is a WooCommerce product page.
     *
     * @return bool
     */
    public static function isProductPage() : bool
    {
        return static::isWooCommerceActive() && is_product();
    }
}
