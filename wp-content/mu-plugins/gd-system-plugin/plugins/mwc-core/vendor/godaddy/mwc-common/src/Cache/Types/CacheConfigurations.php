<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;

/**
 * Configurations cache.
 *
 * @since 1.0.0
 */
final class CacheConfigurations extends Cache implements CacheableContract
{
    /**
     * How long in seconds should the cache be kept for.
     *
     * Static caches are reset on each page change and will not have an expiry set.
     * Databases will respect the expiry.
     *
     * @var int seconds
     */
    protected $expires = 900;

    /** @var string the cache key */
    protected $key = 'configurations';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->type('configurations');
    }

    /**
     * Clears the persisted store.
     *
     * Configurations are needed for checking if a WordPress instance exists,
     * so we need to assume that will be cleared out here and handle this clear manually.
     *
     * @since 1.0.0
     */
    protected function clearPersisted()
    {
        delete_transient($this->getKey());
    }
}
