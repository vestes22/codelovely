<?php

namespace GoDaddy\WordPress\MWC\Common\Loggers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use Psr\Log\AbstractLogger;

/**
 * Logger.
 *
 * This implementation allows for us to use more advanced logging across the platform and have more fine grained control of how Errors and Exceptions are handled.
 * @link https://www.php-fig.org/psr/psr-3/d
 */
class Logger extends AbstractLogger
{
    /**
     * Logs a message according to params.
     *
     * @param string|int $level message level
     * @param string $message log message
     * @param array $context context
     */
    public function log($level, $message, array $context = [])
    {
        // TODO: Do we want error logs to go somewhere else?
        if (Configuration::get('mwc.debug')) {
            error_log(
                print_r(
                    [
                        'level'   => $level,
                        'message' => $message,
                        'context' => $context,
                    ],
                    true
                )
            );
        } else {
            error_log("{$level}: {$message}");
        }
    }
}
