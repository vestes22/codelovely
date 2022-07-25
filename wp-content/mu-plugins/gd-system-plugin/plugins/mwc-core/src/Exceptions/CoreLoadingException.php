<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry exception extension to handle failure to load MWC Core.
 */
class CoreLoadingException extends SentryException
{
    /**
     * Constructor.
     *
     * @param string $message exception message
     * @throws Exception
     */
    public function __construct(string $message)
    {
        parent::__construct($message);

        $this->code = 500;
        $this->level = 'error';
    }
}
