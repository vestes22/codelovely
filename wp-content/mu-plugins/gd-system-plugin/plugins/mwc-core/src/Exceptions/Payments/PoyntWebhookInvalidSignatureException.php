<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry exception extension to handle invalid Poynt webhook signature.
 */
class PoyntWebhookInvalidSignatureException extends SentryException
{
}
