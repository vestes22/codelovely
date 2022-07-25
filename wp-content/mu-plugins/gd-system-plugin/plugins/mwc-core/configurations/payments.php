<?php

return [

    'api' => [
        'productionRoot' => defined('MWC_PAYMENTS_API_ROOT') ? MWC_PAYMENTS_API_ROOT : 'https://api.mwc.secureserver.net/v1/payments',
        'stagingRoot'    => defined('MWC_PAYMENTS_API_ROOT') ? MWC_PAYMENTS_API_ROOT : 'https://api-dev.mwc.secureserver.net/v1/payments',
    ],

    /*
     *--------------------------------------------------------------------------
     * Payments Settings
     *--------------------------------------------------------------------------
     */
    'providers' => [
        \GoDaddy\WordPress\MWC\Core\Payments\Providers\PoyntProvider::class,
        \GoDaddy\WordPress\MWC\Core\Payments\Providers\PoyntPayInPersonProvider::class,
    ],

    /*
     *--------------------------------------------------------------------------
     * Poynt Payment gateways Settings
     *--------------------------------------------------------------------------
     */
    'poynt'     => [
        'active' => defined('MWC_GODADDY_PAYMENTS_IS_ACTIVE') ? MWC_GODADDY_PAYMENTS_IS_ACTIVE : 'yes' === get_option('mwc_payments_poynt_active', 'yes'),
        'capturePaidOrders' => true,
        'chargeVirtualOrders' => false,
        'detailedDecline' => false,
        'paymentMethods' => true,
        'transactionType' => 'authorization',
        'debugMode' => 'off',
        'webhookSecret' => defined('MWC_PAYMENTS_POYNT_WEBHOOK_SECRET') ? MWC_PAYMENTS_POYNT_WEBHOOK_SECRET : get_option('mwc_payments_poynt_webhookSecret', ''),
        'accountsApi' => [
            'productionRoot' => defined('MWC_PAYMENTS_POYNT_ACCOUNTS_API_ROOT') ? MWC_PAYMENTS_POYNT_ACCOUNTS_API_ROOT : 'https://poynt.godaddy.com/api',
            'stagingRoot' => defined('MWC_PAYMENTS_POYNT_ACCOUNTS_API_ROOT') ? MWC_PAYMENTS_POYNT_ACCOUNTS_API_ROOT : 'https://poynt.test-godaddy.com/api',
        ],

        // Gateway API settings
        'api' => [
            'productionRoot' => defined('MWC_PAYMENTS_POYNT_API_ROOT') ? MWC_PAYMENTS_POYNT_API_ROOT : 'https://services.poynt.net',
            'stagingRoot' => defined('MWC_PAYMENTS_POYNT_API_ROOT') ? MWC_PAYMENTS_POYNT_API_ROOT : 'https://services-st.poynt.net',
            'source' => defined('MWC_PAYMENTS_POYNT_API_SOURCE') ? MWC_PAYMENTS_POYNT_API_SOURCE : 'mwp.godaddy-payments',
            'token'  => defined('MWC_PAYMENTS_POYNT_API_TOKEN') ? MWC_PAYMENTS_POYNT_API_TOKEN : get_option('mwc_payments_poynt_api_token'),
            'productionSdkUrl' => defined('MWC_PAYMENTS_POYNT_SDK_URL') ? MWC_PAYMENTS_POYNT_SDK_URL : 'https://poynt.net/snippet/poynt-collect/bundle.js',
            'stagingSdkUrl' => defined('MWC_PAYMENTS_POYNT_SDK_URL') ? MWC_PAYMENTS_POYNT_SDK_URL : 'https://st.poynt.net/snippet/poynt-collect/bundle-st.js',
        ],

        'hub' => [
            'productionUrl' => defined('MWC_PAYMENTS_POYNT_HUB_URL') ? MWC_PAYMENTS_POYNT_HUB_URL : 'https://payments.godaddy.com',
            'stagingUrl' => defined('MWC_PAYMENTS_POYNT_HUB_URL') ? MWC_PAYMENTS_POYNT_HUB_URL : 'https://payments.test-godaddy.com',
            'routes' => [
                'settings' => 'settings',
                'transactions' => 'transactions',
            ],
        ],

        // Account settings
        'applicationId' => defined('MWC_PAYMENTS_POYNT_APPLICATION_ID') ? MWC_PAYMENTS_POYNT_APPLICATION_ID : get_option('mwc_payments_poynt_applicationId'),
        'appId' => defined('MWC_PAYMENTS_POYNT_APP_ID') ? MWC_PAYMENTS_POYNT_APP_ID : get_option('mwc_payments_poynt_appId'),
        'businessId' => defined('MWC_PAYMENTS_POYNT_BUSINESS_ID') ? MWC_PAYMENTS_POYNT_BUSINESS_ID : get_option('mwc_payments_poynt_businessId'),
        'privateKey' => defined('MWC_PAYMENTS_POYNT_PRIVATE_KEY') ? MWC_PAYMENTS_POYNT_PRIVATE_KEY : get_option('mwc_payments_poynt_privateKey', ''),
        'publicKey' => defined('MWC_PAYMENTS_POYNT_PUBLIC_KEY') ? MWC_PAYMENTS_POYNT_PUBLIC_KEY : get_option('mwc_payments_poynt_publicKey', ''),
        'serviceId' => defined('MWC_PAYMENTS_POYNT_SERVICE_ID') ? MWC_PAYMENTS_POYNT_SERVICE_ID : get_option('mwc_payments_poynt_serviceId', ''),
        'serviceType' => defined('MWC_PAYMENTS_POYNT_SERVICE_TYPE') ? MWC_PAYMENTS_POYNT_SERVICE_TYPE : 'mwp.godaddy-payments',
        'storeId' => defined('MWC_PAYMENTS_POYNT_STORE_ID') ? MWC_PAYMENTS_POYNT_STORE_ID : get_option('mwc_payments_poynt_storeId'),

        'onboarding' => [
            'hasBankAccount' => get_option('mwc_payments_poynt_onboarding_hasBankAccount', false),
            'hasFirstPayment' => get_option('mwc_payments_poynt_onboarding_hasFirstPayment', false),
            'depositsEnabled' => get_option('mwc_payments_poynt_onboarding_depositsEnabled', false),
            'paymentsEnabled' => get_option('mwc_payments_poynt_onboarding_paymentsEnabled', false),
            'signupStarted' => defined('MWC_PAYMENTS_POYNT_ONBOARDING_SIGNUP_STARTED') ? MWC_PAYMENTS_POYNT_ONBOARDING_SIGNUP_STARTED : 'yes' === get_option('mwc_payments_poynt_onboarding_signupStarted', 'no'),
            'status'        => defined('MWC_PAYMENTS_POYNT_ONBOARDING_STATUS') ? MWC_PAYMENTS_POYNT_ONBOARDING_STATUS : get_option('mwc_payments_poynt_onboarding_status', ''),
            'webhookSecret' => defined('MWC_PAYMENTS_POYNT_ONBOARDING_WEBHOOK_SECRET') ? MWC_PAYMENTS_POYNT_ONBOARDING_WEBHOOK_SECRET : get_option('mwc_payments_poynt_onboarding_webhookSecret', ''),
            'productionUrl' => defined('MWC_PAYMENTS_POYNT_ONBOARDING_URL') ? MWC_PAYMENTS_POYNT_ONBOARDING_URL : 'https://signup.payments.godaddy.com/r/mwp',
            'stagingUrl' => defined('MWC_PAYMENTS_POYNT_ONBOARDING_URL') ? MWC_PAYMENTS_POYNT_ONBOARDING_URL : 'https://signup.payments.test-godaddy.com/r/mwcpaymentstest',
        ],

        'webhooks' => ! (defined('DISABLE_MWC_GODADDY_PAYMENTS_WEBHOOKS') && DISABLE_MWC_GODADDY_PAYMENTS_WEBHOOKS),
    ],

    /*
     *--------------------------------------------------------------------------
     * Poynt SIP gateway configurations
     *--------------------------------------------------------------------------
     */
    'godaddy-payments-payinperson' => [
        'capturePaidOrders' => false,
        'paymentMethods' => true,
        'hasTerminalActivated' => defined('MWC_PAYMENTS_PAYINPERSON_TERMINAL_ACTIVATED') ? MWC_PAYMENTS_PAYINPERSON_TERMINAL_ACTIVATED : get_option('mwc_payments_payinperson_terminal_activated', false),
    ],
];
