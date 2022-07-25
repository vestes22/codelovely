<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatDatabaseSettingValuesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts\OnboardingSettingFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts\SettingDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\OnboardingSetting;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\OnboardingSettingFactory;
use InvalidArgumentException;

/**
 * Data store that can save Onboarding settings to WP options.
 */
class OptionsSettingDataStore implements SettingDataStoreContract
{
    use CanFormatDatabaseSettingValuesTrait;

    /** @var OnboardingSettingFactoryContract */
    protected $factory;

    /**
     * @var array map setting id => WP option name.
     * @note To use a reader callable, set ['reader' => ..., 'optionName' => ...]
     */
    protected $mapOfSettingIdToOptionName = [
        OnboardingSetting::SETTING_ID_FIRST_TIME                   => 'mwc_onboarding_first_time',
        OnboardingSetting::SETTING_ID_STORE_NAME                   => 'blogname',
        OnboardingSetting::SETTING_ID_STORE_ADDRESS_FIRST_LINE     => [
            'builder'    => 'buildStoreAddressFirstLineSetting',
            'optionName' => 'woocommerce_store_address',
        ],
        OnboardingSetting::SETTING_ID_STORE_ADDRESS_SECOND_LINE    => [
            'builder'    => 'buildStoreAddressSecondLineSetting',
            'optionName' => 'woocommerce_store_address_2',
        ],
        OnboardingSetting::SETTING_ID_COUNTRY_REGION               => 'woocommerce_default_country',
        OnboardingSetting::SETTING_ID_CITY                         => 'woocommerce_store_city',
        OnboardingSetting::SETTING_ID_POSTAL_CODE                  => 'woocommerce_store_postcode',
        OnboardingSetting::SETTING_ID_CURRENCY                     => [
            'optionName' => 'woocommerce_currency',
            'reader'     => 'get_woocommerce_currency',
        ],
        OnboardingSetting::SETTING_ID_CURRENCY_POSITION            => 'woocommerce_currency_pos',
        OnboardingSetting::SETTING_ID_SELLING_LOCATIONS            => 'woocommerce_allowed_countries', // e.g., 'specific'
        OnboardingSetting::SETTING_ID_SELL_TO_SPECIFIC_COUNTRIES   => 'woocommerce_specific_allowed_countries', // e.g., US, IN, BR
        OnboardingSetting::SETTING_ID_SELL_TO_ALL_COUNTRIES_EXCEPT => 'woocommerce_all_except_countries', // e.g., US, IN, BR
        OnboardingSetting::SETTING_ID_SHIPPING_LOCATIONS           => 'woocommerce_ship_to_countries', // e.g., 'specific'
        OnboardingSetting::SETTING_ID_SHIP_TO_SPECIFIC_COUNTRIES   => 'woocommerce_specific_ship_to_countries', // e.g., US, IN, BR
        OnboardingSetting::SETTING_ID_THOUSANDS_SEPARATOR          => [
            'optionName' => 'woocommerce_price_thousand_sep',
            'reader'     => 'wc_get_price_thousand_separator',
        ],
        OnboardingSetting::SETTING_ID_DECIMAL_SEPARATOR            => [
            'optionName' => 'woocommerce_price_decimal_sep',
            'reader'     => 'wc_get_price_decimal_separator',
        ],
        OnboardingSetting::SETTING_ID_NUMBER_OF_DECIMALS           => [
            'optionName' => 'woocommerce_price_num_decimals',
            'reader'     => 'wc_get_price_decimals',
        ],
        OnboardingSetting::SETTING_ID_WEIGHT_UNITS                 => 'woocommerce_weight_unit',
        OnboardingSetting::SETTING_ID_DIMENSION_UNITS              => 'woocommerce_dimension_unit',
        OnboardingSetting::SETTING_ID_ENABLE_TAXES                 => 'woocommerce_calc_taxes',
        OnboardingSetting::SETTING_ID_LAST_ONBOARDING_STEP         => 'mwc_onboarding_last_step',
        OnboardingSetting::SETTING_ID_COMPLETED                    => 'mwc_onboarding_completed',
    ];

    /**
     * Constructor.
     *
     * If a factory is not provided, the constructor will create an instance of {@see OnboardingSettingFactory}.
     *
     * @param OnboardingSettingFactoryContract $factory optional setting factory;
     */
    public function __construct(OnboardingSettingFactoryContract $factory = null)
    {
        if (! $factory) {
            $factory = new OnboardingSettingFactory();
        }

        $this->factory = $factory;
    }

    /**
     * Reads the values of the options settings from database.
     *
     * @param string $identifier
     * @return SettingContract
     */
    public function read(string $identifier) : SettingContract
    {
        $setting = $this->buildSetting($identifier);
        $value = $this->readStoredValue($setting);

        if (null === $value) {
            return $setting->clearValue();
        }

        try {
            $setting->setValue($this->formatValueFromDatabase($value, $setting));
        } catch (InvalidArgumentException $exception) {
            // the option value is not valid for the setting, skip setting it
        }

        return $setting;
    }

    /**
     * Saves the options settings values to database.
     *
     * @param SettingContract $setting
     * @return SettingContract
     * @throws InvalidArgumentException
     */
    public function save(SettingContract $setting) : SettingContract
    {
        $optionName = $this->getOptionName($setting);

        $settingValue = $setting->getValue();

        if (null === $settingValue) {
            return $setting;
        }

        $value = $this->formatValueForDatabase($settingValue, $setting);

        update_option($optionName, $value);

        return $setting;
    }

    /**
     * Deletes the options settings values from database.
     *
     * @param SettingContract $setting
     * @return SettingContract
     */
    public function delete(SettingContract $setting) : SettingContract
    {
        delete_option($this->getOptionName($setting));

        return $setting;
    }

    /**
     * Builds a setting object calling the configured method of the factory instance.
     *
     * @param string $identifier
     * @return SettingContract
     */
    protected function buildSetting(string $identifier)
    {
        $methodName = $this->getBuilderName($identifier);

        return $this->factory->{$methodName}();
    }

    /**
     * Gets the name of the method that should be called to build a setting object for the given identifier.
     *
     * Returns buildIdentifierSetting() as the builder method name if a name cannot be found in the setting map.
     *
     * @param string $identifier
     * @return string
     */
    protected function getBuilderName(string $identifier) : string
    {
        if (! $methodName = ArrayHelper::get($this->getSettingMapFor($identifier), 'builder')) {
            return 'build'.ucfirst($identifier).'Setting';
        }

        return $methodName;
    }

    /**
     * Gets an option name for a given setting.
     *
     * @param SettingContract $setting
     * @return string
     */
    protected function getOptionName(SettingContract $setting) : string
    {
        $map = $this->getSettingMap($setting);
        if (is_array($map)) {
            return $map['optionName'];
        }

        return $map;
    }

    /**
     * Gets the setting reader callable, e.g., get_woocommerce_currency.
     *
     * @param SettingContract $setting
     * @return callable|null
     */
    protected function getSettingReader(SettingContract $setting)
    {
        return ArrayHelper::get($this->getSettingMap($setting), 'reader', null);
    }

    /**
     * Gets a setting map with metadata for the given setting.
     *
     * @param SettingContract $setting
     * @return array|string
     */
    protected function getSettingMap(SettingContract $setting)
    {
        return $this->getSettingMapFor($setting->getId());
    }

    /**
     * Gets a setting map with metadata for the setting identifier with the given identifier.
     *
     * @param string $identifier
     * @return array|string
     */
    protected function getSettingMapFor(string $identifier)
    {
        return $this->mapOfSettingIdToOptionName[$identifier];
    }

    /**
     * Read stored value of a setting.
     *
     * @param SettingContract $setting
     * @return false|mixed
     */
    protected function readStoredValue(SettingContract $setting)
    {
        $reader = $this->getSettingReader($setting);
        if ($reader && is_callable($reader)) {
            return call_user_func($reader);
        }

        return get_option($this->getOptionName($setting), null);
    }
}
