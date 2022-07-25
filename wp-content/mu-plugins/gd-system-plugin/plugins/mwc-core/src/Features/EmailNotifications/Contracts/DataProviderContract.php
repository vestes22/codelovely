<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

/**
 * Interface for data providers that can prepare data used by email notifications.
 *
 * This could be values for merge tags or replacing custom MJML components.
 */
interface DataProviderContract
{
    /**
     * Gets an array of data.
     *
     * @return array
     */
    public function getData() : array;

    /**
     * Gets placeholders in array form.
     *
     * @return string[]
     */
    public function getPlaceholders() : array;
}
