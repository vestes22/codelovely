<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Admin\Views\Components\PlatformContainerElement;
use GoDaddy\WordPress\MWC\Core\Pages\Traits\CanHideWordPressAdminNoticesTrait;

class HomePage extends AbstractAdminPage implements ComponentContract
{
    use CanHideWordPressAdminNoticesTrait;

    /** @var string the page and menu item slug */
    const SLUG = 'mwc-admin';

    /** @var string parent menu item identifier */
    const PARENT_MENU_ITEM = 'woocommerce';

    /** @var string required capability to interact with page and related menu item */
    const CAPABILITY = 'manage_woocommerce';

    /**
     * Constructor for the Home page.
     */
    public function __construct()
    {
        $this->screenId = static::SLUG;
        $this->title = _x('Home', 'Title for the WooCommerce > Home page', 'mwc-core');
        $this->menuTitle = _x('Home', 'Menu title for the WooCommerce > Home page', 'mwc-core');
        $this->menuPosition = 1;
        $this->parentMenuSlug = static::PARENT_MENU_ITEM;
        $this->capability = static::CAPABILITY;

        parent::__construct();
    }

    /**
     * Initializes the Home admin page.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->registerHooks();
    }

    /**
     * Register hook handlers.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_admin_features')
            ->setHandler([$this, 'removeHomescreenFeature'])
            ->setCondition([$this, 'shouldRemoveHomescreenFeature'])
            ->execute();

        // we need to use a large number as the priority to ensure that the original WooCommerce > Home menu item
        // was already added in all supported WooCommerce versions by the time our handler runs
        Register::action()
            ->setGroup('admin_head')
            ->setHandler([$this, 'hideWooCommerceHomeSubmenuItem'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'redirectToOnboardingDashboard'])
            ->setCondition([$this, 'shouldRedirectWooCommerceHomepage'])
            ->execute();

        Register::action()
            ->setGroup('load-woocommerce_page_mwc-admin')
            ->setHandler([$this, 'hideAdminNotices'])
            ->execute();
    }

    /**
     * Redirect to onboarding dashboard page.
     *
     * @internal
     * @throws Exception
     */
    public function redirectToOnboardingDashboard()
    {
        Redirect::to(admin_url('admin.php?page='.static::SLUG))->execute();
    }

    /**
     * Determines if it should redirect away from WooCommerce Homepage.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldRedirectWooCommerceHomepage() : bool
    {
        return $this->isWooCommerceAdminPage() && '' === (string) ArrayHelper::get($_GET, 'path');
    }

    /**
     * Removes the Homescreen feature from the list of WooCommerce Admin features.
     *
     * Handler for the woocommerce_admin_features filter.
     *
     * @internal
     *
     * @param array $features
     * @return array
     */
    public function removeHomescreenFeature($features) : array
    {
        $features = ArrayHelper::wrap($features);

        if (! $key = array_search('homescreen', $features, true)) {
            return $features;
        }

        unset($features[$key]);

        return array_values($features);
    }

    /**
     * Determines whether we can remove the Homescreen feature in the current request.
     *
     * We want to remove the Homescreen feature except when the user is trying to access a WordPress Admin page.
     *
     * @return bool
     */
    public function shouldRemoveHomescreenFeature() : bool
    {
        return ! $this->isWooCommerceAdminPage();
    }

    /**
     * Determines whether the current request is for one of the WooCommerce Admin pages.
     *
     * We can't use {@see WooCommerceRepository::isWooCommerceAdminPage()} in this class because we need to
     * determine whether we are seeing a WooCommerce Admin page before the current screen has been initialized.
     *
     * @return bool
     */
    protected function isWooCommerceAdminPage() : bool
    {
        return ArrayHelper::get($_REQUEST, 'page') === 'wc-admin';
    }

    /**
     * Removes the original WooCommerce > Home submenu item.
     *
     * Prevents WooCommerce > Home from showing up as a menu item but allows the page handler to be loaded.
     *
     * @internal
     */
    public function hideWooCommerceHomeSubmenuItem()
    {
        remove_submenu_page('woocommerce', 'wc-admin');
    }

    /**
     * Renders the page HTML markup.
     *
     * @internal
     */
    public function render()
    {
        PlatformContainerElement::renderIfNotRendered();
    }
}
