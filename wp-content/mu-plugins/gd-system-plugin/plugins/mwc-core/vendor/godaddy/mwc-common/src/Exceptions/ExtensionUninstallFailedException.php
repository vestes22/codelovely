<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

/**
 * Exception to report a failed extension uninstall attempt to Sentry.
 *
 * @since 3.4.1
 */
class ExtensionUninstallFailedException extends SentryException
{
}
