<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Cache for the GoDaddy Payments onboarding return URL.
 *
 * @method static CacheOnboardingReturnUrl getInstance() Gets the singleton instance.
 */
class CacheOnboardingReturnUrl extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for - defaults to a week */
    protected $expires = 604800;

    /** @var string the cache key */
    protected $key = 'godaddy_payments_onboarding_return_url';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->type($this->key);
    }
}
