<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailNotificationsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailTemplatesController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailTemplatesSettingsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\SendersController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\SettingsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailsPage;

/**
 * Email notifications API handler.
 */
class API implements ComponentContract
{
    use HasComponentsTrait;

    /** @var array */
    protected $componentClasses = [
        EmailNotificationsController::class,
        EmailTemplatesController::class,
        SendersController::class,
        SettingsController::class,
        EmailTemplatesSettingsController::class,
    ];

    /**
     * Loads the API component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('rest_api_init')
            ->setHandler([$this, 'registerRoutes'])
            ->execute();
    }

    /**
     * Registers the email notifications REST API routes.
     *
     * @see API::setup()
     * @internal
     *
     * @throws ComponentLoadFailedException
     */
    public function registerRoutes()
    {
        $this->loadComponents();
    }

    /**
     * Determines if the current user has access to the module's REST endpoints.
     *
     * @return bool
     */
    public static function hasAPIAccess() : bool
    {
        return current_user_can(EmailsPage::CAPABILITY);
    }
}
