<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry exception extension to handle failure to schedule a Poynt order sync push job.
 */
class PoyntOrderPushSyncJobException extends SentryException
{
}
