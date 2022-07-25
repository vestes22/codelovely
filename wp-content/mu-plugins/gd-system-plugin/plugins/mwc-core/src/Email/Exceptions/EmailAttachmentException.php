<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Exception to report to Sentry that an error occurred trying to interact with emails attachments.
 */
class EmailAttachmentException extends SentryException
{
}
