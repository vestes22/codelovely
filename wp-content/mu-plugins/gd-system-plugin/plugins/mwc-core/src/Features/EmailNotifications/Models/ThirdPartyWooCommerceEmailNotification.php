<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsWooCommerceEmailNotificationTrait;

/**
 * The reset password email notification model.
 */
class ThirdPartyWooCommerceEmailNotification extends EmailNotification implements WooCommerceEmailNotificationContract
{
    use IsWooCommerceEmailNotificationTrait;

    /** @var string[] */
    protected $categories = [EmailNotifications::CATEGORY_EXTENSION];

    /** @var bool */
    protected $editable = false;
}
