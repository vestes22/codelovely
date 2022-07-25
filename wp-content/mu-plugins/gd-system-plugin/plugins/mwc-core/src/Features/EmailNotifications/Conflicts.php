<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Email services conflicts handler.
 *
 * This class will handle possible conflicts with third party plugins that also handle sending emails.
 */
class Conflicts
{
    /**
     * Determines whether there is at least one conflict.
     *
     * @return bool
     */
    public static function hasConflict() : bool
    {
        return static::hasConflictingEmailSendingPluginActive();
    }

    /**
     * Determines whether a conflicting plugin is active.
     *
     * @param array $pluginData plugin data
     * @return bool
     */
    protected static function hasConflictingEmailSendingPluginActive() : bool
    {
        return ! empty(array_intersect(static::getConflictingEmailSendingPluginsData(), ArrayHelper::wrap(get_option('active_plugins', []))));
    }

    /**
     * Defines a list of conflicting plugins.
     *
     * @return array
     */
    private static function getConflictingEmailSendingPluginsData() : array
    {
        return ArrayHelper::wrap(Configuration::get('email_notifications.conflicts.plugins'));
    }
}
