<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * Repository handler for WordPress date-time functions and methods.
 */
class DateTimeRepository
{
    /**
     * Gets the date format from WordPress settings.
     *
     * @return string
     * @throws Exception
     */
    public static function getDateFormat() : string
    {
        if (WooCommerceRepository::isWooCommerceActive()) {
            return (string) wc_date_format();
        }

        $defaultFormat = 'F j, Y';
        $dateFormat = get_option('date_format', $defaultFormat);

        if (empty($dateFormat) || ! is_string($dateFormat)) {
            return $defaultFormat;
        }

        return $dateFormat;
    }

    /**
     * Gets the time format from WordPress settings.
     *
     * @return string
     * @throws Exception
     */
    public static function getTimeFormat() : string
    {
        if (WooCommerceRepository::isWooCommerceActive()) {
            return (string) wc_time_format();
        }

        $defaultFormat = 'g:i a';
        $timeFormat = get_option('time_format', $defaultFormat);

        if (empty($timeFormat) || ! is_string($timeFormat)) {
            return $defaultFormat;
        }

        return $timeFormat;
    }

    /**
     * Gets a localized date.
     *
     * @param string $format the PHP format used to display the date
     * @param int|false $timestamp optional timestamp with offset
     * @param bool $utc whether date is assumed UTC (only used if timestamp offset not provided)
     * @return string
     */
    public static function getLocalizedDate(string $format, $timestamp = false, bool $utc = false) : string
    {
        $localizedDate = date_i18n($format, $timestamp, $utc);

        return is_string($localizedDate) ? $localizedDate : '';
    }
}
