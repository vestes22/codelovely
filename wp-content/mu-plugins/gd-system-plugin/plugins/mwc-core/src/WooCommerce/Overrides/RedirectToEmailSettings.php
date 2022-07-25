<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailsPage;

class RedirectToEmailSettings implements ComponentContract
{
    /**
     * Initializes the component.
     *
     * @see https://developer.wordpress.org/reference/hooks/admin_page_access_denied/
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('admin_page_access_denied')
            ->setHandler([$this, 'redirectToWooCommerceEmailsSettings'])
            ->setCondition([$this, 'shouldRedirect'])
            ->execute();
    }

    /**
     * Redirects to WooCommerce Emails settings.
     *
     * @internal
     *
     * @throws Exception
     */
    public function redirectToWooCommerceEmailsSettings()
    {
        Redirect::to(admin_url('admin.php?page=wc-settings&tab=email'))->setSafe(true)->execute();
    }

    /**
     * Determines if it should redirect Email Notifications to WooCommerce Emails settings.
     *
     * @internal
     *
     * @return bool
     * @throws Exception
     */
    public function shouldRedirect() : bool
    {
        return WordPressRepository::isAdmin()
            && ! EmailNotifications::isActive()
            && EmailsPage::SLUG === ArrayHelper::get($_GET, 'page');
    }
}
