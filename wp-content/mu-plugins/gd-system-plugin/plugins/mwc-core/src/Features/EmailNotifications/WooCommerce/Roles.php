<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\RolesRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailsPage;

/**
 * Class for handling roles and capabilities used by the Email Notifications feature.
 */
class Roles implements ComponentContract
{
    /** @var string administrator role identifier */
    const ROLE_ADMINISTRATOR = 'administrator';

    /** @var string shop manager role identifier */
    const ROLE_SHOP_MANAGER = 'shop_manager';

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->addManageEmailNotificationsRoleCapability(static::ROLE_ADMINISTRATOR);
        $this->addManageEmailNotificationsRoleCapability(static::ROLE_SHOP_MANAGER);
    }

    /**
     * Adds a capability to a role for managing email notifications.
     *
     * @param string $role
     * @throws Exception
     */
    protected function addManageEmailNotificationsRoleCapability(string $role)
    {
        if (RolesRepository::roleExists($role)) {
            RolesRepository::addRoleCapability($role, EmailsPage::CAPABILITY);
        }
    }
}
