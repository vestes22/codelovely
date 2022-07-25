<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use InvalidArgumentException;

/**
 * Data store for recursively manage email notifications options settings.
 */
class RecursiveOptionsSettingsDataStore extends OptionsSettingsDataStore
{
    /**
     * {@inheritdoc}
     */
    public function read(ConfigurableContract $options) : ConfigurableContract
    {
        return $this->readSubgroupsSettings(parent::read($options));
    }

    /**
     * Reads the values of the options subgroups settings from database.
     *
     * @param ConfigurableContract $options
     * @return ConfigurableContract
     */
    protected function readSubgroupsSettings(ConfigurableContract $options) : ConfigurableContract
    {
        foreach ($options->getSettingsSubgroups() as $settingsSubgroup) {
            // we don't pass the $parentSettingsGroupId parameter on this call because we assume that
            // the ID of the configurable object is already represented in the template for the option name
            $this->deepReadSubgroupSettings($settingsSubgroup);
        }

        return $options;
    }

    /**
     * Reads recursively the values of the given subgroup settings from database.
     *
     * @param ConfigurableContract $settingsSubgroup
     * @param string|null $parentSettingsGroupId
     */
    protected function deepReadSubgroupSettings(ConfigurableContract $settingsSubgroup, string $parentSettingsGroupId = null)
    {
        if ($settingsSubSubgroups = $settingsSubgroup->getSettingsSubgroups()) {
            $settingsSubSubgroupsParentId = $this->prependParentSettingGroupId($settingsSubgroup->getSettingsId(), $parentSettingsGroupId);

            foreach ($settingsSubSubgroups as $settingsSubSubgroup) {
                $this->deepReadSubgroupSettings($settingsSubSubgroup, $settingsSubSubgroupsParentId);
            }
        }

        $this->readSubgroupSettings($settingsSubgroup, $parentSettingsGroupId);
    }

    /**
     * Reads the values of the given subgroup settings from database.
     *
     * @param ConfigurableContract $settingsSubgroup
     * @param string|null $parentSettingsGroupId
     */
    protected function readSubgroupSettings(ConfigurableContract $settingsSubgroup, string $parentSettingsGroupId = null)
    {
        $parentSettingsGroupId = $this->prependParentSettingGroupId($settingsSubgroup->getSettingsId(), $parentSettingsGroupId);

        foreach ($settingsSubgroup->getSettings() as $setting) {
            $value = get_option($this->getSubOptionName($setting, $parentSettingsGroupId), null);

            if (null === $value) {
                continue;
            }

            $value = $this->formatValueFromDatabase($value, $setting);

            try {
                $setting->setValue($value);
            } catch (InvalidArgumentException $exception) {
                // the option value is not valid for the setting, skip setting it
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(ConfigurableContract $options) : ConfigurableContract
    {
        return $this->saveSubgroupsSettings(parent::save($options));
    }

    /**
     * Saves the values of the options subgroups settings to database.
     *
     * @param ConfigurableContract $options
     * @return ConfigurableContract
     */
    protected function saveSubgroupsSettings(ConfigurableContract $options) : ConfigurableContract
    {
        foreach ($options->getSettingsSubgroups() as $settingsSubgroup) {
            // we don't pass the $parentSettingsGroupId parameter on this call because we assume that
            // the ID of the configurable object is already represented in the template for the option name
            $this->deepSaveSubgroupSettings($settingsSubgroup);
        }

        return $options;
    }

    /**
     * Saves recursively the values of the given subgroup settings to database.
     *
     * @param ConfigurableContract $settingsSubgroup
     * @param string|null $parentSettingsGroupId
     */
    protected function deepSaveSubgroupSettings(ConfigurableContract $settingsSubgroup, string $parentSettingsGroupId = null)
    {
        if ($settingsSubSubgroups = $settingsSubgroup->getSettingsSubgroups()) {
            $settingsSubSubgroupsParentId = $this->prependParentSettingGroupId($settingsSubgroup->getSettingsId(), $parentSettingsGroupId);

            foreach ($settingsSubSubgroups as $settingsSubSubgroup) {
                $this->deepSaveSubgroupSettings($settingsSubSubgroup, $settingsSubSubgroupsParentId);
            }
        }

        $this->saveSubgroupSettings($settingsSubgroup, $parentSettingsGroupId);
    }

    /**
     * Saves the values of the given subgroup settings to database.
     *
     * @param ConfigurableContract $settingsSubgroup
     * @param string|null $parentSettingsGroupId
     */
    protected function saveSubgroupSettings(ConfigurableContract $settingsSubgroup, string $parentSettingsGroupId = null)
    {
        $parentSettingsGroupId = $this->prependParentSettingGroupId($settingsSubgroup->getSettingsId(), $parentSettingsGroupId);

        foreach ($settingsSubgroup->getSettings() as $setting) {
            $optionName = $this->getSubOptionName($setting, $parentSettingsGroupId);

            if (! $setting->hasValue()) {
                delete_option($optionName);
                continue;
            }

            $value = $this->formatValueForDatabase($setting->getValue(), $setting);

            update_option($optionName, $value);
        }
    }

    /**
     * Prepends parent settings group ID for full path.
     *
     * @param string $settingGroupId
     * @param string|null $parentSettingsGroupId
     * @return string
     */
    protected function prependParentSettingGroupId(string $settingGroupId, string $parentSettingsGroupId = null) : string
    {
        if ($parentSettingsGroupId) {
            return "{$parentSettingsGroupId}_{$settingGroupId}";
        }

        return $settingGroupId;
    }

    /**
     * Gets a sub-option name for a given setting.
     *
     * @param SettingContract $setting
     * @param string $parentSettingsGroupId
     * @return string
     */
    protected function getSubOptionName(SettingContract $setting, string $parentSettingsGroupId) : string
    {
        return StringHelper::replaceFirst($this->optionNameTemplate, static::SETTING_ID_MERGE_TAG, "{$parentSettingsGroupId}_{$setting->getId()}");
    }
}
