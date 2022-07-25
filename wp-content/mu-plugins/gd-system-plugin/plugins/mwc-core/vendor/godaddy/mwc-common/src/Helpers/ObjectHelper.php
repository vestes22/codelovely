<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use stdClass;

/**
 * A helper to manipulate objects.
 *
 * @since 1.0.0
 */
class ObjectHelper
{
    /**
     * Casts item as array if it is a valid object.
     *
     * @since 1.0.0
     *
     * @param mixed $item
     *
     * @return array
     */
    public static function toArray($item) : array
    {
        return is_object($item) || $item instanceof stdClass ? (array) $item : $item;
    }
}
