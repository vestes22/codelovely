<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Traits;

use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatRequestSettingValuesTrait;
use InvalidArgumentException;

trait CanUpdateSettingsUsingRequestDataTrait
{
    use CanUpdateSettingsTrait;
    use CanFormatRequestSettingValuesTrait;

    /**
     * Converts the given value from a request into a value with the appropriate format and type for the given setting.
     *
     * @param SettingContract $setting
     * @param bool|float|int|string|array|null $value
     * @return bool|float|int|string|array|null
     * @throws InvalidArgumentException
     */
    protected function getFormattedSettingValue(SettingContract $setting, $value)
    {
        return is_null($value) ? $value : $this->formatValueFromRequest($value, $setting);
    }
}
