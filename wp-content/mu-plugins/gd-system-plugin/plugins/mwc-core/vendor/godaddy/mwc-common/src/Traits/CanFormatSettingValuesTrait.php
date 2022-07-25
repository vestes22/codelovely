<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use InvalidArgumentException;

/**
 * Setting values formatter trait.
 *
 * Formats the raw values to/from expected settings values.
 */
trait CanFormatSettingValuesTrait
{
    /**
     * Formats a value or a list of values using the given callback.
     *
     * @param mixed $value
     * @param SettingContract $setting
     * @param callable $callback
     * @return bool|float|int|string|array
     * @throws InvalidArgumentException
     */
    protected function formatValueWithCallback($value, SettingContract $setting, $callback)
    {
        if ($setting->isMultivalued()) {
            return array_map(function ($value) use ($setting, $callback) {
                return $callback($value, $setting);
            }, ArrayHelper::wrap($value));
        }

        return $callback($value, $setting);
    }
}
