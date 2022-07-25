<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;
use InvalidArgumentException;

/**
 * Database setting values formatter trait.
 *
 * Formats the raw DB values to/from expected settings values.
 */
trait CanFormatDatabaseSettingValuesTrait
{
    use CanFormatSettingValuesTrait;

    /**
     * Formats a single value for storage handling.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValue($value, SettingContract $setting)
    {
        switch ($setting->getType()) {
            case AbstractSetting::TYPE_FLOAT:
                $value = (float) $value;
                break;
            case AbstractSetting::TYPE_INTEGER:
                $value = (int) $value;
                break;
            case AbstractSetting::TYPE_EMAIL:
            case AbstractSetting::TYPE_STRING:
            case AbstractSetting::TYPE_URL:
                $value = (string) $value;
                break;
            case AbstractSetting::TYPE_ARRAY:
                $value = ArrayHelper::wrap($value);
                break;
            case AbstractSetting::TYPE_BOOLEAN:
                throw new InvalidArgumentException(sprintf(
                    __('Please use %1$s or %2$s to format a boolean value for reading from or saving to storage.', 'mwc-core'),
                    __CLASS__.'::boolToString()',
                    __CLASS__.'::stringToBool()'
                ));
        }

        return $value;
    }

    /**
     * Converts a setting value from database for setting type consistency.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValueFromDatabase($value, SettingContract $setting)
    {
        return $this->formatValueWithCallback($value, $setting, [$this, 'formatSingleValueFromDatabase']);
    }

    /**
     * Converts a single setting value from database for setting type consistency.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueFromDatabase($value, SettingContract $setting)
    {
        if (AbstractSetting::TYPE_BOOLEAN === $setting->getType()) {
            return $this->stringToBool($value);
        }

        return $this->formatValue($value, $setting);
    }

    /**
     * Converts a setting value for database storage.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValueForDatabase($value, SettingContract $setting)
    {
        return $this->formatValueWithCallback($value, $setting, [$this, 'formatSingleValueForDatabase']);
    }

    /**
     * Converts a setting value for database storage.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueForDatabase($value, SettingContract $setting)
    {
        if (AbstractSetting::TYPE_BOOLEAN === $setting->getType()) {
            return $this->boolToString($value);
        }

        return $this->formatValue($value, $setting);
    }

    /**
     * Converts a string or numerical value to boolean for storage use.
     *
     * @see wc_string_to_bool() for a WooCommerce equivalent
     *
     * @param string|int|bool $value
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function stringToBool($value) : bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return 1 === $value;
        }

        if (is_string($value)) {
            return '1' === $value || 'yes' === strtolower($value) || 'true' === strtolower($value);
        }

        throw new InvalidArgumentException(sprintf(
            __('Cannot handle a "%s" type to parse a valid boolean value.', 'mwc-core'),
            gettype($value)
        ));
    }

    /**
     * Converts a boolean value to string value for storage use.
     *
     * @see wc_bool_to_string() for a WooCommerce equivalent
     *
     * @param string|int|bool $value
     * @return string
     * @throws InvalidArgumentException
     */
    protected function boolToString($value) : string
    {
        if (! is_bool($value)) {
            $value = $this->stringToBool($value);
        }

        return true === $value ? 'yes' : 'no';
    }
}
