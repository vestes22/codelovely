<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits;

trait RequiresWooCommercePermissionsTrait
{
    /**
     * Checks if the current user can get items through the controller.
     *
     * @return bool|\WP_Error
     */
    public function getItemsPermissionsCheck()
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Checks if the current user can create items through the controller.
     *
     * Each controller may overwrite this method to check for different permissions.
     *
     * @return bool|\WP_Error
     */
    public function createItemPermissionsCheck()
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Checks if the current user can update items through the controller.
     *
     * Each controller may overwrite this method to check for different permissions.
     *
     * @return bool|\WP_Error
     */
    public function updateItemPermissionsCheck()
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Checks if the current user can delete items through the controller.
     *
     * Each controller may overwrite this method to check for different permissions.
     *
     * @return bool|\WP_Error
     */
    public function deleteItemPermissionsCheck()
    {
        return current_user_can('manage_woocommerce');
    }
}
