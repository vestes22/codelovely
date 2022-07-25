<?php

return [
    // Determines whether the Dashboard is enabled or not
    'mwc_dashboard' => ! (defined('DISABLE_MWC_DASHBOARD') && DISABLE_MWC_DASHBOARD),

    /*
     *--------------------------------------------------------------------------
     * Features related to extensions UX
     *--------------------------------------------------------------------------
     */
    'extensions' => [
        'versionSelect' => defined('MWC_ENABLE_EXTENSION_VERSION_SELECT') && MWC_ENABLE_EXTENSION_VERSION_SELECT,
    ],
];
