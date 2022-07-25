<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;

/**
 * Setting class for EmailNotifications feature. Implements the methods from the ModelContract as no-ops because they
 * are not currently needed in EmailNotifications context.
 */
class EmailNotificationSetting extends AbstractSetting
{
    /**
     * @note NO-OP
     *
     * @return void
     */
    public static function create()
    {
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public static function get($identifier)
    {
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public function update()
    {
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public function delete()
    {
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public function save()
    {
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public static function seed()
    {
    }
}
