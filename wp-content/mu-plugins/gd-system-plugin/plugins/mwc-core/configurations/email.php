<?php

use GoDaddy\WordPress\MWC\Core\Email\EmailService;
use GoDaddy\WordPress\MWC\Core\Email\RemoteRenderingEmailService;
use GoDaddy\WordPress\MWC\Core\Email\WordPressEmailService;

return [
    'services' => [
        'text/mjml'  => [
            EmailService::class,
            RemoteRenderingEmailService::class,
        ],
        'text/html'  => [
            EmailService::class,
            WordPressEmailService::class,
        ],
        'text/plain' => [
            EmailService::class,
            WordPressEmailService::class,
        ],
    ],
];
