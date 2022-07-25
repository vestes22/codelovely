<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

class NonceVerificationFailedException extends SentryException
{
    /** @var int exception code */
    protected $code = 400;
}
