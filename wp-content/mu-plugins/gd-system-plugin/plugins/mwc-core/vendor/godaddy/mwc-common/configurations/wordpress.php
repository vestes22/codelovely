<?php

/*
 *--------------------------------------------------------------------------
 * WordPress Information
 *--------------------------------------------------------------------------
 *
 * General Information about WooCommerce itself.
 *
 */

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

return [

    /* The absolute path for the WordPress instance */
    'absolute_path' => defined('ABSPATH') ? ABSPATH : null,

    /* The absolute path to the plugins directory (no trailing slash) */
    'plugins_directory' => defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : null,

    /* Determine if WordPress should run in debug mode */
    'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,

    /* The WordPress version being used */
    'version' => ArrayHelper::get($GLOBALS, 'wp_version'),

    /* The WordPress instance locale setting */
    'locale' => function_exists('get_locale') ? get_locale() : 'en_US',
];
