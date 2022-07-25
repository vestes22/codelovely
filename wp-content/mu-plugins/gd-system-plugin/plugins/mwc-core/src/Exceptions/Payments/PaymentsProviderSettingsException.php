<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * Sentry exception extension to handle failure to load MWC Core.
 */
class PaymentsProviderSettingsException extends SentryException
{
}
