<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry exception extension to handle a failure to refund a remote Poynt order.
 */
class RefundRemotePoyntOrderException extends SentryException
{
}
