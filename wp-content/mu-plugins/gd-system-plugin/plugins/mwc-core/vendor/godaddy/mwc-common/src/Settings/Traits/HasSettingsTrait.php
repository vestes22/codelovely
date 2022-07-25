<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use InvalidArgumentException;

/**
 * Trait for classes that have settings.
 */
trait HasSettingsTrait
{
    /** @var SettingContract[]|null */
    protected $settings;

    /** @var ConfigurableContract|null parent group */
    protected $parent;

    /** @var ConfigurableContract[] sub groups */
    protected $subgroups;

    /**
     * Gets the settings configuration.
     *
     * @return array
     * @throws Exception
     */
    public function getConfiguration() : array
    {
        $configuration = [];

        foreach ($this->getSettings() as $setting) {
            $configuration[$setting->getName()] = $setting->hasValue() ? $setting->getValue() : $setting->getDefault();
        }

        return ArrayHelper::combine($configuration, $this->getSubgroupConfiguration($this->getSettingsSubgroups()));
    }

    /**
     * Gets the nested subgroups configurations.
     *
     * This is a recursive method that's keep being called as long as the current subgroup has children subgroups.
     *
     * @param ConfigurableContract[] $subgroups
     * @return array
     * @throws Exception
     */
    protected function getSubgroupConfiguration(array $subgroups) : array
    {
        $configuration = [];

        // iterates over the subgroups to create inner configurations
        foreach ($subgroups as $subgroup) {
            $subgroupConfiguration = [];

            // subgroups can also have their own settings
            foreach ($subgroup->getSettings() as $setting) {
                $subgroupConfiguration[$setting->getName()] = $setting->hasValue() ? $setting->getValue() : $setting->getDefault();
            }

            // creates the next configuration levels
            $subgroupConfiguration = ArrayHelper::combine($subgroupConfiguration, $this->getSubgroupConfiguration($subgroup->getSettingsSubgroups()));

            $configuration[$subgroup->getName()] = $subgroupConfiguration;
        }

        return $configuration;
    }

    /**
     * Gets the initial settings.
     *
     * Classes can override this to return their own settings objects.
     *
     * @return SettingContract[]
     */
    protected function getInitialSettings() : array
    {
        return [];
    }

    /**
     * Gets a setting.
     *
     * @param string $name
     * @return SettingContract
     * @throws InvalidArgumentException
     */
    public function getSetting(string $name) : SettingContract
    {
        foreach ($this->getSettings() as $setting) {
            if ($name === $setting->getName()) {
                return $setting;
            }
        }

        throw new InvalidArgumentException(sprintf(
            __('%s is not a valid setting.', 'mwc-core'),
            $name
        ));
    }

    /**
     * Gets the settings objects.
     *
     * @return SettingContract[]
     */
    public function getSettings() : array
    {
        // load the settings objects if not loaded previously
        if (null === $this->settings) {
            $this->settings = $this->getInitialSettings();
        }

        return $this->settings;
    }

    /**
     * Gets a setting's value.
     *
     * @param string $name
     * @return int|float|string|bool|array
     * @throws InvalidArgumentException
     */
    public function getSettingValue(string $name)
    {
        return $this->getSetting($name)->getValue();
    }

    /**
     * Gets the settings group ID, if available.
     *
     * @return string|null
     */
    public function getSettingsId()
    {
        return is_callable([$this, 'getId']) ? $this->getId() : null;
    }

    /**
     * Gets the parent setting group.
     *
     * @return ConfigurableContract|null
     */
    public function getSettingsParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent setting group.
     *
     * @param ConfigurableContract $value
     * @return self
     */
    public function setSettingsParent(ConfigurableContract $value)
    {
        $this->parent = $value;

        return $this;
    }

    /**
     * Gets the settings subgroups, if available.
     *
     * @return ConfigurableContract[]
     */
    public function getSettingsSubgroups() : array
    {
        // load the settings subgroup objects if not loaded previously
        if (null === $this->subgroups) {
            $this->subgroups = $this->getInitialSettingsSubgroups();
        }

        return $this->subgroups;
    }

    /**
     * Gets a subgroup from the settings subgroups with a given identifier.
     *
     * @param string $identifier
     * @return ConfigurableContract|null
     */
    public function getSettingsSubgroup(string $identifier)
    {
        foreach ($this->getSettingsSubgroups() as $subgroup) {
            if ($subgroup->getSettingsId() === $identifier) {
                return $subgroup;
            }
        }

        return null;
    }

    /**
     * Adds a subgroup.
     *
     * @param ConfigurableContract $value
     * @return self
     */
    public function addSettingsSubgroup(ConfigurableContract $value)
    {
        if (null === $this->subgroups) {
            $this->subgroups = [];
        }

        $this->subgroups[] = $value;

        return $this;
    }

    /**
     * Sets the setting subgroups.
     *
     * @param array $value
     * @return self
     */
    public function setSettingsSubgroups(array $value)
    {
        $this->subgroups = $value;

        return $this;
    }

    /**
     * Gets the initial settings subgroups, if any.
     *
     * @return ConfigurableContract[]
     */
    protected function getInitialSettingsSubgroups() : array
    {
        return [];
    }

    /**
     * Updates a setting's value.
     *
     * Will validate a value to be set against the setting options, if set.
     *
     * Passing a null value clears the value of the setting.
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function updateSettingValue(string $name, $value)
    {
        $setting = $this->getSetting($name);

        if (is_null($value)) {
            $setting->clearValue();

            return;
        }

        $setting->setValue($value)->update();
    }

    /**
     * Sets the settings objects.
     *
     * @param SettingContract[] $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setSettings(array $value)
    {
        foreach ($value as $instance) {
            if (! is_a($instance, SettingContract::class, true)) {
                throw new InvalidArgumentException(__('The settings objects must be an instance of SettingContract', 'mwc-core'));
            }
        }
        $this->settings = $value;

        return $this;
    }
}
