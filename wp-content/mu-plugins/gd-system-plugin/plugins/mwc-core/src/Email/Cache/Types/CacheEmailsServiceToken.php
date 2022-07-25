<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Emails service token cache handler class.
 */
final class CacheEmailsServiceToken extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 7200;

    /** @var string the cache key */
    protected $key = 'email_service_token';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->type('email_service_token');
    }
}
