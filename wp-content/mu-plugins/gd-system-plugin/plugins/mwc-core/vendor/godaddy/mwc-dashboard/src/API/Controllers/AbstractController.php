<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

/**
 * Abstract controller class.
 *
 * Represents a base controller for all controllers to extend from.
 */
abstract class AbstractController
{
    /**
     * Route namespace.
     *
     * @var string
     */
    protected $namespace = 'godaddy/mwc/v1';

    /**
     * Route.
     *
     * @var string
     */
    protected $route;

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    abstract public function registerRoutes();

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array
     */
    abstract public function getItemSchema() : array;

    /**
     * Checks if the current user can get items through the controller.
     * @Note: The permissions checks here should be the broadest common level with controller tightening from there {JO: 2021-09-08}
     *
     * @return bool|\WP_Error
     */
    public function getItemsPermissionsCheck()
    {
        return current_user_can('edit_posts');
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
        return current_user_can('edit_posts');
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
        return current_user_can('edit_posts');
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
        return current_user_can('edit_posts');
    }
}
