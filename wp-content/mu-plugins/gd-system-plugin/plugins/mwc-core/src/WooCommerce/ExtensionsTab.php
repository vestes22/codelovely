<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

class ExtensionsTab
{
    use IsConditionalFeatureTrait;

    /** @var string Tab slug name. */
    const SLUG = 'available_extensions';

    /** @var string Slug for the WooCommerce > Extensions page. */
    const EXTENSIONS_PAGE_SLUG = 'wc-addons';

    /** @var string Slug for the Helper section in the Browse Extensions tab. */
    const HELPER_SECTION_SLUG = 'helper';

    /** @var string Slug for the Featured section in the Browse Extensions tab. */
    const FEATURED_SECTION_SLUG = '_featured';

    /** @var string Slug for the WooCommerce.com Subscriptions tab. */
    const SUBSCRIPTIONS_TAB_SLUG = 'subscriptions';

    /**
     * Class constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'init'])
            ->setPriority(10)
            ->execute();
    }

    /**
     * Initialize the script.
     *
     * @action init
     *
     * @throws Exception
     */
    public function init()
    {
        if (! static::shouldLoadConditionalFeature() || ! ManagedExtensionsRepository::getManagedPlugins()) {
            return;
        }

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueScripts'])
            ->execute();

        // @TODO: Can we scope this to not run on every http_response? {JO: 2021-02-22}
        Register::filter()
            ->setGroup('http_response')
            ->setHandler([$this, 'maybeRemoveManagedPluginsFromBrowser'])
            ->setPriority(10)
            ->setArgumentsCount(3)
            ->execute();

        // remove managed plugins if present in the featured transient data
        Register::filter()
            ->setGroup('transient_wc_addons_featured')
            ->setHandler([$this, 'maybeRemoveManagedPluginsFromFeatured'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        // remove managed plugins before setting the featured transient data
        Register::filter()
            ->setGroup('pre_set_site_transient_wc_addons_featured')
            ->setHandler([$this, 'maybeRemoveManagedPluginsFromFeatured'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();
    }

    /**
     * Enqueues the WooCommerce extensions scripts.
     *
     * @internal
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function enqueueScripts()
    {
        if ('wc-addons' !== filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING)) {
            return;
        }

        Enqueue::script()
            ->setHandle('mwc-extensions')
            ->setSource(WordPressRepository::getAssetsUrl('js/woocommerce-extensions.min.js'))
            ->setDependencies(['jquery'])
            ->setVersion(Configuration::get('mwc.version'))
            ->setDeferred(true)
            ->attachInlineScriptObject('MWCExtensions')
            ->attachInlineScriptVariables([
                'plugins'             => $this->prepareEnqueuedPluginsData(),
                'isSubscriptionsPage' => $this->isSubscriptionTabActive(),
            ])
            ->execute();
    }

    /**
     * Converts plugins data as an associative array.
     *
     * @since 2.0.0
     *
     * @return array
     * @throws Exception
     */
    private function prepareEnqueuedPluginsData() : array
    {
        $plugin_data = [];

        foreach (ManagedExtensionsRepository::getManagedPlugins() as $plugin) {
            $plugin_data[] = $plugin->toArray();
        }

        return $plugin_data;
    }

    /**
     * Attempts to remove managed plugins from the response data use in the Browse Extensions tab.
     *
     * @internal
     *
     * @since 2.10.0
     *
     * @param array $response raw HTTP response
     * @param array $args HTTP request arguments
     * @param string $url request URL
     *
     * @return array
     * @throws Exception
     */
    public function maybeRemoveManagedPluginsFromBrowser($response, $args, $url)
    {
        if (0 !== strpos($url, 'https://woocommerce.com/wp-json/wccom-extensions/1.0/search')) {
            return $response;
        }

        if (! $this->shouldRemoveManagedPluginsFromBrowser()) {
            return $response;
        }

        return $this->removeManagedPluginsFromBrowser($response);
    }

    /**
     * Determines whether the plugin should remove managed plugins on the current page.
     *
     * @since 2.10.0
     *
     * @return bool
     */
    private function shouldRemoveManagedPluginsFromBrowser()
    {
        if (self::EXTENSIONS_PAGE_SLUG !== ArrayHelper::get($_GET, 'page')) {
            return false;
        }

        if (self::HELPER_SECTION_SLUG === ArrayHelper::get($_GET, 'section') || self::FEATURED_SECTION_SLUG === ArrayHelper::get($_GET, 'section')) {
            return false;
        }

        if (self::SLUG === ArrayHelper::get($_GET, 'tab')) {
            return false;
        }

        return true;
    }

    /**
     * Removes managed plugins from the given HTTP response data.
     *
     * @since 2.10.0
     *
     * @param array $response raw HTTP response data
     *
     * @return array
     * @throws Exception
     */
    private function removeManagedPluginsFromBrowser(array $response)
    {
        if (! $data = json_decode(ArrayHelper::get($response, 'body'))) {
            return $response;
        }

        if (! isset($data->products) || ! ArrayHelper::accessible($data->products)) {
            return $response;
        }

        $data->products = $this->excludeProductsWithKnownPluginSlugs($data->products, $this->getPluginSlugs());

        $response['body'] = json_encode($data);

        return $response;
    }

    /**
     * Attempts to remove managed plugins from the featured sections of the browse tab.
     *
     * @since 2.10.0
     *
     * @param mixed $transient cached featured plugins data, ideally an object
     *
     * @return mixed
     * @throws Exception
     */
    public function maybeRemoveManagedPluginsFromFeatured($transient)
    {
        if (! $this->shouldModifyFeaturedTransient($transient)) {
            return $transient;
        }

        $pluginBasenames = array_map(static function ($plugin) {
            return $plugin->getBasename();
        }, ManagedExtensionsRepository::getManagedPlugins());

        foreach ($transient->sections as $key => &$section) {
            if (! property_exists($section, 'items') || ! ArrayHelper::accessible($section->items)) {
                continue;
            }

            $section->items = ArrayHelper::where($section->items, function ($item) use ($pluginBasenames) {
                return ! isset($item->plugin) || ! in_array($item->plugin, $pluginBasenames);
            });

            if (empty($section->items)) {
                unset($transient->sections[$key]);
            }
        }

        return $transient;
    }

    /**
     * Check if a given update list is valid.
     *
     * @param mixed $transient
     *
     * @return bool
     */
    private function shouldModifyFeaturedTransient($transient) : bool
    {
        if (! $this->hasFeaturedPlugins()) {
            return false;
        }

        if (! is_object($transient) || ! property_exists($transient, 'sections') || ! ArrayHelper::accessible($transient->sections)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given page contains featured plugins.
     *
     * @return bool
     */
    private function hasFeaturedPlugins() : bool
    {
        if ('browse_extensions' === ArrayHelper::get($_GET, 'tab') && ! ArrayHelper::get($_GET, 'section')) {
            return true;
        }

        if (self::FEATURED_SECTION_SLUG === ArrayHelper::get($_GET, 'section')) {
            return true;
        }

        return false;
    }

    /**
     * Filters a list of products to remove those that have a matching slug.
     *
     * @since 2.10.0
     *
     * @param array $products list of product objects to filter
     * @param array $plugin_slugs list of slugs to exclude
     *
     * @return array
     */
    private function excludeProductsWithKnownPluginSlugs(array $products, array $plugin_slugs)
    {
        return array_values(ArrayHelper::where($products, function ($product) use ($plugin_slugs) {
            return ! ArrayHelper::exists($plugin_slugs, $product->slug ?? '');
        }));
    }

    /**
     * Gets a list of slugs for managed plugins.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    private function getPluginSlugs()
    {
        return array_fill_keys(array_map(function (PluginExtension $plugin) {
            return $plugin->getSlug();
        }, ManagedExtensionsRepository::getManagedPlugins()), true);
    }

    /**
     * Determines whether the current active tab is the WooCommerce.com Subscriptions.
     *
     * @since 2.0.0
     *
     * @return bool
     */
    protected function isSubscriptionTabActive() : bool
    {
        return self::SUBSCRIPTIONS_TAB_SLUG === ArrayHelper::get($_GET, 'tab') || 'helper' === ArrayHelper::get($_GET, 'section');
    }

    /**
     * Determines whether the feature should load.
     *
     * Implements {@see IsConditionalFeatureTrait::shouldLoadConditionalFeature()}.
     * Returns true if WooCommerce is active and the current site has an eCommerce plan.
     *
     * @since 2.10.0
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature(): bool
    {
        return WooCommerceRepository::isWooCommerceActive() && ManagedWooCommerceRepository::hasEcommercePlan();
    }
}
