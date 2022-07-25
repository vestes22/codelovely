<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatDatabaseSettingValuesTrait;
use InvalidArgumentException;

/**
 * Data store for email notifications options settings.
 *
 * @since 2.15.0
 */
class OptionsSettingsDataStore
{
    use CanFormatDatabaseSettingValuesTrait;

    /** @var string */
    const SETTING_ID_MERGE_TAG = '{{setting_id}}';

    /** @var string a placeholder used to format option names */
    protected $optionNameTemplate;

    /**
     * Options settings data store constructor.
     *
     * @since 2.15.0
     *
     * @param string $optionNameTemplate
     * @throws InvalidArgumentException
     */
    public function __construct(string $optionNameTemplate)
    {
        if (! StringHelper::contains($optionNameTemplate, static::SETTING_ID_MERGE_TAG)) {
            throw new InvalidArgumentException(sprintf(
                __('Invalid option name template "%s": it should contain a {{setting_id}} placeholder.', 'mwc-core'),
                $optionNameTemplate
            ));
        }

        $this->optionNameTemplate = $optionNameTemplate;
    }

    /**
     * Reads the values of the options settings from database.
     *
     * @since 2.15.0
     *
     * @param ConfigurableContract $options
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    public function read(ConfigurableContract $options) : ConfigurableContract
    {
        foreach ($options->getSettings() as $setting) {
            $value = get_option($this->getOptionName($setting), null);

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

        return $options;
    }

    /**
     * Saves the options settings values to database.
     *
     * @since 2.15.0
     *
     * @param ConfigurableContract $options
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    public function save(ConfigurableContract $options) : ConfigurableContract
    {
        foreach ($options->getSettings() as $setting) {
            $optionName = $this->getOptionName($setting);

            if (! $setting->hasValue()) {
                delete_option($optionName);
                continue;
            }

            $value = $this->formatValueForDatabase($setting->getValue(), $setting);

            update_option($optionName, $value);
        }

        return $options;
    }

    /**
     * Deletes the options settings values from database.
     *
     * @since 2.15.0
     *
     * @param ConfigurableContract $options
     * @return ConfigurableContract
     */
    public function delete(ConfigurableContract $options) : ConfigurableContract
    {
        foreach ($options->getSettings() as $setting) {
            delete_option($this->getOptionName($setting));
            $setting->setValue($setting->getDefault());
        }

        return $options;
    }

    /**
     * Gets an option name for a given setting.
     *
     * @since 2.15.0
     *
     * @param SettingContract $setting
     * @return string
     */
    protected function getOptionName(SettingContract $setting) : string
    {
        return StringHelper::replaceFirst($this->optionNameTemplate, static::SETTING_ID_MERGE_TAG, $setting->getId());
    }
}
