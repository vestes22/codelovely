<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Pages;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use WC_Admin_Addons;
use WC_Helper;
use WC_Helper_Updater;

/**
 * The WooCommerce Extensions page.
 *
 * @since 1.0.0
 */
class WooCommerceExtensionsPage extends AbstractAdminPage
{
    use IsConditionalFeatureTrait;

    /** @var string the slug of the Available Extensions tab */
    const TAB_AVAILABLE_EXTENSIONS = 'available_extensions';

    /** @var string the slug of the Browse Extensions tab */
    const TAB_BROWSE_EXTENSIONS = 'browse_extensions';

    /** @var string the slug of the Subscriptions tab */
    const TAB_SUBSCRIPTIONS = 'subscriptions';

    /** @var string ID of the div element inside which the page will be rendered */
    protected $divId;

    /** @var string String of styles to apply to the div element */
    protected $divStyles;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->screenId = 'wc-addons';
        $this->title = __('WooCommerce extensions', 'mwc-dashboard');
        $this->menuTitle = __('Extensions', 'mwc-dashboard');
        $this->parentMenuSlug = 'woocommerce';

        $this->capability = 'manage_woocommerce';

        $this->divId = 'mwc-extensions';
        $this->divStyles = '';

        parent::__construct();

        $this->addHooks();
    }

    /**
     * Renders the page HTML.
     *
     * @since 1.2.0
     */
    public function renderDivContainer()
    {
        ?>
        <div id="<?php echo $this->divId; ?>" style="<?php echo $this->divStyles; ?>"></div>
        <?php
    }

    /**
     * Adds the menu page.
     *
     * @since 1.0.0
     *
     * @internal
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return self
     * @throws Exception
     */
    public function addMenuItem() : AbstractAdminPage
    {
        if ($count = $this->getUpdatesCountHtml()) {
            $this->menuTitle = sprintf(esc_html__('Extensions %s', 'mwc-dashboard'), $count);
        }

        return parent::addMenuItem();
    }

    /**
     * Registers the page hooks.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_show_addons_page')
            ->setHandler('__return_false')
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRedirectToAvailableExtensionsTab'])
            ->execute();
    }

    /**
     * Registers the menu page.
     *
     * Overridden to change the priority of the handler to 20.
     *
     * @since 1.0.0
     *
     * @return self
     * @throws Exception
     */
    protected function registerMenuItem() : AbstractAdminPage
    {
        try {
            if ($this->shouldAddMenuItem()) {
                Register::action()
                    ->setGroup('admin_menu')
                    ->setHandler([$this, 'addMenuItem'])
                    ->setPriority(100)
                    ->execute();
            }
        } catch (Exception $ex) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('Cannot register the menu item: '.$ex->getMessage());
        }

        return $this;
    }

    /**
     * Checks if assets should be enqueued or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        if ($screen = $this->getCurrentScreen()) {
            return 'woocommerce_page_'.$this->screenId === $screen->id;
        }

        return false;
    }

    /**
     * Gets the current admin screen.
     *
     * TODO: move to WordPressRepository
     *
     * @return \WP_Screen|null
     */
    protected function getCurrentScreen()
    {
        return get_current_screen();
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function enqueueAssets()
    {
        Enqueue::style()
            ->setHandle("{$this->divId}-fonts")
            ->setSource(Configuration::get('mwc_dashboard.assets.css.fonts.url'))
            ->execute();

        Enqueue::style()
            ->setHandle("{$this->divId}-style")
            ->setSource(Configuration::get('mwc_extensions.assets.css.admin.url'))
            ->execute();

        parent::enqueueAssets();
    }

    /**
     * Redirect the default page to the Available Extensions tab.
     *
     * @internal
     *
     * @since 1.0.0
     */
    public function maybeRedirectToAvailableExtensionsTab()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
        $tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING);
        $section = filter_input(INPUT_GET, 'section', FILTER_SANITIZE_STRING);
        $helper_connect = (bool) filter_input(INPUT_GET, 'wc-helper-connect');

        if (WordPressRepository::isCurrentPage('admin.php') && 'wc-addons' === $page && ! $helper_connect && ! $section && ! $tab) {
            (new Redirect)->setPath(admin_url('admin.php'))
                ->setQueryParameters([
                    'page' => 'wc-addons', 'tab' => self::TAB_AVAILABLE_EXTENSIONS,
                ])
                ->execute();
        }
    }

    /**
     * Renders the page HTML.
     *
     * @since 1.0.0
     */
    public function render()
    {
        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();

        $current_tab = $this->getCurrentTab(); ?>

        <div class="wrap woocommerce wc_addons_wrap mwc-dashboard-wc-addons-wrap">

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper mwc-dashboard-nav-tab-wrapper">
			<?php
                foreach ($this->getTabs() as $slug => $tab) {
                    printf(
                        '<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
                        esc_url($tab['url']),
                        ($current_tab === $slug) ? ' nav-tab-active' : '',
                        $tab['label']
                    );
                } ?>
            </nav>

            <h1 class="screen-reader-text"><?php esc_html_e('WooCommerce Extensions', 'woocommerce'); ?></h1>

        <?php $this->renderTab($current_tab); ?>

        </div>

        <div class="clear"></div>

        <?php

        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();
    }

    /**
     * Deletes the updates count cache if the current tab is the Subscriptions tab.
     *
     * @since 1.0.0
     */
    private function maybeClearUpdatesCacheCount()
    {
        if ($this->getCurrentTab() === self::TAB_SUBSCRIPTIONS) {
            delete_transient('_woocommerce_helper_updates_count');
        }
    }

    /**
     * Gets the slug for the currently active tab.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function getCurrentTab() : string
    {
        if (! $tab = StringHelper::sanitize(ArrayHelper::get($_GET, 'tab', ''))) {
            $tab = static::TAB_AVAILABLE_EXTENSIONS;
        }

        if ($section = ArrayHelper::get($_GET, 'section')) {
            // self::TAB_SUBSCRIPTIONS necessary to support redirect requests after a merchant connects the site to WooCommerce.com and filter views in the Subscriptions tab
            // self::TAB_BROWSE_EXTENSIONS necessary to support the extensions search and extension cateogires features in the Browse Extensions tab
            $tab = $section === 'helper' ? self::TAB_SUBSCRIPTIONS : self::TAB_BROWSE_EXTENSIONS;
        }

        return $tab;
    }

    /**
     * Gets a list of tabs to render indexed by the tab slug.
     *
     * @since 1.0.0
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getTabs() : array
    {
        $url = admin_url('admin.php?page=wc-addons');

        return [
            self::TAB_AVAILABLE_EXTENSIONS => [
                'label' => ManagedWooCommerceRepository::isReseller() ? esc_html__('Included Extensions', 'mwc-dashboard') : esc_html__('GoDaddy Included Extensions', 'mwc-dashboard'),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_AVAILABLE_EXTENSIONS]),
            ],
            self::TAB_BROWSE_EXTENSIONS => [
                'label' => esc_html__('Browse Extensions', 'woocommerce'),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_BROWSE_EXTENSIONS]),
            ],
            self::TAB_SUBSCRIPTIONS => [
                'label' => esc_html__('WooCommerce.com Subscriptions', 'woocommerce').$this->getUpdatesCountHtml(),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_SUBSCRIPTIONS, 'section' => 'helper']),
            ],
        ];
    }

    /**
     * Gets the HTML for the number of products that have updates, with managed plugins removed from the count.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function getUpdatesCountHtml() : string
    {
        $filter = Register::filter()
            ->setGroup('transient__woocommerce_helper_updates')
            ->setHandler([$this, 'removeManagedPluginsFromCount'])
            ->setPriority(10)
            ->setArgumentsCount(1);

        try {
            $filter->execute();

            $html = WC_Helper_Updater::get_updates_count_html();

            $filter->deregister();
        } catch (Exception $exception) {
            $html = '';
        }

        return $html;
    }

    /**
     * Removes managed plugins from the list of plugins that have updates.
     *
     * @internal
     *
     * @since 1.0.0
     *
     * @param mixed $transient_value array of cached WooCommerce plugins data
     * @return mixed
     * @throws Exception
     */
    public function removeManagedPluginsFromCount($transient_value)
    {
        // bail if not an array
        if (! ArrayHelper::accessible($transient_value)) {
            return $transient_value;
        }

        $urls = array_map(static function ($plugin) {
            return $plugin->getHomepageUrl();
        }, ManagedExtensionsRepository::getManagedPlugins());

        $transient_value['products'] = ArrayHelper::where(ArrayHelper::get($transient_value, 'products', []), static function ($value) use ($urls) {
            return ! in_array(ArrayHelper::get($value, 'url'), $urls, true);
        });

        return $transient_value;
    }

    /**
     * Renders the content for the given tab.
     *
     * @since 1.0.0
     *
     * @param string $currentTab
     */
    protected function renderTab(string $currentTab)
    {
        $methodName = 'render'.str_replace(' ', '', ucwords(str_replace('_', ' ', $currentTab))).'Tab';

        if (method_exists($this, $methodName)) {
            $this->{$methodName}();
        }
    }

    /**
     * Renders the content for the GoDaddy Included Extensions tab.
     *
     * @since 1.0.0
     */
    protected function renderAvailableExtensionsTab()
    {
        $this->renderDivContainer();
    }

    /**
     * Renders the content for the Browse Extensions tab.
     *
     * @since 1.0.0
     */
    protected function renderBrowseExtensionsTab()
    {
        WC_Admin_Addons::output();
    }

    /**
     * Renders the content for the Subscriptions tab.
     *
     * @since 1.0.0
     */
    protected function renderSubscriptionsTab()
    {
        WC_Helper::render_helper_output();
    }

    /**
     * Determines whether the feature can be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive() && ManagedWooCommerceRepository::hasEcommercePlan();
    }
}
