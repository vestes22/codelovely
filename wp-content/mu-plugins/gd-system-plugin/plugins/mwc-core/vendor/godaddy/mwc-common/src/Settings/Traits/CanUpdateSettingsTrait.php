<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use InvalidArgumentException;

trait CanUpdateSettingsTrait
{
    /**
     * Updates the settings and settings subgroups in the given configurable object using the provided data.
     *
     * @param ConfigurableContract $configurable the configurable object to update
     * @param array $settings optional array of setting data
     * @param array $subgroups optional array of subgroups data
     * @throws InvalidArgumentException
     */
    protected function updateConfigurableSettings(ConfigurableContract $configurable, array $settings = [], array $subgroups = [])
    {
        $this->updateSettingsValues($configurable, $settings);
        $this->updateSettingsSubgroups($configurable, $subgroups);
    }

    /**
     * Updates the settings in the given configurable object using the given settings data.
     *
     * @param ConfigurableContract $configurable the configurable object to update
     * @param array $settings array of setting data
     * @throws InvalidArgumentException
     */
    protected function updateSettingsValues(ConfigurableContract $configurable, array $settings = [])
    {
        foreach ($settings as $data) {
            if (! $settingName = ArrayHelper::get($data, 'name')) {
                throw new InvalidArgumentException(__('The name of the setting is required.', 'mwc-core'), 400);
            }

            $this->updateSettingValue($configurable, $settingName, ArrayHelper::get($data, 'value'));
        }
    }

    /**
     * Updates the value of a setting in the given configurable object using a formatted version of the given value.
     *
     * @param ConfigurableContract $configurable
     * @param string $settingName
     * @param mixed $settingValue
     * @throws InvalidArgumentException
     */
    protected function updateSettingValue(ConfigurableContract $configurable, string $settingName, $settingValue)
    {
        $setting = $configurable->getSetting($settingName);

        $configurable->updateSettingValue($settingName, $this->getFormattedSettingValue($setting, $settingValue));
    }

    /**
     * Converts the given value from a request into a value with the appropriate format and type for the given setting.
     *
     * @param SettingContract $setting
     * @param bool|float|int|string|array|null $value
     * @return bool|float|int|string|array|null
     * @throws InvalidArgumentException
     */
    abstract protected function getFormattedSettingValue(SettingContract $setting, $value);

    /**
     * Updates the settings subgroups in the given configurable object using the given settings data.
     *
     * @param ConfigurableContract $configurable the configurable object to update
     * @param array $subgroups an array of subgroups data
     * @throws InvalidArgumentException
     */
    public function updateSettingsSubgroups(ConfigurableContract $configurable, array $subgroups)
    {
        foreach ($subgroups as $data) {
            if (! $subgroupName = ArrayHelper::get($data, 'name')) {
                throw new InvalidArgumentException(__('The name of the setting subgroup is required.', 'mwc-core'), 400);
            }

            if (! $subgroup = $configurable->getSettingsSubgroup($subgroupName)) {
                throw new InvalidArgumentException(__("No setting subgroup exists with name: {$subgroupName}.", 'mwc-core'), 400);
            }

            $this->updateConfigurableSettings(
                $subgroup,
                ArrayHelper::wrap(ArrayHelper::get($data, 'settings')),
                ArrayHelper::wrap(ArrayHelper::get($data, 'subgroups'))
            );
        }
    }
}
