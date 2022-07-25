<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use wpdb;

/**
 * Repository handler for WordPress database handling.
 */
class DatabaseRepository
{
    /**
     * Gets the WordPress DataBase handler instance.
     *
     * @return wpdb
     */
    public static function instance() : wpdb
    {
        global $wpdb;

        return $wpdb;
    }
}
