<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\SettingsDataStore;

/**
 * A trait for objects that need an instance of a WooCommerce settings data store.
 *
 * @see \GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\SettingsDataStore
 */
trait CanGetWooCommerceSettingsDataStoreTrait
{
    /**
     * Gets an instance of the WooCommerce settings data store.
     *
     * @return SettingsDataStore
     */
    protected function getWooCommerceSettingsDataStore() : SettingsDataStore
    {
        return new SettingsDataStore();
    }
}
