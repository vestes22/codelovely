<?php

return [

    /*
     *--------------------------------------------------------------------------
     * Information related to the support user account
     *--------------------------------------------------------------------------
     */
    'support_user' => [

        // account login
        'login' => 'mwccare',

        // account e-mail
        'email' => 'mwccare@godaddy.com',
    ],

    /*
     *--------------------------------------------------------------------------
     * Information related to the support bot user account
     *--------------------------------------------------------------------------
     */
    'support_bot' => [

        // support bot app name (also used as the WooCommerce API key description)
        'app_name' => 'GoDaddy Support',

        // support bot app name for reseller sites (also used as the WooCommerce API key description)
        'app_name_reseller' => 'Hosting Support',

        // support bot e-mail information
        'mwc_dashboard_support_request_email' => [

            // support bot e-mail "to" field
            'to' => 'mwcincoming@skyver.ge',

            // support bot e-mail "subject" field
            'subject' => 'Support Request from the MWC Dashboard',

            // support bot e-mail request source
            'request_source' => 'MWC Dashboard',
        ],

        'connect_url' => 'https://support-bot.skyverge.com/woocommerce/auth/connect',
    ],
];
