<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatDatabaseSettingValuesTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\SettingsDataStore as RawSettingsDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use InvalidArgumentException;

/**
 * Wrapper for Settings Data store.
 */
class SettingsDataStore
{
    use CanFormatDatabaseSettingValuesTrait;

    /** @var RawSettingsDataStore */
    protected $dataStore;

    /** @var string[] */
    protected $wooCommerceOptionsMap = [
        GeneralSettings::SETTING_ID_SENDER_NAME    => 'woocommerce_email_from_name',
        GeneralSettings::SETTING_ID_SENDER_ADDRESS => 'woocommerce_email_from_address',
    ];

    /**
     * SettingsDataStore constructor.
     *
     * @param RawSettingsDataStore|null $dataStore
     */
    public function __construct(RawSettingsDataStore $dataStore = null)
    {
        $this->dataStore = $dataStore ?? new RawSettingsDataStore();
    }

    /**
     * Reads the values of the settings from database.
     *
     * @param string $id
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    public function read(string $id) : ConfigurableContract
    {
        return $this->maybeMapWooCommerceSettings($this->dataStore->read($id), false);
    }

    /**
     * Saves the settings values to database.
     *
     * @param ConfigurableContract $settingGroup
     * @return ConfigurableContract
     */
    public function save(ConfigurableContract $settingGroup) : ConfigurableContract
    {
        return $this->dataStore->save($this->maybeMapWooCommerceSettings($settingGroup, true));
    }

    /**
     * May map settings with counterpart WooCommerce options.
     *
     * @param ConfigurableContract $settingGroup
     * @param bool $saveSettings
     * @return ConfigurableContract
     */
    protected function maybeMapWooCommerceSettings(ConfigurableContract $settingGroup, bool $saveSettings) : ConfigurableContract
    {
        if (GeneralSettings::GROUP_ID === $settingGroup->getSettingsId()) {
            $settingGroup = $saveSettings ? $this->mapSaveWooCommerceSettings($settingGroup) : $this->mapReadWooCommerceSettings($settingGroup);
        }

        return $settingGroup;
    }

    /**
     * @param SettingContract $setting
     * @return string
     */
    protected function getCounterPartOptionName(SettingContract $setting) : string
    {
        return ArrayHelper::get($this->wooCommerceOptionsMap, $setting->getId(), '');
    }

    /**
     * Map reading settings with counterpart WooCommerce options.
     *
     * @param ConfigurableContract $settingGroup
     * @return ConfigurableContract
     */
    protected function mapReadWooCommerceSettings(ConfigurableContract $settingGroup) : ConfigurableContract
    {
        foreach ($settingGroup->getSettings() as $setting) {
            $counterpartOptionName = $this->getCounterPartOptionName($setting);

            // skip if no counterpart option found
            if (empty($counterpartOptionName) || $setting->hasValue()) {
                continue;
            }

            $optionValue = get_option($counterpartOptionName);

            if (empty($optionValue)) {
                $optionValue = $setting->getDefault();
            }

            if (null !== $optionValue) {
                // set value if it has counterpart option
                try {
                    $setting->setValue($this->formatValueFromDatabase($optionValue, $setting));
                } catch (InvalidArgumentException $exception) {
                    // the option value is not valid for the setting, skip setting it
                }
            }
        }

        return $settingGroup;
    }

    /**
     * Map saving settings with counterpart WooCommerce options.
     *
     * @param ConfigurableContract $settingGroup
     * @return ConfigurableContract
     */
    protected function mapSaveWooCommerceSettings(ConfigurableContract $settingGroup) : ConfigurableContract
    {
        foreach ($settingGroup->getSettings() as $setting) {
            $counterpartOptionName = $this->getCounterPartOptionName($setting);

            // skip if no counterpart option found
            if (empty($counterpartOptionName) || ! $setting->hasValue()) {
                continue;
            }

            // update the counterpart option with the setting value
            update_option($counterpartOptionName, $this->formatValueForDatabase($setting->getValue(), $setting));
        }

        return $settingGroup;
    }
}
