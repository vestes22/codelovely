<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Loggers\Logger;

/**
 * A helper to handle deprecations.
 */
class DeprecationHelper
{
    /**
     * Flags a class as deprecated.
     *
     * @param string $class the class being deprecated, e.g. __CLASS__
     * @param string $version version from which the class was deprecated
     * @param string $replacement replacement handler (optional)
     * @return string deprecation message
     */
    public static function deprecatedClass(string $class, string $version, string $replacement = '')  : string
    {
        return self::logDeprecation($class, $version, $replacement);
    }

    /**
     * Flags a function or method as deprecated.
     *
     * @param string $function the function or method being deprecated, e.g. __METHOD__
     * @param string $version version from which the class was deprecated
     * @param string $replacement function or method replacing the deprecated one (optional)
     * @return string deprecation message
     */
    public static function deprecatedFunction(string $function, string $version, string $replacement = '') : string
    {
        return self::logDeprecation($function, $version, $replacement);
    }

    /**
     * Logs an item as deprecated.
     *
     * @param string $element class, method or function name
     * @param string $version version from which the item was deprecated
     * @param string $replacement an alternative class, method or function to use in place of the deprecated (optional)
     * @return string deprecation message
     */
    private static function logDeprecation(string $element, string $version, string $replacement = '') : string
    {
        if ($replacement) {
            $message = "{$element} is deprecated since version {$version}! Use {$replacement} instead.";
        } else {
            $message = "{$element} is deprecated since version {$version} with no alternative available.";
        }

        (new Logger())->log(E_USER_DEPRECATED, $message);

        if (Configuration::get('mwc.debug')) {
            trigger_error($message, E_USER_DEPRECATED);
        }

        return $message;
    }
}
