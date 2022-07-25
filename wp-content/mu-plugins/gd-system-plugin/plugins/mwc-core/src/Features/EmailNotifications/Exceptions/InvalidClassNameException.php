<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * An exception to be thrown when a class name doesn't point to a class of the expected type.
 */
class InvalidClassNameException extends SentryException
{
}
