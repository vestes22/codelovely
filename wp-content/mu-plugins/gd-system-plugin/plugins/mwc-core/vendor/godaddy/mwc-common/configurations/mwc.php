<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Managed WooCommerce General Settings
     *--------------------------------------------------------------------------
     *
     * The following configuration items are general settings or high level
     * configurations for Managed WooCommerce
     *
     */

    /*
     * Should the instance display debugging information
     */
    'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,

    /*
     * Determine if Managed WooCommerce is running in CLI mode or not
     */
    'mode' => defined('WP_CLI') && WP_CLI ? 'cli' : 'web',

    /*
     * eCommerce Plan Name for Managed WooCommerce
     */
    'plan_name' => 'eCommerce Managed WordPress',

    /*
     * Managed WooCommerce Plugin URL
     */
    'url' => defined('MWC_CORE_PLUGIN_URL') ? MWC_CORE_PLUGIN_URL : null,

    /*
     * Managed WooCommerce Version
     */
    'version' => defined('MWC_CORE_VERSION') ? MWC_CORE_VERSION : null,

    /*
     *--------------------------------------------------------------------------
     * Information related to extensions
     *--------------------------------------------------------------------------
     */
    'extensions' => [

        /*
         * API configurations
         */
        'api' => [
            'url' => defined('MWC_EXTENSIONS_API_URL') ? MWC_EXTENSIONS_API_URL : 'https://api.mwc.secureserver.net/v1',
            'settings' => [
                'reseller' => [
                    'endpoint' => 'settings/resellers',
                ],
            ],
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Information related to Events
     *--------------------------------------------------------------------------
     */
    'events' => [

        /*
         * API configurations
         */
        'api' => [
            'url' => defined('MWC_EVENTS_API_URL') ? MWC_EVENTS_API_URL : 'https://api-events.mwc.secureserver.net',
        ],
    ],
];
