<?php

namespace GoDaddy\WordPress\MWC\Dashboard;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Plugin\BasePlatformPlugin;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\API;
use GoDaddy\WordPress\MWC\Dashboard\Menu\GetHelpMenu;
use GoDaddy\WordPress\MWC\Dashboard\Pages\WooCommerceExtensionsPage;

/**
 * MWC Dashboard class.
 *
 * @since 1.0.0
 *
 * @method static \GoDaddy\WordPress\MWC\Dashboard\Dashboard getInstance()
 */
final class Dashboard extends BasePlatformPlugin
{
    use IsSingletonTrait;

    /**
     * Plugin name.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $name = 'MWC Dashboard';

    /**
     * Classes to instantiate.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $classesToInstantiate = [
        API::class                       => true,
        GetHelpMenu::class               => 'web',
        WooCommerceExtensionsPage::class => 'web',
    ];

    /**
     * Plugin constructor.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        // needed to load the configurations so we can use them for the following check
        parent::__construct();

        load_plugin_textdomain('mwc-dashboard', false, dirname(plugin_basename(__FILE__)).'/languages');

        if (GetHelpMenu::shouldLoadConditionalFeature()) {
            $this->deactivateSkyVergeDashboard();
        }
    }

    /**
     * Initializes the Configuration class adding the plugin's configuration directory.
     *
     * @since 1.0.0
     */
    protected function initializeConfiguration()
    {
        Configuration::initialize(StringHelper::trailingSlash(StringHelper::before(__DIR__, 'src').'configurations'));
    }

    /**
     * Gets configuration values.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getConfigurationValues() : array
    {
        $configurationValues = parent::getConfigurationValues();

        $configurationValues['PLUGIN_DIR'] = StringHelper::before(__DIR__, 'src');
        $configurationValues['PLUGIN_URL'] = StringHelper::before(plugin_dir_url(__FILE__), 'src');
        $configurationValues['VERSION'] = '1.3.1';

        return $configurationValues;
    }

    /**
     * Deactivates SkyVerge Dashboard plugin completely.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    protected function deactivateSkyVergeDashboard()
    {
        $this->deactivateSkyVergeDashboardPlugin();
        $this->stopBundledSkyVergeDashboardFromLoading();
    }

    /**
     * Makes sure to prevent bundled SkyVerge dashboard from loading.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    protected function stopBundledSkyVergeDashboardFromLoading()
    {
        Register::action()
            ->setGroup('plugins_loaded')
            ->setPriority(1)
            ->setHandler([$this, 'unhookBundledSkyVergeDashboard'])
            ->execute();
    }

    /**
     * Unhooks bundled SkyVerge Dashboard initialization.
     *
     * @internal
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function unhookBundledSkyVergeDashboard()
    {
        if (class_exists('SkyVerge_Dashboard_Loader')) {
            Register::action()
                ->setGroup('plugins_loaded')
                ->setHandler([SkyVerge_Dashboard_Loader::instance(), 'init_plugin'])
                ->deregister();
        }
    }

    /**
     * Checks if the SkyVerge Dashboard plugin is active and deactivates it.
     *
     * @since 1.0.0
     */
    protected function deactivateSkyVergeDashboardPlugin()
    {
        if (! class_exists('SkyVerge_Dashboard_Loader')) {
            return;
        }

        deactivate_plugins('skyverge-dashboard/skyverge-dashboard.php');

        try {
            $this->displayAdminNoticeForSkyVergeDashboardPlugin();
        } catch (Exception $ex) {
            // @TODO maybe upon error, display and notice in some other way {NM 2021-01-08}
        }
    }

    /**
     * Displays admin notice upon deactivating the SkyVerge Dashboard plugin.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    protected function displayAdminNoticeForSkyVergeDashboardPlugin()
    {
        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'renderAdminNoticeForSkyVergeDashboardPlugin'])
            ->execute();
    }

    /**
     * Renders admin notice upon deactivating the SkyVerge Dashboard plugin.
     *
     * @internal
     *
     * @since 1.0.0
     */
    public function renderAdminNoticeForSkyVergeDashboardPlugin()
    {
        echo '<div class="notice notice-info is-dismissible"><p>',
        __('<strong>Heads up!</strong> We\'ve deactivated the SkyVerge Dashboard plugin since you now have access to the dashboard via the Get Help menu!',
            'mwc-dashboard'),
        '</p></div>';
    }
}
