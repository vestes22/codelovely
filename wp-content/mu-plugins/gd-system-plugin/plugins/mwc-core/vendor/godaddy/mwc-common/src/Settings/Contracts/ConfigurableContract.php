<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;

/**
 * A contract for objects that can be configured (and hold settings).
 */
interface ConfigurableContract extends HasLabelContract
{
    /**
     * Gets the settings.
     *
     * @return SettingContract[]
     */
    public function getSettings() : array;

    /**
     * Gets a setting by its name.
     *
     * @param string $name
     * @return self
     */
    public function getSetting(string $name);

    /**
     * Updates a setting value.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function updateSettingValue(string $name, $value);

    /**
     * Gets a setting value.
     *
     * @param string $name
     * @return mixed
     */
    public function getSettingValue(string $name);

    /**
     * Gets the object's configuration as an array with setting names as keys and setting values as values.
     *
     * @return array
     */
    public function getConfiguration() : array;

    /**
     * Sets the settings.
     *
     * @param SettingContract[] $value
     * @return self
     */
    public function setSettings(array $value);

    /**
     * Gets the settings id.
     *
     * @return string|null
     */
    public function getSettingsId();

    /**
     * Gets the settings parent.
     *
     * @return self|null
     */
    public function getSettingsParent();

    /**
     * Gets the settings subgroups.
     *
     * @return ConfigurableContract[]
     */
    public function getSettingsSubgroups() : array;

    /**
     * Gets a settings subgroup by ID.
     *
     * @param string $identifier
     * @return self|null
     */
    public function getSettingsSubgroup(string $identifier);

    /**
     * Adds a settings subgroup.
     *
     * @param ConfigurableContract $value
     * @return self
     */
    public function addSettingsSubgroup(ConfigurableContract $value);

    /**
     * Sets a settings parent.
     *
     * @param ConfigurableContract $value
     * @return self
     */
    public function setSettingsParent(ConfigurableContract $value);

    /**
     * Sets the settings subgroups.
     *
     * @param ConfigurableContract[] $value
     * @return self
     */
    public function setSettingsSubgroups(array $value);
}
