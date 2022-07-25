<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CostOfGoods;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\Events\ButtonClickedEvent;
use GoDaddy\WordPress\MWC\Core\Events\FeatureEnabledEvent;
use GoDaddy\WordPress\MWC\Core\Events\PluginDeactivatedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyBranding;
use function GoDaddy\WordPress\MWC\CostOfGoods\wc_cog;
use GoDaddy\WordPress\MWC\CostOfGoods\WC_COG_Loader;

/**
 * The Cost of Goods feature loader.
 *
 * @since 2.15.0
 */
class CostOfGoods
{
    use IsConditionalFeatureTrait;

    /** @var string the plugin name */
    protected static $communityPluginName = 'woocommerce-cost-of-goods/woocommerce-cost-of-goods.php';

    /** @var string the community plugin slug */
    protected static $communityPluginSlug = 'woocommerce-cost-of-goods';

    /**
     * Constructs the class and loads the Cost of Goods feature.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $rootVendorPath = StringHelper::trailingSlash(StringHelper::before(__DIR__, 'src').'vendor');

        $this->loadRequiredFeatureFiles($rootVendorPath);

        WC_COG_Loader::instance()->init_plugin();

        $this->registerHooks();
    }

    /**
     * Registers hooks.
     *
     * @since 2.15.0
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDeactivateCostOfGoodsPlugin'])
            ->execute();

        Register::action()
                ->setGroup('admin_footer')
                ->setHandler([$this, 'addGoDaddyBrandingStyles'])
                ->setCondition([$this, 'shouldAddGoDaddyBranding'])
                ->execute();

        Register::action()
                ->setGroup('admin_footer')
                ->setHandler([GoDaddyBranding::getInstance(), 'render'])
                ->setCondition([$this, 'shouldAddGoDaddyBranding'])
                ->execute();

        Register::action()
            ->setGroup('wp_ajax_wc_cog_apply_costs_to_previous_orders')
            ->setHandler([$this, 'broadcastApplyCostsToPreviousOrdersEvent'])
            ->setArgumentsCount(0)
            ->execute();

        Register::action()
                ->setGroup('load-plugins.php')
                ->setHandler([$this, 'removePluginUpdateNotice'])
                ->setPriority(PHP_INT_MAX)
                ->setCondition([self::class, 'shouldLoadConditionalFeature'])
                ->execute();
    }

    /**
     * Removes the WP action that displays the plugin update notice below each plugin on the Plugins page.
     */
    public function removePluginUpdateNotice()
    {
        remove_action('after_plugin_row_'.static::$communityPluginName, 'wp_plugin_update_row');
    }

    /**
     * May deactivate the Cost of Goods plugin.
     *
     * @since 2.15.0
     *
     * @throws Exception
     */
    public function maybeDeactivateCostOfGoodsPlugin()
    {
        if (! static::isCogsPluginActive()) {
            return;
        }

        update_option('mwc_cost_of_goods_show_notice_cogs_users', 'yes');

        if (false === get_option('mwc_cost_of_goods_show_notice_reactivation_cogs', false)) {
            // the community plugin is being deactivated automatically for the 1st time, so this is not a reactivation attempt
            add_option('mwc_cost_of_goods_show_notice_reactivation_cogs', 'no', '', 'no');
        } else {
            // the community plugin was already deactivated automatically before, this is a reactivation attempt
            update_option('mwc_cost_of_goods_show_notice_reactivation_cogs', 'yes');
            // we want to display the notice again even it was previously dismissed
            wc_cog()->get_admin_notice_handler()->undismiss_notice(wc_cog()->get_id_dasherized().'-reactivation-cogs');
        }

        $this->deactivateCostOfGoodsPlugin();
    }

    /**
     * Deactivates the Cost of Goods plugin.
     *
     * @since 2.15.0
     *
     * @throws Exception
     */
    protected function deactivateCostOfGoodsPlugin()
    {
        if (! function_exists('deactivate_plugins')) {
            return;
        }

        deactivate_plugins(static::$communityPluginName);

        // unset GET param so that the "Plugin activated." notice is not displayed
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $pluginExtension = (new PluginExtension())
            ->setName(static::$communityPluginName)
            ->setSlug(static::$communityPluginSlug);

        Events::broadcast(new PluginDeactivatedEvent($pluginExtension));

        Events::broadcast(new FeatureEnabledEvent('cost_of_goods'));
    }

    /**
     * Checks if should add GoDaddy branding to module settings page.
     *
     * @since 3.0.0
     *
     * @throws Exception
     * @return bool
     */
    public function shouldAddGoDaddyBranding() : bool
    {
        return ! ManagedWooCommerceRepository::isReseller() &&
               wc_cog()->is_plugin_settings() &&
               // only add branding if another feature is not already adding it
               ! has_action('admin_footer', [GoDaddyBranding::getInstance(), 'render']);
    }

    /**
     * Adds the style tag used by the GoDaddy branding.
     *
     * @since 3.0.0
     */
    public function addGoDaddyBrandingStyles()
    {
        ob_start(); ?>
        <style>
            .mwc-gd-branding {
                position: absolute;
                bottom: 18px;
                left: 180px;
            }

            <?php
            // hide WordPress footer on older WooCommerce 3.x versions
            if (version_compare(WC_VERSION, '4.0', '<')) : ?>
            #wpfooter {
                display: none;
            }
            <?php endif; ?>

            @media screen and (max-width: 960px) {
                .mwc-gd-branding {
                    left: 55px;
                }
            }

            @media screen and (max-width: 782px) {
                .mwc-gd-branding {
                    left: 20px;
                }
            }
        </style>
        <?php

        (GoDaddyBranding::getInstance())->addStyle(ob_get_clean());
    }

    /**
     * Loads feature main file as well as SV Framework from root vendor folder.
     *
     * @since 2.15.0
     *
     * @param string $vendorPath
     */
    protected function loadRequiredFeatureFiles(string $vendorPath)
    {
        // load plugin class file
        require_once $vendorPath.'godaddy/mwc-cost-of-goods/woocommerce-cost-of-goods.php';

        // plugin class
        require_once $vendorPath.'skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php';

        // async request class
        require_once $vendorPath.'skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-async-request.php';

        // background job handler class
        require_once $vendorPath.'skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-background-job-handler.php';
    }

    /**
     * Broadcasts an event indicating that the user clicked on the Apply Costs button.
     *
     * @since 2.15.0
     *
     * @throws Exception
     */
    public function broadcastApplyCostsToPreviousOrdersEvent()
    {
        Events::broadcast(new ButtonClickedEvent('wc_cog_apply_costs_to_previous_orders'));
    }

    /**
     * Determines whether this feature should be loaded.
     *
     * @since 2.15.0
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        // should not display if Cost of Goods is disabled through configurations
        if (! Configuration::get('features.cost_of_goods.enabled', true)) {
            return false;
        }

        return ManagedWooCommerceRepository::hasEcommercePlan() && WooCommerceRepository::isWooCommerceActive();
    }

    /**
     * Checks if Cost of Good plugin is active.
     *
     * @since 2.15.0
     *
     * @return bool
     */
    public static function isCogsPluginActive() : bool
    {
        return function_exists('is_plugin_active') && is_plugin_active(static::$communityPluginName);
    }
}
