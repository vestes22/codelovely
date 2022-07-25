<?php

$cdnUrl = defined('MWC_CDN_URL') ? MWC_CDN_URL : 'https://cdn4.mwc.secureserver.net';

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
     * Managed WooCommerce Plugin URL
     */
    'url' => defined('MWC_CORE_PLUGIN_URL') ? MWC_CORE_PLUGIN_URL : null,

    /*
     * Managed WooCommerce Plugin directory
     */
    'directory' => defined('MWC_CORE_PLUGIN_DIR') ? MWC_CORE_PLUGIN_DIR : null,

    /*
     * Managed WooCommerce Version
     */
    'version' => defined('MWC_CORE_VERSION') ? MWC_CORE_VERSION : null,

    /*
     *--------------------------------------------------------------------------
     * Managed WooCommerce Client
     *--------------------------------------------------------------------------
     *
     * The below information stores values related to the client side of MWC.
     * See https://github.com/gdcorp-partners/mwc-admin-client for more details
     *
     */
    'client' => [
        'runtime' => [
            'url' => "{$cdnUrl}/runtime.js",
        ],
        'vendors' => [
            'url' => "{$cdnUrl}/vendors.js",
        ],
        'index' => [
            'url' => "{$cdnUrl}/index.js",
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * MWC Local Assets
     *--------------------------------------------------------------------------
     *
     * Base directory locations for assets stored locally
     *
     */
    'assets' => [
        'styles' => defined('MWC_CORE_PLUGIN_DIR') ? MWC_CORE_PLUGIN_URL.'assets/css/' : '',
    ],

    /*
     *--------------------------------------------------------------------------
     * MWC Emails service
     *--------------------------------------------------------------------------
     */
    'emails_service' => [
        'api' => [
            'url' => defined('MWC_EMAILS_SERVICE_API_URL') ? MWC_EMAILS_SERVICE_API_URL : 'https://mwc-emails-service.mwc.secureserver.net/graphql',
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * MWC Features
     *--------------------------------------------------------------------------
     *
     * General configuration values that affect all MWC features.
     */
    'allow_native_features_for_resellers' => defined('DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES') && DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES,
];
