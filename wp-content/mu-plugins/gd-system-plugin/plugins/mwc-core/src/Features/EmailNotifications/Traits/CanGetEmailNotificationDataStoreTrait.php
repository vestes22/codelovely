<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\EmailNotificationDataStore as WooCommerceEmailNotificationDataStore;

/**
 * A trait for objects that need an instance of an Email Notification data store.
 *
 * @see EmailNotificationDataStoreContract
 * @see EmailNotificationDataStore
 */
trait CanGetEmailNotificationDataStoreTrait
{
    /**
     * Gets an instance of the email notifications data store.
     *
     * @return EmailNotificationDataStoreContract
     */
    protected function getEmailNotificationDataStore() : EmailNotificationDataStoreContract
    {
        return new WooCommerceEmailNotificationDataStore();
    }
}
