<?php

return [

    /*
     *--------------------------------------------------------------------------
     * MWC Dashboard Assets
     *--------------------------------------------------------------------------
     *
     * Locations for dashboard assets
     */
    'assets' => [
        'css' => [
            'fonts' => [
                'url' => defined('MWC_DASHBOARD_PLUGIN_URL') ? MWC_DASHBOARD_PLUGIN_URL.'assets/css/dashboard-fonts.css' : '',
            ],
            'admin' => [
                'url' => defined('MWC_DASHBOARD_PLUGIN_URL') ? MWC_DASHBOARD_PLUGIN_URL.'assets/css/dashboard-admin.css' : '',
            ],
        ],
        'images' => [
            'go_icon' => [
                'url' => defined('MWC_DASHBOARD_PLUGIN_DIR') ? MWC_DASHBOARD_PLUGIN_DIR.'assets/images/go-icon.svg' : '',
            ],
        ],
    ],
];
