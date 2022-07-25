<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;

/**
 * Extensions cache.
 *
 * @since 1.0.0
 */
final class CacheExtensions extends Cache implements CacheableContract
{
    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 5400;

    /** @var string the cache key */
    protected $key = 'extensions';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->type('extensions');
    }
}
