<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use Exception;
use InvalidArgumentException;
use WP_Roles;

/**
 * Repository handler for WordPress user roles and capabilities.
 */
class RolesRepository
{
    /**
     * Gets the WordPress roles handler instance, if available.
     *
     * @return WP_Roles|null
     */
    public static function instance()
    {
        global $wp_roles;

        if (! isset($wp_roles) && class_exists(WP_Roles::class)) {
            $wp_roles = new WP_Roles();
        }

        return $wp_roles;
    }

    /**
     * Adds a user role and capability to WordPress.
     *
     * @param string $role
     * @param string $capability
     * @throws Exception|InvalidArgumentException
     */
    public static function addRoleCapability(string $role, string $capability)
    {
        $roles = static::instance();

        /* translators: Placeholders: %1$s - WordPress user capability, %2$s - WordPress user role, %3$s - Error message */
        $errorMessage = __('Cannot add "%1$s" capability to "%2$s" user role: %3$s', 'mwc-common');

        if (! $roles) {
            throw new Exception(sprintf($errorMessage, $capability, $role, __('Cannot load WordPress Roles handler.', 'mwc-common')));
        }

        if (! static::roleExists($role)) {
            throw new InvalidArgumentException(sprintf($errorMessage, $capability, $role, __('User role does not exist.', 'mwc-common')));
        }

        $roles->add_cap($role, $capability);
    }

    /**
     * Determines whether a role exists or not.
     *
     * @param string $role
     * @return bool
     * @throws Exception
     */
    public static function roleExists(string $role) : bool
    {
        return isset(static::instance()->roles[$role]);
    }
}
