<?php

return [

    /*
     *--------------------------------------------------------------------------
     * Information related to the GoDaddy account
     *--------------------------------------------------------------------------
     */
    'account' => [

        'plan' => [
            'name' => defined('GD_PLAN_NAME') ? GD_PLAN_NAME : null,
        ],

        // account UID
        'uid' => defined('GD_ACCOUNT_UID') ? GD_ACCOUNT_UID : '',
    ],

    /*
     *--------------------------------------------------------------------------
     * CDN Settings
     *--------------------------------------------------------------------------
     */
    'cdn' => [
        'enabled' => defined('GD_CDN_ENABLED') ? GD_CDN_ENABLED : false,
    ],

    /*
     *--------------------------------------------------------------------------
     * Private label ID (1 means GoDaddy, so not actually a reseller)
     *--------------------------------------------------------------------------
     */
    'reseller' => defined('GD_RESELLER') ? GD_RESELLER : false,

    /*
     *--------------------------------------------------------------------------
     * Information related to the GoDaddy site
     *--------------------------------------------------------------------------
     */
    'site' => [

        // date the site was created as timestamp
        'created' => defined('GD_SITE_CREATED') ? GD_SITE_CREATED : null,

        // site token
        'token' => defined('GD_SITE_TOKEN') ? GD_SITE_TOKEN : '',

        // site id
        'id' => get_option('gd_mwc_site_id', ''),
    ],

    /*
     *--------------------------------------------------------------------------
     * Is it a staging site
     *--------------------------------------------------------------------------
     */
    'is_staging_site' => defined('GD_STAGING_SITE') ? GD_STAGING_SITE : false,

    'temporary_domain' => defined('GD_TEMP_DOMAIN') ? GD_TEMP_DOMAIN : null,

    /*
     *--------------------------------------------------------------------------
     * Information related to the GoDaddy Storefront API
     *
     * https://www.secureserver.net/api/explore/
     *--------------------------------------------------------------------------
     */
    'storefront' => [
        'api' => [
            'url' => defined('MWC_GODADDY_STOREFRONT_API_URL') ? MWC_GODADDY_STOREFRONT_API_URL : 'https://www.secureserver.net/api/v1/',
        ],
    ],
];
