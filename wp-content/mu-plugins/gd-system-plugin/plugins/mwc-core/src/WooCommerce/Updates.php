<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use stdClass;

/**
 * Class Updates.
 *
 * @since 2.10.0
 */
class Updates
{
    use IsConditionalFeatureTrait;

    /**
     * Class constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if (! ManagedWooCommerceRepository::hasEcommercePlan()) {
            return;
        }

        if (ManagedExtensionsRepository::getManagedPlugins()) {
            Register::action()
                ->setGroup('admin_print_scripts')
                ->setHandler([$this, 'hideWooExtensionDetailsLinks'])
                ->setPriority(PHP_INT_MAX)
                ->execute();

            Register::filter()
                ->setGroup('pre_set_site_transient_update_plugins')
                ->setHandler([$this, 'addPluginExtensionsToUpdatesList'])
                ->setPriority(PHP_INT_MAX)
                ->execute();

            Register::filter()
                ->setGroup('upgrader_package_options')
                ->setHandler([$this, 'setPluginExtensionPackage'])
                ->setPriority(PHP_INT_MAX)
                ->execute();
        }

        if (ManagedExtensionsRepository::getManagedThemes()) {
            Register::filter()
                ->setGroup('pre_set_site_transient_update_themes')
                ->setHandler([$this, 'addThemeExtensionsToUpdatesList'])
                ->setPriority(PHP_INT_MAX)
                ->execute();
        }
    }

    /**
     * Intercepts the plugins update transient to inject self-served plugins.
     *
     * @filter pre_set_site_transient_update_themes - PHP_INT_MAX
     *
     * @param mixed $list
     *
     * @return mixed|stdClass
     * @throws Exception
     */
    public function addPluginExtensionsToUpdatesList($list)
    {
        if (! $this->isUpdateListValid($list)) {
            return $list;
        }

        foreach (ManagedExtensionsRepository::getInstalledManagedPlugins() as $plugin) {
            $itemVersion = $list->checked[$plugin->getBasename()];

            if ($itemVersion && version_compare($itemVersion, $plugin->getVersion(), '<')) {
                // @TODO: Should be removed in favor of ->toArray from the CanConvertToArrayTrait when parity confirmed {JO: 2021-02-21}
                $list->response[$plugin->getBasename()] = (object) [
                    'id'            => "w.org/plugins/{$plugin->getSlug()}",
                    'slug'          => $plugin->getSlug(),
                    'plugin'        => $plugin->getBasename(),
                    'new_version'   => $plugin->getVersion(),
                    'url'           => $plugin->getHomepageUrl(),
                    'package'       => $plugin->getPackageUrl(),
                    'icons'         => $plugin->getImageUrls(),
                    'banners'       => [],
                    'banners_rtl'   => [],
                    'tested'        => StringHelper::beforeLast(Configuration::get('wordpress.version') ?? '', '-beta'),
                    'requires_php'  => '',
                    'compatibility' => new stdClass(),
                ];
            }
        }

        return $list;
    }

    /**
     * Intercepts the plugin update package options to inject correct package url before downloading an update.
     *
     * @filter upgrader_package_options - PHP_INT_MAX
     *
     * @param mixed|array $options
     * @return mixed|array
     * @throws Exception
     */
    public function setPluginExtensionPackage($options)
    {
        $plugin = ArrayHelper::get($options, 'hook_extra.plugin');

        if (! $plugin) {
            return $options;
        }

        if ($managedPlugin = ManagedExtensionsRepository::getInstalledManagedPlugin($plugin)) {
            $options['package'] = $managedPlugin->getPackageUrl();
        }

        return $options;
    }

    /**
     * Intercepts the transient that holds available theme updates.
     *
     * @filter pre_set_site_transient_update_themes - PHP_INT_MAX
     *
     * @param mixed $list
     *
     * @return mixed|stdClass
     * @throws Exception
     */
    public function addThemeExtensionsToUpdatesList($list)
    {
        if (! $this->isUpdateListValid($list)) {
            return $list;
        }

        foreach (ManagedExtensionsRepository::getInstalledManagedThemes() as $theme) {
            $itemVersion = $list->checked[$theme->getSlug()];

            if ($itemVersion && version_compare($itemVersion, $theme->getVersion(), '<')) {
                // @TODO: Should be removed in favor of ->toArray from the CanConvertToArrayTrait when parity confirmed {JO: 2021-02-21}
                $list->response[$theme->getSlug()] = [
                    'download_link'         => $theme->getPackageUrl(),
                    'homepage'              => $theme->getHomepageUrl(),
                    'icons'                 => $theme->getImageUrls(),
                    'last_updated'          => date('Y-m-d', $theme->getLastUpdated()),
                    'name'                  => $theme->getName(),
                    'short_description'     => $theme->getShortDescription(),
                    'slug'                  => $theme->getSlug(),
                    'support_documentation' => $theme->getDocumentationUrl(),
                    'type'                  => $theme->getType(),
                    'version'               => $theme->getInstalledVersion(),
                    'new_version'           => $theme->getVersion(),
                    'url'                   => $theme->getHomepageUrl(),
                    'package'               => $theme->getPackageUrl(),
                ];
            }
        }

        return $list;
    }

    /**
     * Checks if a given update list is valid.
     *
     * @param mixed $list
     *
     * @return bool
     */
    private function isUpdateListValid($list) : bool
    {
        if (! is_object($list) || ! property_exists($list, 'checked') || ! ArrayHelper::accessible($list->checked)) {
            return false;
        }

        return true;
    }

    /**
     * Hide the WooCommerce extension view details links.
     *
     * @action admin_print_scripts - PHP_INT_MAX
     * @throws Exception
     */
    public function hideWooExtensionDetailsLinks()
    {
        if (! WordPressRepository::isCurrentPage(['update-core.php', 'plugins.php'])) {
            return;
        }

        $names = [];
        $styles = '';

        foreach (ManagedExtensionsRepository::getInstalledManagedPlugins() as $plugin) {
            $names[] = $plugin->getBasename();
            $styles .= sprintf("a[href*='%s']{display: none;}", esc_attr($plugin->getSlug()));
        }

        if (WordPressRepository::isCurrentPage('update-core.php')) {
            ?><style type="text/css"><?php echo wp_kses_post($styles); ?></style><?php
        }

        if (WordPressRepository::isCurrentPage('plugins.php')) {
            Enqueue::script()
                ->setHandle('remove-extensions-details')
                ->setSource(WordPressRepository::getAssetsUrl('js/hide-extensions-details.js'))
                ->setDeferred(true)
                ->attachInlineScriptObject('MWCExtensionsHideDetails')
                ->attachInlineScriptVariables(['names' => $names])
                ->execute();
        }
    }

    /**
     * Determines whether the feature should be loaded.
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
