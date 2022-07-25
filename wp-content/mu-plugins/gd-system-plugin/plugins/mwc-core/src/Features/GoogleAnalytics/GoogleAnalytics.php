<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
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
use GoDaddy\WordPress\MWC\Core\Events\SettingsUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\Events\GoogleAnalyticsConnectedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyBranding;
use function GoDaddy\WordPress\MWC\GoogleAnalytics\wc_google_analytics_pro;
use GoDaddy\WordPress\MWC\GoogleAnalytics\WC_Google_Analytics_Pro_Loader;

/**
 * The Google Analytics feature loader.
 */
class GoogleAnalytics implements ConditionalComponentContract
{
    use IsConditionalFeatureTrait;

    /** @var string the plugin name */
    protected static $communityPluginName = 'woocommerce-google-analytics-pro/woocommerce-google-analytics-pro.php';

    /** @var string the community plugin slug */
    protected static $communityPluginSlug = 'woocommerce-google-analytics-pro';

    /**
     * Constructs the class and loads the Google Analytics feature.
     *
     * TODO: remove this method when {@see Pacakge} is converted to use {@see ConditionalComponentContract} {nmolham 2021-09-22}
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $rootVendorPath = StringHelper::trailingSlash(StringHelper::before(__DIR__, 'src').'vendor');

        // load plugin class file
        require_once $rootVendorPath.'godaddy/mwc-google-analytics/woocommerce-google-analytics-pro.php';

        // load SV Framework from root vendor folder first
        require_once $rootVendorPath.'skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php';

        WC_Google_Analytics_Pro_Loader::instance()->init_plugin();

        $this->registerHooks();
    }

    /**
     * Registers hooks.
     *
     * @since x.y.z
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
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
            ->setGroup('mwc_google_analytics_connected')
            ->setHandler([$this, 'broadcastConnectedEvent'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_settings_integration')
            ->setHandler([$this, 'enqueueConnectButtonClickScript'])
            ->setCondition([$this, 'isSettingsPage'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_mwc_google_analytics_connect_btn_clicked')
            ->setHandler([$this, 'broadcastConnectButtonClickedEvent'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_update_options_integration_google_analytics_pro')
            ->setHandler([$this, 'broadcastSettingsUpdatedEvent'])
            // so it runs after SV_WC_Tracking_Integration::process_admin_options()
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDeactivateGaProPlugin'])
            ->execute();

        Register::action()
                ->setGroup('load-plugins.php')
                ->setHandler([$this, 'removePluginUpdateNotice'])
                ->setPriority(PHP_INT_MAX)
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
     * May deactivate the Google Analytics Pro plugin.
     *
     * @throws Exception
     */
    public function maybeDeactivateGaProPlugin()
    {
        if (! static::isGaProPluginActive()) {
            return;
        }

        $this->deactivateGaProPlugin();

        update_option('mwc_google_analytics_show_notice_ga_pro_users', 'yes');
    }

    /**
     * Deactivates the Google Analytics Pro plugin.
     *
     * @throws Exception
     */
    protected function deactivateGaProPlugin()
    {
        if (! function_exists('deactivate_plugins')) {
            return;
        }

        // we want to display the notice again even it was previously dismissed
        wc_google_analytics_pro()->get_admin_notice_handler()->undismiss_notice(wc_google_analytics_pro()->get_id_dasherized().'-ga-pro-users');

        deactivate_plugins(static::$communityPluginName);

        // unset GET param so that the "Plugin activated." notice is not displayed
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $pluginExtension = (new PluginExtension())
            ->setName(static::$communityPluginName)
            ->setSlug(static::$communityPluginSlug);

        Events::broadcast(new PluginDeactivatedEvent($pluginExtension));

        Events::broadcast(new FeatureEnabledEvent('google_analytics'));
    }

    /**
     * Broadcasts an event indicating that the user clicked on the Connect a Google Account button.
     *
     * @internal
     *
     * @throws Exception
     */
    public function broadcastConnectButtonClickedEvent()
    {
        Events::broadcast(new ButtonClickedEvent('woocommerce_google_analytics_pro_oauth_button'));
    }

    /**
     * Enqueues JS to listen for connect Google account button.
     *
     * @internal
     */
    public function enqueueConnectButtonClickScript()
    {
        ob_start(); ?>
        (function($) {
            $('#woocommerce_google_analytics_pro_oauth_button').on('click', () => {
                $.post(ajaxurl, {action: 'mwc_google_analytics_connect_btn_clicked'});
            });
        })(jQuery);
        <?php

        wc_enqueue_js(ob_get_clean());
    }

    /**
     * Broadcasts a Google account connected event.
     *
     * @internal
     *
     * @throws Exception
     */
    public function broadcastConnectedEvent()
    {
        Events::broadcast(new GoogleAnalyticsConnectedEvent());
    }

    /**
     * Checks if it should add GoDaddy branding to module settings page.
     *
     * @since x.y.z
     *
     * @throws Exception
     * @return bool
     */
    public function shouldAddGoDaddyBranding() : bool
    {
        return ! ManagedWooCommerceRepository::isReseller() && $this->isSettingsPage();
    }

    /**
     * Determines if the current page is the module's settings page.
     *
     * @internal
     *
     * @return bool
     */
    public function isSettingsPage() : bool
    {
        return wc_google_analytics_pro()->is_plugin_settings();
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

            <?php if (version_compare(WC_VERSION, '4.0', '<')) : ?>
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
     * Broadcasts an event when settings are updated.
     *
     * @internal
     */
    public function broadcastSettingsUpdatedEvent()
    {
        $event = new SettingsUpdatedEvent(wc_google_analytics_pro()->get_id());
        $event->setSettings(get_option('woocommerce_google_analytics_pro_settings'));

        Events::broadcast($event);
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * TODO: remove this method when {@see Pacakge} is converted to use {@see ConditionalComponentContract} {nmolham 2021-09-22}
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return static::shouldLoad();
    }

    /**
     * Determines whether the Google Analytics feature should load.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad() : bool
    {
        // should not display if Google Analytics is disabled through configurations
        if (! Configuration::get('features.google_analytics.enabled', true)) {
            return false;
        }

        return ManagedWooCommerceRepository::hasEcommercePlan() && WooCommerceRepository::isWooCommerceActive();
    }

    /**
     * Determines whether the Google Analytics Pro plugin is active.
     *
     * @return bool
     */
    public static function isGaProPluginActive() : bool
    {
        return static::isPluginActive(static::$communityPluginName);
    }

    /**
     * Determines whether the given plugin is active.
     *
     * @param string $pluginName
     * @return bool
     */
    protected static function isPluginActive(string $pluginName) : bool
    {
        return function_exists('is_plugin_active') && is_plugin_active($pluginName);
    }
}
