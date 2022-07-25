<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

/**
 * A helper for validating value types.
 */
class ValidationHelper
{
    /**
     * Determines whether a value is an email.
     *
     * @see is_email() as an alternative WordPress function
     *
     * @since x.y.z
     *
     * @param mixed $value
     * @return bool
     */
    public static function isEmail($value) : bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Determines whether a value is a URL.
     *
     * This function does not evaluate the validity of a URL protocol.
     *
     * @since x.y.z
     *
     * @param mixed $value)
     * @param string[] $protocols optional protocols to validate the URL (default none)
     * @return bool
     */
    public static function isUrl($value, array $protocols = []) : bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return empty($protocols) || array_filter($protocols, static function ($protocol) use ($value) {
            return StringHelper::startsWith(parse_url($value, PHP_URL_SCHEME), $protocol);
        });
    }
}
