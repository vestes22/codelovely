<?php

namespace GoDaddy\WordPress\MWC\Core\Pages\Plugins;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

/**
 * The included WooCommerce extensions tab.
 *
 * @since 2.10.0
 */
class IncludedWooCommerceExtensionsTab
{
    use IsConditionalFeatureTrait;

    /**
     * IncludedWooCommerceExtensionsTab constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->registerFilters();
    }

    /**
     * Registers filters.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    private function registerFilters()
    {
        Register::filter()
            ->setGroup('views_plugin-install')
            ->setHandler([$this, 'addView'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();
    }

    /**
     * Callback to add a view to the current view list.
     *
     * @internal
     *
     * @since 2.10.0
     *
     * @param mixed|array $views current view list
     *
     * @return mixed|array modified view list
     *
     * @throws Exception
     */
    public function addView($views)
    {
        if (! is_array($views)) {
            return $views;
        }

        // gets the current plugin-install-popular key index
        $keyIndex = array_search('plugin-install-popular', array_keys($views), true);

        // the new tab
        $pluginInstallGdIncludedTab = ['plugin-install-gd-included' => '<a href="/wp-admin/admin.php?page=wc-addons">'.__('Included WooCommerce Extensions', 'mwc-core').'</a>'];

        // adds the new tab in the proper index
        $views = ArrayHelper::combine(
            $keyIndex > -1 ? array_slice($views, 0, $keyIndex) : [],
            $pluginInstallGdIncludedTab,
            $keyIndex > -1 ? array_slice($views, $keyIndex) : $views
        );

        return $views;
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature(): bool
    {
        return WooCommerceRepository::isWooCommerceActive() && ManagedWooCommerceRepository::hasEcommercePlan();
    }
}
