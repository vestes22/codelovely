<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

/**
 * A trait with helper methods to convert WooCommerce email placeholders.
 */
trait CanConvertWooCommerceEmailPlaceholdersTrait
{
    /**
     * Converts WooCommerce email placeholders into Email Notifications placeholders.
     *
     * Returns placeholders using two curly braces at each side.
     *
     * @param string $value a string that can contain placeholders
     * @return string
     */
    protected function convertPlaceholdersFromSource(string $value) : string
    {
        return preg_replace('/\{{1,2}\s*(\w+)\s*\}{1,2}/', '{{${1}}}', $value);
    }

    /**
     * Converts Email Notifications placeholders into WooCommerce email placeholders.
     *
     * Returns placeholders using one curly brace at each side.
     *
     * @param string $value a string that can contain placeholders
     * @return string
     */
    protected function convertPlaceholdersToSource(string $value) : string
    {
        return preg_replace('/\{{1,2}\s*(\w+)\s*\}{1,2}/', '{${1}}', $value);
    }
}
