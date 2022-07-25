<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Features Settings
     *--------------------------------------------------------------------------
     *
     * The information below is used to display the nature features on the WooCommerce > Extensions page.
     *
     * The enabled key determines whether a given feature is available or not.
     */
    'apple_pay'                => (defined('ENABLE_MWC_APPLE_PAY') && ENABLE_MWC_APPLE_PAY),
    'bopit'                    => ! (defined('DISABLE_MWC_BOPIT') && DISABLE_MWC_BOPIT),
    'cost_of_goods'            => [
        'name'              => function_exists('__') ? __('Cost of goods', 'mwc-core') : 'Cost of goods',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Track profit and cost of goods for your store. Generate profit reports by date, product, category, and more. This feature replaces the %1$sCost of Goods%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-cost-of-goods/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40874',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=orders') : '',
        'categories'        => [
            'Store Management',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_COST_OF_GOODS') && DISABLE_MWC_COST_OF_GOODS),
    ],
    'email_deliverability'     => ! (defined('DISABLE_MWC_EMAIL_DELIVERABILITY') && DISABLE_MWC_EMAIL_DELIVERABILITY),
    'email_notifications'      => [
        'name'              => function_exists('__') ? __('Ecommerce emails', 'mwc-core') : 'Ecommerce emails',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Customize your emails to reflect your brand and increase customer loyalty. This feature replaces the %1$sWooCommerce Email Customizer%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-email-customizer/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40929',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=godaddy-email-notifications&tab=settings') : '',
        'categories'        => [
            'Marketing and Messaging',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_EMAIL_NOTIFICATIONS') && DISABLE_MWC_EMAIL_NOTIFICATIONS),
    ],
    'google_analytics'         => [
        'name'              => function_exists('__') ? __('Google analytics', 'mwc-core') : 'Google analytics',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s and %3$s - <a> tag for the plugin link, %2$s and %4$s - </a> tag */
            __('Track advanced eCommerce events and more with Google Analytics. This feature replaces the %1$sGoogle Analytics%2$s and %3$sGoogle Analytics Pro%4$s plugins.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics/" target="_blank">', '</a>',
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics-pro/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40882',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=integration&section=google_analytics_pro') : '',
        'categories'        => [
            'Marketing and Messaging',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_GOOGLE_ANALYTICS') && DISABLE_MWC_GOOGLE_ANALYTICS),
    ],
    'onboarding'               => [
        'enabled' => ! (defined('DISABLE_MWC_ONBOARDING') && DISABLE_MWC_ONBOARDING),
    ],
    'sequential_order_numbers' => [
        'name'              => function_exists('__') ? __('Sequential order numbers', 'mwc-core') : 'Sequential order numbers',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Format order numbers, change your starting number, and differentiate free orders. This feature replaces the %1$sSequential Order Numbers Pro%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/sequential-order-numbers-pro/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40712',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=orders') : '',
        'categories'        => [
            'Store Management',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_SEQUENTIAL_ORDER_NUMBERS') && DISABLE_MWC_SEQUENTIAL_ORDER_NUMBERS),
    ],
    'shipment_tracking'        => [
        'name'              => function_exists('__') ? __('Shipment tracking', 'mwc-core') : 'Shipment tracking',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share shipment tracking information with your customers. Open one of your Processing orders to get started. This feature replaces the %1$sShipment Tracking%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/shipment-tracking/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40631',
        'settings_url'      => '',
        'categories'        => [
            'Shipping',
        ],
        'enabled'           => get_option('mwc_shipment_tracking_active', 'yes') === 'yes',
    ],
    'url_coupons'              => [
        'name'              => function_exists('__') ? __('Discount links', 'mwc-core') : 'Discount links',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share discount links with your customers and add coupons from ads, email campaigns, or social links. Create or edit a coupon to get started. This feature replaces the %1$sURL Coupons%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/url-coupons/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40840',
        'categories'        => [
            'Marketing and Messaging',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_URL_COUPONS') && DISABLE_MWC_URL_COUPONS),
    ],
    'gift_certificates'        => [
        'name'              => function_exists('__') ? __('Gift certificates', 'mwc-core') : 'Gift certificates',
        'description'       => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Create custom gift certificates that your customers can purchase and send to their friends and family. This feature replaces the %1$sPDF Product Vouchers%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/pdf-product-vouchers/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://www.godaddy.com/help/40294',
        'settings_url'      => function_exists('admin_url') ? admin_url('edit.php?post_type=wc_voucher') : '',
        'categories'        => [
            'Merchandising',
            'Product Type',
        ],
        'enabled'           => ! (defined('DISABLE_MWC_GIFT_CERTIFICATES') && DISABLE_MWC_GIFT_CERTIFICATES),
    ],
    'onboarding_dashboard'     => [
        'enabled' => ! (defined('DISABLE_MWC_ONBOARDING_DASHBOARD') && DISABLE_MWC_ONBOARDING_DASHBOARD),
    ],
];
