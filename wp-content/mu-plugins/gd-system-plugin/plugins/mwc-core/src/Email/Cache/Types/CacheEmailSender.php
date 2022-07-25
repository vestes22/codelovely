<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Email sender cache handler class.
 */
class CacheEmailSender extends Cache implements CacheableContract
{
    use IsSingletonTrait;

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 600;

    /**
     * Constructor.
     *
     * @param string $emailAddress
     */
    final public function __construct(string $emailAddress)
    {
        $this->type('email_sender');
        $this->key(sprintf('email_sender_%s', strtolower($emailAddress)));
    }

    /**
     * Creates a new email sender cache for a given email address.
     *
     * @param string $emailAddress
     * @return CacheEmailSender
     */
    public static function for(string $emailAddress) : CacheEmailSender
    {
        return new static($emailAddress);
    }
}
