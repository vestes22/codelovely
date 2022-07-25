<?php

return [

    /*
     *--------------------------------------------------------------------------
     * Information related to the dashboard messages
     *--------------------------------------------------------------------------
     */
    'api' => [
        'auth' => [
            'type'  => 'Bearer',
            'token' => defined('MWC_DASHBOARD_MESSAGES_AUTH_TOKEN') ? MWC_DASHBOARD_MESSAGES_AUTH_TOKEN : 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJhcGktZXZlbnRzLm13Yy5zZWN1cmVzZXJ2ZXIubmV0Iiwic2NvcGUiOiJ3cml0ZSIsImlhdCI6MTYxNzMwNDUwOSwiZXhwIjoxNjI1MDgwNTA5LCJpc3MiOiJhcGktZXZlbnRzLWF1dGgifQ.9CQuWuykArqzbFFXg0IbIwSJ9cKs2VzvqjjPLya7UktKEx9HnYNgcPnB5FTHbEY2aUc4yz9UBkYfJgRiiD5dfA',
        ],
        'url' => defined('MWC_DASHBOARD_MESSAGES_API_URL') ? MWC_DASHBOARD_MESSAGES_API_URL : 'https://api-events.mwc.secureserver.net/graphql',
    ],
];
