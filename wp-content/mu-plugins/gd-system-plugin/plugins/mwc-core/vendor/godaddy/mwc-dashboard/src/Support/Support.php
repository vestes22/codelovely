<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Support;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;

/**
 * Support bot helper.
 */
class Support
{
    /**
     * Checks whether or not the site is connected to the support bot.
     *
     * @return bool
     * @throws Exception
     */
    public static function isSupportConnected() : bool
    {
        $appName = static::getSupportAppName();
        $database = ArrayHelper::get($GLOBALS, 'wpdb');

        // look for WooCommerce API keys containing the support bot app name in their description
        $keys = $database->get_var($database->prepare("SELECT COUNT(key_id) FROM {$database->prefix}woocommerce_api_keys WHERE description LIKE %s", "{$appName}%"));

        return ! empty($keys);
    }

    /**
     * Gets the support bot app name.
     *
     * @return string
     * @throws Exception
     */
    public static function getSupportAppName()
    {
        return ManagedWooCommerceRepository::isReseller() ? Configuration::get('support.support_bot.app_name_reseller') : Configuration::get('support.support_bot.app_name');
    }

    /**
     * Gets the type of connection that should be formed with support.
     *
     * @return string `godaddy` or `reseller`
     * @throws Exception
     */
    public static function getConnectType()
    {
        return ManagedWooCommerceRepository::isReseller() ? 'reseller' : 'godaddy';
    }

    /**
     * Gets the WordPress site URL.
     *
     * We use this instead of {@see site_url()} to grab the unfiltered value and avoid double URL encoding.
     *
     * @NOTE There's no unique foolproof way to check the encoding of a URL.
     * However, we can safely assume that, if there is a colon in a string presumed to be the URL, then that string is likely not encoded.
     * This does not guarantee that the URL is not broken or the site has other issues.
     *
     * @return string
     */
    public static function getSiteUrl() : string
    {
        $siteUrl = get_option('siteurl');
        $siteUrl = is_string($siteUrl) ? set_url_scheme($siteUrl) : '';

        // the raw URL option from database shouldn't be encoded, however we can still perform a sanity check here to avoid double encoding
        if (StringHelper::contains($siteUrl, ':')) {
            $siteUrl = urlencode($siteUrl);
        }

        return urldecode($siteUrl);
    }

    /**
     * Gets the URL to connect the site to support.
     *
     * @return string
     * @throws Exception
     */
    public static function getConnectUrl() : string
    {
        $baseUrl = StringHelper::beforeLast(StringHelper::trailingSlash(Configuration::get('support.support_bot.connect_url')), '/');

        return "{$baseUrl}?".ArrayHelper::query([
            'context' => static::getConnectType(),
            'url'     => static::getSiteUrl(),
        ]);
    }
}
