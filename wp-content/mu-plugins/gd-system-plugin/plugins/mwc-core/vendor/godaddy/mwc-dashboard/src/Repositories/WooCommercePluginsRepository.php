<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Repositories;

use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * WooCommerce plugins repository class.
 *
 * Provides methods for getting WooCommerce extensions installed independently (non-managed).
 *
 * @deprecated Consider use of the ManagedExtensionsRepository and WooCommerceRepository instead or adding the needed functionality in the common package
 */
class WooCommercePluginsRepository
{
    /**
     * Gets plugin data for locally installed non-managed plugins.
     *
     * @return array
     */
    public static function getLocalWooPluginsData(): array
    {
        return \WC_Helper::get_local_woo_plugins();
    }

    /**
     * Gets plugin data for a locally installed non-managed plugin by its slug.
     *
     * @param string $pluginSlug
     * @return array
     */
    public static function getPluginDataBySlug(string $pluginSlug) : array
    {
        $pluginsData = ArrayHelper::where(static::getLocalWooPluginsData(), function ($wooCommercePluginData) use ($pluginSlug) {
            return $pluginSlug === $wooCommercePluginData['slug'];
        });

        return ! empty($pluginsData) ? current($pluginsData) : [];
    }

    /**
     * Gets all locally installed SkyVerge non-managed WooCommerce plugins.
     *
     * @return PluginExtension[]
     */
    public static function getWooCommerceSkyVergePlugins() : array
    {
        $plugins = [];

        foreach (static::getLocalWooPluginsData() as $wooCommercePluginData) {
            if (! isset($wooCommercePluginData['_product_id']) || 'SkyVerge' !== ($wooCommercePluginData['Author'] ?? null)) {
                continue;
            }

            $plugin = new PluginExtension();
            $plugin->setProperties([
                'id' => $wooCommercePluginData['_product_id'] ?? '',
                'slug' => $wooCommercePluginData['slug'] ?? '',
                'name' => $wooCommercePluginData['Name'] ?? '',
                'shortDescription' => $wooCommercePluginData['Description'] ?? '',
                'minimumPhpVersion' => $wooCommercePluginData['RequiresPHP'] ?? '',
                'minimumWordPressVersion' => $wooCommercePluginData['RequiresWP'] ?? '',
                'minimumWooCommerceVersion' => $wooCommercePluginData['WC requires at least'] ?? '',
                'homepageUrl' => $wooCommercePluginData['PluginURI'] ?? '',
                'documentationUrl' => $wooCommercePluginData['Documentation URI'] ?? '',
                'basename' => $wooCommercePluginData['_filename'] ?? '',
            ]);
            $plugins[] = $plugin;
        }

        return $plugins;
    }

    /**
     * Gets the plugin documentation URL.
     *
     * @param array $pluginData
     * @return string
     */
    public static function getDocumentationUrl(array $pluginData) : string
    {

        // try to get the documentation URL from the plugin header
        if (! empty($pluginData['Documentation URI'])) {
            return $pluginData['Documentation URI'];
        }

        // try to get the documentation URL from the plugin instance
        try {

            // only works for SkyVerge plugins that use the Framework
            if ('SkyVerge' === ($pluginData['Author'] ?? null) &&
                 // if the plugin is not active, we cannot call the plugin's get_documentation_url() method
                 is_plugin_active($pluginData['_filename'])) {
                $slug = $pluginData['slug'];

                $instanceMethod = str_replace('woocommerce', 'wc', str_replace('-', '_', $slug));

                if (is_callable($instanceMethod)) {
                    $pluginInstance = $instanceMethod();

                    if (method_exists($pluginInstance, 'get_documentation_url')) {
                        return $pluginInstance->get_documentation_url();
                    }
                }
            }
        } catch (\Exception $e) {
            // if we can't get the documentation URL from the plugin instance, fallback to the plugin URL
        }

        // fallback to the plugin URL
        return $pluginData['PluginURI'] ?: '';
    }

    /**
     * Gets the connected user's WooCommerce subscriptions.
     *
     * @return array
     */
    public static function getWooCommerceSubscriptions(): array
    {
        return \WC_Helper::get_subscriptions();
    }

    /**
     * Gets the connected site ID.
     *
     * @return int|false
     */
    public static function getWooCommerceConnectedSiteId()
    {
        $auth = \WC_Helper_Options::get('auth');

        if (! empty($auth['site_id'])) {
            return absint($auth['site_id']);
        }

        return false;
    }

    /**
     * Gets the plugin WooCommerce subscription.
     *
     * @param array $pluginData
     * @return mixed|bool
     */
    public static function getWooCommerceSubscription(array $pluginData)
    {
        $subscriptions = static::getWooCommerceSubscriptions();

        if (empty($pluginData) || empty($productId = $pluginData['_product_id'])) {
            return false;
        }

        $subscriptions = ArrayHelper::where($subscriptions, function ($value) use ($productId) {
            return $productId === $value['product_id'];
        });

        if (empty($subscriptions)) {
            // there is no subscription for this product
            return false;
        }

        return current($subscriptions);
    }

    /**
     * Gets the plugin WooCommerce license status, based on WC subscriptions and connections data.
     *
     * @param array $pluginData
     * @return string 'none', 'expired', 'active' or 'inactive'
     */
    public static function getWooCommerceLicense(array $pluginData) : string
    {
        $license = 'none';

        $subscription = static::getWooCommerceSubscription($pluginData);

        if (empty($subscription)) {
            // there is no subscription for this product
            return $license;
        }

        if (! WooCommerceRepository::isWooCommerceConnected()) {
            // not connected to WooCommerce.com
            return $license;
        }

        if (! empty($subscription['expired'])) {
            return 'expired';
        }

        if (! empty($siteId = static::getWooCommerceConnectedSiteId())) {
            $connected = in_array($siteId, $subscription['connections'], true);
            if ($connected) {
                return 'active';
            }
        }

        // license is not expired but it is not active for this site either
        return 'inactive';
    }

    /**
     * Gets a string representation of the WC subscription end.
     *
     * @param array $pluginData
     * @return string date in Y-m-d format, 'no subscription' or 'lifetime'
     */
    public static function getWooCommerceSubscriptionEnd(array $pluginData) : string
    {
        $subscription = static::getWooCommerceSubscription($pluginData);

        if (empty($subscription)) {
            return __('no subscription', 'mwc-dashboard');
        }

        if (! empty($subscription['lifetime'])) {
            return __('lifetime', 'mwc-dashboard');
        }

        if (! empty($subscription['expires'])) {
            try {
                $expiration = new \DateTime("@{$subscription['expires']}");

                return $expiration->format('Y-m-d');
            } catch (\Exception $exception) {
                // if the given expiration timestamp is not valid, we ignore it
            }
        }

        return '';
    }

    /**
     * Gets the plugin version from the WooCommerce data.
     *
     * @param array $pluginData
     * @return string
     */
    public static function getPluginVersion(array $pluginData)
    {
        return ! empty($pluginData['Version']) ? $pluginData['Version'] : '';
    }

    /**
     * Gets the plugin name from the WooCommerce data.
     *
     * @param array $pluginData
     * @return string
     */
    public static function getPluginName(array $pluginData)
    {
        return ! empty($pluginData['Name']) ? $pluginData['Name'] : '';
    }
}
