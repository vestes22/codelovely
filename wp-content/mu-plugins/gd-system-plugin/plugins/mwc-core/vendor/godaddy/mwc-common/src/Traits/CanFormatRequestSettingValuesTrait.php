<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;

/**
 * Request setting values formatter trait.
 *
 * Formats the raw request/response values to/from expected settings values.
 */
trait CanFormatRequestSettingValuesTrait
{
    use CanFormatSettingValuesTrait;

    /**
     * Converts a setting value from a request for setting type consistency.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValueFromRequest($value, SettingContract $setting)
    {
        return $this->formatValueWithCallback($value, $setting, [$this, 'formatSingleValueFromRequest']);
    }

    /**
     * Converts a single setting value from a request for setting type consistency.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueFromRequest($value, SettingContract $setting)
    {
        return $this->formatValue($value, $setting);
    }

    /**
     * Converts a setting value for a response.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValueForRequest($value, SettingContract $setting)
    {
        return $this->formatValueWithCallback($value, $setting, [$this, 'formatSingleValueForRequest']);
    }

    /**
     * Converts a single setting value for a response.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueForRequest($value, SettingContract $setting)
    {
        return $this->formatValue($value, $setting);
    }

    /**
     * Formats a single value to be used in request and responses.
     *
     * TODO: how can we allow individual settings to determine whether they allow HTML tags in their values? {wvega 2021-10-14}
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string|array
     */
    protected function formatValue($value, SettingContract $setting)
    {
        switch ($setting->getType()) {
            case AbstractSetting::TYPE_FLOAT:
                return (float) $value;
            case AbstractSetting::TYPE_INTEGER:
                return (int) $value;
            case AbstractSetting::TYPE_EMAIL:
            case AbstractSetting::TYPE_URL:
                return (string) StringHelper::sanitize(StringHelper::unslash($value));
            case AbstractSetting::TYPE_STRING:
                return (string) $this->sanitizeString(StringHelper::unslash((string) $value), $setting);
            case AbstractSetting::TYPE_BOOLEAN:
                return (bool) $value;
            case AbstractSetting::TYPE_ARRAY:
                return ArrayHelper::wrap($value);
        }

        return $value;
    }

    /**
     * Sanitizes the given string removing any HTML tags and element attributes not explicitly allowed.
     *
     * @param stirng $value setting value
     * @param SettingContract $setting
     * @return string
     */
    protected function sanitizeString(string $value, SettingContract $setting) : string
    {
        if ($allowedHtmlTags = $this->getAllowedHtmlTagsForSetting($setting)) {
            // TODO: allow us to use something like StringHelper::sanitize($value)->withHtmlTags() to get a string with allowed HTML tags {wvega 2021-10-14}
            return (string) wp_kses($value, $allowedHtmlTags);
        }

        if ($this->shouldAllowWhitespaceForSetting($setting)) {
            return (string) sanitize_textarea_field($value);
        }

        return StringHelper::sanitize($value);
    }

    /**
     * Determines whether we should allow new lines and other whitespace in the value of the given setting.
     *
     * Whitespace is not allowed for any setting by default.
     *
     * @param SettingContract $setting
     * @return bool
     */
    protected function shouldAllowWhitespaceForSetting(SettingContract $setting) : bool
    {
        return false;
    }

    /**
     * Gets a list of HTML tags allowed in the value of the given setting.
     *
     * No HTML tags are allowed by default.
     *
     * @param SettingContract $setting
     * @return array
     */
    protected function getAllowedHtmlTagsForSetting(SettingContract $setting) : array
    {
        return [];
    }
}
