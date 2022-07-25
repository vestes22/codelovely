<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * A base exception to be thrown when an object cannot be found from a datastore.
 */
abstract class AbstractNotFoundException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 404;
}
