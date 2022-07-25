<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * An exception to be thrown when a required parameter is missing.
 */
class MissingParameterException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 400;
}
