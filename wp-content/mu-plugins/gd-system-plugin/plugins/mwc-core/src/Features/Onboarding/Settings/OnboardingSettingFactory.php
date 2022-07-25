<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings;

use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts\OnboardingSettingFactoryContract;

/**
 * Onboarding settings class.
 */
class OnboardingSettingFactory implements OnboardingSettingFactoryContract
{
    /**
     * Builds the object that represents the First time setting.
     *
     * @return OnboardingSetting
     */
    public function buildFirstTimeSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_FIRST_TIME)
            ->setLabel(__('First time', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_BOOLEAN)
            ->setDefault(true)
            ->setControl((new Control())->setType(Control::TYPE_CHECKBOX));
    }

    /**
     * Builds a setting object with the given identifier.
     *
     * @param string $identifier used as the ID and name of the setting
     * @return OnboardingSetting
     */
    protected function buildSetting(string $identifier) : OnboardingSetting
    {
        return (new OnboardingSetting())->setId($identifier)->setName($identifier);
    }

    /**
     * Builds the object that represents the Store name setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreNameSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_STORE_NAME)
            ->setLabel(__('Store name', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Store address 1 setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreAddressFirstLineSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_STORE_ADDRESS_FIRST_LINE)
            ->setLabel(__('Store address 1', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Store address 2 setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreAddressSecondLineSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_STORE_ADDRESS_SECOND_LINE)
            ->setLabel(__('Store address 2', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Country Region setting.
     *
     * @return OnboardingSetting
     */
    public function buildCountryRegionSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_COUNTRY_REGION)
            ->setLabel(__('Country Region', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('US:CA')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the City setting.
     *
     * @return OnboardingSetting
     */
    public function buildCitySetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_CITY)
            ->setLabel(__('City', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Postal code setting.
     *
     * @return OnboardingSetting
     */
    public function buildPostalCodeSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_POSTAL_CODE)
            ->setLabel(__('Postal code', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Currency setting.
     *
     * @return OnboardingSetting
     */
    public function buildCurrencySetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_CURRENCY)
            ->setLabel(__('Currency', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('USD')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Currency position setting.
     *
     * @return OnboardingSetting
     */
    public function buildCurrencyPositionSetting() : OnboardingSetting
    {
        $options = [
            'left'        => __('Left aligned', 'mwc-core'),
            'right'       => __('Right aligned', 'mwc-core'),
            'left_space'  => __('Left aligned with space', 'mwc-core'),
            'right_space' => __('Right aligned with space', 'mwc-core'),
        ];

        return $this->buildSetting(OnboardingSetting::SETTING_ID_CURRENCY_POSITION)
            ->setLabel(__('Currency position', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setOptions(array_keys($options))
            ->setDefault('left')
            ->setControl(
                (new Control())
                    ->setType(Control::TYPE_SELECT)
                    ->setOptions($options)
            );
    }

    /**
     * Builds the object that represents the Selling locations setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellingLocationsSetting() : OnboardingSetting
    {
        $options = [
            'all'        => __('All countries', 'mwc-core'),
            'all_except' => __('Sell to all countries, except for...', 'mwc-core'),
            'specific'   => __('Sell to specific countries', 'mwc-core'),
        ];

        return $this->buildSetting(OnboardingSetting::SETTING_ID_SELLING_LOCATIONS)
            ->setLabel(__('Selling locations', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setOptions(array_keys($options))
            ->setDefault('all')
            ->setControl(
                (new Control())
                    ->setType(Control::TYPE_SELECT)
                    ->setOptions($options)
            );
    }

    /**
     * Builds the object that represents the Sell to specific countries setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellToSpecificCountriesSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_SELL_TO_SPECIFIC_COUNTRIES)
            ->setLabel(__('Sell to specific countries', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setIsMultivalued(true)
            ->setDefault([])
            ->setControl((new Control())->setType(Control::TYPE_SELECT));
    }

    /**
     * Builds the object that represents the Sell to all countries except setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellToAllCountriesExceptSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_SELL_TO_ALL_COUNTRIES_EXCEPT)
            ->setLabel(__('Sell to all countries except for', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setIsMultivalued(true)
            ->setDefault([])
            ->setControl((new Control())->setType(Control::TYPE_SELECT));
    }

    /**
     * Builds the object that represents the Shipping locations setting.
     *
     * @return OnboardingSetting
     */
    public function buildShippingLocationsSetting() : OnboardingSetting
    {
        $options = [
            // an empty string means that the store ships to all countries it sells to
            ''         => __('Ship to all countries you sell to', 'mwc-core'),
            'all'      => __('All countries', 'mwc-core'),
            'specific' => __('Ship to specific countries only', 'mwc-core'),
            'disabled' => __('Disable shipping & shipping calculations', 'mwc-core'),
        ];

        return $this->buildSetting(OnboardingSetting::SETTING_ID_SHIPPING_LOCATIONS)
            ->setLabel(__('Shipping locations', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setOptions(array_keys($options))
            ->setDefault('')
            ->setControl(
                (new Control())
                    ->setType(Control::TYPE_SELECT)
                    ->setOptions($options)
            );
    }

    /**
     * Builds the object that represents the Ship to specific countries setting.
     *
     * @return OnboardingSetting
     */
    public function buildShipToSpecificCountriesSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_SHIP_TO_SPECIFIC_COUNTRIES)
            ->setLabel(__('Ship to specific countries', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setIsMultivalued(true)
            ->setDefault([])
            ->setControl((new Control())->setType(Control::TYPE_SELECT));
    }

    /**
     * Builds the object that represents the Thousands separator setting.
     *
     * @return OnboardingSetting
     */
    public function buildThousandsSeparatorSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_THOUSANDS_SEPARATOR)
            ->setLabel(__('Thousands separator', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault(',')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Decimal separator setting.
     *
     * @return OnboardingSetting
     */
    public function buildDecimalSeparatorSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_DECIMAL_SEPARATOR)
            ->setLabel(__('Decimal separator', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setDefault('.')
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Number of decimals setting.
     *
     * @return OnboardingSetting
     */
    public function buildNumberOfDecimalsSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_NUMBER_OF_DECIMALS)
            ->setLabel(__('Number of decimals', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_INTEGER)
            ->setDefault(2)
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Weight units setting.
     *
     * @return OnboardingSetting
     */
    public function buildWeightUnitsSetting() : OnboardingSetting
    {
        $options = [
            'kg'  => __('Kilograms', 'mwc-core'),
            'g'   => __('Grams', 'mwc-core'),
            'lbs' => __('Pounds', 'mwc-core'),
            'oz'  => __('Ounces', 'mwc-core'),
        ];

        return $this->buildSetting(OnboardingSetting::SETTING_ID_WEIGHT_UNITS)
            ->setLabel(__('Weight units', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setOptions(array_keys($options))
            ->setDefault('kg')
            ->setControl(
                (new Control())
                    ->setType(Control::TYPE_SELECT)
                    ->setOptions($options)
            );
    }

    /**
     * Builds the object that represents the Dimension units setting.
     *
     * @return OnboardingSetting
     */
    public function buildDimensionUnitsSetting() : OnboardingSetting
    {
        $options = [
            'm'  => __('Meters', 'mwc-core'),
            'cm' => __('Centimeters', 'mwc-core'),
            'mm' => __('Millimeters', 'mwc-core'),
            'in' => __('Inches', 'mwc-core'),
            'yd' => __('Yards', 'mwc-core'),
        ];

        return $this->buildSetting(OnboardingSetting::SETTING_ID_DIMENSION_UNITS)
            ->setLabel(__('Dimension units', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_STRING)
            ->setOptions(array_keys($options))
            ->setDefault('cm')
            ->setControl(
                (new Control())
                    ->setType(Control::TYPE_SELECT)
                    ->setOptions($options)
            );
    }

    /**
     * Builds the object that represents the Enable taxes setting.
     *
     * @return OnboardingSetting
     */
    public function buildEnableTaxesSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_ENABLE_TAXES)
            ->setLabel(__('Enable taxes', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_BOOLEAN)
            ->setDefault(false)
            ->setControl((new Control())->setType(Control::TYPE_CHECKBOX));
    }

    /**
     * Builds the object that represents the Last onboarding step setting.
     *
     * @return OnboardingSetting
     */
    public function buildLastOnboardingStepSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_LAST_ONBOARDING_STEP)
            ->setLabel(__('Last onboarding step', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_ARRAY)
            ->setControl((new Control())->setType(Control::TYPE_TEXT));
    }

    /**
     * Builds the object that represents the Completed setting.
     *
     * @return OnboardingSetting
     */
    public function buildCompletedSetting() : OnboardingSetting
    {
        return $this->buildSetting(OnboardingSetting::SETTING_ID_COMPLETED)
            ->setLabel(__('Completed', 'mwc-core'))
            ->setType(OnboardingSetting::TYPE_BOOLEAN)
            ->setDefault(false)
            ->setControl((new Control())->setType(Control::TYPE_CHECKBOX));
    }
}
